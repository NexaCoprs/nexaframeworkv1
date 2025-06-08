<?php

use Nexa\Routing\Router;
use Workspace\Handlers\WelcomeHandler;
use Workspace\Handlers\TestHandler;

$webRouter = new Router();

// Routes principales
$webRouter->get('/', [WelcomeHandler::class, 'index']);
$webRouter->get('/about', [WelcomeHandler::class, 'about']);
$webRouter->get('/documentation', [WelcomeHandler::class, 'documentation']);
$webRouter->get('/contact', [WelcomeHandler::class, 'contact']);
$webRouter->post('/contact', [WelcomeHandler::class, 'contact']);

// Routes de test
$webRouter->group(['prefix' => 'test'], function($router) {
    $router->get('/', [TestHandler::class, 'index']);
    $router->get('/show/{id}', [TestHandler::class, 'show']);
    $router->get('/create', [TestHandler::class, 'create']);
    $router->post('/create', [TestHandler::class, 'create']);
});

// Routes CRUD pour TestHandler
$webRouter->group(['prefix' => 'items'], function($router) {
    $router->get('/', [TestHandler::class, 'index']);
    $router->get('/{id}', [TestHandler::class, 'show']);
    $router->post('/', [TestHandler::class, 'store']);
    $router->put('/{id}', [TestHandler::class, 'update']);
    $router->delete('/{id}', [TestHandler::class, 'destroy']);
});

return $webRouter;