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

    // ตรวจสอบว่า parameter ครบถ้วน
    if (!$startDate || !$endDate || !$province || !$district || !$subDistrict) {
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
        GROUP BY 
            oa.province, oa.district, oa.sub_district'
    );

    $stmt->bind_param('sssss', $startDate, $endDate, $province, $district, $subDistrict);
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
