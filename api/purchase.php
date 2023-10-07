<?php


ini_set('display_error', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


// $app->get('/purchase/{id}', function (Request $request, Response $response, $args) {
//     $id = $args['id'];
//     $conn = $GLOBALS['connect'];

//     // เรียกข้อมูลการซื้อ
//     $purchaseSql = 'SELECT * FROM purchase WHERE user_id = ?';
//     $purchaseStmt = $conn->prepare($purchaseSql);
//     $purchaseStmt->bind_param('i', $id);
//     $purchaseStmt->execute();
//     $purchaseResult = $purchaseStmt->get_result();
//     $purchaseData = [];
//     while ($row = $purchaseResult->fetch_assoc()) {
//         $purchaseData[] = $row;
//     }

//     // ตรวจสอบว่ามีข้อมูลการซื้อหรือไม่
//     if (empty($purchaseData)) {
//         $response->getBody()->write(json_encode(['message' => 'ไม่พบข้อมูลการซื้อสำหรับผู้ใช้นี้'], JSON_UNESCAPED_UNICODE));
//         return $response
//             ->withHeader('Content-Type', 'application/json; charset=utf-8')
//             ->withStatus(404); // ส่งสถานะ 404 Not Found
//     }

//     // สร้างอาร์เรย์สำหรับเก็บข้อมูลรวม
//     $responseData = [];

//     // ลูปผ่านการซื้อทุกรายการ
//     foreach ($purchaseData as $purchaseItem) {
//         // เรียกข้อมูลรายละเอียดการซื้อสำหรับแต่ละการซื้อ
//         $detailsSql = 'SELECT * FROM purchase_details WHERE purchase_id = ?';
//         $detailsStmt = $conn->prepare($detailsSql);
//         $detailsStmt->bind_param('i', $purchaseItem['id']);
//         $detailsStmt->execute();
//         $detailsResult = $detailsStmt->get_result();
//         $detailsData = $detailsResult->fetch_assoc();

//         // เรียกข้อมูล "ชื่อ" จากตาราง "users"
//         $userSql = 'SELECT first_name FROM users WHERE id = ?';
//         $userStmt = $conn->prepare($userSql);
//         $userStmt->bind_param('i', $purchaseItem['user_id']);
//         $userStmt->execute();
//         $userResult = $userStmt->get_result();
//         $userData = $userResult->fetch_assoc();

//         // เรียกข้อมูล "หมายเลขสลาก" จากตาราง "lottery"
//         $lotteryData = [];
//         if (!empty($detailsData)) {
//             $lotterySql = 'SELECT ticket_number FROM lottery WHERE id = ?';
//             $lotteryStmt = $conn->prepare($lotterySql);
//             $lotteryStmt->bind_param('i', $detailsData['ticket_id']);
//             $lotteryStmt->execute();
//             $lotteryResult = $lotteryStmt->get_result();
//             $lotteryData = $lotteryResult->fetch_assoc();
//         }

//         // รวมข้อมูลการซื้อและรายละเอียดการซื้อลงใน responseData
//         $responseData[] = [
//             'created_at' => $detailsData['created_at'],
//             'first_name' => $userData['first_name'], // ใช้ข้อมูลจากตาราง "users"
//             'ticket_number' => $lotteryData['ticket_number'], // ใช้ข้อมูลจากตาราง "lottery"
//             'quantity' => $purchaseItem['quantity'],
//             'price' => $detailsData['price'],
//             'total_price' => $purchaseItem['total_price'],
//             'รายละเอียดการซื้อ' => $detailsData,
//         ];
//     }

//     $response->getBody()->write(json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));

//     return $response
//         ->withHeader('Content-Type', 'application/json; charset=utf-8')
//         ->withStatus(200);
// });




$app->get('/purchase/{id}', function (Request $request, Response $response, $args) {
    $id = $args['id'];
    $conn = $GLOBALS['connect'];

    // เรียกข้อมูลรายละเอียดการซื้อสำหรับแต่ละการซื้อของผู้ใช้ที่ระบุ
    $detailsSql = 'SELECT * FROM purchase_details WHERE purchase_id IN (SELECT id FROM purchase WHERE user_id = ?)';
    $detailsStmt = $conn->prepare($detailsSql);
    $detailsStmt->bind_param('i', $id);
    $detailsStmt->execute();
    $detailsResult = $detailsStmt->get_result();

    $responseData = [];

    // ลูปผ่านรายละเอียดการซื้อ
    while ($row = $detailsResult->fetch_assoc()) {
        // เรียกข้อมูลการซื้อ
        $purchaseSql = 'SELECT * FROM purchase WHERE id = ?';
        $purchaseStmt = $conn->prepare($purchaseSql);
        $purchaseStmt->bind_param('i', $row['purchase_id']);
        $purchaseStmt->execute();
        $purchaseResult = $purchaseStmt->get_result();
        $purchaseData = $purchaseResult->fetch_assoc();

        // เรียกข้อมูล "ชื่อ" จากตาราง "users"
        $userSql = 'SELECT first_name, last_name FROM users WHERE id = ?';
        $userStmt = $conn->prepare($userSql);
        $userStmt->bind_param('i', $id);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $userData = $userResult->fetch_assoc();

        // เรียกข้อมูล "หมายเลขสลาก" จากตาราง "lottery"
        $lotteryData = [];
        if (!empty($row['ticket_id'])) {
            $lotterySql = 'SELECT ticket_number FROM lottery WHERE id = ?';
            $lotteryStmt = $conn->prepare($lotterySql);
            $lotteryStmt->bind_param('i', $row['ticket_id']);
            $lotteryStmt->execute();
            $lotteryResult = $lotteryStmt->get_result();
            $lotteryData = $lotteryResult->fetch_assoc();
        }

        $newDateFormat = date("M-d-Y", strtotime($row['created_at']));

        // รวมข้อมูลการซื้อและรายละเอียดการซื้อลงใน responseData
        $responseData[] = [
            'created_at' => $newDateFormat,
            'first_name' => $userData['first_name'],
            'last_name' => $userData['last_name'],
            'ticket_number' => $lotteryData['ticket_number'],
            'quantity' => $row['quantity'],
            'price' => $row['price'],
            'total_price' => $row['total_price'],
            'Details' => $row,
        ];
    }

    $response->getBody()->write(json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));

    return $response
        ->withHeader('Content-Type', 'application/json; charset=utf-8')
        ->withStatus(200);
});



$app->get('/purchase', function (Request $request, Response $response, $args) {
    $conn = $GLOBALS['connect'];

    // เรียกข้อมูลรายละเอียดการซื้อสำหรับแต่ละการซื้อ
    $detailsSql = 'SELECT * FROM purchase_details ';
    $detailsStmt = $conn->prepare($detailsSql);
    $detailsStmt->execute();
    $detailsResult = $detailsStmt->get_result();

    $detailData = [];
    while ($row = $detailsResult->fetch_assoc()) {
        $detailData[] = $row;
    }

    // สร้างอาร์เรย์สำหรับเก็บข้อมูลรวม
    $responseData = [];

    // ลูปผ่านการซื้อทุกรายการ
    foreach ($detailData as $detailsData) {
        // เรียกข้อมูลการซื้อ
        $purchaseSql = 'SELECT * FROM purchase WHERE id = ?';
        $purchaseStmt = $conn->prepare($purchaseSql);
        $purchaseStmt->bind_param('i', $detailsData['purchase_id']);
        $purchaseStmt->execute();
        $purchaseResult = $purchaseStmt->get_result();
        $purchaseresult = $purchaseResult->fetch_assoc();

        // เรียกข้อมูล "ชื่อ" จากตาราง "users"
        $userSql = 'SELECT first_name, last_name FROM users WHERE id = ?';
        $userStmt = $conn->prepare($userSql);
        $userStmt->bind_param('i', $purchaseresult['user_id']);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $userData = $userResult->fetch_assoc();

        // เรียกข้อมูล "หมายเลขสลาก" จากตาราง "lottery"
        $lotteryData = [];
        if (!empty($detailsData)) {
            $lotterySql = 'SELECT ticket_number FROM lottery WHERE id = ?';
            $lotteryStmt = $conn->prepare($lotterySql);
            $lotteryStmt->bind_param('i', $detailsData['ticket_id']);
            $lotteryStmt->execute();
            $lotteryResult = $lotteryStmt->get_result();
            $lotteryData = $lotteryResult->fetch_assoc();
        }

        $newDateFormat = date("M-d-Y", strtotime($detailsData['created_at']));

        // รวมข้อมูลการซื้อและรายละเอียดการซื้อลงใน responseData
        $responseData[] = [
            'created_at' => $newDateFormat,
            'first_name' => $userData['first_name'],
            'last_name' => $userData['last_name'],
            'ticket_number' => $lotteryData['ticket_number'],
            'quantity' => $detailsData['quantity'],
            'price' => $detailsData['price'],
            'total_price' => $detailsData['total_price'],
            'Details' => $detailsData,
        ];
    }

    $response->getBody()->write(json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));

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

$app->post('/lottery/buy', function (Request $request, Response $response, $args) {
    $json = $request->getBody();
    $jsonData = json_decode($json, true);
    $conn = $GLOBALS['connect'];
    $conn->begin_transaction();

    $sqlBuyticket = "INSERT INTO purchase (user_id,total_price,quantity) VALUES (?,?,?)";

    $stmtBuyticket = $conn->prepare($sqlBuyticket);
    $stmtBuyticket->bind_param('sss', $jsonData['ac_id'], $jsonData['grand_total'],$jsonData['quantity']);
    $stmtBuyticket->execute();

    $purchase_id = $conn->insert_id;

    foreach ($jsonData['details'] as $data) {
        $lotId = $data['lot_id'];
        $amount = $data['amount'];
        $price = $data['price'];
        $totalPrice = $data['total_price'];

        $sqlDetailBuyticket = "INSERT INTO purchase_details (purchase_id, ticket_id, quantity, price, total_price) VALUES (?, ?, ?, ?, ?)";

        $stmtDetailBuyticket = $conn->prepare($sqlDetailBuyticket);
        $stmtDetailBuyticket->bind_param('iiidd', $purchase_id, $lotId, $amount, $price, $totalPrice);
        $stmtDetailBuyticket->execute();

        $affectedDetailBuyticket = $stmtDetailBuyticket->affected_rows;

        if ($affectedDetailBuyticket <= 0) {
            $conn->rollback();
            $response->getBody()->write(json_encode(["error" => "Insert failed"]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
    }
    $conn->commit();


    $data = ["affected_rows" => count($jsonData['details']), "last_idx" => $conn->insert_id];
    $response->getBody()->write(json_encode($data));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(200);
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