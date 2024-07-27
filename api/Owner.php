<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$app->post('/owners', function (Request $request, Response $response, $args) {
    $json = $request->getBody();
    $jsonData = json_decode($json, true);

    $conn = $GLOBALS['connect'];
    $stmt = $conn->prepare('INSERT INTO owners
        (first_name, last_name, phone, email, rabies_vaccine_history, vaccine_date, bite_history, bite_count)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)');

    $stmt->bind_param(
        'sssssssi',
        $jsonData['first_name'],
        $jsonData['last_name'],
        $jsonData['phone'],
        $jsonData['email'],
        $jsonData['rabies_vaccine_history'],
        $jsonData['vaccine_date'],
        $jsonData['bite_history'],
        $jsonData['bite_count']
    );

    if ($stmt->execute()) {
        $owner_id = $stmt->insert_id;
        $responseBody = [
            'message' => 'Owner created',
            'owner_id' => $owner_id
        ];
        $response->getBody()->write(json_encode($responseBody));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    } else {
        $errorResponse = [
            'error' => 'Failed to create owner'
        ];
        $response->getBody()->write(json_encode($errorResponse));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->post('/owners/{id}/address', function (Request $request, Response $response, $args) {
    $owner_id = $args['id'];
    $json = $request->getBody();
    $jsonData = json_decode($json, true);

    $conn = $GLOBALS['connect'];
    $stmt = $conn->prepare('INSERT INTO owner_addresses
        (owner_id, province, district, sub_district, house_number, village_number, community_name, alley, road, postal_code, latitude, longitude) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

    $stmt->bind_param(
        'iiiissssssdd',
        $owner_id,
        $jsonData['province'],
        $jsonData['district'],
        $jsonData['sub_district'],
        $jsonData['house_number'],
        $jsonData['village_number'],
        $jsonData['community_name'],
        $jsonData['alley'],
        $jsonData['road'],
        $jsonData['postal_code'],
        $jsonData['latitude'],
        $jsonData['longitude']
    );

    if ($stmt->execute()) {
        $responseBody = [
            'message' => 'Address added'
        ];
        $response->getBody()->write(json_encode($responseBody));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    } else {
        $errorResponse = [
            'error' => 'Failed to add address'
        ];
        $response->getBody()->write(json_encode($errorResponse));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});



$app->put('/owners/{id}', function (Request $request, Response $response, $args) {
    $id = $args['id'];
    $data = $request->getParsedBody();



    $conn = $GLOBALS['connect'];
    $stmt = $conn->prepare('UPDATE owners
        SET first_name = ?, last_name = ?, phone = ?, email = ?, rabies_vaccine_history = ?, vaccine_date = ?, bite_history = ?, bite_count = ? 
        WHERE id = ?');

    $stmt->bind_param(
        'ssssssssi',
        $data['first_name'],
        $data['last_name'],
        $data['phone'],
        $data['email'],
        $data['rabies_vaccine_history'],
        $data['vaccine_date'],
        $data['bite_history'],
        $data['bite_count'],
        $id
    );

    $stmt->execute();

    $response->getBody()->write(json_encode(['message' => 'Owner updated']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});


$app->put('/owners/{id}/address', function (Request $request, Response $response, $args) {
    $owner_id = $args['id'];
    $data = $request->getParsedBody();

    $conn = $GLOBALS['connect'];
    $stmt = $conn->prepare('UPDATE owner_addresses
        SET address = ?, province = ?, district = ?, sub_district = ?, house_number = ?, village_number = ?, community_name = ?, alley = ?, road = ?, postal_code = ?, latitude = ?, longitude = ? 
        WHERE owner_id = ?');

    $stmt->bind_param(
        'sssssssssssddi',
        $data['address'],
        $data['province'],
        $data['district'],
        $data['sub_district'],
        $data['house_number'],
        $data['village_number'],
        $data['community_name'],
        $data['alley'],
        $data['road'],
        $data['postal_code'],
        $data['latitude'],
        $data['longitude'],
        $owner_id
    );

    $stmt->execute();

    $response->getBody()->write(json_encode(['message' => 'Address updated']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});


$app->get('/owners', function (Request $request, Response $response, $args) {
    $conn = $GLOBALS['connect'];
    $query = 'SELECT * FROM owners';
    $result = $conn->query($query);
    $owners = [];
    while ($row = $result->fetch_assoc()) {
        $owners[] = $row;
    }

    $response->getBody()->write(json_encode($owners));
    return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus(200);
});


$app->get('/owners/{id}', function (Request $request, Response $response, $args) {
    $conn = $GLOBALS['connect'];
    $id = $args['id'];
    $stmt = $conn->prepare('SELECT * FROM owners WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $owner = $result->fetch_assoc();

    if ($owner) {
        $response->getBody()->write(json_encode($owner));
    } else {
        $response->getBody()->write(json_encode(['message' => 'Owner not found']));
        return $response->withStatus(404);
    }

    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});
