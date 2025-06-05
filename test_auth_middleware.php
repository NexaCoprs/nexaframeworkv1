<?php

require_once 'vendor/autoload.php';

use Nexa\Http\Middleware\AuthMiddleware;
use Nexa\Http\Request;

class AuthMiddlewareTest
{
    private $middleware;
    private $testResults = [];

    public function __construct()
    {
        $this->middleware = new AuthMiddleware();
    }

    public function runAllTests()
    {
        echo "=== Test de l'AuthMiddleware ===\n\n";
        
        $this->testUnauthenticatedUser();
        $this->testAuthenticatedUser();
        $this->testLoginLogout();
        $this->testUserMethod();
        $this->testRolePermissions();
        
        $this->displayResults();
    }

    private function testUnauthenticatedUser()
    {
        echo "Test 1: Utilisateur non authentifié\n";
        
        // Nettoyer la session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        // Créer une requête mock
        $request = $this->createMockRequest();
        
        // Tester directement la méthode isAuthenticated via reflection
        $reflection = new ReflectionClass($this->middleware);
        $method = $reflection->getMethod('isAuthenticated');
        $method->setAccessible(true);
        
        $isAuth = $method->invoke($this->middleware, $request);
        
        if (!$isAuth) {
            $this->testResults[] = ['test' => 'Unauthenticated User', 'status' => 'PASSED', 'message' => 'Correctly detected unauthenticated user'];
        } else {
            $this->testResults[] = ['test' => 'Unauthenticated User', 'status' => 'FAILED', 'message' => 'Should detect unauthenticated user'];
        }
        
        echo "✓ Test terminé\n\n";
    }

    private function testAuthenticatedUser()
    {
        echo "Test 2: Utilisateur authentifié\n";
        
        // Simuler une session authentifiée
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['user_id'] = 1;
        $_SESSION['user_name'] = 'Test User';
        $_SESSION['user_email'] = 'test@example.com';
        
        $request = $this->createMockRequest();
        
        // Tester directement la méthode isAuthenticated via reflection
        $reflection = new ReflectionClass($this->middleware);
        $method = $reflection->getMethod('isAuthenticated');
        $method->setAccessible(true);
        
        $isAuth = $method->invoke($this->middleware, $request);
        
        if ($isAuth) {
            $this->testResults[] = ['test' => 'Authenticated User', 'status' => 'PASSED', 'message' => 'Correctly detected authenticated user'];
        } else {
            $this->testResults[] = ['test' => 'Authenticated User', 'status' => 'FAILED', 'message' => 'Should detect authenticated user'];
        }
        
        echo "✓ Test terminé\n\n";
    }

    private function testLoginLogout()
    {
        echo "Test 3: Login et Logout\n";
        
        // Test login
        $userData = [
            'id' => 123,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];
        
        AuthMiddleware::login($userData);
        
        $user = AuthMiddleware::user();
        
        if ($user && $user['id'] === 123 && $user['name'] === 'John Doe') {
            $this->testResults[] = ['test' => 'Login', 'status' => 'PASSED', 'message' => 'User logged in successfully'];
        } else {
            $this->testResults[] = ['test' => 'Login', 'status' => 'FAILED', 'message' => 'Login failed or user data incorrect'];
        }
        
        // Test logout
        AuthMiddleware::logout();
        
        $userAfterLogout = AuthMiddleware::user();
        
        if ($userAfterLogout === null) {
            $this->testResults[] = ['test' => 'Logout', 'status' => 'PASSED', 'message' => 'User logged out successfully'];
        } else {
            $this->testResults[] = ['test' => 'Logout', 'status' => 'FAILED', 'message' => 'Logout failed - user still logged in'];
        }
        
        echo "✓ Test terminé\n\n";
    }

    private function testUserMethod()
    {
        echo "Test 4: Méthode user()\n";
        
        // Test sans utilisateur connecté
        $user = AuthMiddleware::user();
        
        if ($user === null) {
            $this->testResults[] = ['test' => 'User Method (No User)', 'status' => 'PASSED', 'message' => 'Correctly returns null when no user'];
        } else {
            $this->testResults[] = ['test' => 'User Method (No User)', 'status' => 'FAILED', 'message' => 'Should return null when no user'];
        }
        
        // Test avec utilisateur connecté
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['user_id'] = 456;
        $_SESSION['user_name'] = 'Jane Doe';
        $_SESSION['user_email'] = 'jane@example.com';
        
        $user = AuthMiddleware::user();
        
        if ($user && $user['id'] === 456 && $user['name'] === 'Jane Doe') {
            $this->testResults[] = ['test' => 'User Method (With User)', 'status' => 'PASSED', 'message' => 'Correctly returns user data'];
        } else {
            $this->testResults[] = ['test' => 'User Method (With User)', 'status' => 'FAILED', 'message' => 'User data incorrect or missing'];
        }
        
        echo "✓ Test terminé\n\n";
    }

    private function testRolePermissions()
    {
        echo "Test 5: Rôles et Permissions\n";
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Test sans rôles/permissions
        $hasRole = AuthMiddleware::hasRole('admin');
        $hasPermission = AuthMiddleware::hasPermission('edit_posts');
        
        if (!$hasRole && !$hasPermission) {
            $this->testResults[] = ['test' => 'Roles/Permissions (Empty)', 'status' => 'PASSED', 'message' => 'Correctly returns false for non-existent roles/permissions'];
        } else {
            $this->testResults[] = ['test' => 'Roles/Permissions (Empty)', 'status' => 'FAILED', 'message' => 'Should return false for non-existent roles/permissions'];
        }
        
        // Test avec rôles/permissions
        $_SESSION['user_roles'] = ['admin', 'editor'];
        $_SESSION['user_permissions'] = ['edit_posts', 'delete_posts'];
        
        $hasAdminRole = AuthMiddleware::hasRole('admin');
        $hasUserRole = AuthMiddleware::hasRole('user');
        $hasEditPermission = AuthMiddleware::hasPermission('edit_posts');
        $hasViewPermission = AuthMiddleware::hasPermission('view_analytics');
        
        if ($hasAdminRole && !$hasUserRole && $hasEditPermission && !$hasViewPermission) {
            $this->testResults[] = ['test' => 'Roles/Permissions (With Data)', 'status' => 'PASSED', 'message' => 'Correctly validates roles and permissions'];
        } else {
            $this->testResults[] = ['test' => 'Roles/Permissions (With Data)', 'status' => 'FAILED', 'message' => 'Role/permission validation failed'];
        }
        
        echo "✓ Test terminé\n\n";
    }

    private function createMockRequest()
    {
        // Créer une requête mock simple
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['HTTP_HOST'] = 'localhost';
        
        return Request::capture();
    }

    private function displayResults()
    {
        echo "=== Résultats des Tests ===\n\n";
        
        $passed = 0;
        $failed = 0;
        
        foreach ($this->testResults as $result) {
            $status = $result['status'] === 'PASSED' ? '✅' : '❌';
            echo "{$status} {$result['test']}: {$result['message']}\n";
            
            if ($result['status'] === 'PASSED') {
                $passed++;
            } else {
                $failed++;
            }
        }
        
        echo "\n=== Résumé ===\n";
        echo "Tests réussis: {$passed}\n";
        echo "Tests échoués: {$failed}\n";
        echo "Total: " . ($passed + $failed) . "\n";
        
        if ($failed === 0) {
            echo "\n🎉 Tous les tests sont passés!\n";
        } else {
            echo "\n⚠️  Certains tests ont échoué.\n";
        }
    }
}

// Exécuter les tests
try {
    $tester = new AuthMiddlewareTest();
    $tester->runAllTests();
} catch (Exception $e) {
    echo "Erreur lors de l'exécution des tests: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}