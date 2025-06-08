<?php

// Include helper functions
require_once __DIR__ . '/../../kernel/Nexa/Support/helpers.php';

use Nexa\Routing\Router;
use Nexa\Core\Config;
use Nexa\Http\Middleware\AuthMiddleware;
use Nexa\Http\Middleware\ThrottleMiddleware;
use Nexa\Http\Middleware\CorsMiddleware;
use Workspace\Handlers\UserHandler;
use Workspace\Handlers\AuthHandler;
use Workspace\Handlers\DashboardHandler;
use Workspace\Handlers\FileHandler;
use Workspace\Handlers\NotificationHandler;
use Workspace\Handlers\WebSocketHandler;
use Workspace\Handlers\IntegrationHandler;
use Workspace\Handlers\AdminHandler;

/**
 * API Routes avec architecture moderne
 * Auto-découverte et optimisations avancées
 * Intégration avec les handlers
 */

$apiRouter = new Router();

// ========================================
// Routes publiques (sans authentification)
// ========================================

$apiRouter->group(['prefix' => 'api/v1'], function($router) {
    
    // Status et santé de l'API
    $router->get('/status', function() {
        return response()->json([
            'status' => 'active',
            'version' => '1.0.0',
            'timestamp' => now(),
            'advanced_mode' => true,
            'engine' => 'operational',
            'auto_discovery' => 'enabled'
        ]);
    });
    
    $router->get('/health', function() {
        return response()->json([
            'status' => 'healthy',
            'database' => 'connected',
            'cache' => 'operational',
            'engine' => 'active',
            'handlers_discovered' => app('handler.registry')->count(),
            'entities_discovered' => app('entity.registry')->count()
        ]);
    });
    
    // Documentation API auto-générée
    $router->get('/docs', function() {
        return app('api.documentation')->generate();
    });
});

// ========================================
// Routes d'authentification
// ========================================

$apiRouter->group(['prefix' => 'api/v1/auth'], function($router) {
    
    // Connexion sécurisée
    $router->post('/login', [AuthHandler::class, 'login']);
    
    // Inscription avec validation avancée
    $router->post('/register', [AuthHandler::class, 'register']);
    
    // Déconnexion sécurisée
    $router->post('/logout', [AuthHandler::class, 'logout'])
           ->middleware([AuthMiddleware::class]);
    
    // Rafraîchissement de token
    $router->post('/refresh', [AuthHandler::class, 'refresh'])
           ->middleware([AuthMiddleware::class]);
    
    // Vérification d'email
    $router->post('/verify-email', [AuthHandler::class, 'verifyEmail']);
    
    // Réinitialisation de mot de passe sécurisée
    $router->post('/forgot-password', [AuthHandler::class, 'forgotPassword']);
    $router->post('/reset-password', [AuthHandler::class, 'resetPassword']);
});
    
// ========================================
// Routes protégées (authentification requise)
// ========================================

$apiRouter->group([
    'prefix' => 'api/v1',
    'middleware' => [AuthMiddleware::class, ThrottleMiddleware::class]
], function($router) {
    
    // ========================================
    // Gestion des utilisateurs
    // ========================================
    $router->group(['prefix' => 'users'], function($router) {
        
        // Routes CRUD de base
        $router->get('/', [UserHandler::class, 'index']);
        $router->post('/', [UserHandler::class, 'store']);
        $router->get('/{id}', [UserHandler::class, 'show']);
        $router->put('/{id}', [UserHandler::class, 'update']);
        $router->delete('/{id}', [UserHandler::class, 'destroy']);
        
        // Routes avancées
        $router->get('/search', [UserHandler::class, 'search']);
        $router->get('/{id}/dashboard', [UserHandler::class, 'dashboard']);
        $router->get('/{id}/stats', [UserHandler::class, 'getStats']);
        $router->get('/{id}/analytics', [UserHandler::class, 'analytics']);
        
        // Gestion des préférences
        $router->put('/{id}/preferences', [UserHandler::class, 'updatePreferences']);
        
        // Export des données
        $router->post('/export', [UserHandler::class, 'export']);
        
        // Profil utilisateur
        $router->get('/{id}/profile', [UserHandler::class, 'getProfile']);
        $router->put('/{id}/profile', [UserHandler::class, 'updateProfile']);
    });
    
    // ========================================
    // Tableau de bord
    // ========================================
    $router->group(['prefix' => 'dashboard'], function($router) {
        $router->get('/', [DashboardHandler::class, 'index']);
        $router->get('/stats', [DashboardHandler::class, 'getStats']);
        $router->get('/analytics', [DashboardHandler::class, 'getAnalytics']);
        $router->get('/insights', [DashboardHandler::class, 'getInsights']);
        $router->get('/metrics', [DashboardHandler::class, 'getMetrics']);
    });
    
    // ========================================
    // Gestion des fichiers
    // ========================================
    $router->group(['prefix' => 'files'], function($router) {
        $router->post('/upload', [FileHandler::class, 'upload']);
        $router->get('/{id}', [FileHandler::class, 'show']);
        $router->delete('/{id}', [FileHandler::class, 'destroy']);
        $router->post('/analyze', [FileHandler::class, 'analyze']);
        $router->post('/optimize', [FileHandler::class, 'optimize']);
    });
    
    // ========================================
    // Notifications
    // ========================================
    $router->group(['prefix' => 'notifications'], function($router) {
        $router->get('/', [NotificationHandler::class, 'index']);
        $router->put('/{id}/read', [NotificationHandler::class, 'markAsRead']);
        $router->delete('/{id}', [NotificationHandler::class, 'destroy']);
        $router->post('/preferences', [NotificationHandler::class, 'updatePreferences']);
        $router->get('/suggestions', [NotificationHandler::class, 'getSuggestions']);
    });
    
    // ========================================
    // Routes d'administration
    // ========================================
    $router->group([
        'prefix' => 'admin',
        'middleware' => ['admin']
    ], function($router) {
        
        // Monitoring système
        $router->get('/system/status', [AdminHandler::class, 'getSystemStatus']);
        $router->get('/system/metrics', [AdminHandler::class, 'getSystemMetrics']);
        $router->get('/performance', [AdminHandler::class, 'getPerformance']);
        
        // Gestion des utilisateurs admin
        $router->get('/users/analytics', [AdminHandler::class, 'getUserAnalytics']);
        $router->post('/users/bulk-actions', [AdminHandler::class, 'bulkUserActions']);
        
        // Configuration avancée
        $router->get('/config', [AdminHandler::class, 'getConfig']);
        $router->put('/config', [AdminHandler::class, 'updateConfig']);
        
        // Optimisation système
        $router->post('/optimize', [AdminHandler::class, 'triggerOptimization']);
        $router->get('/logs', [AdminHandler::class, 'getLogs']);
    });
});

// ========================================
// Routes WebSocket temps réel
// ========================================

$apiRouter->group(['prefix' => 'ws'], function($router) {
    $router->get('/connect', [WebSocketHandler::class, 'connect']);
    $router->post('/broadcast', [WebSocketHandler::class, 'broadcast']);
    $router->get('/channels', [WebSocketHandler::class, 'getChannels']);
});

// ========================================
// Routes d'intégration externe
// ========================================

$apiRouter->group(['prefix' => 'integrations'], function($router) {
    $router->post('/webhooks/{service}', [IntegrationHandler::class, 'handleWebhook']);
    $router->get('/oauth/{provider}/redirect', [IntegrationHandler::class, 'oauthRedirect']);
    $router->get('/oauth/{provider}/callback', [IntegrationHandler::class, 'oauthCallback']);
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

return $apiRouter;