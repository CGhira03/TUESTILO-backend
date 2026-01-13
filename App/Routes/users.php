<?php

use App\Controllers\UserController;
use App\Middlewares\AuthMiddleware;

$router->addRoute('GET', '/users/me', function () {
    $user = AuthMiddleware::verifyToken();
    return (new UserController())->getMe($user);
});

$router->addRoute('PUT', '/users/me', function () {
    $user = AuthMiddleware::verifyToken();
    return (new UserController())->updateProfile($user);
});

