<?php

use Nexa\Routing\Router;
use App\Http\Controllers\WelcomeController;

$router = new Router();

$router->get('/', [WelcomeController::class, 'index']);
$router->get('/about', [WelcomeController::class, 'about']);
$router->get('/documentation', [WelcomeController::class, 'documentation']);
$router->get('/contact', [WelcomeController::class, 'contact']);
$router->post('/contact', [WelcomeController::class, 'contact']);

return $router;