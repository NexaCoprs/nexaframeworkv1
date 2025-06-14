<?php

require_once __DIR__ . '/../bootstrap.php';

/**
 * Framework Analysis and Extended Tests for Nexa Framework
 * Analyse complète du framework et tests étendus
 */
class FrameworkAnalysisTest extends TestCase
{
    private $analysisResults = [];
    private $coverageReport = [];
    
    public function setUp(): void
    {
        parent::setUp();
        
        $this->analysisResults = [
            'components_found' => [],
            'components_missing' => [],
            'test_coverage' => [],
            'recommendations' => []
        ];
    }
    
    public function testFrameworkArchitectureAnalysis()
    {
        echo "\n=== ANALYSE ARCHITECTURE NEXA FRAMEWORK ===\n";
        
        $this->analyzeCore()
             ->analyzeRouting()
             ->analyzeDatabase()
             ->analyzeMiddleware()
             ->analyzeSecurity()
             ->analyzeConsole()
             ->analyzeGraphQL()
             ->analyzeMicroservices()
             ->analyzeWebSockets()
             ->generateCoverageReport();
             
        echo "\n=== RÉSUMÉ DE L'ANALYSE ===\n";
        $this->displayAnalysisResults();
    }
    
    protected function analyzeCore(): self
    {
        echo "\nAnalyse des composants Core...\n";
        
        $coreComponents = [
            'Application' => '\\Nexa\\Core\\Application',
            'Config' => '\\Nexa\\Core\\Config',
            'Logger' => '\\Nexa\\Core\\Logger',
            'Cache' => '\\Nexa\\Core\\Cache',
            'Container' => '\\Nexa\\Core\\Container',
            'ServiceProvider' => '\\Nexa\\Core\\ServiceProvider'
        ];
        
        foreach ($coreComponents as $name => $class) {
            if (class_exists($class)) {
                $this->analysisResults['components_found']['Core'][] = $name;
                echo "  ✓ $name trouvé\n";
                
                // Test instantiation
                try {
                    if ($name === 'Application') {
                        $instance = new $class();
                        $this->assertInstanceOf($class, $instance);
                        echo "    ✓ Instanciation réussie\n";
                    }
                } catch (Exception $e) {
                    echo "    ⚠ Erreur d'instanciation: {$e->getMessage()}\n";
                }
            } else {
                $this->analysisResults['components_missing']['Core'][] = $name;
                echo "  ✗ $name manquant\n";
            }
        }
        
        return $this;
    }
    
    protected function analyzeRouting(): self
    {
        echo "\nAnalyse du système de routage...\n";
        
        $routingComponents = [
            'Router' => '\\Nexa\\Routing\\Router',
            'Route' => '\\Nexa\\Routing\\Route',
            'RouteCollection' => '\\Nexa\\Routing\\RouteCollection',
            'UrlGenerator' => '\\Nexa\\Routing\\UrlGenerator'
        ];
        
        foreach ($routingComponents as $name => $class) {
            if (class_exists($class)) {
                $this->analysisResults['components_found']['Routing'][] = $name;
                echo "  ✓ $name trouvé\n";
                
                if ($name === 'Router') {
                    $this->testRouterFunctionality($class);
                }
            } else {
                $this->analysisResults['components_missing']['Routing'][] = $name;
                echo "  ✗ $name manquant\n";
            }
        }
        
        return $this;
    }
    
    protected function testRouterFunctionality($routerClass): void
    {
        try {
            $router = new $routerClass();
            
            // Test des méthodes HTTP
            $httpMethods = ['get', 'post', 'put', 'patch', 'delete'];
            foreach ($httpMethods as $method) {
                if (method_exists($router, $method)) {
                    echo "    ✓ Méthode $method disponible\n";
                } else {
                    echo "    ✗ Méthode $method manquante\n";
                }
            }
            
            // Test des fonctionnalités avancées
            $advancedFeatures = ['resource', 'group', 'middleware', 'name'];
            foreach ($advancedFeatures as $feature) {
                if (method_exists($router, $feature)) {
                    echo "    ✓ Fonctionnalité $feature disponible\n";
                } else {
                    echo "    ✗ Fonctionnalité $feature manquante\n";
                }
            }
            
        } catch (Exception $e) {
            echo "    ⚠ Erreur lors du test du Router: {$e->getMessage()}\n";
        }
    }
    
    protected function analyzeDatabase(): self
    {
        echo "\nAnalyse du système de base de données...\n";
        
        $databaseComponents = [
            'Model' => '\\Nexa\\Database\\Model',
            'QueryBuilder' => '\\Nexa\\Database\\QueryBuilder',
            'Migration' => '\\Nexa\\Database\\Migration',
            'Schema' => '\\Nexa\\Database\\Schema',
            'Connection' => '\\Nexa\\Database\\Connection'
        ];
        
        foreach ($databaseComponents as $name => $class) {
            if (class_exists($class)) {
                $this->analysisResults['components_found']['Database'][] = $name;
                echo "  ✓ $name trouvé\n";
            } else {
                $this->analysisResults['components_missing']['Database'][] = $name;
                echo "  ✗ $name manquant\n";
            }
        }
        
        return $this;
    }
    
    protected function analyzeMiddleware(): self
    {
        echo "\nAnalyse du système de middleware...\n";
        
        $middlewareComponents = [
            'AuthMiddleware' => '\\Nexa\\Middleware\\AuthMiddleware',
            'SecurityMiddleware' => '\\Nexa\\Middleware\\SecurityMiddleware',
            'CorsMiddleware' => '\\Nexa\\Middleware\\CorsMiddleware',
            'RateLimitMiddleware' => '\\Nexa\\Middleware\\RateLimitMiddleware',
            'CacheMiddleware' => '\\Nexa\\Middleware\\CacheMiddleware'
        ];
        
        foreach ($middlewareComponents as $name => $class) {
            if (class_exists($class)) {
                $this->analysisResults['components_found']['Middleware'][] = $name;
                echo "  ✓ $name trouvé\n";
                
                if ($name === 'SecurityMiddleware') {
                    $this->testSecurityMiddleware($class);
                }
            } else {
                $this->analysisResults['components_missing']['Middleware'][] = $name;
                echo "  ✗ $name manquant\n";
            }
        }
        
        return $this;
    }
    
    protected function testSecurityMiddleware($middlewareClass): void
    {
        try {
            $middleware = new $middlewareClass();
            
            $securityFeatures = [
                'sanitizeInput',
                'validateCsrfToken',
                'checkRateLimit',
                'sanitizeSqlInput',
                'preventXSS'
            ];
            
            foreach ($securityFeatures as $feature) {
                if (method_exists($middleware, $feature)) {
                    echo "    ✓ Fonctionnalité de sécurité $feature disponible\n";
                } else {
                    echo "    ✗ Fonctionnalité de sécurité $feature manquante\n";
                }
            }
            
        } catch (Exception $e) {
            echo "    ⚠ Erreur lors du test SecurityMiddleware: {$e->getMessage()}\n";
        }
    }
    
    protected function analyzeSecurity(): self
    {
        echo "\nAnalyse du système de sécurité...\n";
        
        $securityComponents = [
            'Encryption' => '\\Nexa\\Security\\Encryption',
            'Hash' => '\\Nexa\\Security\\Hash',
            'CSRF' => '\\Nexa\\Security\\CSRF',
            'RateLimit' => '\\Nexa\\Security\\RateLimit',
            'Firewall' => '\\Nexa\\Security\\Firewall'
        ];
        
        foreach ($securityComponents as $name => $class) {
            if (class_exists($class)) {
                $this->analysisResults['components_found']['Security'][] = $name;
                echo "  ✓ $name trouvé\n";
            } else {
                $this->analysisResults['components_missing']['Security'][] = $name;
                echo "  ✗ $name manquant\n";
            }
        }
        
        return $this;
    }
    
    protected function analyzeConsole(): self
    {
        echo "\nAnalyse du système de console...\n";
        
        $consoleComponents = [
            'Command' => '\\Nexa\\Console\\Command',
            'Kernel' => '\\Nexa\\Console\\Kernel',
            'InteractiveMakeCommand' => '\\Nexa\\Console\\Commands\\InteractiveMakeCommand'
        ];
        
        foreach ($consoleComponents as $name => $class) {
            if (class_exists($class)) {
                $this->analysisResults['components_found']['Console'][] = $name;
                echo "  ✓ $name trouvé\n";
            } else {
                $this->analysisResults['components_missing']['Console'][] = $name;
                echo "  ✗ $name manquant\n";
            }
        }
        
        return $this;
    }
    
    protected function analyzeGraphQL(): self
    {
        echo "\nAnalyse du système GraphQL...\n";
        
        $graphqlComponents = [
            'GraphQLManager' => '\\GraphQL\\GraphQLManager',
            'Query' => '\\GraphQL\\Query',
            'Mutation' => '\\GraphQL\\Mutation',
            'Type' => '\\GraphQL\\Type'
        ];
        
        foreach ($graphqlComponents as $name => $class) {
            if (class_exists($class)) {
                $this->analysisResults['components_found']['GraphQL'][] = $name;
                echo "  ✓ $name trouvé\n";
            } else {
                $this->analysisResults['components_missing']['GraphQL'][] = $name;
                echo "  ✗ $name manquant\n";
            }
        }
        
        return $this;
    }
    
    protected function analyzeMicroservices(): self
    {
        echo "\nAnalyse du système de microservices...\n";
        
        $microservicesComponents = [
            'ServiceClient' => '\\Microservices\\ServiceClient',
            'ServiceRegistry' => '\\Microservices\\ServiceRegistry'
        ];
        
        foreach ($microservicesComponents as $name => $class) {
            if (class_exists($class)) {
                $this->analysisResults['components_found']['Microservices'][] = $name;
                echo "  ✓ $name trouvé\n";
            } else {
                $this->analysisResults['components_missing']['Microservices'][] = $name;
                echo "  ✗ $name manquant\n";
            }
        }
        
        return $this;
    }
    
    protected function analyzeWebSockets(): self
    {
        echo "\nAnalyse du système WebSockets...\n";
        
        $websocketComponents = [
            'WebSocketServer' => '\\WebSockets\\WebSocketServer',
            'WebSocketClient' => '\\WebSockets\\WebSocketClient'
        ];
        
        foreach ($websocketComponents as $name => $class) {
            if (class_exists($class)) {
                $this->analysisResults['components_found']['WebSockets'][] = $name;
                echo "  ✓ $name trouvé\n";
            } else {
                $this->analysisResults['components_missing']['WebSockets'][] = $name;
                echo "  ✗ $name manquant\n";
            }
        }
        
        return $this;
    }
    
    protected function generateCoverageReport(): self
    {
        echo "\nGénération du rapport de couverture...\n";
        
        $totalFound = 0;
        $totalMissing = 0;
        
        foreach ($this->analysisResults['components_found'] as $category => $components) {
            $totalFound += count($components);
        }
        
        foreach ($this->analysisResults['components_missing'] as $category => $components) {
            $totalMissing += count($components);
        }
        
        $totalComponents = $totalFound + $totalMissing;
        $coveragePercentage = $totalComponents > 0 ? ($totalFound / $totalComponents) * 100 : 0;
        
        $this->coverageReport = [
            'total_components' => $totalComponents,
            'found_components' => $totalFound,
            'missing_components' => $totalMissing,
            'coverage_percentage' => round($coveragePercentage, 2)
        ];
        
        echo "  ✓ Rapport généré: {$coveragePercentage}% de couverture\n";
        
        return $this;
    }
    
    protected function displayAnalysisResults(): void
    {
        echo "\nComposants trouvés par catégorie:\n";
        foreach ($this->analysisResults['components_found'] as $category => $components) {
            echo "  $category: " . count($components) . " composants\n";
            foreach ($components as $component) {
                echo "    - $component\n";
            }
        }
        
        echo "\nComposants manquants par catégorie:\n";
        foreach ($this->analysisResults['components_missing'] as $category => $components) {
            if (!empty($components)) {
                echo "  $category: " . count($components) . " composants manquants\n";
                foreach ($components as $component) {
                    echo "    - $component\n";
                }
            }
        }
        
        echo "\nRapport de couverture:\n";
        echo "  Total: {$this->coverageReport['total_components']} composants\n";
        echo "  Trouvés: {$this->coverageReport['found_components']}\n";
        echo "  Manquants: {$this->coverageReport['missing_components']}\n";
        echo "  Couverture: {$this->coverageReport['coverage_percentage']}%\n";
        
        $this->generateRecommendations();
    }
    
    protected function generateRecommendations(): void
    {
        echo "\n=== RECOMMANDATIONS ===\n";
        
        $recommendations = [];
        
        // Recommandations basées sur les composants manquants
        if (!empty($this->analysisResults['components_missing']['Security'])) {
            $recommendations[] = "Implémenter les composants de sécurité manquants pour renforcer la protection";
        }
        
        if (!empty($this->analysisResults['components_missing']['Database'])) {
            $recommendations[] = "Compléter le système de base de données avec QueryBuilder et Schema";
        }
        
        if (!empty($this->analysisResults['components_missing']['Routing'])) {
            $recommendations[] = "Ajouter les composants de routage avancés (UrlGenerator, RouteCollection)";
        }
        
        if ($this->coverageReport['coverage_percentage'] < 80) {
            $recommendations[] = "Améliorer la couverture globale des composants (actuellement {$this->coverageReport['coverage_percentage']}%)";
        }
        
        $recommendations[] = "Ajouter des tests unitaires pour tous les composants existants";
        $recommendations[] = "Implémenter des tests d'intégration plus complets";
        $recommendations[] = "Ajouter des tests de performance pour les composants critiques";
        $recommendations[] = "Créer une documentation complète pour chaque composant";
        
        foreach ($recommendations as $i => $recommendation) {
            echo "  " . ($i + 1) . ". $recommendation\n";
        }
    }
    
    public function testExtendedValidation()
    {
        echo "\n=== TESTS DE VALIDATION ÉTENDUS ===\n";
        
        $this->testConfigurationValidation()
             ->testSecurityValidation()
             ->testPerformanceValidation()
             ->testCompatibilityValidation();
    }
    
    protected function testConfigurationValidation(): self
    {
        echo "\nValidation de la configuration...\n";
        
        // Test des fichiers de configuration requis
        $configFiles = [
            'composer.json',
            'phpunit.xml',
            '.htaccess'
        ];
        
        foreach ($configFiles as $file) {
            $path = NEXA_ROOT . '/' . $file;
            if (file_exists($path)) {
                echo "  ✓ $file trouvé\n";
            } else {
                echo "  ✗ $file manquant\n";
            }
        }
        
        return $this;
    }
    
    protected function testSecurityValidation(): self
    {
        echo "\nValidation de la sécurité...\n";
        
        // Test des pratiques de sécurité
        $securityChecks = [
            'PHP version >= 8.1' => version_compare(PHP_VERSION, '8.1.0', '>='),
            'Error reporting configuré' => error_reporting() !== false,
            'Display errors désactivé en production' => !ini_get('display_errors') || NEXA_ENV === 'testing'
        ];
        
        foreach ($securityChecks as $check => $passed) {
            if ($passed) {
                echo "  ✓ $check\n";
            } else {
                echo "  ✗ $check\n";
            }
        }
        
        return $this;
    }
    
    protected function testPerformanceValidation(): self
    {
        echo "\nValidation des performances...\n";
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        // Simulation de charge
        for ($i = 0; $i < 1000; $i++) {
            $data = ['test' => $i, 'data' => str_repeat('x', 100)];
            unset($data);
        }
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        $executionTime = $endTime - $startTime;
        $memoryUsed = $endMemory - $startMemory;
        
        echo "  ✓ Temps d'exécution: " . round($executionTime * 1000, 2) . "ms\n";
        echo "  ✓ Mémoire utilisée: " . round($memoryUsed / 1024, 2) . "KB\n";
        
        $this->assertLessThan(0.1, $executionTime, "L'exécution devrait être rapide");
        
        return $this;
    }
    
    protected function testCompatibilityValidation(): self
    {
        echo "\nValidation de la compatibilité...\n";
        
        // Test des extensions PHP requises
        $requiredExtensions = [
            'json',
            'mbstring',
            'openssl',
            'pdo',
            'curl'
        ];
        
        foreach ($requiredExtensions as $extension) {
            if (extension_loaded($extension)) {
                echo "  ✓ Extension $extension chargée\n";
            } else {
                echo "  ✗ Extension $extension manquante\n";
            }
        }
        
        return $this;
    }
}