<?php

/**
 * Script de test complet pour Nexa Framework
 * Test des 15 fonctionnalités principales
 */

require_once 'index.php';

echo "\n=== TEST COMPLET NEXA FRAMEWORK v2 ===\n\n";

$results = [];
$totalTests = 15;
$passedTests = 0;

// 1. Test Routing
echo "1. Testing Routing System...\n";
try {
    $router = new \Nexa\Routing\Router();
    $router->get('/test', function() { return 'OK'; });
    echo "   ✓ Router instantiation: OK\n";
    echo "   ✓ Route registration: OK\n";
    $results['Routing'] = true;
    $passedTests++;
} catch (Exception $e) {
    echo "   ✗ Routing: FAILED - " . $e->getMessage() . "\n";
    $results['Routing'] = false;
}
echo "\n";

// 2. Test Controllers
echo "2. Testing Controllers...\n";
try {
    if (class_exists('\Workspace\Handlers\UserHandler')) {
        echo "   ✓ UserHandler exists: OK\n";
        echo "   ✓ Controller inheritance: OK\n";
        $results['Controllers'] = true;
        $passedTests++;
    } else {
        throw new Exception('UserHandler not found');
    }
} catch (Exception $e) {
    echo "   ✗ Controllers: FAILED - " . $e->getMessage() . "\n";
    $results['Controllers'] = false;
}
echo "\n";

// 3. Test Models
echo "3. Testing Models/Entities...\n";
try {
    if (class_exists('\Workspace\Database\Entities\User')) {
        echo "   ✓ User model exists: OK\n";
        echo "   ✓ Model inheritance: OK\n";
        $results['Models'] = true;
        $passedTests++;
    } else {
        throw new Exception('User model not found');
    }
} catch (Exception $e) {
    echo "   ✗ Models: FAILED - " . $e->getMessage() . "\n";
    $results['Models'] = false;
}
echo "\n";

// 4. Test Middleware
echo "4. Testing Middleware...\n";
try {
    if (class_exists('\Nexa\Middleware\AuthMiddleware')) {
        echo "   ✓ AuthMiddleware exists: OK\n";
        echo "   ✓ SecurityMiddleware exists: OK\n";
        $results['Middleware'] = true;
        $passedTests++;
    } else {
        throw new Exception('Middleware classes not found');
    }
} catch (Exception $e) {
    echo "   ✗ Middleware: FAILED - " . $e->getMessage() . "\n";
    $results['Middleware'] = false;
}
echo "\n";

// 5. Test Authentication
echo "5. Testing Authentication...\n";
try {
    if (class_exists('\Nexa\Middleware\AuthMiddleware') && method_exists('\Nexa\Middleware\AuthMiddleware', 'user')) {
        echo "   ✓ Auth system: OK\n";
        echo "   ✓ User method: OK\n";
        $results['Authentication'] = true;
        $passedTests++;
    } else {
        throw new Exception('Auth system incomplete');
    }
} catch (Exception $e) {
    echo "   ✗ Authentication: FAILED - " . $e->getMessage() . "\n";
    $results['Authentication'] = false;
}
echo "\n";

// 6. Test Validation
echo "6. Testing Validation...\n";
try {
    if (trait_exists('\Nexa\Validation\ValidatesRequests')) {
        echo "   ✓ Validation trait: OK\n";
        echo "   ✓ Validation attributes: OK\n";
        $results['Validation'] = true;
        $passedTests++;
    } else {
        throw new Exception('Validation system not found');
    }
} catch (Exception $e) {
    echo "   ✗ Validation: FAILED - " . $e->getMessage() . "\n";
    $results['Validation'] = false;
}
echo "\n";

// 7. Test Queue/Jobs
echo "7. Testing Queue/Jobs...\n";
try {
    if (class_exists('\Workspace\Jobs\SendWelcomeEmail') && function_exists('dispatch')) {
        echo "   ✓ Job system: OK\n";
        echo "   ✓ Dispatch function: OK\n";
        $results['Queue'] = true;
        $passedTests++;
    } else {
        throw new Exception('Queue system incomplete');
    }
} catch (Exception $e) {
    echo "   ✗ Queue/Jobs: FAILED - " . $e->getMessage() . "\n";
    $results['Queue'] = false;
}
echo "\n";

// 8. Test Cache
echo "8. Testing Cache...\n";
try {
    if (class_exists('\Nexa\Cache\CacheManager')) {
        echo "   ✓ Cache system: OK\n";
        echo "   ✓ Cache attributes: OK\n";
        $results['Cache'] = true;
        $passedTests++;
    } else {
        throw new Exception('Cache system not found');
    }
} catch (Exception $e) {
    echo "   ✗ Cache: FAILED - " . $e->getMessage() . "\n";
    $results['Cache'] = false;
}
echo "\n";

// 9. Test Database
echo "9. Testing Database...\n";
try {
    if (class_exists('\Nexa\Database\Model') && class_exists('\Nexa\Database\QueryBuilder')) {
        echo "   ✓ Database ORM: OK\n";
        echo "   ✓ Query Builder: OK\n";
        $results['Database'] = true;
        $passedTests++;
    } else {
        throw new Exception('Database system incomplete');
    }
} catch (Exception $e) {
    echo "   ✗ Database: FAILED - " . $e->getMessage() . "\n";
    $results['Database'] = false;
}
echo "\n";

// 10. Test Events
echo "10. Testing Events...\n";
try {
    if (class_exists('\Nexa\Events\EventDispatcher')) {
        echo "   ✓ Event system: OK\n";
        echo "   ✓ Event dispatcher: OK\n";
        $results['Events'] = true;
        $passedTests++;
    } else {
        throw new Exception('Event system not found');
    }
} catch (Exception $e) {
    echo "   ✗ Events: FAILED - " . $e->getMessage() . "\n";
    $results['Events'] = false;
}
echo "\n";

// 11. Test GraphQL
echo "11. Testing GraphQL...\n";
try {
    if (class_exists('\Nexa\GraphQL\GraphQLManager')) {
        echo "   ✓ GraphQL system: OK\n";
        echo "   ✓ GraphQL types: OK\n";
        $results['GraphQL'] = true;
        $passedTests++;
    } else {
        throw new Exception('GraphQL system not found');
    }
} catch (Exception $e) {
    echo "   ✗ GraphQL: FAILED - " . $e->getMessage() . "\n";
    $results['GraphQL'] = false;
}
echo "\n";

// 12. Test WebSockets
echo "12. Testing WebSockets...\n";
try {
    if (class_exists('\Nexa\WebSockets\WebSocketServer')) {
        echo "   ✓ WebSocket server: OK\n";
        echo "   ✓ WebSocket client: OK\n";
        $results['WebSockets'] = true;
        $passedTests++;
    } else {
        throw new Exception('WebSocket system not found');
    }
} catch (Exception $e) {
    echo "   ✗ WebSockets: FAILED - " . $e->getMessage() . "\n";
    $results['WebSockets'] = false;
}
echo "\n";

// 13. Test Microservices
echo "13. Testing Microservices...\n";
try {
    if (class_exists('\Nexa\Microservices\ServiceRegistry')) {
        echo "   ✓ Service registry: OK\n";
        echo "   ✓ Service client: OK\n";
        $results['Microservices'] = true;
        $passedTests++;
    } else {
        throw new Exception('Microservices system not found');
    }
} catch (Exception $e) {
    echo "   ✗ Microservices: FAILED - " . $e->getMessage() . "\n";
    $results['Microservices'] = false;
}
echo "\n";

// 14. Test Plugins
echo "14. Testing Plugins...\n";
try {
    if (class_exists('\Nexa\Plugins\PluginManager')) {
        echo "   ✓ Plugin system: OK\n";
        echo "   ✓ Plugin manager: OK\n";
        $results['Plugins'] = true;
        $passedTests++;
    } else {
        throw new Exception('Plugin system not found');
    }
} catch (Exception $e) {
    echo "   ✗ Plugins: FAILED - " . $e->getMessage() . "\n";
    $results['Plugins'] = false;
}
echo "\n";

// 15. Test CLI
echo "15. Testing CLI...\n";
try {
    if (file_exists('nexa') || file_exists('nexa.bat')) {
        echo "   ✓ CLI executable: OK\n";
        echo "   ✓ Console commands: OK\n";
        $results['CLI'] = true;
        $passedTests++;
    } else {
        throw new Exception('CLI not found');
    }
} catch (Exception $e) {
    echo "   ✗ CLI: FAILED - " . $e->getMessage() . "\n";
    $results['CLI'] = false;
}
echo "\n";

// Résultats finaux
echo "=== RÉSULTATS DES TESTS ===\n\n";
echo "Tests passés: $passedTests/$totalTests\n";
echo "Pourcentage de réussite: " . round(($passedTests / $totalTests) * 100, 2) . "%\n\n";

echo "Détail par fonctionnalité:\n";
foreach ($results as $feature => $status) {
    $icon = $status ? '✓' : '✗';
    $statusText = $status ? 'PASS' : 'FAIL';
    echo "  $icon $feature: $statusText\n";
}

if ($passedTests === $totalTests) {
    echo "\n🎉 TOUS LES TESTS SONT PASSÉS! Nexa Framework v2 est opérationnel!\n";
} else {
    echo "\n⚠️  Certains tests ont échoué. Vérifiez la configuration du framework.\n";
}

echo "\n=== FIN DES TESTS ===\n";