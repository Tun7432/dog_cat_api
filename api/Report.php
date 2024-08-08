<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$app->get('/report', function (Request $request, Response $response, $args) {
    $queryParams = $request->getQueryParams();
    $startDate = $queryParams['start_date'] ?? null;
    $endDate = $queryParams['end_date'] ?? null;
    $province = $queryParams['province'] ?? null;
    $district = $queryParams['district'] ?? null;
    $subDistrict = $queryParams['sub_district'] ?? null;
    $villageNumber = $queryParams['village_number'] ?? null;

    // ตรวจสอบว่า parameter ครบถ้วน
    if (!$startDate || !$endDate || !$province || !$district || !$subDistrict || !$villageNumber) {
        $response->getBody()->write(json_encode(['message' => 'Invalid parameters']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $conn = $GLOBALS['connect'];
    $stmt = $conn->prepare(
        'SELECT 
            o.id AS owner_id,
            o.first_name,
            o.last_name,
            o.rabies_vaccine_history,
            oa.province,
            oa.district,
            oa.sub_district,
            oa.village_number,
            COUNT(DISTINCT o.id) AS total_owners,
            SUM(o.rabies_vaccine_history) AS vaccinated_owners,
            (SUM(o.rabies_vaccine_history) / COUNT(DISTINCT o.id)) * 100 AS vaccination_rate
        FROM 
            owners o
        JOIN 
            owner_addresses oa ON o.id = oa.owner_id
        WHERE 
            o.created_at BETWEEN ? AND ?
            AND oa.province = ?
            AND oa.district = ?
            AND oa.sub_district = ?
            AND oa.village_number = ?
        GROUP BY 
            oa.province, oa.district, oa.sub_district, oa.village_number'
    );

    $stmt->bind_param('ssssss', $startDate, $endDate, $province, $district, $subDistrict, $villageNumber);
    $stmt->execute();
    $result = $stmt->get_result();

    $reportData = [];
    while ($row = $result->fetch_assoc()) {
        $reportData[] = $row;
    }

    $response->getBody()->write(json_encode($reportData));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});




$app->get('/pet-owners-report', function (Request $request, Response $response, $args) {
    $queryParams = $request->getQueryParams();
    $province = $queryParams['province'] ?? null;
    $district = $queryParams['district'] ?? null;
    $sub_district = $queryParams['sub_district'] ?? null;

    // ตรวจสอบว่า parameter ครบถ้วน
    if (!$province || !$district || !$sub_district) {
        $response->getBody()->write(json_encode(['message' => 'Invalid parameters']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $conn = $GLOBALS['connect'];
    $stmt = $conn->prepare(
        'SELECT 
            o.first_name AS owner_first_name,
            o.last_name AS owner_last_name,
            p.type AS pet_type,
            p.name AS pet_name,
            oa.house_number AS house_number,
            oa.village_number AS village_number,
            oa.community_name AS community_name,
            oa.alley AS alley,
            oa.road AS road,
            oa.postal_code AS postal_code,
            o.created_at AS registration_date
        FROM 
            owners o
        JOIN 
            owner_addresses oa ON o.id = oa.owner_id
        JOIN 
            pets p ON o.id = p.owner_id
        WHERE 
            oa.province = ?
            AND oa.district = ?
            AND oa.sub_district = ?
        ORDER BY 
            o.created_at'
    );

    $stmt->bind_param('sss', $province, $district, $sub_district);
    $stmt->execute();
    $result = $stmt->get_result();

    $reportData = [];
    while ($row = $result->fetch_assoc()) {
        $reportData[] = $row;
    }

    $response->getBody()->write(json_encode($reportData));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});


$app->get('/pet-analysis-report', function (Request $request, Response $response, $args) {
    $queryParams = $request->getQueryParams();
    $startDate = $queryParams['start_date'] ?? null;
    $endDate = $queryParams['end_date'] ?? null;
    $province = $queryParams['province'] ?? null;
    $district = $queryParams['district'] ?? null;
    $subDistrict = $queryParams['sub_district'] ?? null;
    $villageNumber = $queryParams['village_number'] ?? null;

    if (!$startDate || !$endDate || !$province || !$district || !$subDistrict || !$villageNumber) {
        $response->getBody()->write(json_encode(['message' => 'Invalid parameters']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $conn = $GLOBALS['connect'];


    $stmt = $conn->prepare(
        'SELECT
            COUNT(p.id) AS total_pets,
            SUM(CASE WHEN p.type = \'dog\' THEN 1 ELSE 0 END) AS total_dogs,
            SUM(CASE WHEN p.type = \'cat\' THEN 1 ELSE 0 END) AS total_cats,
            SUM(CASE WHEN p.type = \'dog\' AND p.owner_id IS NOT NULL THEN 1 ELSE 0 END) AS owned_dogs,
            SUM(CASE WHEN p.type = \'dog\' AND p.owner_id IS NULL THEN 1 ELSE 0 END) AS stray_dogs,
            SUM(CASE WHEN p.type = \'cat\' AND p.owner_id IS NOT NULL THEN 1 ELSE 0 END) AS owned_cats,
            SUM(CASE WHEN p.type = \'cat\' AND p.owner_id IS NULL THEN 1 ELSE 0 END) AS stray_cats,
            SUM(CASE WHEN p.type = \'dog\' AND p.neutered = 1 THEN 1 ELSE 0 END) AS neutered_dogs,
            SUM(CASE WHEN p.type = \'dog\' AND p.neutered = 0 THEN 1 ELSE 0 END) AS unneutered_dogs,
            SUM(CASE WHEN p.type = \'cat\' AND p.neutered = 1 THEN 1 ELSE 0 END) AS neutered_cats,
            SUM(CASE WHEN p.type = \'cat\' AND p.neutered = 0 THEN 1 ELSE 0 END) AS unneutered_cats,
            SUM(CASE WHEN p.type = \'dog\' AND p.rabies_vaccine = 1 THEN 1 ELSE 0 END) AS vaccinated_dogs,
            SUM(CASE WHEN p.type = \'dog\' AND p.rabies_vaccine = 0 THEN 1 ELSE 0 END) AS unvaccinated_dogs,
            SUM(CASE WHEN p.type = \'cat\' AND p.rabies_vaccine = 1 THEN 1 ELSE 0 END) AS vaccinated_cats,
            SUM(CASE WHEN p.type = \'cat\' AND p.rabies_vaccine = 0 THEN 1 ELSE 0 END) AS unvaccinated_cats
        FROM
            pets p
        JOIN
            owner_addresses oa ON p.owner_id = oa.owner_id
        WHERE
            p.created_at BETWEEN ? AND ?
            AND oa.sub_district = ?
            AND oa.district = ?
            AND oa.province = ?
            AND oa.village_number = ?'
    );

    if (!$stmt) {
        $response->getBody()->write(json_encode(['message' => 'Database query preparation failed']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }

    $stmt->bind_param('ssssss', $startDate, $endDate, $subDistrict, $district, $province, $villageNumber);

    if (!$stmt->execute()) {
        $response->getBody()->write(json_encode(['message' => 'Database query execution failed']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }

    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    $stmt->close();

    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});


