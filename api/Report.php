<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$app->get('/report', function (Request $request, Response $response, $args) {
    $params = $request->getQueryParams();
    $startDate = $params['start_date'];
    $endDate = $params['end_date'];
    $province = $params['province'];
    $district = $params['district'];
    $subDistrict = $params['sub_district'];

    $conn = $GLOBALS['connect'];

    // Query to get the data
    $query = "
        SELECT 
            o.id, o.first_name, o.last_name, o.rabies_vaccine_history, o.vaccine_date,
            oa.province, oa.district, oa.sub_district,
            COUNT(DISTINCT o.id) as owner_count,
            SUM(CASE WHEN o.rabies_vaccine_history = 1 THEN 1 ELSE 0 END) as vaccinated_count,
            (SUM(CASE WHEN o.rabies_vaccine_history = 1 THEN 1 ELSE 0 END) / COUNT(DISTINCT o.id)) * 100 as vaccinated_percentage
        FROM 
            owners o
            JOIN owner_addresses oa ON o.id = oa.owner_id
        WHERE 
            oa.province LIKE ? AND
            oa.district LIKE ? AND
            oa.sub_district LIKE ? AND
            o.created_at BETWEEN ? AND ?
        GROUP BY 
            oa.province, oa.district, oa.sub_district;
    ";

    $stmt = $conn->prepare($query);
    $likeProvince = "%$province%";
    $likeDistrict = "%$district%";
    $likeSubDistrict = "%$subDistrict%";
    $stmt->bind_param('sssss', $likeProvince, $likeDistrict, $likeSubDistrict, $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();

    $report = [];
    while ($row = $result->fetch_assoc()) {
        $report[] = $row;
    }

    $response->getBody()->write(json_encode($report));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});
