<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Assuming $app and $conn (MySQLi instance) are already defined and available in the global scope.

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$app->post('/pets', function (Request $request, Response $response, $args) {
    $data = $request->getParsedBody();
    
    $conn = $GLOBALS['connect'];
    $errors = [];

    foreach ($data as $pet) {
        if (
            !isset($pet['owner_id']) || !isset($pet['image']) || !isset($pet['type']) || !isset($pet['color']) ||
            !isset($pet['name']) || !isset($pet['gender']) || !isset($pet['birth_date']) || !isset($pet['neutered']) ||
            !isset($pet['rabies_vaccine']) || !isset($pet['vaccine_date']) || !isset($pet['status'])
        ) {
            $errors[] = ['message' => 'Incomplete pet data'];
            continue;
        }

        $stmt = $conn->prepare('INSERT INTO pets 
            (owner_id, image, type, color, name, gender, birth_date, neutered, rabies_vaccine, vaccine_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

        $stmt->bind_param(
            'issssssssss',
            $pet['owner_id'],
            $pet['image'],
            $pet['type'],
            $pet['color'],
            $pet['name'],
            $pet['gender'],
            $pet['birth_date'],
            $pet['neutered'],
            $pet['rabies_vaccine'],
            $pet['vaccine_date'],
            $pet['status']
        );

        if (!$stmt->execute()) {
            $errors[] = ['message' => 'Failed to insert pet data', 'error' => $stmt->error];
        }
    }

    if (!empty($errors)) {
        $response->getBody()->write(json_encode(['errors' => $errors]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $response->getBody()->write(json_encode(['message' => 'Pets created']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
});





$app->put('/pets/{id}', function (Request $request, Response $response, $args)  {
    $id = $args['id'];
    $data = $request->getParsedBody();

    // Process and validate data here

    $conn = $GLOBALS['connect'];
    $stmt = $conn->prepare('UPDATE pets 
        SET owner_id = ?, image = ?, type = ?, color = ?, name = ?, gender = ?, birth_date = ?, neutered = ?, rabies_vaccine = ?, vaccine_date = ?, status = ? 
        WHERE id = ?');

    $stmt->bind_param(
        'issssssssssi',
        $data['owner_id'],
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

    $response->getBody()->write(json_encode(['message' => 'Pet updated']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

$app->get('/pets', function (Request $request, Response $response, $args)  {
    $conn = $GLOBALS['connect'];
    $query = 'SELECT * FROM pets';
    $result = $conn->query($query);
    $pets = [];
    while ($row = $result->fetch_assoc()) {
        $pets[] = $row;
    }

    $response->getBody()->write(json_encode($pets));
    return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus(200);
});

$app->get('/pets/{id}', function (Request $request, Response $response, $args)  {
    $conn = $GLOBALS['connect'];
    $id = $args['id'];
    $stmt = $conn->prepare('SELECT * FROM pets WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $pet = $stmt->get_result()->fetch_assoc();

    if ($pet) {
        $response->getBody()->write(json_encode($pet));
    } else {
        $response->getBody()->write(json_encode(['message' => 'Pet not found']));
        return $response->withStatus(404);
    }

    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});
