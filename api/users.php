<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$app->post('/login', function (Request $request, Response $response, $args) {
    $json = $request->getBody();
    $jsonData = json_decode($json, true);
    $email = $jsonData['email'];
    $password = $jsonData['password'];

    $conn = $GLOBALS['connect'];
    $sql = 'SELECT * FROM users WHERE email = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $hashedPasswordFromDatabase = $user['password'];

         
        if (password_verify($password, $hashedPasswordFromDatabase)) {
            $data = [
                "message" => "เข้าสู่ระบบสำเร็จ",
                "user" => $user
            ];
            $response->getBody()->write(json_encode($data));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        }
    }

    $data = ["message" => "อีเมลหรือรหัสผ่านไม่ถูกต้อง"];
    $response->getBody()->write(json_encode($data));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(401);
});



$app->get('/users', function (Request $request, Response $response, $args) {
    
    $conn = $GLOBALS['connect'];
    $sql = 'SELECT * FROM users ';
    $stmt = $conn->prepare($sql);
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        array_push($data, $row);
    }
    $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));
    return $response
        ->withHeader('Content-Type', 'application/json; charset=utf-8')
        ->withStatus(200);
});

$app->get('/users/{id}', function (Request $request, Response $response, $args) {
    $id = $args['id'];
    $conn = $GLOBALS['connect'];
    $sql = 'SELECT * FROM users WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        array_push($data, $row);
    }
    $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));
    return $response
        ->withHeader('Content-Type', 'application/json; charset=utf-8')
        ->withStatus(200);
});


$app->post('/users', function (Request $request, Response $response, $args) {
    $json = $request->getBody();
    $jsonData = json_decode($json, true);

    $conn = $GLOBALS['connect'];
    $bdate = date("Y-m-d", strtotime($jsonData['birthdate']));
$hashedPassword = password_hash($jsonData['password'], PASSWORD_BCRYPT);

$sql = 'INSERT INTO users (email, password, prefix, first_name, last_name, birthdate, phone_number) VALUES (?, ?, ?, ?, ?, ?, ?)';
$stmt = $conn->prepare($sql);
$stmt->bind_param('sssssss', $jsonData['email'], $hashedPassword, $jsonData['prefix'], $jsonData['first_name'], $jsonData['last_name'], $bdate, $jsonData['phone_number']);
$stmt->execute();

    $affected = $stmt->affected_rows;
    if ($affected > 0) {
        $data = ["affected_rows" => $affected, "last_id" => $conn->insert_id];
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    }
    
    $data = ["message" => "อีเมลซ้ำในระบบ"];
    $response->getBody()->write(json_encode($data));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(409);
});


$app->put('/users/{id}', function (Request $request, Response $response, $args) {
    $json = $request->getBody();
    $jsonData = json_decode($json, true);
    $id = $args['id'];
    $conn = $GLOBALS['connect'];
    $sql = 'UPDATE users SET email=?, password=?, prefix=?, first_name=?, last_name=?, birthdate=?, phone_number=?, role=? WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssssssi', $jsonData['email'], $jsonData['password'], $jsonData['prefix'], $jsonData['first_name'], $jsonData['last_name'], $jsonData['birthdate'], $jsonData['phone_number'], $jsonData['role'], $id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    if ($affected > 0) {
        $data = ["affected_rows" => $affected];
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
});

$app->delete('/users/{id}', function (Request $request, Response $response, $args) {
    $id = $args['id'];
    $conn = $GLOBALS['connect'];
    $sql = 'DELETE FROM users WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    if ($affected > 0) {
        $data = ["affected_rows" => $affected];
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
});
