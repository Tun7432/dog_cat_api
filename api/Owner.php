<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\UploadedFile;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$app->post('/owners', function (Request $request, Response $response, $args) {
    $directory = __DIR__ . '../uploads';
    $uploadedFiles = $request->getUploadedFiles();
    $data = $request->getParsedBody();

    // ตรวจสอบการอัปโหลดไฟล์
    if (empty($uploadedFiles['image'])) {
        $response->getBody()->write(json_encode(['error' => 'No image uploaded']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $image = $uploadedFiles['image'];
    if ($image->getError() === UPLOAD_ERR_OK) {
        $filename = moveUploadedFile($directory, $image);
        $data['image_path'] = $directory . '/' . $filename;
    } else {
        $response->getBody()->write(json_encode(['error' => 'Image upload failed']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }

    $conn = $GLOBALS['connect'];
    $stmt = $conn->prepare('INSERT INTO owners
        (first_name, last_name, phone, email, rabies_vaccine_history, vaccine_date, bite_history, bite_count, image_path)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');

    $stmt->bind_param(
        'sssssssiss',
        $data['first_name'],
        $data['last_name'],
        $data['phone'],
        $data['email'],
        $data['rabies_vaccine_history'],
        $data['vaccine_date'],
        $data['bite_history'],
        $data['bite_count'],
        $data['image_path']
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

function moveUploadedFile($directory, UploadedFile $uploadedFile)
{
    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    $basename = bin2hex(random_bytes(8));
    $filename = sprintf('%s.%0.8s', $basename, $extension);

    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

    return $filename;
}




// $app->post('/owners/{id}/upload', function (Request $request, Response $response, $args) {
//     $owner_id = $args['id'];
//     $directory = __DIR__ . '../uploads';

//     // ตรวจสอบและสร้างโฟลเดอร์ถ้ายังไม่มี
//     if (!is_dir($directory)) {
//         mkdir($directory, 0777, true);
//     }

//     $uploadedFiles = $request->getUploadedFiles();
//     $errors = [];

//     if (!isset($uploadedFiles['image'])) {
//         $errors[] = 'No image uploaded';
//     }

//     if (!empty($errors)) {
//         $response->getBody()->write(json_encode(['errors' => $errors]));
//         return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
//     }

//     $uploadedFile = $uploadedFiles['image'];

//     if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
//         $filename = moveUploadedFile($directory, $uploadedFile);

//         $conn = $GLOBALS['connect'];
//         $stmt = $conn->prepare('UPDATE owners SET image_path = ? WHERE id = ?');
//         $stmt->bind_param('si', $filename, $owner_id);

//         if ($stmt->execute()) {
//             $responseBody = [
//                 'message' => 'Image uploaded successfully',
//                 'image_path' => $filename
//             ];
//             $response->getBody()->write(json_encode($responseBody));
//             return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
//         } else {
//             $response->getBody()->write(json_encode(['error' => 'Failed to update image path']));
//             return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
//         }
//     } else {
//         $response->getBody()->write(json_encode(['error' => 'Failed to upload image']));
//         return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
//     }
// });

// function moveUploadedFile($directory, UploadedFile $uploadedFile)
// {
//     $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
//     $basename = bin2hex(random_bytes(8)); // สร้างชื่อไฟล์แบบสุ่ม
//     $filename = sprintf('%s.%0.8s', $basename, $extension);

//     $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

//     return $filename;
// }



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



$app->get('/search-owners', function (Request $request, Response $response, $args) {
    $queryParams = $request->getQueryParams();
    $firstName = $queryParams['first_name'] ?? null;
    $lastName = $queryParams['last_name'] ?? null;
    $phone = $queryParams['phone'] ?? null;

    $conn = $GLOBALS['connect'];
    $sql = '
        SELECT 
            o.id AS owner_id, 
            o.prefix, 
            o.first_name, 
            o.last_name, 
            o.phone, 
            o.email, 
            oa.province, 
            oa.district, 
            oa.sub_district, 
            oa.house_number, 
            oa.village_number, 
            oa.community_name, 
            oa.alley, 
            oa.road, 
            oa.postal_code,
            p.id AS pet_id, 
            p.type AS pet_type, 
            p.name AS pet_name, 
            p.color AS pet_color, 
            p.gender AS pet_gender, 
            p.birth_date AS pet_birth_date, 
            p.neutered AS pet_neutered, 
            p.rabies_vaccine AS pet_rabies_vaccine, 
            p.status AS pet_status
        FROM 
            owners o
        LEFT JOIN 
            owner_addresses oa ON o.id = oa.owner_id
        LEFT JOIN 
            pets p ON o.id = p.owner_id
        WHERE 
            (? IS NULL OR o.first_name LIKE ?)
            AND (? IS NULL OR o.last_name LIKE ?)
            AND (? IS NULL OR o.phone LIKE ?)
    ';

    $stmt = $conn->prepare($sql);

    $likeFirstName = $firstName ? "%$firstName%" : null;
    $likeLastName = $lastName ? "%$lastName%" : null;
    $likePhone = $phone ? "%$phone%" : null;

    $stmt->bind_param('ssssss', $firstName, $likeFirstName, $lastName, $likeLastName, $phone, $likePhone);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});
