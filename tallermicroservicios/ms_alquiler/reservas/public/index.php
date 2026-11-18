<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use App\Views\Vista;
use Illuminate\Database\Capsule\Manager as Capsule;

require __DIR__ . '/../vendor/autoload.php';

// Eloquent: conexión a la base de datos del profesor
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'database' => 'alquiler_vehiculos_db',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();


$app = AppFactory::create();
$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();

// Middleware global: token fijo (como en la guía). La ruta raíz queda libre para comprobar que el servicio vive.
$app->add(function (Request $request, $handler) {
    $libre = $request->getMethod() === 'OPTIONS' || $request->getUri()->getPath() === '/';
    if (!$libre && $request->getHeaderLine('Authorization') !== 'Bearer 12345') {
        return Vista::error(new \Slim\Psr7\Response(), "No autorizado", 401);
    }
    return $handler->handle($request);
});

// CORS (se agrega después, así corre primero y también cubre los errores 401)
$app->add(function (Request $request, $handler) {
    $origin = $request->getHeaderLine('Origin') ?: '*';
    $response = $request->getMethod() === 'OPTIONS' ? new \Slim\Psr7\Response(200) : $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', $origin)
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'true');
});

$errores = $app->addErrorMiddleware(true, true, true);
$errores->getDefaultErrorHandler()->forceContentType("application/json");

$app->get('/', function (Request $request, Response $response) {
    return Vista::responder($response, ['servicio' => 'reservas', 'estado' => 'activo']);
});

require __DIR__ . '/../app/rutas.php';
$app->run();
