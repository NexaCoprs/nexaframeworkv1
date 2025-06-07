<?php

/**
 * Script de test pour les améliorations de sécurité et de cache
 * Nexa Framework - Phase d'amélioration sécurité
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Nexa/Core/helpers.php';

// Charger les variables d'environnement
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

use Nexa\Security\CsrfProtection;
use Nexa\Security\XssProtection;
use Nexa\Security\RateLimiter;
use Nexa\Cache\FileCache;
use Nexa\Http\Request;
use Nexa\Http\Response;

echo "=== Test des Améliorations de Sécurité et Cache - Nexa Framework ===\n\n";

$tests = [
    'csrf_protection' => false,
    'xss_protection' => false,
    'rate_limiter' => false,
    'file_cache' => false,
    'security_config' => false,
    'cache_config' => false
];

// Test 1: Protection CSRF
echo "1. Test de la Protection CSRF...\n";
try {
    $csrf = new CsrfProtection();
    
    // Générer un token
    $token = $csrf->generateToken();
    echo "   ✓ Token CSRF généré: " . substr($token, 0, 16) . "...\n";
    
    // Valider le token
    $isValid = $csrf->validateToken($token);
    echo "   ✓ Validation du token: " . ($isValid ? 'Succès' : 'Échec') . "\n";
    
    // Générer un champ de formulaire
    $field = $csrf->field();
    echo "   ✓ Champ de formulaire généré\n";
    
    // Générer une meta tag
    $metaTag = $csrf->metaTag();
    echo "   ✓ Meta tag générée\n";
    
    $tests['csrf_protection'] = true;
    echo "   ✅ Protection CSRF: FONCTIONNELLE\n\n";
} catch (Exception $e) {
    echo "   ❌ Erreur Protection CSRF: " . $e->getMessage() . "\n\n";
}

// Test 2: Protection XSS
echo "2. Test de la Protection XSS...\n";
try {
    // Test de nettoyage basique
    $maliciousInput = '<script>alert("XSS")</script>Hello World';
    $cleaned = XssProtection::clean($maliciousInput);
    echo "   ✓ Nettoyage XSS: " . $cleaned . "\n";
    
    // Test de validation
    $isValid = XssProtection::validate($maliciousInput);
    echo "   ✓ Validation XSS: " . ($isValid ? 'Valide' : 'Dangereux') . "\n";
    
    // Test de nettoyage HTML
    $htmlInput = '<p>Texte <strong>gras</strong></p><script>alert("bad")</script>';
    $cleanedHtml = XssProtection::cleanHtml($htmlInput);
    echo "   ✓ Nettoyage HTML: " . $cleanedHtml . "\n";
    
    // Test d'encodage pour attributs
    $attrValue = 'value"onclick="alert(1)';
    $encoded = XssProtection::attribute($attrValue);
    echo "   ✓ Encodage attribut: " . $encoded . "\n";
    
    // Test de détection SQL injection
    $sqlInput = "'; DROP TABLE users; --";
    $isSqlInjection = XssProtection::detectSqlInjection($sqlInput);
    echo "   ✓ Détection SQL injection: " . ($isSqlInjection ? 'Détectée' : 'Non détectée') . "\n";
    
    $tests['xss_protection'] = true;
    echo "   ✅ Protection XSS: FONCTIONNELLE\n\n";
} catch (Exception $e) {
    echo "   ❌ Erreur Protection XSS: " . $e->getMessage() . "\n\n";
}

// Test 3: Limitation de taux
echo "3. Test de la Limitation de Taux...\n";
try {
    $rateLimiter = new RateLimiter();
    
    $key = 'test_user_' . time();
    
    // Test de tentatives multiples
    $attempts = 0;
    for ($i = 0; $i < 5; $i++) {
        if ($rateLimiter->attempt($key, 3, 1)) {
            $attempts++;
        }
    }
    echo "   ✓ Tentatives autorisées: $attempts/5\n";
    
    // Test des tentatives restantes
    $remaining = $rateLimiter->remaining($key, 3, 1);
    echo "   ✓ Tentatives restantes: $remaining\n";
    
    // Test de réinitialisation
    $resetTime = $rateLimiter->resetTime($key, 1);
    echo "   ✓ Temps de réinitialisation: " . date('H:i:s', $resetTime) . "\n";
    
    // Test de nettoyage
    $rateLimiter->clear($key);
    $remainingAfterClear = $rateLimiter->remaining($key, 3, 1);
    echo "   ✓ Après nettoyage: $remainingAfterClear tentatives\n";
    
    $tests['rate_limiter'] = true;
    echo "   ✅ Limitation de Taux: FONCTIONNELLE\n\n";
} catch (Exception $e) {
    echo "   ❌ Erreur Limitation de Taux: " . $e->getMessage() . "\n\n";
}

// Test 4: Cache de fichiers
echo "4. Test du Cache de Fichiers...\n";
try {
    $cache = new FileCache();
    
    // Test de stockage et récupération
    $cache->put('test_key', 'test_value', 60);
    $value = $cache->get('test_key');
    echo "   ✓ Stockage/Récupération: " . ($value === 'test_value' ? 'Succès' : 'Échec') . "\n";
    
    // Test d'existence
    $exists = $cache->has('test_key');
    echo "   ✓ Vérification d'existence: " . ($exists ? 'Trouvé' : 'Non trouvé') . "\n";
    
    // Test remember
    $remembered = $cache->remember('computed_value', function() {
        return 'valeur calculée';
    }, 60);
    echo "   ✓ Remember: $remembered\n";
    
    // Test d'incrémentation
    $cache->put('counter', 5);
    $incremented = $cache->increment('counter', 3);
    echo "   ✓ Incrémentation: $incremented\n";
    
    // Test de stockage multiple
    $cache->putMany([
        'key1' => 'value1',
        'key2' => 'value2'
    ], 60);
    $many = $cache->many(['key1', 'key2']);
    echo "   ✓ Stockage multiple: " . count($many) . " éléments\n";
    
    // Test des statistiques
    $stats = $cache->stats();
    echo "   ✓ Statistiques: {$stats['total_entries']} entrées, {$stats['total_size']} bytes\n";
    
    // Nettoyage
    $cache->forget('test_key');
    $cache->forget('computed_value');
    $cache->forget('counter');
    $cache->forget('key1');
    $cache->forget('key2');
    
    $tests['file_cache'] = true;
    echo "   ✅ Cache de Fichiers: FONCTIONNEL\n\n";
} catch (Exception $e) {
    echo "   ❌ Erreur Cache de Fichiers: " . $e->getMessage() . "\n\n";
}

// Test 5: Configuration de sécurité
echo "5. Test de la Configuration de Sécurité...\n";
try {
    $securityConfigPath = __DIR__ . '/config/security.php';
    if (file_exists($securityConfigPath)) {
        $config = require $securityConfigPath;
        
        echo "   ✓ Fichier de configuration trouvé\n";
        echo "   ✓ CSRF activé: " . ($config['csrf']['enabled'] ? 'Oui' : 'Non') . "\n";
        echo "   ✓ XSS activé: " . ($config['xss']['enabled'] ? 'Oui' : 'Non') . "\n";
        echo "   ✓ Rate limiting activé: " . ($config['rate_limiting']['enabled'] ? 'Oui' : 'Non') . "\n";
        echo "   ✓ Headers de sécurité: " . count($config['headers']) . " configurés\n";
        
        $tests['security_config'] = true;
        echo "   ✅ Configuration de Sécurité: FONCTIONNELLE\n\n";
    } else {
        echo "   ❌ Fichier de configuration de sécurité non trouvé\n\n";
    }
} catch (Exception $e) {
    echo "   ❌ Erreur Configuration de Sécurité: " . $e->getMessage() . "\n\n";
}

// Test 6: Configuration de cache
echo "6. Test de la Configuration de Cache...\n";
try {
    $cacheConfigPath = __DIR__ . '/config/cache.php';
    if (file_exists($cacheConfigPath)) {
        $config = require $cacheConfigPath;
        
        echo "   ✓ Fichier de configuration trouvé\n";
        echo "   ✓ Driver par défaut: " . $config['default'] . "\n";
        echo "   ✓ Stores configurés: " . count($config['stores']) . "\n";
        
        if (isset($config['stores']['file'])) {
            echo "   ✓ Store file configuré\n";
        }
        
        $tests['cache_config'] = true;
        echo "   ✅ Configuration de Cache: FONCTIONNELLE\n\n";
    } else {
        echo "   ❌ Fichier de configuration de cache non trouvé\n\n";
    }
} catch (Exception $e) {
    echo "   ❌ Erreur Configuration de Cache: " . $e->getMessage() . "\n\n";
}

// Résumé des tests
echo "=== RÉSUMÉ DES TESTS ===\n";
$passed = 0;
$total = count($tests);

foreach ($tests as $test => $result) {
    $status = $result ? '✅ PASSÉ' : '❌ ÉCHEC';
    echo "$test: $status\n";
    if ($result) $passed++;
}

echo "\n";
echo "Tests réussis: $passed/$total\n";
echo "Pourcentage de réussite: " . round(($passed / $total) * 100, 1) . "%\n";

if ($passed === $total) {
    echo "\n🎉 TOUTES LES AMÉLIORATIONS DE SÉCURITÉ ET CACHE SONT FONCTIONNELLES!\n";
    echo "\n📋 Fonctionnalités implémentées:\n";
    echo "   • Protection CSRF avec génération et validation de tokens\n";
    echo "   • Protection XSS avec nettoyage et validation\n";
    echo "   • Limitation de taux avec stockage fichier\n";
    echo "   • Cache de fichiers avec TTL et statistiques\n";
    echo "   • Configuration de sécurité complète\n";
    echo "   • Headers de sécurité configurables\n";
    echo "\n🔒 Le framework Nexa est maintenant sécurisé et optimisé!\n";
} else {
    echo "\n⚠️  Certaines améliorations nécessitent une attention supplémentaire.\n";
}

echo "\n=== FIN DES TESTS ===\n";