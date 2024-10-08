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


$app->get('/pet-analysis-report', function (Request $request, Response $response) {
    $queryParams = $request->getQueryParams();
    $startDate = $queryParams['start_date'] ?? null;
    $endDate = $queryParams['end_date'] ?? null;
    $province = $queryParams['province'] ?? null;
    $district = $queryParams['district'] ?? null;
    $subDistrict = $queryParams['sub_district'] ?? null;
    

    if (!$startDate || !$endDate || !$province || !$district || !$subDistrict) {
        $response->getBody()->write(json_encode(['message' => 'Invalid parameters']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $conn = $GLOBALS['connect'];

    // Query for owned pets
    $sqlPets = "
        SELECT
            'owned' AS pet_type, p.id AS pet_id, p.type, p.name, p.color, p.gender,
            p.birth_date, p.neutered, p.rabies_vaccine, p.status, o.id AS owner_id,
            o.first_name, o.last_name, o.phone, o.email,
            oa.province, oa.district, oa.sub_district, oa.village_number
        FROM pets p
        INNER JOIN owners o ON p.owner_id = o.id
        INNER JOIN owner_addresses oa ON o.id = oa.owner_id
        WHERE
            oa.province = ? AND
            oa.district = ? AND
            oa.sub_district = ? AND
            
            p.created_at BETWEEN ? AND ?
    ";

    // Query for stray pets
    $sqlStrayPets = "
        SELECT
            'stray' AS pet_type, sp.id AS pet_id, sp.type, sp.name, sp.color, sp.gender,
            sp.birth_date, sp.neutered, sp.rabies_vaccine, sp.status,
            NULL AS owner_id, NULL AS first_name, NULL AS last_name,
            NULL AS phone, NULL AS email,
            spa.province, spa.district, spa.sub_district, spa.village_number
        FROM stray_pets sp
        INNER JOIN stray_pets_address spa ON sp.stray_pets_address_id = spa.id
        WHERE
            spa.province = ? AND
            spa.district = ? AND
            spa.sub_district = ? AND
            
            sp.created_at BETWEEN ? AND ?
    ";

    // Execute query for pets
    $stmtPets = $conn->prepare($sqlPets);
    $stmtPets->bind_param('sssss', $province, $district, $subDistrict, $startDate, $endDate);
    $stmtPets->execute();
    $resultPets = $stmtPets->get_result()->fetch_all(MYSQLI_ASSOC);

    // Execute query for stray pets
    $stmtStrayPets = $conn->prepare($sqlStrayPets);
    $stmtStrayPets->bind_param('sssss', $province, $district, $subDistrict, $startDate, $endDate);
    $stmtStrayPets->execute();
    $resultStrayPets = $stmtStrayPets->get_result()->fetch_all(MYSQLI_ASSOC);

    // Merge results
    $result = array_merge($resultPets, $resultStrayPets);

    $response->getBody()->write(json_encode($result));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

//วิเคราะห์ข้อมูลโรคพิษสุนัขบ้า
$app->get('/rabies-analysis', function ($request, $response, $args) {
    $queryParams = $request->getQueryParams();
    $startDate = $queryParams['start_date'] ?? null;
    $endDate = $queryParams['end_date'] ?? null;
    $provinceId = $queryParams['province'] ?? null;
    $amphureId = $queryParams['amphure'] ?? null;
    $tambonId = $queryParams['tambon'] ?? null;
    $villageNumber = $queryParams['village_number'] ?? null;

    $conn = $GLOBALS['connect'];

    // First SQL query to get the total number of rabies cases
    $sqlCases = "SELECT 
                    COUNT(DISTINCT r.id) AS total_cases
                 FROM rabies_records r
                 JOIN pets p ON r.pet_id = p.id
                 JOIN owners o ON p.owner_id = o.id
                 JOIN owner_addresses oa ON oa.owner_id = o.id
                 WHERE r.diagnosis_date BETWEEN ? AND ?
                 AND oa.province = ?
                 AND oa.district = ?
                 AND oa.sub_district = ?
                 AND oa.village_number = ?";

    $stmtCases = $conn->prepare($sqlCases);
    $stmtCases->bind_param('ssssss', $startDate, $endDate, $provinceId, $amphureId, $tambonId, $villageNumber);
    $stmtCases->execute();

    $resultCases = $stmtCases->get_result();
    $dataCases = $resultCases->fetch_assoc();

    // Second SQL query to get the total number of pets
    $sqlPets = "SELECT 
                    COUNT(DISTINCT p.id) AS total_pets
                FROM pets p
                JOIN owners o ON p.owner_id = o.id
                JOIN owner_addresses oa ON o.id = oa.owner_id
                WHERE oa.province = ?
                AND oa.district = ?
                AND oa.sub_district = ?
                AND oa.village_number = ?";

    $stmtPets = $conn->prepare($sqlPets);
    $stmtPets->bind_param('ssss', $provinceId, $amphureId, $tambonId, $villageNumber);
    $stmtPets->execute();

    $resultPets = $stmtPets->get_result();
    $dataPets = $resultPets->fetch_assoc();

    // Calculate incidence rate based on total cases and total pets
    $totalCases = $dataCases['total_cases'] ?? 0;
    $totalPets = $dataPets['total_pets'] ?? 0;
    $incidenceRate = ($totalPets > 0) ? ($totalCases / $totalPets) * 100 : 0.00;

    // Manually structure data
    $structuredData = [
        'startDate' => $startDate,
        'endDate' => $endDate,
        'province' => $provinceId,
        'district' => $amphureId,
        'subDistrict' => $tambonId,
        'villageNumber' => $villageNumber,
        'totalPets' => $totalPets,
        'totalCases' => $totalCases,
        'incidenceRate' => $incidenceRate
    ];

    // Return response with JSON data
    $json = json_encode($structuredData, JSON_UNESCAPED_UNICODE);
    $response->getBody()->write($json);
    return $response->withHeader('Content-Type', 'application/json');
});





















