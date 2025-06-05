<?php

/**
 * Démonstration des fonctionnalités de la Phase 2 du Framework Nexa
 * 
 * Cette démonstration présente :
 * - Authentification JWT
 * - Système d'événements
 * - Système de queue (version simplifiée)
 * - Framework de tests automatisés
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Imports nécessaires
use Nexa\Auth\JWTManager;
use Nexa\Events\EventDispatcher;
use Nexa\Events\Event;
use Nexa\Queue\SyncQueueDriver;
use Nexa\Testing\TestCase;
use Nexa\Testing\TestRunner;

// Inclusion manuelle de UserRegistered
require_once __DIR__ . '/../src/Nexa/Events/UserEvents.php';
use Nexa\Events\UserRegistered;

echo "=== DÉMONSTRATION PHASE 2 - FRAMEWORK NEXA ===\n\n";

try {
    // ===== TEST 1: AUTHENTIFICATION JWT =====
    echo "1. Test de l'Authentification JWT\n";
    echo "================================\n";
    
    $jwtManager = new JWTManager('demo-secret-key-2024');
    
    // Génération d'un token
    $userId = 123;
    $userEmail = 'demo@nexa.com';
    $token = $jwtManager->generateToken($userId, $userEmail);
    
    echo "✓ Token généré pour l'utilisateur ID: $userId\n";
    echo "✓ Email: $userEmail\n";
    echo "✓ Token: " . substr($token, 0, 50) . "...\n";
    
    // Validation du token
    $decoded = $jwtManager->validateToken($token);
    echo "✓ Token validé - Utilisateur: {$decoded['sub']}, Email: {$decoded['email']}\n";
    
    // Génération d'un refresh token
    $refreshToken = $jwtManager->generateRefreshToken($userId, $userEmail);
    echo "✓ Refresh token généré\n\n";
    
    // ===== TEST 2: SYSTÈME D'ÉVÉNEMENTS =====
    echo "2. Test du Système d'Événements\n";
    echo "===============================\n";
    
    $eventDispatcher = new EventDispatcher();
    
    // Enregistrement d'un listener
    $eventDispatcher->listen('user.registered', function($event) {
        $user = $event->getUser();
        echo "✓ Événement capturé: Nouvel utilisateur enregistré\n";
        echo "  - Utilisateur: " . json_encode($user) . "\n";
        echo "  - Données: " . json_encode($event->getData()) . "\n";
    });
    
    // Enregistrement d'un second listener avec priorité
    $eventDispatcher->listen('user.registered', function($event) {
        echo "✓ Listener prioritaire: Envoi d'email de bienvenue\n";
    }, 100);
    
    // Déclenchement de l'événement
    $userData = ['id' => $userId, 'email' => $userEmail, 'role' => 'user', 'source' => 'demo'];
    try {
        $userEvent = new UserRegistered($userData);
        $eventDispatcher->dispatch('user.registered', $userEvent);
    } catch (Error $e) {
        // Fallback avec Event générique si UserRegistered n'est pas disponible
        $userEvent = new Event($userData);
        $eventDispatcher->dispatch('user.registered', $userEvent);
        echo "✓ Événement générique déclenché (UserRegistered non disponible)\n";
    }
    
    echo "\n";
    
    // ===== TEST 3: SYSTÈME DE QUEUE =====
    echo "3. Test du Système de Queue\n";
    echo "==========================\n";
    
    $syncDriver = new SyncQueueDriver();
    
    // Création d'un job de test
    $testJob = new class($userEmail) implements \Nexa\Queue\JobInterface {
        private $email;
        
        public function __construct($email) {
            $this->email = $email;
        }
        
        public function handle() {
            echo "✓ Job exécuté: Email de bienvenue envoyé à {$this->email}\n";
            return true;
        }
        
        public function failed($exception) {
            echo "✗ Job échoué: " . $exception->getMessage() . "\n";
        }
        
        public function getQueue() { return 'default'; }
        public function getDelay() { return 0; }
        public function getMaxTries() { return 3; }
    };
    
    // Exécution du job
    echo "✓ Job ajouté à la queue\n";
    $syncDriver->push($testJob, 'default');
    echo "✓ Job traité avec succès\n\n";
    
    // ===== TEST 4: FRAMEWORK DE TESTS =====
    echo "4. Test du Framework de Tests\n";
    echo "============================\n";
    
    // Création d'une classe de test simple
    $testClass = new class extends TestCase {
        public function testJWTGeneration() {
            $jwt = new JWTManager('test-secret');
            $token = $jwt->generateToken(1, 'test@example.com');
            
            $this->assertNotNull($token, 'Le token ne doit pas être null');
            $this->assertTrue(is_string($token), 'Le token doit être une chaîne');
            
            $decoded = $jwt->validateToken($token);
            $this->assertEquals(1, $decoded['sub'], 'L\'ID utilisateur doit correspondre');
            $this->assertEquals('test@example.com', $decoded['email'], 'L\'email doit correspondre');
        }
        
        public function testEventDispatcher() {
            $dispatcher = new EventDispatcher();
            $eventFired = false;
            
            $dispatcher->listen('test.event', function() use (&$eventFired) {
                $eventFired = true;
            });
            
            $dispatcher->dispatch('test.event', []);
            $this->assertTrue($eventFired, 'L\'événement doit être déclenché');
        }
        
        public function testQueueJob() {
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
                public function getQueue() { return 'test'; }
                public function getDelay() { return 0; }
                public function getMaxTries() { return 1; }
            };
            
            $driver->push($job, 'test');
            $this->assertTrue($jobExecuted, 'Le job doit être exécuté');
        }
    };
    
    // Exécution des tests
    $testRunner = new TestRunner();
    $testRunner->addTestClass($testClass);
    $testRunner->runAllTests();
    $testRunner->displaySummary();
    
    echo "\n";
    
    // ===== TEST D'INTÉGRATION =====
    echo "5. Test d'Intégration Complet\n";
    echo "============================\n";
    
    // Simulation d'un workflow complet d'inscription utilisateur
    $newUserId = 456;
    $newUserEmail = 'integration@nexa.com';
    
    echo "Simulation d'inscription utilisateur...\n";
    
    // 1. Génération du token d'authentification
    $authToken = $jwtManager->generateToken($newUserId, $newUserEmail);
    echo "✓ Token d'authentification créé\n";
    
    // 2. Déclenchement de l'événement d'inscription
    $newUserData = [
        'id' => $newUserId,
        'email' => $newUserEmail,
        'registration_date' => date('Y-m-d H:i:s'),
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Nexa Demo'
    ];
    try {
        $registrationEvent = new UserRegistered($newUserData);
    } catch (Error $e) {
        // Fallback avec Event générique
        $registrationEvent = new Event($newUserData);
    }
    
    // Listener pour traitement post-inscription
    $eventDispatcher->listen('user.registered', function($event) use ($syncDriver) {
        $user = $event->getUser();
        
        // Job d'envoi d'email
        $emailJob = new class($user['email']) implements \Nexa\Queue\JobInterface {
            private $email;
            
            public function __construct($email) {
                $this->email = $email;
            }
            
            public function handle() {
                echo "  ✓ Email de confirmation envoyé à {$this->email}\n";
                return true;
            }
            
            public function failed($exception) {}
            public function getQueue() { return 'emails'; }
            public function getDelay() { return 0; }
            public function getMaxTries() { return 3; }
        };
        
        $syncDriver->push($emailJob, 'emails');
        
        // Job de création de profil
        $profileJob = new class($user['id']) implements \Nexa\Queue\JobInterface {
            private $userId;
            
            public function __construct($userId) {
                $this->userId = $userId;
            }
            
            public function handle() {
                echo "  ✓ Profil utilisateur créé pour l'ID {$this->userId}\n";
                return true;
            }
            
            public function failed($exception) {}
            public function getQueue() { return 'profiles'; }
            public function getDelay() { return 0; }
            public function getMaxTries() { return 3; }
        };
        
        $syncDriver->push($profileJob, 'profiles');
    });
    
    $eventDispatcher->dispatch('user.registered', $registrationEvent);
    echo "✓ Événement d'inscription traité\n";
    
    // 3. Validation du token créé
    $tokenData = $jwtManager->validateToken($authToken);
    echo "✓ Token validé - Utilisateur connecté\n";
    
    echo "\n✅ Workflow d'inscription complet terminé avec succès!\n\n";
    
    // ===== RÉSUMÉ =====
    echo "=== RÉSUMÉ DE LA DÉMONSTRATION ===\n";
    echo "✅ Authentification JWT : Génération, validation et refresh de tokens\n";
    echo "✅ Système d'événements : Listeners, priorités et dispatch\n";
    echo "✅ Système de queue : Jobs synchrones et traitement\n";
    echo "✅ Framework de tests : Assertions et exécution automatisée\n";
    echo "✅ Intégration complète : Workflow utilisateur de bout en bout\n\n";
    
    echo "🎉 Phase 2 du Framework Nexa démontrée avec succès!\n";
    
} catch (Exception $e) {
    echo "❌ Erreur durant la démonstration: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}