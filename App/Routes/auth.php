<?php

use App\Controllers\AuthController;

$router->addRoute('POST', '/auth/register', [AuthController::class, 'register']);
$router->addRoute('POST', '/auth/login', [AuthController::class, 'login']);
