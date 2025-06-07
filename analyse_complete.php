<?php

/**
 * Analyse complète du framework Nexa
 * 
 * Ce script effectue une analyse approfondie de toutes les améliorations
 * et identifie les points d'amélioration supplémentaires.
 */

// Définir le chemin de base
define('BASE_PATH', __DIR__);

// Charger l'autoloader Composer
require_once BASE_PATH . '/vendor/autoload.php';

// Charger les variables d'environnement
if (file_exists(BASE_PATH . '/.env')) {
    $lines = file(BASE_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, '"\' ');
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Charger les helpers
require_once BASE_PATH . '/src/Nexa/Core/helpers.php';

class AnalyseComplete
{
    private $resultats = [];
    private $ameliorations = [];
    
    public function __construct()
    {
        echo "\n=== ANALYSE COMPLÈTE DU FRAMEWORK NEXA ===\n";
        echo "Analyse en cours...\n\n";
    }
    
    public function executerAnalyse()
    {
        $this->analyserStructure();
        $this->analyserConfiguration();
        $this->analyserControleurs();
        $this->analyserRoutes();
        $this->analyserTests();
        $this->analyserPerformances();
        $this->analyserSecurite();
        $this->analyserArchitecture();
        
        $this->afficherResultats();
        $this->proposerAmeliorations();
        
        return $this->resultats;
    }
    
    private function analyserStructure()
    {
        echo "1. Analyse de la structure du projet...\n";
        
        $dossiers = [
            'src/Nexa/Core' => 'Core du framework',
            'src/Nexa/Http' => 'Composants HTTP',
            'src/Nexa/Database' => 'Couche base de données',
            'src/Nexa/Auth' => 'Système d\'authentification',
            'src/Nexa/Cache' => 'Système de cache',
            'src/Nexa/Queue' => 'Système de files d\'attente',
            'src/Nexa/Testing' => 'Framework de tests',
            'src/Nexa/Console' => 'Interface en ligne de commande',
            'config' => 'Fichiers de configuration',
            'routes' => 'Définition des routes',
            'storage' => 'Stockage des fichiers',
            'tests' => 'Tests unitaires'
        ];
        
        $structure_ok = true;
        foreach ($dossiers as $dossier => $description) {
            if (is_dir(BASE_PATH . '/' . $dossier)) {
                echo "   ✓ $description ($dossier)\n";
            } else {
                echo "   ❌ $description manquant ($dossier)\n";
                $structure_ok = false;
            }
        }
        
        $this->resultats['structure'] = $structure_ok;
        echo "\n";
    }
    
    private function analyserConfiguration()
    {
        echo "2. Analyse de la configuration...\n";
        
        // Vérifier les fichiers de configuration
        $configs = [
            'app.php' => 'Configuration principale',
            'database.php' => 'Configuration base de données',
            'cache.php' => 'Configuration cache',
            'logging.php' => 'Configuration logs'
        ];
        
        $config_ok = true;
        foreach ($configs as $fichier => $description) {
            $chemin = BASE_PATH . '/config/' . $fichier;
            if (file_exists($chemin)) {
                echo "   ✓ $description\n";
            } else {
                echo "   ❌ $description manquant\n";
                $config_ok = false;
            }
        }
        
        // Vérifier les variables d'environnement
        $env_vars = ['APP_ENV', 'APP_DEBUG', 'APP_KEY'];
        foreach ($env_vars as $var) {
            if (isset($_ENV[$var])) {
                echo "   ✓ Variable $var définie\n";
            } else {
                echo "   ⚠️ Variable $var manquante\n";
            }
        }
        
        $this->resultats['configuration'] = $config_ok;
        echo "\n";
    }
    
    private function analyserControleurs()
    {
        echo "3. Analyse des contrôleurs...\n";
        
        $controleurs = [
            'app/Http/Controllers/WelcomeController.php',
            'app/Http/Controllers/ApiController.php',
            'app/Http/Controllers/TestController.php'
        ];
        
        $controleurs_ok = true;
        foreach ($controleurs as $controleur) {
            $chemin = BASE_PATH . '/' . $controleur;
            if (file_exists($chemin)) {
                $contenu = file_get_contents($chemin);
                if (strpos($contenu, 'extends Controller') !== false) {
                    echo "   ✓ " . basename($controleur) . " (héritage correct)\n";
                } else {
                    echo "   ⚠️ " . basename($controleur) . " (héritage manquant)\n";
                }
            } else {
                echo "   ❌ " . basename($controleur) . " manquant\n";
                $controleurs_ok = false;
            }
        }
        
        $this->resultats['controleurs'] = $controleurs_ok;
        echo "\n";
    }
    
    private function analyserRoutes()
    {
        echo "4. Analyse des routes...\n";
        
        $fichiers_routes = [
            'routes/web.php' => 'Routes web',
            'routes/api.php' => 'Routes API'
        ];
        
        $routes_ok = true;
        foreach ($fichiers_routes as $fichier => $description) {
            $chemin = BASE_PATH . '/' . $fichier;
            if (file_exists($chemin)) {
                $contenu = file_get_contents($chemin);
                $nb_routes = substr_count($contenu, '$router->');
                echo "   ✓ $description ($nb_routes routes définies)\n";
            } else {
                echo "   ❌ $description manquant\n";
                $routes_ok = false;
            }
        }
        
        $this->resultats['routes'] = $routes_ok;
        echo "\n";
    }
    
    private function analyserTests()
    {
        echo "5. Analyse des tests...\n";
        
        $dossier_tests = BASE_PATH . '/tests';
        if (is_dir($dossier_tests)) {
            $fichiers = glob($dossier_tests . '/*.php');
            echo "   ✓ " . count($fichiers) . " fichiers de test trouvés\n";
            
            foreach ($fichiers as $fichier) {
                $nom = basename($fichier);
                echo "   - $nom\n";
            }
        } else {
            echo "   ❌ Dossier tests manquant\n";
        }
        
        $this->resultats['tests'] = is_dir($dossier_tests);
        echo "\n";
    }
    
    private function analyserPerformances()
    {
        echo "6. Analyse des performances...\n";
        
        // Vérifier la présence de systèmes d'optimisation
        $optimisations = [
            'src/Nexa/Cache' => 'Système de cache',
            'src/Nexa/Queue' => 'Système de files d\'attente',
            'bootstrap/cache' => 'Cache de démarrage'
        ];
        
        foreach ($optimisations as $chemin => $description) {
            if (is_dir(BASE_PATH . '/' . $chemin)) {
                echo "   ✓ $description présent\n";
            } else {
                echo "   ⚠️ $description manquant\n";
            }
        }
        
        echo "\n";
    }
    
    private function analyserSecurite()
    {
        echo "7. Analyse de la sécurité...\n";
        
        // Vérifier les composants de sécurité
        $securite = [
            'src/Nexa/Auth' => 'Système d\'authentification',
            'src/Nexa/Middleware' => 'Middlewares de sécurité',
            'src/Nexa/Validation' => 'Validation des données'
        ];
        
        foreach ($securite as $chemin => $description) {
            if (is_dir(BASE_PATH . '/' . $chemin)) {
                echo "   ✓ $description présent\n";
            } else {
                echo "   ⚠️ $description manquant\n";
            }
        }
        
        // Vérifier le fichier .env
        if (file_exists(BASE_PATH . '/.env')) {
            echo "   ✓ Fichier .env présent\n";
        } else {
            echo "   ❌ Fichier .env manquant\n";
        }
        
        echo "\n";
    }
    
    private function analyserArchitecture()
    {
        echo "8. Analyse de l'architecture...\n";
        
        // Vérifier les patterns architecturaux
        $patterns = [
            'src/Nexa/Core/Application.php' => 'Pattern Application',
            'src/Nexa/Core/Container.php' => 'Injection de dépendances',
            'src/Nexa/Events' => 'Système d\'événements',
            'src/Nexa/Http/Request.php' => 'Abstraction des requêtes',
            'src/Nexa/Http/Response.php' => 'Abstraction des réponses'
        ];
        
        foreach ($patterns as $chemin => $description) {
            $chemin_complet = BASE_PATH . '/' . $chemin;
            if (file_exists($chemin_complet) || is_dir($chemin_complet)) {
                echo "   ✓ $description implémenté\n";
            } else {
                echo "   ⚠️ $description manquant\n";
            }
        }
        
        echo "\n";
    }
    
    private function afficherResultats()
    {
        echo "=== RÉSULTATS DE L'ANALYSE ===\n";
        
        $total = count($this->resultats);
        $reussis = array_sum($this->resultats);
        $pourcentage = round(($reussis / $total) * 100, 2);
        
        echo "Score global: $reussis/$total ($pourcentage%)\n\n";
        
        foreach ($this->resultats as $categorie => $resultat) {
            $status = $resultat ? '✓' : '❌';
            echo "$status " . ucfirst($categorie) . "\n";
        }
        
        echo "\n";
    }
    
    private function proposerAmeliorations()
    {
        echo "=== AMÉLIORATIONS PROPOSÉES ===\n";
        
        $ameliorations = [
            "🚀 Performance" => [
                "Implémenter un système de cache Redis/Memcached",
                "Ajouter la compression gzip automatique",
                "Optimiser l'autoloader avec un cache de classes",
                "Implémenter le lazy loading pour les services"
            ],
            "🔒 Sécurité" => [
                "Ajouter la protection CSRF automatique",
                "Implémenter la validation XSS",
                "Ajouter le rate limiting",
                "Chiffrement automatique des données sensibles"
            ],
            "🧪 Tests" => [
                "Augmenter la couverture de tests à 90%+",
                "Ajouter des tests d'intégration",
                "Implémenter les tests de performance",
                "Ajouter des tests de sécurité automatisés"
            ],
            "📚 Documentation" => [
                "Générer la documentation API automatiquement",
                "Ajouter des exemples d'utilisation",
                "Créer des tutoriels vidéo",
                "Documenter les meilleures pratiques"
            ],
            "🔧 DevOps" => [
                "Ajouter Docker Compose pour le développement",
                "Implémenter CI/CD avec GitHub Actions",
                "Ajouter le monitoring automatique",
                "Créer des scripts de déploiement"
            ],
            "🎯 Fonctionnalités" => [
                "Ajouter un ORM plus avancé",
                "Implémenter WebSockets en temps réel",
                "Ajouter le support GraphQL natif",
                "Créer un système de plugins dynamiques"
            ]
        ];
        
        foreach ($ameliorations as $categorie => $items) {
            echo "\n$categorie\n";
            echo str_repeat("-", strlen($categorie)) . "\n";
            foreach ($items as $item) {
                echo "• $item\n";
            }
        }
        
        echo "\n=== PRIORITÉS RECOMMANDÉES ===\n";
        echo "1. 🔒 Renforcer la sécurité (CSRF, XSS, Rate limiting)\n";
        echo "2. 🧪 Améliorer la couverture de tests\n";
        echo "3. 🚀 Optimiser les performances (Cache, Compression)\n";
        echo "4. 📚 Compléter la documentation\n";
        echo "5. 🔧 Automatiser le déploiement\n";
        
        echo "\n=== ANALYSE TERMINÉE ===\n";
    }
}

// Exécuter l'analyse
try {
    $analyse = new AnalyseComplete();
    $resultats = $analyse->executerAnalyse();
    
    // Sauvegarder les résultats
    $rapport = [
        'timestamp' => date('Y-m-d H:i:s'),
        'version_php' => PHP_VERSION,
        'resultats' => $resultats,
        'recommandations' => 'Voir le rapport détaillé ci-dessus'
    ];
    
    file_put_contents(
        BASE_PATH . '/storage/logs/analyse_' . date('Y-m-d_H-i-s') . '.json',
        json_encode($rapport, JSON_PRETTY_PRINT)
    );
    
    echo "\n📊 Rapport sauvegardé dans storage/logs/\n";
    
} catch (Exception $e) {
    echo "❌ Erreur lors de l'analyse: " . $e->getMessage() . "\n";
    exit(1);
}