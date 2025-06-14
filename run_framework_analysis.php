<?php
/**
 * Script d'analyse complète du Framework Nexa
 * Tests réels pour la mise en production
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/tests/bootstrap.php';

class NexaFrameworkAnalyzer
{
    private array $results = [];
    private array $errors = [];
    private array $warnings = [];
    private float $startTime;
    
    public function __construct()
    {
        $this->startTime = microtime(true);
        echo "\n=== ANALYSE COMPLÈTE DU FRAMEWORK NEXA ===\n";
        echo "Démarrage de l'analyse à " . date('Y-m-d H:i:s') . "\n\n";
    }
    
    public function runCompleteAnalysis(): void
    {
        $this->analyzeProjectStructure();
        $this->analyzeComposerDependencies();
        $this->analyzeCoreComponents();
        $this->analyzeRouting();
        $this->analyzeMiddleware();
        $this->analyzeSecurity();
        $this->analyzeDatabase();
        $this->analyzePerformance();
        $this->analyzeTestCoverage();
        $this->generateProductionReadinessReport();
    }
    
    private function analyzeProjectStructure(): void
    {
        echo "📁 Analyse de la structure du projet...\n";
        
        $requiredDirs = [
            'kernel/Nexa',
            'tests',
            'public',
            'storage',
            'workspace'
        ];
        
        $missingDirs = [];
        foreach ($requiredDirs as $dir) {
            if (!is_dir(__DIR__ . '/' . $dir)) {
                $missingDirs[] = $dir;
            }
        }
        
        if (empty($missingDirs)) {
            $this->results['structure'] = '✅ Structure du projet complète';
        } else {
            $this->errors['structure'] = '❌ Répertoires manquants: ' . implode(', ', $missingDirs);
        }
        
        // Vérification des fichiers critiques
        $criticalFiles = [
            'composer.json',
            'index.php',
            'kernel/Nexa/Core/Application.php',
            'kernel/Nexa/Routing/Router.php'
        ];
        
        $missingFiles = [];
        foreach ($criticalFiles as $file) {
            if (!file_exists(__DIR__ . '/' . $file)) {
                $missingFiles[] = $file;
            }
        }
        
        if (!empty($missingFiles)) {
            $this->errors['critical_files'] = '❌ Fichiers critiques manquants: ' . implode(', ', $missingFiles);
        }
    }
    
    private function analyzeComposerDependencies(): void
    {
        echo "📦 Analyse des dépendances Composer...\n";
        
        if (!file_exists(__DIR__ . '/composer.json')) {
            $this->errors['composer'] = '❌ composer.json manquant';
            return;
        }
        
        $composer = json_decode(file_get_contents(__DIR__ . '/composer.json'), true);
        
        // Vérification des dépendances critiques
        $criticalDeps = [
            'monolog/monolog',
            'vlucas/phpdotenv',
            'illuminate/database',
            'symfony/console'
        ];
        
        $missingDeps = [];
        foreach ($criticalDeps as $dep) {
            if (!isset($composer['require'][$dep])) {
                $missingDeps[] = $dep;
            }
        }
        
        if (empty($missingDeps)) {
            $this->results['dependencies'] = '✅ Toutes les dépendances critiques présentes';
        } else {
            $this->warnings['dependencies'] = '⚠️ Dépendances manquantes: ' . implode(', ', $missingDeps);
        }
        
        // Vérification de la version PHP
        $phpVersion = $composer['require']['php'] ?? 'non spécifiée';
        $this->results['php_version'] = "Version PHP requise: {$phpVersion}";
    }
    
    private function analyzeCoreComponents(): void
    {
        echo "🔧 Analyse des composants core...\n";
        
        $coreComponents = [
            'Application' => 'kernel/Nexa/Core/Application.php',
            'Router' => 'kernel/Nexa/Routing/Router.php',
            'Config' => 'kernel/Nexa/Core/Config.php',
            'Logger' => 'kernel/Nexa/Core/Logger.php'
        ];
        
        foreach ($coreComponents as $name => $path) {
            if (file_exists(__DIR__ . '/' . $path)) {
                $this->results['core_' . strtolower($name)] = "✅ {$name} disponible";
                
                // Analyse basique du code
                $content = file_get_contents(__DIR__ . '/' . $path);
                $lines = substr_count($content, "\n");
                $this->results['core_' . strtolower($name) . '_size'] = "{$name}: {$lines} lignes";
            } else {
                $this->errors['core_' . strtolower($name)] = "❌ {$name} manquant: {$path}";
            }
        }
    }
    
    private function analyzeRouting(): void
    {
        echo "🛣️ Analyse du système de routage...\n";
        
        try {
            if (class_exists('\\Nexa\\Routing\\Router')) {
                $router = new \Nexa\Routing\Router();
                
                // Test des méthodes HTTP de base
                $httpMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
                $availableMethods = [];
                
                foreach ($httpMethods as $method) {
                    if (method_exists($router, strtolower($method))) {
                        $availableMethods[] = $method;
                    }
                }
                
                $this->results['routing_methods'] = '✅ Méthodes HTTP: ' . implode(', ', $availableMethods);
                
                // Test de performance du routage
                $startTime = microtime(true);
                for ($i = 0; $i < 1000; $i++) {
                    $router->get('/test-route-' . $i, function() { return 'test'; });
                }
                $endTime = microtime(true);
                
                $routingTime = ($endTime - $startTime) * 1000;
                $this->results['routing_performance'] = sprintf('⚡ Performance routage: %.2f ms pour 1000 routes', $routingTime);
                
            } else {
                $this->errors['routing'] = '❌ Classe Router non trouvée';
            }
        } catch (Exception $e) {
            $this->errors['routing'] = '❌ Erreur routage: ' . $e->getMessage();
        }
    }
    
    private function analyzeMiddleware(): void
    {
        echo "🔒 Analyse du système de middleware...\n";
        
        $middlewareDir = __DIR__ . '/kernel/Nexa/Middleware';
        if (!is_dir($middlewareDir)) {
            $this->errors['middleware'] = '❌ Répertoire middleware manquant';
            return;
        }
        
        $middlewareFiles = glob($middlewareDir . '/*.php');
        $middlewareCount = count($middlewareFiles);
        
        $this->results['middleware_count'] = "✅ {$middlewareCount} middleware(s) trouvé(s)";
        
        // Vérification des middleware critiques
        $criticalMiddleware = [
            'AuthMiddleware.php',
            'CorsMiddleware.php',
            'RateLimitMiddleware.php'
        ];
        
        $foundMiddleware = [];
        foreach ($criticalMiddleware as $middleware) {
            if (file_exists($middlewareDir . '/' . $middleware)) {
                $foundMiddleware[] = str_replace('.php', '', $middleware);
            }
        }
        
        if (!empty($foundMiddleware)) {
            $this->results['critical_middleware'] = '✅ Middleware critiques: ' . implode(', ', $foundMiddleware);
        }
    }
    
    private function analyzeSecurity(): void
    {
        echo "🛡️ Analyse de sécurité...\n";
        
        $securityDir = __DIR__ . '/kernel/Nexa/Security';
        if (!is_dir($securityDir)) {
            $this->errors['security'] = '❌ Répertoire sécurité manquant';
            return;
        }
        
        $securityFeatures = [
            'CSRF.php' => 'Protection CSRF',
            'XSS.php' => 'Protection XSS',
            'RateLimit.php' => 'Limitation de taux',
            'Encryption.php' => 'Chiffrement'
        ];
        
        $availableFeatures = [];
        foreach ($securityFeatures as $file => $feature) {
            if (file_exists($securityDir . '/' . $file)) {
                $availableFeatures[] = $feature;
            }
        }
        
        if (!empty($availableFeatures)) {
            $this->results['security_features'] = '✅ Fonctionnalités sécurité: ' . implode(', ', $availableFeatures);
        }
        
        // Vérification des configurations de sécurité
        $htaccessExists = file_exists(__DIR__ . '/.htaccess');
        $publicHtaccessExists = file_exists(__DIR__ . '/public/.htaccess');
        
        if ($htaccessExists && $publicHtaccessExists) {
            $this->results['security_config'] = '✅ Fichiers .htaccess configurés';
        } else {
            $this->warnings['security_config'] = '⚠️ Configuration .htaccess incomplète';
        }
    }
    
    private function analyzeDatabase(): void
    {
        echo "🗄️ Analyse du système de base de données...\n";
        
        $dbDir = __DIR__ . '/kernel/Nexa/Database';
        if (!is_dir($dbDir)) {
            $this->errors['database'] = '❌ Répertoire database manquant';
            return;
        }
        
        $dbComponents = [
            'Connection.php' => 'Connexion DB',
            'QueryBuilder.php' => 'Query Builder',
            'Migration.php' => 'Migrations',
            'Model.php' => 'Modèle ORM'
        ];
        
        $availableComponents = [];
        foreach ($dbComponents as $file => $component) {
            if (file_exists($dbDir . '/' . $file)) {
                $availableComponents[] = $component;
            }
        }
        
        if (!empty($availableComponents)) {
            $this->results['database_components'] = '✅ Composants DB: ' . implode(', ', $availableComponents);
        }
    }
    
    private function analyzePerformance(): void
    {
        echo "⚡ Analyse de performance...\n";
        
        // Test de mémoire
        $memoryStart = memory_get_usage();
        $memoryPeakStart = memory_get_peak_usage();
        
        // Simulation de charge
        $data = [];
        for ($i = 0; $i < 10000; $i++) {
            $data[] = 'test_data_' . $i;
        }
        
        $memoryEnd = memory_get_usage();
        $memoryPeakEnd = memory_get_peak_usage();
        
        $memoryUsed = ($memoryEnd - $memoryStart) / 1024 / 1024;
        $memoryPeak = ($memoryPeakEnd - $memoryPeakStart) / 1024 / 1024;
        
        $this->results['memory_usage'] = sprintf('📊 Utilisation mémoire: %.2f MB (pic: %.2f MB)', $memoryUsed, $memoryPeak);
        
        // Test de temps d'exécution
        $executionTime = (microtime(true) - $this->startTime) * 1000;
        $this->results['execution_time'] = sprintf('⏱️ Temps d\'exécution: %.2f ms', $executionTime);
    }
    
    private function analyzeTestCoverage(): void
    {
        echo "🧪 Analyse de la couverture de tests...\n";
        
        $testDir = __DIR__ . '/tests';
        if (!is_dir($testDir)) {
            $this->errors['tests'] = '❌ Répertoire tests manquant';
            return;
        }
        
        $testFiles = [];
        $testTypes = ['Unit', 'Feature', 'Integration', 'Performance'];
        
        foreach ($testTypes as $type) {
            $typeDir = $testDir . '/' . $type;
            if (is_dir($typeDir)) {
                $files = glob($typeDir . '/*Test.php');
                $testFiles[$type] = count($files);
            }
        }
        
        $totalTests = array_sum($testFiles);
        $this->results['test_coverage'] = "✅ {$totalTests} fichiers de test trouvés";
        
        foreach ($testFiles as $type => $count) {
            if ($count > 0) {
                $this->results['test_' . strtolower($type)] = "📝 {$type}: {$count} test(s)";
            }
        }
    }
    
    private function generateProductionReadinessReport(): void
    {
        echo "\n📋 RAPPORT DE PRÉPARATION PRODUCTION\n";
        echo str_repeat('=', 50) . "\n";
        
        // Calcul du score de préparation
        $totalChecks = count($this->results) + count($this->errors) + count($this->warnings);
        $successfulChecks = count($this->results);
        $readinessScore = $totalChecks > 0 ? ($successfulChecks / $totalChecks) * 100 : 0;
        
        echo "\n🎯 SCORE DE PRÉPARATION: " . round($readinessScore, 1) . "%\n\n";
        
        // Affichage des résultats
        if (!empty($this->results)) {
            echo "✅ POINTS POSITIFS:\n";
            foreach ($this->results as $key => $result) {
                echo "   {$result}\n";
            }
            echo "\n";
        }
        
        if (!empty($this->warnings)) {
            echo "⚠️ AVERTISSEMENTS:\n";
            foreach ($this->warnings as $key => $warning) {
                echo "   {$warning}\n";
            }
            echo "\n";
        }
        
        if (!empty($this->errors)) {
            echo "❌ PROBLÈMES CRITIQUES:\n";
            foreach ($this->errors as $key => $error) {
                echo "   {$error}\n";
            }
            echo "\n";
        }
        
        // Recommandations
        echo "💡 RECOMMANDATIONS POUR LA PRODUCTION:\n";
        
        if ($readinessScore >= 80) {
            echo "   🟢 Le framework est prêt pour la production\n";
            echo "   🔧 Effectuez des tests de charge supplémentaires\n";
            echo "   📊 Configurez la surveillance en production\n";
        } elseif ($readinessScore >= 60) {
            echo "   🟡 Le framework nécessite des améliorations mineures\n";
            echo "   🔍 Corrigez les avertissements avant la mise en production\n";
            echo "   🧪 Augmentez la couverture de tests\n";
        } else {
            echo "   🔴 Le framework n'est PAS prêt pour la production\n";
            echo "   🚨 Corrigez TOUS les problèmes critiques\n";
            echo "   🔄 Relancez l'analyse après corrections\n";
        }
        
        echo "\n📈 MÉTRIQUES FINALES:\n";
        echo "   📊 Checks réussis: {$successfulChecks}/{$totalChecks}\n";
        echo "   ⚠️ Avertissements: " . count($this->warnings) . "\n";
        echo "   ❌ Erreurs: " . count($this->errors) . "\n";
        
        $totalTime = (microtime(true) - $this->startTime) * 1000;
        echo "   ⏱️ Temps total d'analyse: " . round($totalTime, 2) . " ms\n";
        
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "Analyse terminée à " . date('Y-m-d H:i:s') . "\n";
    }
}

// Exécution de l'analyse
try {
    $analyzer = new NexaFrameworkAnalyzer();
    $analyzer->runCompleteAnalysis();
} catch (Exception $e) {
    echo "\n❌ ERREUR FATALE: " . $e->getMessage() . "\n";
    echo "📍 Fichier: " . $e->getFile() . " (ligne " . $e->getLine() . ")\n";
    exit(1);
}