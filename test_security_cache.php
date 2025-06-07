<?php

require_once 'vendor/autoload.php';

// Définir le chemin de base si pas déjà défini
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}

// Charger les helpers
require_once __DIR__ . '/src/Nexa/Core/helpers.php';

// Démarrer la session si pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Créer le dossier storage si nécessaire
if (!is_dir(__DIR__ . '/storage')) {
    mkdir(__DIR__ . '/storage', 0755, true);
}
if (!is_dir(__DIR__ . '/storage/cache')) {
    mkdir(__DIR__ . '/storage/cache', 0755, true);
}

use Nexa\Core\Cache;
use Nexa\Middleware\SecurityMiddleware;
use Nexa\Security\CsrfProtection;
use Nexa\Security\XssProtection;
use Nexa\Security\RateLimiter;
use Nexa\Core\Config;

/**
 * Script de test pour la sécurité et le cache du Framework Nexa
 * 
 * Ce script teste :
 * - Fonctionnalités de cache (stockage, récupération, expiration)
 * - Protections de sécurité (CSRF, XSS, Rate Limiting)
 * - Middleware de sécurité
 * - Configuration de sécurité
 */

echo "\n🔒 === TESTS DE SÉCURITÉ ET CACHE NEXA FRAMEWORK ===\n";
echo "📅 Date: " . date('Y-m-d H:i:s') . "\n\n";

// Compteurs de tests
$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

/**
 * Fonction utilitaire pour exécuter un test
 */
function runTest($testName, $testFunction) {
    global $totalTests, $passedTests, $failedTests;
    
    $totalTests++;
    $startTime = microtime(true);
    
    try {
        $result = $testFunction();
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);
        
        if ($result === true) {
            echo "✅ {$testName}";
            if ($duration > 0) {
                echo " ({$duration}ms)";
            }
            echo "\n";
            $passedTests++;
        } else {
            echo "❌ {$testName} - Test échoué";
            if (is_string($result)) {
                echo " ({$result})";
            }
            echo "\n";
            $failedTests++;
        }
    } catch (Exception $e) {
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);
        echo "❌ {$testName} - Exception: " . $e->getMessage() . "\n";
        $failedTests++;
    }
}

// ===== TESTS DE CACHE =====
echo "\n💾 === TESTS DU SYSTÈME DE CACHE ===\n";

// Initialisation du cache
Cache::init(__DIR__ . '/storage/cache', 'test_', 3600);

// Test 1: Stockage et récupération de base
runTest("Cache - Stockage et récupération", function() {
    $key = 'test_key_' . time();
    $value = 'test_value_' . rand(1000, 9999);
    
    // Stocker
    $stored = Cache::put($key, $value, 60);
    if (!$stored) return "Échec du stockage";
    
    // Récupérer
    $retrieved = Cache::get($key);
    return $retrieved === $value;
});

// Test 2: Cache avec expiration
runTest("Cache - Expiration", function() {
    $key = 'expire_test_' . time();
    $value = 'expire_value';
    
    // Stocker avec TTL très court (1 seconde)
    Cache::put($key, $value, 1);
    
    // Vérifier que la valeur existe
    if (Cache::get($key) !== $value) {
        return "Valeur non trouvée immédiatement";
    }
    
    // Attendre l'expiration
    sleep(2);
    
    // Vérifier que la valeur a expiré
    return Cache::get($key) === null;
});

// Test 3: Cache - Vérification d'existence
runTest("Cache - Vérification d'existence", function() {
    $key = 'exists_test_' . time();
    $value = 'exists_value';
    
    // Vérifier que la clé n'existe pas
    if (Cache::has($key)) {
        return "Clé existe déjà";
    }
    
    // Stocker
    Cache::put($key, $value, 60);
    
    // Vérifier que la clé existe maintenant
    return Cache::has($key);
});

// Test 4: Cache - Suppression
runTest("Cache - Suppression", function() {
    $key = 'delete_test_' . time();
    $value = 'delete_value';
    
    // Stocker
    Cache::put($key, $value, 60);
    
    // Vérifier que la valeur existe
    if (Cache::get($key) !== $value) {
        return "Valeur non stockée";
    }
    
    // Supprimer
    $deleted = Cache::forget($key);
    if (!$deleted) {
        return "Échec de la suppression";
    }
    
    // Vérifier que la valeur n'existe plus
    return Cache::get($key) === null;
});

// Test 5: Cache - Stockage d'objets complexes
runTest("Cache - Objets complexes", function() {
    $key = 'object_test_' . time();
    $object = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'data' => ['key1' => 'value1', 'key2' => 'value2'],
        'timestamp' => time()
    ];
    
    // Stocker l'objet
    Cache::put($key, $object, 60);
    
    // Récupérer l'objet
    $retrieved = Cache::get($key);
    
    return $retrieved === $object;
});

// ===== TESTS DE SÉCURITÉ =====
echo "\n🔒 === TESTS DE SÉCURITÉ ===\n";

// Test 6: Protection CSRF - Génération de token
runTest("CSRF - Génération de token", function() {
    try {
        $csrf = new CsrfProtection();
        $token = $csrf->generateToken();
        
        return !empty($token) && strlen($token) >= 32;
    } catch (Exception $e) {
        return "Exception: " . $e->getMessage();
    }
});

// Test 7: Protection CSRF - Validation de token
runTest("CSRF - Validation de token", function() {
    try {
        $csrf = new CsrfProtection();
        $token = $csrf->generateToken();
        
        // Simuler une session avec le token
        $_SESSION['_token'] = $token;
        
        // Valider le token
        return $csrf->validateToken($token);
    } catch (Exception $e) {
        return "Exception: " . $e->getMessage();
    }
});

// Test 8: Protection XSS - Nettoyage de base
runTest("XSS - Nettoyage de base", function() {
    try {
        $maliciousInput = '<script>alert("XSS")</script>Hello World';
        $cleaned = XssProtection::clean($maliciousInput);
        
        // Vérifier que le script a été supprimé
        return strpos($cleaned, '<script>') === false && strpos($cleaned, 'Hello World') !== false;
    } catch (Exception $e) {
        return "Exception: " . $e->getMessage();
    }
});

// Test 9: Protection XSS - Attributs malicieux
runTest("XSS - Attributs malicieux", function() {
    try {
        $maliciousInput = '<img src="x" onerror="alert(1)">Image';
        $cleaned = XssProtection::clean($maliciousInput);
        
        // Vérifier que l'attribut onerror a été supprimé
        return strpos($cleaned, 'onerror') === false;
    } catch (Exception $e) {
        return "Exception: " . $e->getMessage();
    }
});

// Test 10: Rate Limiting - Limitation de base
runTest("Rate Limiting - Fonctionnement de base", function() {
    try {
        $rateLimiter = new RateLimiter();
        $key = 'test_ip_' . time();
        
        // Première tentative - devrait passer
        $result1 = $rateLimiter->attempt($key, 5, 60); // 5 tentatives par minute
        
        if (!$result1) {
            return "Première tentative échouée";
        }
        
        // Vérifier le nombre de tentatives restantes
        $remaining = $rateLimiter->remaining($key, 5, 1);
        
        return $remaining < 5; // Devrait être 4 maintenant
    } catch (Exception $e) {
        return "Exception: " . $e->getMessage();
    }
});

// Test 11: Configuration de sécurité
runTest("Configuration de sécurité", function() {
    try {
        // Charger la configuration de sécurité
        $securityConfig = include __DIR__ . '/config/security.php';
        
        // Vérifier les éléments essentiels
        $hasEncryption = isset($securityConfig['encryption']);
        $hasPassword = isset($securityConfig['password']);
        $hasCsrf = isset($securityConfig['csrf']);
        $hasXss = isset($securityConfig['xss']);
        $hasRateLimit = isset($securityConfig['rate_limiting']);
        
        return $hasEncryption && $hasPassword && $hasCsrf && $hasXss && $hasRateLimit;
    } catch (Exception $e) {
        return "Exception: " . $e->getMessage();
    }
});

// Test 12: Configuration de cache
runTest("Configuration de cache", function() {
    try {
        // Charger la configuration de cache
        $cacheConfig = include __DIR__ . '/config/cache.php';
        
        // Vérifier les éléments essentiels
        $hasDefault = isset($cacheConfig['default']);
        $hasStores = isset($cacheConfig['stores']);
        $hasFileStore = isset($cacheConfig['stores']['file']);
        
        return $hasDefault && $hasStores && $hasFileStore;
    } catch (Exception $e) {
        return "Exception: " . $e->getMessage();
    }
});

// Test 13: Headers de sécurité
runTest("Headers de sécurité", function() {
    try {
        // Appliquer les headers de sécurité
        $securityConfig = include __DIR__ . '/config/security.php';
        $headers = $securityConfig['headers'] ?? [];
        
        // Vérifier que les headers essentiels sont configurés
        $hasXFrameOptions = isset($headers['X-Frame-Options']);
        $hasXContentType = isset($headers['X-Content-Type-Options']);
        $hasXXssProtection = isset($headers['X-XSS-Protection']);
        
        return $hasXFrameOptions && $hasXContentType && $hasXXssProtection;
    } catch (Exception $e) {
        return "Exception: " . $e->getMessage();
    }
});

// Test 14: Validation de mot de passe
runTest("Validation de mot de passe", function() {
    try {
        $securityConfig = include __DIR__ . '/config/security.php';
        $passwordConfig = $securityConfig['password'];
        
        // Tester un mot de passe faible
        $weakPassword = 'weak';
        $strongPassword = 'StrongP@ssw0rd123';
        
        // Vérifier la longueur minimale
        $minLength = $passwordConfig['min_length'];
        $weakTooShort = strlen($weakPassword) < $minLength;
        $strongLongEnough = strlen($strongPassword) >= $minLength;
        
        // Vérifier les exigences
        $requireUpper = $passwordConfig['require_uppercase'];
        $requireLower = $passwordConfig['require_lowercase'];
        $requireNumbers = $passwordConfig['require_numbers'];
        
        $strongHasUpper = preg_match('/[A-Z]/', $strongPassword);
        $strongHasLower = preg_match('/[a-z]/', $strongPassword);
        $strongHasNumbers = preg_match('/[0-9]/', $strongPassword);
        
        return $weakTooShort && $strongLongEnough && 
               (!$requireUpper || $strongHasUpper) &&
               (!$requireLower || $strongHasLower) &&
               (!$requireNumbers || $strongHasNumbers);
    } catch (Exception $e) {
        return "Exception: " . $e->getMessage();
    }
});

// Test 15: Nettoyage du cache
runTest("Cache - Nettoyage complet", function() {
    try {
        // Stocker plusieurs valeurs
        Cache::put('cleanup_test_1', 'value1', 60);
        Cache::put('cleanup_test_2', 'value2', 60);
        Cache::put('cleanup_test_3', 'value3', 60);
        
        // Vérifier qu'elles existent
        if (!Cache::has('cleanup_test_1') || !Cache::has('cleanup_test_2') || !Cache::has('cleanup_test_3')) {
            return "Valeurs non stockées";
        }
        
        // Nettoyer le cache
        $cleared = Cache::flush();
        
        if (!$cleared) {
            return "Échec du nettoyage";
        }
        
        // Vérifier que les valeurs n'existent plus
        return !Cache::has('cleanup_test_1') && !Cache::has('cleanup_test_2') && !Cache::has('cleanup_test_3');
    } catch (Exception $e) {
        return "Exception: " . $e->getMessage();
    }
});

// ===== RÉSULTATS FINAUX =====
echo "\n📈 === RÉSULTATS FINAUX ===\n";
echo "🎯 Total des tests: {$totalTests}\n";
echo "✅ Tests réussis: {$passedTests}\n";
echo "❌ Tests échoués: {$failedTests}\n";

$successRate = round(($passedTests / $totalTests) * 100, 1);
echo "📊 Taux de réussite: {$successRate}%\n\n";

// Évaluation finale
if ($successRate >= 95) {
    echo "🏆 EXCELLENT! Les systèmes de sécurité et cache sont très robustes\n";
} elseif ($successRate >= 85) {
    echo "✅ BIEN! Les systèmes de sécurité et cache sont fonctionnels\n";
} elseif ($successRate >= 70) {
    echo "⚠️ MOYEN! Quelques améliorations nécessaires\n";
} else {
    echo "❌ INSUFFISANT! Des corrections importantes sont requises\n";
}

echo "\n🎉 Test de sécurité et cache terminé!\n";
echo "📋 Framework Nexa - Sécurité et Cache testés\n";

if ($successRate >= 85) {
    echo "🚀 Systèmes prêts pour la production: OUI\n";
} else {
    echo "⚠️ Systèmes prêts pour la production: NON\n";
}

echo "\n📊 Détails: {$passedTests}/{$totalTests} tests réussis\n";
echo "🔒 Sécurité: " . ($successRate >= 90 ? 'Excellente' : ($successRate >= 80 ? 'Bonne' : 'À améliorer')) . "\n";
echo "💾 Cache: " . ($successRate >= 90 ? 'Performant' : ($successRate >= 80 ? 'Fonctionnel' : 'À optimiser')) . "\n";
echo "🛡️ Robustesse: Protections multiples intégrées\n";

?>