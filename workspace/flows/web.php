<?php

use Nexa\Routing\Router;
use Workspace\Handlers\WelcomeController;
use Workspace\Handlers\TestController;
use Workspace\Handlers\TestControllerController;

$webRouter = new Router();

// Routes principales
$webRouter->get('/', [WelcomeController::class, 'index']);
$webRouter->get('/about', [WelcomeController::class, 'about']);
$webRouter->get('/documentation', [WelcomeController::class, 'documentation']);
$webRouter->get('/contact', [WelcomeController::class, 'contact']);
$webRouter->post('/contact', [WelcomeController::class, 'contact']);

// Routes de test
$webRouter->group(['prefix' => 'test'], function($router) {
    $router->get('/', [TestController::class, 'index']);
    $router->get('/show/{id}', [TestController::class, 'show']);
    $router->get('/create', [TestController::class, 'create']);
    $router->post('/create', [TestController::class, 'create']);
});

// Routes CRUD pour TestControllerController
$webRouter->group(['prefix' => 'items'], function($router) {
    $router->get('/', [TestControllerController::class, 'index']);
    $router->get('/{id}', [TestControllerController::class, 'show']);
    $router->post('/', [TestControllerController::class, 'store']);
    $router->put('/{id}', [TestControllerController::class, 'update']);
    $router->delete('/{id}', [TestControllerController::class, 'destroy']);
});

return $webRouter;