<?php

require_once 'vendor/autoload.php';

use Nexa\Http\Middleware\AuthMiddleware;
use Nexa\Http\Request;
use Nexa\Routing\Router;

/**
 * Exemple d'utilisation de l'AuthMiddleware
 * Ce fichier montre comment intégrer le middleware d'authentification
 * dans une application Nexa Framework
 */

class AuthExampleController
{
    public function dashboard()
    {
        $user = AuthMiddleware::user();
        return "Bienvenue sur le tableau de bord, {$user['name']}!";
    }
    
    public function adminPanel()
    {
        if (!AuthMiddleware::hasRole('admin')) {
            http_response_code(403);
            return 'Accès refusé - Rôle administrateur requis';
        }
        
        return 'Panneau d\'administration - Accès autorisé';
    }
    
    public function editPost()
    {
        if (!AuthMiddleware::hasPermission('edit_posts')) {
            http_response_code(403);
            return 'Accès refusé - Permission d\'édition requise';
        }
        
        return 'Édition d\'article autorisée';
    }
    
    public function login()
    {
        // Simuler une connexion utilisateur
        $userData = [
            'id' => 1,
            'name' => 'Jean Dupont',
            'email' => 'jean@example.com'
        ];
        
        AuthMiddleware::login($userData);
        
        // Ajouter des rôles et permissions
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        $_SESSION['user_roles'] = ['user', 'editor'];
        $_SESSION['user_permissions'] = ['edit_posts', 'view_posts'];
        
        return 'Connexion réussie!';
    }
    
    public function loginAdmin()
    {
        // Simuler une connexion administrateur
        $userData = [
            'id' => 2,
            'name' => 'Admin User',
            'email' => 'admin@example.com'
        ];
        
        AuthMiddleware::login($userData);
        
        // Ajouter des rôles et permissions d'admin
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        $_SESSION['user_roles'] = ['user', 'admin'];
        $_SESSION['user_permissions'] = ['edit_posts', 'delete_posts', 'manage_users'];
        
        return 'Connexion administrateur réussie!';
    }
    
    public function logout()
    {
        AuthMiddleware::logout();
        return 'Déconnexion réussie!';
    }
    
    public function profile()
    {
        $user = AuthMiddleware::user();
        if (!$user) {
            return 'Utilisateur non connecté';
        }
        
        return json_encode([
            'user' => $user,
            'roles' => $_SESSION['user_roles'] ?? [],
            'permissions' => $_SESSION['user_permissions'] ?? []
        ], JSON_PRETTY_PRINT);
    }
}

/**
 * Exemple de configuration de routes avec middleware
 */
function setupAuthRoutes()
{
    $router = new Router();
    $controller = new AuthExampleController();
    
    // Routes publiques (sans authentification)
    $router->get('/login', [$controller, 'login']);
    $router->get('/login-admin', [$controller, 'loginAdmin']);
    
    // Routes protégées (avec authentification)
    // Note: Dans une vraie application, vous appliqueriez le middleware ici
    $router->get('/dashboard', [$controller, 'dashboard']);
    $router->get('/profile', [$controller, 'profile']);
    $router->get('/admin', [$controller, 'adminPanel']);
    $router->get('/edit-post', [$controller, 'editPost']);
    $router->get('/logout', [$controller, 'logout']);
    
    return $router;
}

/**
 * Fonction pour tester l'authentification
 */
function testAuthenticationFlow()
{
    echo "=== Test du flux d'authentification ===\n\n";
    
    $controller = new AuthExampleController();
    
    // 1. Tester sans authentification
    echo "1. Test sans authentification:\n";
    $user = AuthMiddleware::user();
    echo "Utilisateur connecté: " . ($user ? json_encode($user) : 'Aucun') . "\n\n";
    
    // 2. Connexion utilisateur normal
    echo "2. Connexion utilisateur normal:\n";
    echo $controller->login() . "\n";
    $user = AuthMiddleware::user();
    echo "Utilisateur connecté: " . json_encode($user) . "\n\n";
    
    // 3. Test d'accès au dashboard
    echo "3. Accès au dashboard:\n";
    echo $controller->dashboard() . "\n\n";
    
    // 4. Test d'accès admin (devrait échouer)
    echo "4. Test d'accès admin (utilisateur normal):\n";
    echo $controller->adminPanel() . "\n\n";
    
    // 5. Test de permission d'édition
    echo "5. Test de permission d'édition:\n";
    echo $controller->editPost() . "\n\n";
    
    // 6. Connexion admin
    echo "6. Connexion administrateur:\n";
    echo $controller->loginAdmin() . "\n";
    
    // 7. Test d'accès admin (devrait réussir)
    echo "7. Test d'accès admin (administrateur):\n";
    echo $controller->adminPanel() . "\n\n";
    
    // 8. Affichage du profil complet
    echo "8. Profil utilisateur complet:\n";
    echo $controller->profile() . "\n\n";
    
    // 9. Déconnexion
    echo "9. Déconnexion:\n";
    echo $controller->logout() . "\n";
    $user = AuthMiddleware::user();
    echo "Utilisateur après déconnexion: " . ($user ? json_encode($user) : 'Aucun') . "\n\n";
}

/**
 * Fonction pour démontrer l'utilisation du middleware
 */
function demonstrateMiddleware()
{
    echo "=== Démonstration du Middleware ===\n\n";
    
    $middleware = new AuthMiddleware();
    $request = Request::capture();
    
    // Test avec utilisateur non connecté
    echo "Test 1: Utilisateur non connecté\n";
    $reflection = new ReflectionClass($middleware);
    $method = $reflection->getMethod('isAuthenticated');
    $method->setAccessible(true);
    
    $isAuth = $method->invoke($middleware, $request);
    echo "Authentifié: " . ($isAuth ? 'Oui' : 'Non') . "\n\n";
    
    // Connecter un utilisateur
    AuthMiddleware::login([
        'id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com'
    ]);
    
    // Test avec utilisateur connecté
    echo "Test 2: Utilisateur connecté\n";
    $isAuth = $method->invoke($middleware, $request);
    echo "Authentifié: " . ($isAuth ? 'Oui' : 'Non') . "\n\n";
    
    // Nettoyer
    AuthMiddleware::logout();
}

// Exécuter les démonstrations
if (php_sapi_name() === 'cli') {
    echo "=== Démonstration de l'AuthMiddleware ===\n\n";
    
    try {
        demonstrateMiddleware();
        testAuthenticationFlow();
        
        echo "✅ Démonstration terminée avec succès!\n";
    } catch (Exception $e) {
        echo "❌ Erreur: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
} else {
    echo "<h1>Démonstration de l'AuthMiddleware</h1>";
    echo "<p>Exécutez ce script en ligne de commande pour voir la démonstration.</p>";
    echo "<pre>php example_auth_usage.php</pre>";
}