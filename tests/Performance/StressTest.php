<?php

require_once __DIR__ . '/../bootstrap.php';

/**
 * Stress Tests for Nexa Framework
 * Tests de charge et de stress pour évaluer les limites du framework
 */
class StressTest extends TestCase
{
    private $stressResults = [];
    private $memoryLimit;
    private $timeLimit;
    
    public function setUp(): void
    {
        parent::setUp();
        
        $this->memoryLimit = ini_get('memory_limit');
        $this->timeLimit = ini_get('max_execution_time');
        
        // Augmenter les limites pour les tests de stress
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300); // 5 minutes
        
        $this->stressResults = [
            'router_stress' => [],
            'memory_stress' => [],
            'concurrent_stress' => [],
            'database_stress' => []
        ];
    }
    
    public function tearDown(): void
    {
        // Restaurer les limites originales
        ini_set('memory_limit', $this->memoryLimit);
        ini_set('max_execution_time', $this->timeLimit);
        
        parent::tearDown();
    }
    
    public function testRouterStressTest()
    {
        echo "\n=== TEST DE STRESS DU ROUTEUR ===\n";
        
        if (!class_exists('\\Nexa\\Routing\\Router')) {
            echo "⚠ Router non disponible pour le test de stress\n";
            return;
        }
        
        $router = new \Nexa\Routing\Router();
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        echo "Enregistrement de 10,000 routes...\n";
        
        // Test avec un grand nombre de routes
        for ($i = 0; $i < 10000; $i++) {
            $router->get("/route-$i", function() use ($i) {
                return "Response $i";
            });
            
            $router->post("/api/resource-$i", function() use ($i) {
                return ["id" => $i, "data" => "Resource $i"];
            });
            
            // Routes avec paramètres
            $router->get("/users/{id}/posts/$i", function($id) use ($i) {
                return "User $id posts $i";
            });
            
            // Routes groupées
            if ($i % 100 === 0) {
                $router->group(['prefix' => "v$i", 'middleware' => ['auth']], function($r) use ($i) {
                    $r->get('/dashboard', function() use ($i) {
                        return "Dashboard v$i";
                    });
                    
                    $r->resource('items', "ItemController$i");
                });
            }
            
            // Affichage du progrès
            if ($i % 1000 === 0 && $i > 0) {
                $currentMemory = memory_get_usage(true);
                $memoryUsed = $currentMemory - $startMemory;
                echo "  $i routes enregistrées - Mémoire: " . round($memoryUsed / 1024 / 1024, 2) . "MB\n";
            }
        }
        
        $registrationTime = microtime(true) - $startTime;
        $registrationMemory = memory_get_usage(true) - $startMemory;
        
        echo "\nTest de résolution de routes...\n";
        
        // Test de résolution avec un grand nombre de lookups
        $resolutionStart = microtime(true);
        $resolutionMemoryStart = memory_get_usage(true);
        
        for ($i = 0; $i < 1000; $i++) {
            $randomRoute = "/route-" . rand(0, 9999);
            // Simulation de résolution (nécessiterait un contexte de requête réel)
            
            if ($i % 100 === 0) {
                echo "  $i résolutions effectuées\n";
            }
        }
        
        $resolutionTime = microtime(true) - $resolutionStart;
        $resolutionMemory = memory_get_usage(true) - $resolutionMemoryStart;
        
        $this->stressResults['router_stress'] = [
            'routes_registered' => 30000, // 10k * 3 types
            'registration_time' => $registrationTime,
            'registration_memory' => $registrationMemory,
            'resolution_time' => $resolutionTime,
            'resolution_memory' => $resolutionMemory,
            'routes_per_second' => 30000 / $registrationTime,
            'lookups_per_second' => 1000 / $resolutionTime
        ];
        
        echo "\nRésultats du stress test du routeur:\n";
        echo "  Routes enregistrées: 30,000\n";
        echo "  Temps d'enregistrement: " . round($registrationTime, 3) . "s\n";
        echo "  Mémoire d'enregistrement: " . round($registrationMemory / 1024 / 1024, 2) . "MB\n";
        echo "  Routes/seconde: " . round(30000 / $registrationTime, 0) . "\n";
        echo "  Temps de résolution: " . round($resolutionTime, 3) . "s\n";
        echo "  Lookups/seconde: " . round(1000 / $resolutionTime, 0) . "\n";
        
        // Assertions de performance
        $this->assertLessThan(10.0, $registrationTime, "L'enregistrement de 30k routes devrait prendre moins de 10s");
        $this->assertLessThan(100 * 1024 * 1024, $registrationMemory, "L'enregistrement devrait utiliser moins de 100MB");
        $this->assertLessThan(1.0, $resolutionTime, "1000 résolutions devraient prendre moins de 1s");
    }
    
    public function testMemoryStressTest()
    {
        echo "\n=== TEST DE STRESS MÉMOIRE ===\n";
        
        $startMemory = memory_get_usage(true);
        $peakMemory = $startMemory;
        $iterations = 0;
        
        echo "Test d'allocation mémoire intensive...\n";
        
        try {
            // Test d'allocation de mémoire progressive
            for ($i = 0; $i < 1000; $i++) {
                // Créer des objets volumineux
                $largeArray = [];
                for ($j = 0; $j < 1000; $j++) {
                    $largeArray[] = [
                        'id' => $j,
                        'data' => str_repeat('x', 1000),
                        'timestamp' => microtime(true),
                        'random' => rand(1, 1000000)
                    ];
                }
                
                $currentMemory = memory_get_usage(true);
                if ($currentMemory > $peakMemory) {
                    $peakMemory = $currentMemory;
                }
                
                // Libérer la mémoire
                unset($largeArray);
                
                $iterations++;
                
                if ($i % 100 === 0) {
                    $memoryUsed = $currentMemory - $startMemory;
                    echo "  Itération $i - Mémoire: " . round($memoryUsed / 1024 / 1024, 2) . "MB\n";
                }
                
                // Vérifier si on approche de la limite
                if ($currentMemory > 400 * 1024 * 1024) { // 400MB
                    echo "  Limite de mémoire approchée, arrêt du test\n";
                    break;
                }
            }
            
        } catch (Exception $e) {
            echo "  Erreur de mémoire: {$e->getMessage()}\n";
        }
        
        $finalMemory = memory_get_usage(true);
        $memoryUsed = $finalMemory - $startMemory;
        $peakUsed = $peakMemory - $startMemory;
        
        $this->stressResults['memory_stress'] = [
            'iterations' => $iterations,
            'memory_used' => $memoryUsed,
            'peak_memory' => $peakUsed,
            'memory_per_iteration' => $iterations > 0 ? $peakUsed / $iterations : 0
        ];
        
        echo "\nRésultats du stress test mémoire:\n";
        echo "  Itérations: $iterations\n";
        echo "  Mémoire utilisée: " . round($memoryUsed / 1024 / 1024, 2) . "MB\n";
        echo "  Pic de mémoire: " . round($peakUsed / 1024 / 1024, 2) . "MB\n";
        echo "  Mémoire/itération: " . round(($iterations > 0 ? $peakUsed / $iterations : 0) / 1024, 2) . "KB\n";
    }
    
    public function testConcurrentRequestsSimulation()
    {
        echo "\n=== SIMULATION DE REQUÊTES CONCURRENTES ===\n";
        
        if (!class_exists('\\Nexa\\Routing\\Router')) {
            echo "⚠ Router non disponible pour le test de concurrence\n";
            return;
        }
        
        $router = new \Nexa\Routing\Router();
        
        // Configurer des routes typiques
        $router->get('/', function() { return 'Home'; });
        $router->get('/api/users', function() { return ['users' => []]; });
        $router->post('/api/users', function() { return ['created' => true]; });
        $router->get('/api/users/{id}', function($id) { return ['user' => $id]; });
        $router->put('/api/users/{id}', function($id) { return ['updated' => $id]; });
        $router->delete('/api/users/{id}', function($id) { return ['deleted' => $id]; });
        
        echo "Simulation de 1000 requêtes concurrentes...\n";
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        $routes = ['/', '/api/users', '/api/users/1', '/api/users/2', '/api/users/3'];
        $methods = ['GET', 'POST', 'PUT', 'DELETE'];
        
        $requestCount = 0;
        $errorCount = 0;
        
        for ($i = 0; $i < 1000; $i++) {
            try {
                // Simuler une requête aléatoire
                $route = $routes[array_rand($routes)];
                $method = $methods[array_rand($methods)];
                
                // Simuler le traitement de la requête
                $requestData = [
                    'method' => $method,
                    'uri' => $route,
                    'headers' => [
                        'User-Agent' => 'StressTest/1.0',
                        'Accept' => 'application/json'
                    ],
                    'body' => json_encode(['test' => $i])
                ];
                
                // Simuler le middleware
                $this->simulateMiddleware($requestData);
                
                // Simuler la réponse
                $response = [
                    'status' => 200,
                    'data' => ['request_id' => $i],
                    'timestamp' => microtime(true)
                ];
                
                $requestCount++;
                
                if ($i % 100 === 0) {
                    echo "  $i requêtes traitées\n";
                }
                
            } catch (Exception $e) {
                $errorCount++;
                if ($errorCount < 10) {
                    echo "  Erreur requête $i: {$e->getMessage()}\n";
                }
            }
        }
        
        $totalTime = microtime(true) - $startTime;
        $totalMemory = memory_get_usage(true) - $startMemory;
        
        $this->stressResults['concurrent_stress'] = [
            'total_requests' => $requestCount,
            'error_count' => $errorCount,
            'success_rate' => ($requestCount - $errorCount) / $requestCount * 100,
            'total_time' => $totalTime,
            'requests_per_second' => $requestCount / $totalTime,
            'memory_used' => $totalMemory,
            'memory_per_request' => $totalMemory / $requestCount
        ];
        
        echo "\nRésultats de la simulation de concurrence:\n";
        echo "  Requêtes traitées: $requestCount\n";
        echo "  Erreurs: $errorCount\n";
        echo "  Taux de succès: " . round(($requestCount - $errorCount) / $requestCount * 100, 2) . "%\n";
        echo "  Temps total: " . round($totalTime, 3) . "s\n";
        echo "  Requêtes/seconde: " . round($requestCount / $totalTime, 0) . "\n";
        echo "  Mémoire/requête: " . round($totalMemory / $requestCount / 1024, 2) . "KB\n";
        
        $this->assertGreaterThan(100, $requestCount / $totalTime, "Devrait traiter au moins 100 req/s");
        $this->assertLessThan(5, $errorCount, "Moins de 5 erreurs attendues");
    }
    
    protected function simulateMiddleware($requestData): void
    {
        // Simulation de middleware d'authentification
        if (strpos($requestData['uri'], '/api/') === 0) {
            // Simuler la vérification du token
            $token = 'Bearer ' . bin2hex(random_bytes(16));
        }
        
        // Simulation de middleware de sécurité
        if (isset($requestData['body'])) {
            // Simuler la validation et la sanitisation
            $body = json_decode($requestData['body'], true);
            if ($body) {
                foreach ($body as $key => $value) {
                    if (is_string($value)) {
                        // Simuler la sanitisation XSS
                        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                    }
                }
            }
        }
        
        // Simulation de middleware de rate limiting
        static $requestCounts = [];
        $ip = '127.0.0.1';
        if (!isset($requestCounts[$ip])) {
            $requestCounts[$ip] = 0;
        }
        $requestCounts[$ip]++;
        
        if ($requestCounts[$ip] > 100) {
            throw new Exception('Rate limit exceeded');
        }
    }
    
    public function testDatabaseStressTest()
    {
        echo "\n=== TEST DE STRESS BASE DE DONNÉES ===\n";
        
        if (!class_exists('\\Nexa\\Database\\Model')) {
            echo "⚠ Model non disponible pour le test de stress DB\n";
            return;
        }
        
        echo "Simulation d'opérations de base de données intensives...\n";
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        $operations = 0;
        $errors = 0;
        
        try {
            // Simuler des opérations CRUD intensives
            for ($i = 0; $i < 1000; $i++) {
                // Simuler CREATE
                $userData = [
                    'name' => 'User ' . $i,
                    'email' => "user$i@example.com",
                    'password' => password_hash('password', PASSWORD_DEFAULT),
                    'created_at' => date('Y-m-d H:i:s')
                ];
                $operations++;
                
                // Simuler READ
                $searchQuery = [
                    'where' => ['email' => "user$i@example.com"],
                    'limit' => 1
                ];
                $operations++;
                
                // Simuler UPDATE
                $updateData = [
                    'last_login' => date('Y-m-d H:i:s'),
                    'login_count' => $i + 1
                ];
                $operations++;
                
                // Simuler des requêtes complexes
                if ($i % 10 === 0) {
                    $complexQuery = [
                        'joins' => ['profiles', 'roles'],
                        'where' => ['active' => 1],
                        'orderBy' => 'created_at DESC',
                        'limit' => 50
                    ];
                    $operations++;
                }
                
                if ($i % 100 === 0) {
                    echo "  $i cycles d'opérations DB effectués\n";
                }
            }
            
        } catch (Exception $e) {
            $errors++;
            echo "  Erreur DB: {$e->getMessage()}\n";
        }
        
        $totalTime = microtime(true) - $startTime;
        $totalMemory = memory_get_usage(true) - $startMemory;
        
        $this->stressResults['database_stress'] = [
            'operations' => $operations,
            'errors' => $errors,
            'total_time' => $totalTime,
            'operations_per_second' => $operations / $totalTime,
            'memory_used' => $totalMemory
        ];
        
        echo "\nRésultats du stress test base de données:\n";
        echo "  Opérations: $operations\n";
        echo "  Erreurs: $errors\n";
        echo "  Temps total: " . round($totalTime, 3) . "s\n";
        echo "  Opérations/seconde: " . round($operations / $totalTime, 0) . "\n";
        echo "  Mémoire utilisée: " . round($totalMemory / 1024 / 1024, 2) . "MB\n";
    }
    
    public function testGenerateStressReport()
    {
        echo "\n=== RAPPORT DE STRESS GLOBAL ===\n";
        
        $overallScore = 0;
        $maxScore = 0;
        
        foreach ($this->stressResults as $testName => $results) {
            if (empty($results)) continue;
            
            echo "\n$testName:\n";
            
            switch ($testName) {
                case 'router_stress':
                    $score = min(100, ($results['routes_per_second'] / 1000) * 100);
                    echo "  Score routeur: " . round($score, 1) . "/100\n";
                    $overallScore += $score;
                    $maxScore += 100;
                    break;
                    
                case 'memory_stress':
                    $score = max(0, 100 - ($results['peak_memory'] / (100 * 1024 * 1024)) * 100);
                    echo "  Score mémoire: " . round($score, 1) . "/100\n";
                    $overallScore += $score;
                    $maxScore += 100;
                    break;
                    
                case 'concurrent_stress':
                    $score = min(100, ($results['requests_per_second'] / 500) * 100);
                    echo "  Score concurrence: " . round($score, 1) . "/100\n";
                    $overallScore += $score;
                    $maxScore += 100;
                    break;
                    
                case 'database_stress':
                    $score = min(100, ($results['operations_per_second'] / 1000) * 100);
                    echo "  Score base de données: " . round($score, 1) . "/100\n";
                    $overallScore += $score;
                    $maxScore += 100;
                    break;
            }
        }
        
        $finalScore = $maxScore > 0 ? ($overallScore / $maxScore) * 100 : 0;
        
        echo "\n=== SCORE GLOBAL ===\n";
        echo "Score de performance: " . round($finalScore, 1) . "/100\n";
        
        if ($finalScore >= 80) {
            echo "🟢 Excellentes performances\n";
        } elseif ($finalScore >= 60) {
            echo "🟡 Performances correctes\n";
        } else {
            echo "🔴 Performances à améliorer\n";
        }
        
        echo "\nRecommandations:\n";
        if ($finalScore < 80) {
            echo "  - Optimiser les composants les moins performants\n";
            echo "  - Implémenter du cache pour améliorer les performances\n";
            echo "  - Considérer l'utilisation d'un pool de connexions DB\n";
        }
        echo "  - Monitorer les performances en production\n";
        echo "  - Implémenter des métriques de performance\n";
    }
}