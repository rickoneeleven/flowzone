<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/src/Router.php';

// Initialize router
$router = new Router();

// Register test route
$router->register('GET', '/', function() {
    echo 'Hello World!';
});

// Handle 404
$router->setNotFoundHandler(function() {
    http_response_code(404);
    echo '404 - Page Not Found';
});

// Handle the request
$router->handle();