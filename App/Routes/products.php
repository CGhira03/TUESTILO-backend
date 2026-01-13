<?php

use App\Controllers\ProductController;

// 🌍 PÚBLICO
$router->addRoute('GET', '/products', [ProductController::class, 'getAll']);
$router->addRoute('GET', '/products/{id:\d+}', [ProductController::class, 'getOne']);

// 🔐 ADMIN
$router->addRoute('POST', '/products', [ProductController::class, 'create']);
$router->addRoute('POST', '/products/{id:\d+}', [ProductController::class, 'update']);
$router->addRoute('DELETE', '/products/{id:\d+}', [ProductController::class, 'remove']);
