<?php
use Slim\Exception\HttpNotFoundException;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

require __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();
$app->setBasePath('/dog_cat_api');
$app->addErrorMiddleware(true, true, true);


require __DIR__ . '/api/User.php';
require __DIR__ . '/api/Owner.php';
require __DIR__ . '/api/Pet.php';
require __DIR__ . '/api/RabiesRecord.php';
require __DIR__ . '/api/Report.php';
require __DIR__ . '/api/StrayPet.php';
require __DIR__ . '/api/Address.php';
require __DIR__ . '/connection/dbconn.php';

$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

$app->add(function (Request $request, RequestHandler $handler): Response {
    $response = $handler->handle($request);
    $origin = $request->getHeaderLine('Origin');
    $allowedOrigins = ['http://localhost:4200', 'http://another-allowed-origin.com'];

    if (in_array($origin, $allowedOrigins)) {
        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    }

    return $response;
});

$app->get('/ping', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Pong!!!");
    return $response;
});

$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
    throw new HttpNotFoundException($request);
});

$app->addErrorMiddleware(true, true, true);
$app->run();
