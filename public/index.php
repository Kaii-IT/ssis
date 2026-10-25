<?php
require __DIR__ . '/../app/bootstrap.php';

Auth::start();
$router = new Router();
require APP . '/routes.php';

try {
    $router->dispatch();
} catch (Throwable $e) {
    error_log((string)$e);
    http_response_code(500);
    echo config('debug') ? '<pre>' . e((string)$e) . '</pre>' : 'Something went wrong. Please try again later.';
}