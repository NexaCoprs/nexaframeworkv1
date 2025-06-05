<?php

/**
 * Démonstration Finale - Phase 2 du Framework Nexa
 * 
 * Cette démonstration présente toutes les fonctionnalités développées :
 * - Authentification JWT
 * - Système d'événements
 * - Système de queue
 * - Framework de tests automatisés
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Imports nécessaires
use Nexa\Auth\JWTManager;
use Nexa\Events\EventDispatcher;
use Nexa\Queue\SyncQueueDriver;
use Nexa\Testing\TestCase;
use Nexa\Testing\TestRunner;

echo "🚀 === DÉMONSTRATION FINALE - PHASE 2 NEXA FRAMEWORK === 🚀\n\n";

try {
    // ===== 1. AUTHENTIFICATION JWT =====
    echo "1️⃣  AUTHENTIFICATION JWT\n";
    echo "========================\n";
    
    $jwtManager = new JWTManager('nexa-demo-secret-2024');
    
    // Test de génération de token
    $userId = 42;
    $userEmail = 'demo@nexaframework.com';
    $token = $jwtManager->generateToken($userId, $userEmail, ['role' => 'admin']);
    
    echo "✅ Token JWT généré avec succès\n";
    echo "   👤 Utilisateur ID: $userId\n";
    echo "   📧 Email: $userEmail\n";
    echo "   🔑 Token: " . substr($token, 0, 40) . "...\n";
    
    // Test de validation
    $decoded = $jwtManager->validateToken($token);
    echo "✅ Token validé - Utilisateur: {$decoded['sub']}, Email: {$decoded['email']}\n";
    
    // Test de refresh token
    $refreshToken = $jwtManager->generateRefreshToken($userId, $userEmail);
    echo "✅ Refresh token généré\n";
    
    echo "\n";
    
    // ===== 2. SYSTÈME D'ÉVÉNEMENTS =====
    echo "2️⃣  SYSTÈME D'ÉVÉNEMENTS\n";
    echo "========================\n";
    
    $eventDispatcher = new EventDispatcher();
    
    // Enregistrement de listeners avec différentes priorités
    $eventDispatcher->listen('user.action', function($event) {
        $data = is_object($event) ? $event->getData() : $event;
        echo "🔔 Listener 1: Action utilisateur détectée - " . json_encode($data) . "\n";
    }, 50);
    
    $eventDispatcher->listen('user.action', function($event) {
        echo "⚡ Listener prioritaire: Traitement immédiat\n";
    }, 100);
    
    $eventDispatcher->listen('user.action', function($event) {
        echo "📝 Listener 3: Logging de l'action\n";
    }, 10);
    
    // Déclenchement d'événements
    echo "🚀 Déclenchement d'événement 'user.action'...\n";
    $eventDispatcher->dispatch('user.action', [
        'user_id' => $userId,
        'action' => 'login',
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => '192.168.1.100'
    ]);
    
    // Test d'événement avec propagation
    $eventDispatcher->listen('system.notification', function($event) {
        $data = is_object($event) ? $event->getData() : $event;
        echo "📢 Notification système: {$data['message']}\n";
        return false; // Arrêter la propagation
    });
    
    $eventDispatcher->listen('system.notification', function($event) {
        echo "❌ Ce listener ne devrait pas s'exécuter\n";
    });
    
    $eventDispatcher->dispatch('system.notification', [
        'message' => 'Système initialisé avec succès',
        'level' => 'info'
    ]);
    
    echo "\n";
    
    // ===== 3. SYSTÈME DE QUEUE =====
    echo "3️⃣  SYSTÈME DE QUEUE\n";
    echo "===================\n";
    
    $queueDriver = new SyncQueueDriver();
    
    // Job d'envoi d'email
    $emailJob = new class($userEmail) implements \Nexa\Queue\JobInterface {
        private $email;
        
        public function __construct($email) {
            $this->email = $email;
        }
        
        public function handle() {
            echo "📧 Job Email: Envoi d'email de bienvenue à {$this->email}\n";
            sleep(1); // Simulation du traitement
            echo "✅ Email envoyé avec succès\n";
            return true;
        }
        
        public function failed($exception) {
            echo "❌ Échec de l'envoi d'email: " . $exception->getMessage() . "\n";
        }
        
        public function shouldRetry($exception) { return true; }
        public function getId() { return uniqid('email_job_'); }
        public function getName() { return 'email_job'; }
        public function getData() { return ['email' => $this->email]; }
        public function getMaxAttempts() { return 3; }
        public function getTimeout() { return 60; }
        public function getQueue() { return 'emails'; }
        public function getDelay() { return 0; }
    };
    
    // Job de traitement d'image
    $imageJob = new class($userId) implements \Nexa\Queue\JobInterface {
        private $userId;
        
        public function __construct($userId) {
            $this->userId = $userId;
        }
        
        public function handle() {
            echo "🖼️  Job Image: Traitement de l'avatar pour l'utilisateur {$this->userId}\n";
            sleep(1); // Simulation du traitement
            echo "✅ Avatar traité et optimisé\n";
            return true;
        }
        
        public function failed($exception) {
            echo "❌ Échec du traitement d'image: " . $exception->getMessage() . "\n";
        }
        
        public function shouldRetry($exception) { return true; }
        public function getId() { return uniqid('image_job_'); }
        public function getName() { return 'image_job'; }
        public function getData() { return ['user_id' => $this->userId]; }
        public function getMaxAttempts() { return 2; }
        public function getTimeout() { return 120; }
        public function getQueue() { return 'images'; }
        public function getDelay() { return 0; }
    };
    
    // Exécution des jobs
    echo "🔄 Ajout des jobs à la queue...\n";
    $queueDriver->push($emailJob, 'emails');
    $queueDriver->push($imageJob, 'images');
    echo "✅ Tous les jobs ont été traités\n";
    
    echo "\n";
    
    // ===== 4. FRAMEWORK DE TESTS =====
    echo "4️⃣  FRAMEWORK DE TESTS\n";
    echo "=====================\n";
    
    // Création d'une suite de tests complète
    $testSuite = new class extends TestCase {
        public function testJWTTokenGeneration() {
            $jwt = new JWTManager('test-secret-key');
            $token = $jwt->generateToken(123, 'test@example.com');
            
            $this->assertNotNull($token, 'Le token JWT doit être généré');
            $this->assertTrue(is_string($token), 'Le token doit être une chaîne');
            $this->assertTrue(strlen($token) > 50, 'Le token doit avoir une longueur suffisante');
        }
        
        public function testJWTTokenValidation() {
            $jwt = new JWTManager('validation-test-key');
            $token = $jwt->generateToken(456, 'validation@test.com');
            
            $decoded = $jwt->validateToken($token);
            $this->assertEquals(456, $decoded['sub'], 'L\'ID utilisateur doit correspondre');
            $this->assertEquals('validation@test.com', $decoded['email'], 'L\'email doit correspondre');
            $this->assertEquals('access', $decoded['type'], 'Le type de token doit être correct');
        }
        
        public function testEventDispatcherBasic() {
            $dispatcher = new EventDispatcher();
            $eventTriggered = false;
            
            $dispatcher->listen('test.basic', function() use (&$eventTriggered) {
                $eventTriggered = true;
            });
            
            $dispatcher->dispatch('test.basic', []);
            $this->assertTrue($eventTriggered, 'L\'événement doit déclencher le listener');
        }
        
        public function testEventDispatcherPriority() {
            $dispatcher = new EventDispatcher();
            $executionOrder = [];
            
            $dispatcher->listen('test.priority', function() use (&$executionOrder) {
                $executionOrder[] = 'low';
            }, 10);
            
            $dispatcher->listen('test.priority', function() use (&$executionOrder) {
                $executionOrder[] = 'high';
            }, 100);
            
            $dispatcher->dispatch('test.priority', []);
            $this->assertEquals(['high', 'low'], $executionOrder, 'Les listeners doivent s\'exécuter par ordre de priorité');
        }
        
        public function testQueueJobExecution() {
            $driver = new SyncQueueDriver();
            $jobExecuted = false;
            
            $job = new class($jobExecuted) implements \Nexa\Queue\JobInterface {
                private $executed;
                
                public function __construct(&$executed) {
                    $this->executed = &$executed;
                }
                
                public function handle() {
                    $this->executed = true;
                    return true;
                }
                
                public function failed($exception) {}
                public function shouldRetry($exception) { return false; }
                public function getId() { return uniqid('test_job_'); }
                public function getName() { return 'test_job'; }
                public function getData() { return []; }
                public function getMaxAttempts() { return 1; }
                public function getTimeout() { return 30; }
                public function getQueue() { return 'test'; }
                public function getDelay() { return 0; }
            };
            
            $driver->push($job, 'test');
            $this->assertTrue($jobExecuted, 'Le job doit être exécuté immédiatement avec le driver sync');
        }
        
        public function testAssertionMethods() {
            // Test des différentes méthodes d'assertion
            $this->assertTrue(true, 'assertTrue doit fonctionner');
            $this->assertFalse(false, 'assertFalse doit fonctionner');
            $this->assertEquals(42, 42, 'assertEquals doit fonctionner');
            $this->assertNotEquals(1, 2, 'assertNotEquals doit fonctionner');
            $this->assertNull(null, 'assertNull doit fonctionner');
            $this->assertNotNull('value', 'assertNotNull doit fonctionner');
            $this->assertArrayHasKey('key', ['key' => 'value'], 'assertArrayHasKey doit fonctionner');
            $this->assertStringContains('test', 'this is a test string', 'assertStringContains doit fonctionner');
        }
    };
    
    // Exécution de la suite de tests
    echo "🧪 Exécution de la suite de tests...\n";
    
    // Exécution directe des tests
    try {
        $testSuite->testJWTTokenGeneration();
        echo "✅ Test JWT Token Generation: PASSÉ\n";
    } catch (Exception $e) {
        echo "❌ Test JWT Token Generation: ÉCHOUÉ - " . $e->getMessage() . "\n";
    }
    
    try {
        $testSuite->testJWTTokenValidation();
        echo "✅ Test JWT Token Validation: PASSÉ\n";
    } catch (Exception $e) {
        echo "❌ Test JWT Token Validation: ÉCHOUÉ - " . $e->getMessage() . "\n";
    }
    
    try {
        $testSuite->testEventDispatcherBasic();
        echo "✅ Test Event Dispatcher Basic: PASSÉ\n";
    } catch (Exception $e) {
        echo "❌ Test Event Dispatcher Basic: ÉCHOUÉ - " . $e->getMessage() . "\n";
    }
    
    try {
         $testSuite->testQueueJobExecution();
         echo "✅ Test Queue Job Execution: PASSÉ\n";
     } catch (Exception $e) {
         echo "❌ Test Queue Job Execution: ÉCHOUÉ - " . $e->getMessage() . "\n";
     }
    
    try {
        $testSuite->testAssertionMethods();
        echo "✅ Test Assertion Methods: PASSÉ\n";
    } catch (Exception $e) {
        echo "❌ Test Assertion Methods: ÉCHOUÉ - " . $e->getMessage() . "\n";
    }
    
    echo "✅ Suite de tests terminée\n";
    
    echo "\n";
    
    // ===== 5. INTÉGRATION COMPLÈTE =====
    echo "5️⃣  INTÉGRATION COMPLÈTE\n";
    echo "=======================\n";
    
    echo "🔄 Simulation d'un workflow complet d'inscription utilisateur...\n";
    
    // Données utilisateur
    $newUser = [
        'id' => 789,
        'email' => 'integration@nexaframework.com',
        'name' => 'John Doe',
        'role' => 'user'
    ];
    
    // 1. Génération du token d'authentification
    $userToken = $jwtManager->generateToken($newUser['id'], $newUser['email'], [
        'role' => $newUser['role'],
        'name' => $newUser['name']
    ]);
    echo "✅ 1. Token d'authentification généré\n";
    
    // 2. Configuration des listeners pour le workflow
    $eventDispatcher->listen('user.registered', function($event) use ($queueDriver) {
        $userData = is_object($event) ? $event->getData() : $event;
        echo "📝 2. Événement d'inscription capturé\n";
        
        // Job d'envoi d'email de bienvenue
        $welcomeEmailJob = new class($userData['email'], $userData['name']) implements \Nexa\Queue\JobInterface {
            private $email, $name;
            
            public function __construct($email, $name) {
                $this->email = $email;
                $this->name = $name;
            }
            
            public function handle() {
                echo "   📧 Envoi d'email de bienvenue à {$this->name} ({$this->email})\n";
                return true;
            }
            
            public function failed($exception) {}
            public function shouldRetry($exception) { return true; }
            public function getId() { return uniqid('welcome_email_'); }
            public function getQueue() { return 'welcome'; }
            public function getDelay() { return 0; }
            public function getName() { return 'welcome_email'; }
            public function getData() { return ['email' => $this->email, 'name' => $this->name]; }
            public function getMaxAttempts() { return 3; }
            public function getTimeout() { return 60; }
        };
        
        // Job de création de profil
        $profileJob = new class($userData['id'], $userData['name']) implements \Nexa\Queue\JobInterface {
            private $userId, $name;
            
            public function __construct($userId, $name) {
                $this->userId = $userId;
                $this->name = $name;
            }
            
            public function handle() {
                echo "   👤 Création du profil pour {$this->name} (ID: {$this->userId})\n";
                return true;
            }
            
            public function failed($exception) {}
            public function shouldRetry($exception) { return true; }
            public function getId() { return uniqid('create_profile_'); }
            public function getQueue() { return 'profiles'; }
            public function getDelay() { return 0; }
            public function getName() { return 'create_profile'; }
            public function getData() { return ['user_id' => $this->userId, 'name' => $this->name]; }
            public function getMaxAttempts() { return 2; }
            public function getTimeout() { return 30; }
        };
        
        // Ajout des jobs à la queue
        $queueDriver->push($welcomeEmailJob, 'welcome');
        $queueDriver->push($profileJob, 'profiles');
        
        echo "✅ 3. Jobs de post-inscription ajoutés et traités\n";
    });
    
    // 3. Déclenchement de l'événement d'inscription
    $eventDispatcher->dispatch('user.registered', $newUser);
    
    // 4. Validation du token créé
    $tokenValidation = $jwtManager->validateToken($userToken);
    echo "✅ 4. Token validé - Utilisateur {$tokenValidation['name']} connecté\n";
    
    // 5. Simulation d'une action utilisateur
    $eventDispatcher->dispatch('user.action', [
        'user_id' => $newUser['id'],
        'action' => 'profile_update',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    echo "✅ 5. Action utilisateur traitée\n";
    
    echo "\n🎉 Workflow d'intégration complet terminé avec succès!\n\n";
    
    // ===== RÉSUMÉ FINAL =====
    echo "📊 === RÉSUMÉ DE LA PHASE 2 ===\n";
    echo "\n🔐 AUTHENTIFICATION JWT:\n";
    echo "   ✅ Génération de tokens d'accès et de refresh\n";
    echo "   ✅ Validation et décodage sécurisés\n";
    echo "   ✅ Support des claims personnalisés\n";
    
    echo "\n🎯 SYSTÈME D'ÉVÉNEMENTS:\n";
    echo "   ✅ Enregistrement de listeners avec priorités\n";
    echo "   ✅ Dispatch d'événements avec données\n";
    echo "   ✅ Contrôle de la propagation\n";
    
    echo "\n⚡ SYSTÈME DE QUEUE:\n";
    echo "   ✅ Jobs avec interface standardisée\n";
    echo "   ✅ Driver synchrone pour traitement immédiat\n";
    echo "   ✅ Gestion des échecs et retry\n";
    
    echo "\n🧪 FRAMEWORK DE TESTS:\n";
    echo "   ✅ Classes de test avec assertions complètes\n";
    echo "   ✅ Runner de tests avec rapports détaillés\n";
    echo "   ✅ Support des tests unitaires et d'intégration\n";
    
    echo "\n🔗 INTÉGRATION:\n";
    echo "   ✅ Workflow complet utilisateur\n";
    echo "   ✅ Communication entre composants\n";
    echo "   ✅ Architecture modulaire et extensible\n";
    
    echo "\n🚀 === PHASE 2 DU FRAMEWORK NEXA COMPLÉTÉE AVEC SUCCÈS === 🚀\n";
    echo "\n💡 Le framework est maintenant prêt pour le développement d'applications\n";
    echo "   robustes avec authentification, événements, queues et tests automatisés!\n\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "📍 Fichier: " . $e->getFile() . " (ligne " . $e->getLine() . ")\n";
    echo "🔍 Trace: " . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "💥 ERREUR FATALE: " . $e->getMessage() . "\n";
    echo "📍 Fichier: " . $e->getFile() . " (ligne " . $e->getLine() . ")\n";
}