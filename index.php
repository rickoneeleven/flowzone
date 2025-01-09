<?php
declare(strict_types=1);

// Load .env file
$env = parse_ini_file(__DIR__ . '/.env');
foreach ($env as $key => $value) {
    putenv("$key=$value");
}
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/src/Router.php';
require_once __DIR__ . '/src/Auth.php';

// Initialize auth and router
$auth = new Auth();
$router = new Router($auth);

// Register login routes (public)
$router->register('GET', '/login', function() {
    require __DIR__ . '/views/login.php';
});

$router->register('POST', '/login', function() use ($auth) {
    $password = $_POST['password'] ?? '';
    
    if ($auth->verifyPassword($password)) {
        $auth->createSession();
        header('Location: /');
        exit;
    }
    
    http_response_code(401);
    echo 'Invalid password';
});

// Register protected routes
$router->registerProtected('GET', '/', function() {
    echo 'Protected content here';
});

// Handle 404
$router->setNotFoundHandler(function() {
    http_response_code(404);
    echo '404 - Page Not Found';
});

// Handle the request
$router->handle();