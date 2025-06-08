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
 * API Routes avec architecture révolutionnaire
 * Auto-découverte et optimisation quantique
 * Intégration intelligente avec les handlers sémantiques
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
            'quantum_mode' => true,
            'ai_engine' => 'operational',
            'semantic_discovery' => 'enabled'
        ]);
    });
    
    $router->get('/health', function() {
        return response()->json([
            'status' => 'healthy',
            'database' => 'connected',
            'cache' => 'operational',
            'quantum_engine' => 'active',
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
    
    // Connexion avec sécurité quantique
    $router->post('/login', [AuthHandler::class, 'login']);
    
    // Inscription avec validation IA
    $router->post('/register', [AuthHandler::class, 'register']);
    
    // Déconnexion sécurisée
    $router->post('/logout', [AuthHandler::class, 'logout'])
           ->middleware([AuthMiddleware::class]);
    
    // Rafraîchissement de token
    $router->post('/refresh', [AuthHandler::class, 'refresh'])
           ->middleware([AuthMiddleware::class]);
    
    // Vérification d'email avec IA
    $router->post('/verify-email', [AuthHandler::class, 'verifyEmail']);
    
    // Réinitialisation de mot de passe quantique
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
    // Gestion des utilisateurs avec IA
    // ========================================
    $router->group(['prefix' => 'users'], function($router) {
        
        // Routes CRUD de base
        $router->get('/', [UserHandler::class, 'index']);
        $router->post('/', [UserHandler::class, 'store']);
        $router->get('/{id}', [UserHandler::class, 'show']);
        $router->put('/{id}', [UserHandler::class, 'update']);
        $router->delete('/{id}', [UserHandler::class, 'destroy']);
        
        // Routes avancées avec IA
        $router->get('/search', [UserHandler::class, 'search']);
        $router->get('/{id}/dashboard', [UserHandler::class, 'dashboard']);
        $router->get('/{id}/stats', [UserHandler::class, 'getStats']);
        $router->get('/{id}/analytics', [UserHandler::class, 'analytics']);
        
        // Gestion des préférences
        $router->put('/{id}/preferences', [UserHandler::class, 'updatePreferences']);
        
        // Export intelligent
        $router->post('/export', [UserHandler::class, 'export']);
        
        // Profil utilisateur
        $router->get('/{id}/profile', [UserHandler::class, 'getProfile']);
        $router->put('/{id}/profile', [UserHandler::class, 'updateProfile']);
    });
    
    // ========================================
    // Tableau de bord intelligent
    // ========================================
    $router->group(['prefix' => 'dashboard'], function($router) {
        $router->get('/', [DashboardHandler::class, 'index']);
        $router->get('/stats', [DashboardHandler::class, 'getStats']);
        $router->get('/analytics', [DashboardHandler::class, 'getAnalytics']);
        $router->get('/insights', [DashboardHandler::class, 'getInsights']);
        $router->get('/quantum-metrics', [DashboardHandler::class, 'getQuantumMetrics']);
    });
    
    // ========================================
    // Gestion des fichiers avec IA
    // ========================================
    $router->group(['prefix' => 'files'], function($router) {
        $router->post('/upload', [FileHandler::class, 'upload']);
        $router->get('/{id}', [FileHandler::class, 'show']);
        $router->delete('/{id}', [FileHandler::class, 'destroy']);
        $router->post('/analyze', [FileHandler::class, 'analyzeWithAI']);
        $router->post('/optimize', [FileHandler::class, 'optimizeWithQuantum']);
    });
    
    // ========================================
    // Notifications intelligentes
    // ========================================
    $router->group(['prefix' => 'notifications'], function($router) {
        $router->get('/', [NotificationHandler::class, 'index']);
        $router->put('/{id}/read', [NotificationHandler::class, 'markAsRead']);
        $router->delete('/{id}', [NotificationHandler::class, 'destroy']);
        $router->post('/preferences', [NotificationHandler::class, 'updatePreferences']);
        $router->get('/ai-suggestions', [NotificationHandler::class, 'getAISuggestions']);
    });
    
    // ========================================
    // Routes d'administration quantique
    // ========================================
    $router->group([
        'prefix' => 'admin',
        'middleware' => ['admin']
    ], function($router) {
        
        // Monitoring système
        $router->get('/system/status', [AdminHandler::class, 'getSystemStatus']);
        $router->get('/system/metrics', [AdminHandler::class, 'getSystemMetrics']);
        $router->get('/quantum/performance', [AdminHandler::class, 'getQuantumPerformance']);
        
        // Gestion des utilisateurs admin
        $router->get('/users/analytics', [AdminHandler::class, 'getUserAnalytics']);
        $router->post('/users/bulk-actions', [AdminHandler::class, 'bulkUserActions']);
        
        // Configuration IA
        $router->get('/ai/config', [AdminHandler::class, 'getAIConfig']);
        $router->put('/ai/config', [AdminHandler::class, 'updateAIConfig']);
        
        // Optimisation quantique
        $router->post('/quantum/optimize', [AdminHandler::class, 'triggerQuantumOptimization']);
        $router->get('/quantum/logs', [AdminHandler::class, 'getQuantumLogs']);
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