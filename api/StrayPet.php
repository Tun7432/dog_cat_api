<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Assuming $app and $conn (MySQLi instance) are already defined and available in the global scope.

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$app->post('/stray-pets', function (Request $request, Response $response, $args)  {
    $data = $request->getParsedBody();

    // Process and validate data here

    $conn = $GLOBALS['connect'];
    $stmt = $conn->prepare('INSERT INTO stray_pets 
        (provider_name, provider_phone, location_latitude, location_longitude, image, type, color, name, gender, birth_date, neutered, rabies_vaccine, vaccine_date, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

    $stmt->bind_param(
        'ssddssssssisss',
        $data['provider_name'],
        $data['provider_phone'],
        $data['location_latitude'],
        $data['location_longitude'],
        $data['image'],
        $data['type'],
        $data['color'],
        $data['name'],
        $data['gender'],
        $data['birth_date'],
        $data['neutered'],
        $data['rabies_vaccine'],
        $data['vaccine_date'],
        $data['status']
    );

    $stmt->execute();

    $response->getBody()->write(json_encode(['message' => 'Stray pet created']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
});

$app->put('/stray-pets/{id}', function (Request $request, Response $response, $args)  {
    $id = $args['id'];
    $data = $request->getParsedBody();

    // Process and validate data here

    $conn = $GLOBALS['connect'];
    $stmt = $conn->prepare('UPDATE stray_pets 
        SET provider_name = ?, provider_phone = ?, location_latitude = ?, location_longitude = ?, image = ?, type = ?, color = ?, name = ?, gender = ?, birth_date = ?, neutered = ?, rabies_vaccine = ?, vaccine_date = ?, status = ? 
        WHERE id = ?');

    $stmt->bind_param(
        'ssddssssssisssi',
        $data['provider_name'],
        $data['provider_phone'],
        $data['location_latitude'],
        $data['location_longitude'],
        $data['image'],
        $data['type'],
        $data['color'],
        $data['name'],
        $data['gender'],
        $data['birth_date'],
        $data['neutered'],
        $data['rabies_vaccine'],
        $data['vaccine_date'],
        $data['status'],
        $id
    );

    $stmt->execute();

    $response->getBody()->write(json_encode(['message' => 'Stray pet updated']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

$app->get('/stray-pets', function (Request $request, Response $response, $args)  {
    $conn = $GLOBALS['connect'];
    $query = 'SELECT * FROM stray_pets';
    $result = $conn->query($query);
    $strayPets = [];
    while ($row = $result->fetch_assoc()) {
        $strayPets[] = $row;
    }

    $response->getBody()->write(json_encode($strayPets));
    return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus(200);
});

$app->get('/stray-pets/{id}', function (Request $request, Response $response, $args)  {
    $conn = $GLOBALS['connect'];
    $id = $args['id'];
    $stmt = $conn->prepare('SELECT * FROM stray_pets WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $strayPet = $result->fetch_assoc();

    if ($strayPet) {
        $response->getBody()->write(json_encode($strayPet));
    } else {
        $response->getBody()->write(json_encode(['message' => 'Stray pet not found']));
        return $response->withStatus(404);
    }

    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});
