<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Assuming $app and $conn (MySQLi instance) are already defined and available in the global scope.

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// $app->post('/login', function (Request $request, Response $response, $args)  {
//     $json = $request->getBody();
//     $jsonData = json_decode($json, true);
//     $username = $jsonData['username'];
//     $password = $jsonData['password'];

//     $conn = $GLOBALS['connect'];
//     $stmt = $conn->prepare('SELECT * FROM users WHERE username = ?');
//     $stmt->bind_param('s', $username);
//     $stmt->execute();
//     $result = $stmt->get_result();
//     $user = $result->fetch_assoc();

//     if ($user && password_verify($password, $user['password'])) {
//         // Generate JWT token or session
//         $data = ["message" => "Login successful"];
//         $response->getBody()->write(json_encode($data));
//         return $response
//             ->withHeader('Content-Type', 'application/json')
//             ->withStatus(200);
//     }

//     $data = ["message" => "Invalid credentials"];
//     $response->getBody()->write(json_encode($data));
//     return $response
//         ->withHeader('Content-Type', 'application/json')
//         ->withStatus(401);
// });

$app->post('/login', function (Request $request, Response $response, $args) {
    $json = $request->getBody();
    $jsonData = json_decode($json, true);
    $username = $jsonData['username'];
    $password = $jsonData['password'];

    $conn = $GLOBALS['connect'];
    $stmt = $conn->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        
        $query = 'SELECT id, username, role, created_at FROM users';
        $result = $conn->query($query);
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }

    
        $data = [
            "message" => "Login successful",
            "user" => $user,
            "all_users" => $users
        ];
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    $data = ["message" => "Invalid credentials"];
    $response->getBody()->write(json_encode($data));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(401);
});

$app->get('/users', function (Request $request, Response $response, $args)  {
    $conn = $GLOBALS['connect'];
    $query = 'SELECT id, username, role, created_at FROM users';
    $result = $conn->query($query);
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    $response->getBody()->write(json_encode($users));
    return $response
        ->withHeader('Content-Type', 'application/json; charset=utf-8')
        ->withStatus(200);
});

$app->post('/users', function (Request $request, Response $response, $args)  {
    $json = $request->getBody();
    $jsonData = json_decode($json, true);
    $username = $jsonData['username'];
    $password = password_hash($jsonData['password'], PASSWORD_DEFAULT);
    $role = $jsonData['role'];
    $conn = $GLOBALS['connect'];
    $stmt = $conn->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $username, $password, $role);
    $stmt->execute();

    $data = ["message" => "User created"];
    $response->getBody()->write(json_encode($data));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(201);
});

$app->put('/users/{id}', function (Request $request, Response $response, $args)  {
    $id = $args['id'];
    $json = $request->getBody();
    $jsonData = json_decode($json, true);
    $username = $jsonData['username'];
    $password = password_hash($jsonData['password'], PASSWORD_DEFAULT);
    $role = $jsonData['role'];

    $conn = $GLOBALS['connect'];
    $stmt = $conn->prepare('UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?');
    $stmt->bind_param('sssi', $username, $password, $role, $id);
    $stmt->execute();

    $data = ["message" => "User updated"];
    $response->getBody()->write(json_encode($data));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(200);
});

$app->delete('/users/{id}', function (Request $request, Response $response, $args)  {
    $id = $args['id'];

    $conn = $GLOBALS['connect'];
    $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();

    $data = ["message" => "User deleted"];
    $response->getBody()->write(json_encode($data));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(200);
});
