<?php

use Nexa\Routing\Router;
use App\Http\Controllers\Api\ApiController;
use Nexa\Core\Config;

$apiRouter = new Router();

// Routes API avec préfixe /api
$apiRouter->group(['prefix' => 'api', 'middleware' => 'cors'], function($router) {
    
    // ========================================
    // Routes publiques (sans authentification)
    // ========================================
    
    // Monitoring et santé de l'API
    $router->get('/status', [ApiController::class, 'status']);
    $router->get('/health', [ApiController::class, 'health']);
    
    // ========================================
    // Routes d'authentification
    // ========================================
    $router->group(['prefix' => 'auth'], function($router) {
        $router->post('/login', [ApiController::class, 'login']);
        $router->post('/register', [ApiController::class, 'register']);
        $router->post('/logout', [ApiController::class, 'logout']);
    });
    
    // ========================================
    // Routes protégées (authentification requise)
    // ========================================
    $router->group(['middleware' => 'auth:api'], function($router) {
        
        // Profil utilisateur
        $router->get('/user', [ApiController::class, 'user']);
        
        // Gestion des utilisateurs (CRUD)
        $router->group(['prefix' => 'users'], function($router) {
            $router->get('/', [ApiController::class, 'users']);           // GET /api/users
            $router->post('/', [ApiController::class, 'createUser']);     // POST /api/users
            $router->get('/{id}', [ApiController::class, 'getUser']);     // GET /api/users/{id}
            $router->put('/{id}', [ApiController::class, 'updateUser']);  // PUT /api/users/{id}
            $router->delete('/{id}', [ApiController::class, 'deleteUser']); // DELETE /api/users/{id}
        });
        
    });
    
    // ========================================
    // Routes de développement (à supprimer en production)
    // ========================================
    if (Config::env('APP_ENV', 'production') === 'development') {
        $router->get('/debug/routes', function() {
            return json_encode([
                'message' => 'Routes de debug disponibles',
                'routes' => [
                    'GET /api/status' => 'Statut de l\'API',
                    'GET /api/health' => 'Santé de l\'API',
                    'POST /api/auth/login' => 'Connexion',
                    'POST /api/auth/register' => 'Inscription',
                    'GET /api/user' => 'Profil utilisateur (auth)',
                    'GET /api/users' => 'Liste des utilisateurs (auth)'
                ]
            ]);
        });
    }
});

return $apiRouter;