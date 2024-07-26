<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$app->get('/provinces', function (Request $request, Response $response, $args) {
    $conn = $GLOBALS['connect'];
    $query = 'SELECT * FROM thai_provinces';
    $result = $conn->query($query);
    $provinces = [];
    while ($row = $result->fetch_assoc()) {
        $provinces[] = $row;
    }

    $response->getBody()->write(json_encode($provinces));
    return $response
        ->withHeader('Content-Type', 'application/json; charset=utf-8')
        ->withStatus(200);
});


$app->get('/amphures/{province_id}', function (Request $request, Response $response, $args) {
    $province_id = $args['province_id'];
    $conn = $GLOBALS['connect'];
    $stmt = $conn->prepare('SELECT * FROM thai_amphures WHERE province_id = ?');
    $stmt->bind_param('i', $province_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $districts = [];
    while ($row = $result->fetch_assoc()) {
        $districts[] = $row;
    }

    $response->getBody()->write(json_encode($districts));
    return $response
        ->withHeader('Content-Type', 'application/json; charset=utf-8')
        ->withStatus(200);
});


$app->get('/tambons/{amphure_id}', function (Request $request, Response $response, $args) {
    $amphure_id = $args['amphure_id'];
    $conn = $GLOBALS['connect'];
    $stmt = $conn->prepare('SELECT * FROM thai_tambons WHERE amphure_id = ?');
    $stmt->bind_param('i', $amphure_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $tambons = [];
    while ($row = $result->fetch_assoc()) {
        $tambons[] = $row;
    }

    $response->getBody()->write(json_encode($tambons));
    return $response
        ->withHeader('Content-Type', 'application/json; charset=utf-8')
        ->withStatus(200);
});


