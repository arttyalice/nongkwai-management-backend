<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\UploadedFileInterface as UploadedFile;

require './vendor/autoload.php';
require './src/config/db.php';

$app = new \Slim\App([
  'settings' => [
    'displayErrorDetails' => true
  ]
]);

$container = $app->getContainer();
$container['upload_directory'] = __DIR__ . '/uploads';
$container['upload_url'] = 'http://localhost:10080/www/api/uploads/';

$app->options('/{routes:.+}', function ($request, $response, $args) {
  return $response;
});

$app->add(function ($req, $res, $next) {
  $response = $next($req, $res);
  return $response
    ->withHeader('Access-Control-Allow-Origin', '*')
    ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
    ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
}); 

require './src/routes/user.php';
require './src/routes/address.php';
require './src/routes/person.php';
require './src/routes/disabled.php';
require './src/routes/elder.php';
require './src/routes/aid.php';
require './src/routes/contact.php';
require './src/routes/visiting.php';
require './src/routes/treatment.php';
require './src/routes/allowance.php';

$app->run();