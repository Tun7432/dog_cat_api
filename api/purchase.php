<?php


ini_set('display_error', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->get('/purchase/{id}', function (Request $request, Response $response, $args) {
    $id = $args['id'];
    $conn = $GLOBALS['connect'];
    $sql = 'SELECT * FROM purchase WHERE id = ?';
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


$app->post('/purchase', function (Request $request, Response $response, $args) {
    $json = $request->getBody();
    $jsonData = json_decode($json, true);

    $conn = $GLOBALS['connect'];

    $userCheckSql = 'SELECT * FROM users WHERE id = ?';
    $userCheckStmt = $conn->prepare($userCheckSql);
    $userCheckStmt->bind_param('i', $jsonData['user_id']);
    $userCheckStmt->execute();
    $userResult = $userCheckStmt->get_result();

    if ($userResult->num_rows === 0) {
        
        $data = ["message" => "User does not exist"];
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(404); 
    }

    $lotteryCheckSql = 'SELECT * FROM lottery WHERE ticket_number = ? AND period = ? AND set_number = ?';
    $lotteryCheckStmt = $conn->prepare($lotteryCheckSql);
    $lotteryCheckStmt->bind_param('iss', $jsonData['ticket_number'], $jsonData['period'], $jsonData['set_number']);
    $lotteryCheckStmt->execute();
    $lotteryResult = $lotteryCheckStmt->get_result();

    if ($lotteryResult->num_rows === 0) {
        $data = ["message" => "Lottery does not exist"];
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(404);  
    }

    $lotteryRow = $lotteryResult->fetch_assoc();
    $lotteryId = $lotteryRow['id'];

    $sql = 'INSERT INTO purchase (user_id, lottery_id, quantity, total_price, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)';
    $stmt = $conn->prepare($sql);

    $currentTimestamp = date('Y-m-d H:i:s'); 

    $stmt->bind_param(
        'iiidss',
        $jsonData['user_id'],
        $lotteryId, 
        $jsonData['quantity'],
        $jsonData['total_price'],
        $currentTimestamp,
        $currentTimestamp
    );

    if ($stmt->execute()) {
        $data = ["message" => "Purchase created successfully"];
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201); 
    } else {
        $data = ["message" => "Failed to create purchase"];
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
});


$app->put('/purchase/{id}', function (Request $request, Response $response, $args) {
    $json = $request->getBody();
    $jsonData = json_decode($json, true);
    $id = $args['id'];
    $conn = $GLOBALS['connect'];
    $sql = 'UPDATE purchase SET user_id=?, lottery_id=?, quantity=?, total_price=?, created_at=?, updated_at=? WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'iiidssi',
        $jsonData['user_id'],
        $jsonData['lottery_id'],
        $jsonData['quantity'],
        $jsonData['total_price'],
        $jsonData['created_at'],
        $jsonData['updated_at'],
        $id
    );

    if ($stmt->execute()) {
        $data = ["message" => "Purchase updated successfully"];
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    } else {
        $data = ["message" => "Failed to update purchase"];
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
});



$app->delete('/purchase/{id}', function (Request $request, Response $response, $args) {
    $id = $args['id'];
    $conn = $GLOBALS['connect'];
    $sql = 'DELETE FROM purchase WHERE id = ?';
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


?>