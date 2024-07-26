<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Assuming $app and $conn (MySQLi instance) are already defined and available in the global scope.

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$app->post('/rabies-records', function (Request $request, Response $response, $args)  {
    $data = $request->getParsedBody();

    // Process and validate data here

    $conn = $GLOBALS['connect'];
    $stmt = $conn->prepare('INSERT INTO rabies_records 
        (pet_id, vaccine_date, vaccine_type, veterinarian, next_vaccine_date) 
        VALUES (?, ?, ?, ?, ?)');

    $stmt->bind_param(
        'issss',
        $data['pet_id'],
        $data['vaccine_date'],
        $data['vaccine_type'],
        $data['veterinarian'],
        $data['next_vaccine_date']
    );

    $stmt->execute();

    $response->getBody()->write(json_encode(['message' => 'Rabies record created']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
});

$app->get('/rabies-records', function (Request $request, Response $response, $args)  {
    $conn = $GLOBALS['connect'];
    $query = 'SELECT * FROM rabies_records';
    $result = $conn->query($query);
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }

    $response->getBody()->write(json_encode($records));
    return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus(200);
});
