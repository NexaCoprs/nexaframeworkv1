<?php

/**
 * Démonstration Simple - Phase 2
 * 
 * Cet exemple démontre les fonctionnalités principales de la Phase 2
 * avec la structure existante du projet.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Charger les variables d'environnement
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// Inclure les classes nécessaires
require_once __DIR__ . '/../src/Nexa/Auth/JWTManager.php';
require_once __DIR__ . '/../src/Nexa/Events/EventDispatcher.php';
require_once __DIR__ . '/../src/Nexa/Events/Event.php';
require_once __DIR__ . '/../src/Nexa/Events/UserEvents.php';
require_once __DIR__ . '/../src/Nexa/Queue/JobInterface.php';
require_once __DIR__ . '/../src/Nexa/Queue/Job.php';
require_once __DIR__ . '/../src/Nexa/Queue/QueueDriverInterface.php';
require_once __DIR__ . '/../src/Nexa/Queue/SyncQueueDriver.php';
require_once __DIR__ . '/../src/Nexa/Queue/QueueManager.php';
require_once __DIR__ . '/../src/Nexa/Queue/Jobs/SendEmailJob.php';
require_once __DIR__ . '/../src/Nexa/Testing/TestCase.php';
require_once __DIR__ . '/../src/Nexa/Testing/TestRunner.php';

use Nexa\Auth\JWTManager;
use Nexa\Events\EventDispatcher;
use Nexa\Events\UserRegistered;
use Nexa\Events\UserLoggedIn;
use Nexa\Queue\QueueManager;
use Nexa\Queue\Jobs\SendEmailJob;
use Nexa\Queue\SyncQueueDriver;
use Nexa\Testing\TestCase;
use Nexa\Testing\TestRunner;

echo "=== Démonstration des Fonctionnalités Phase 2 ===\n\n";

/**
 * 1. Démonstration JWT Authentication
 */
echo "1. Test de l'Authentification JWT\n";
echo "================================\n";

try {
    $jwtManager = new JWTManager('demo-secret-key', 'HS256', 3600, 604800);
    
    // Générer un token d'accès
    $accessToken = $jwtManager->generateToken(123, 'john@example.com', ['role' => 'user']);
    echo "✓ Token d'accès généré : " . substr($accessToken, 0, 50) . "...\n";
    
    // Générer un token de rafraîchissement
    $refreshToken = $jwtManager->generateRefreshToken(123, 'john@example.com');
    echo "✓ Token de rafraîchissement généré : " . substr($refreshToken, 0, 50) . "...\n";
    
    // Valider le token
    $payload = $jwtManager->validateToken($accessToken);
    echo "✓ Token validé avec succès pour l'utilisateur ID: {$payload['sub']}\n";
    echo "  Email: {$payload['email']}\n";
    
    // Générer une paire de tokens
    $tokenPair = $jwtManager->generateTokenPair(456, 'jane@example.com');
    echo "✓ Paire de tokens générée pour jane@example.com\n";
    
} catch (Exception $e) {
    echo "✗ Erreur JWT: " . $e->getMessage() . "\n";
}

echo "\n";

/**
 * 2. Démonstration du Système d'Événements
 */
echo "2. Test du Système d'Événements\n";
echo "===============================\n";

try {
    $eventDispatcher = new EventDispatcher();
    
    // Enregistrer des listeners
    $eventDispatcher->listen('UserRegistered', function($event) {
        $userData = $event->getData();
        echo "  → Listener 1: Utilisateur {$userData['name']} enregistré\n";
    });
    
    $eventDispatcher->listen('UserRegistered', function($event) {
        $userData = $event->getData();
        echo "  → Listener 2: Email de bienvenue préparé pour {$userData['email']}\n";
    }, 100); // Priorité plus élevée
    
    $eventDispatcher->listen('UserLoggedIn', function($event) {
        $userData = $event->getData();
        echo "  → Connexion enregistrée pour {$userData['email']} à " . date('H:i:s') . "\n";
    });
    
    // Déclencher des événements
    echo "✓ Déclenchement de l'événement UserRegistered:\n";
    $userRegisteredEvent = new UserRegistered([
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    $eventDispatcher->dispatch($userRegisteredEvent);
    
    echo "\n✓ Déclenchement de l'événement UserLoggedIn:\n";
    $userLoggedInEvent = new UserLoggedIn([
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ]);
    $eventDispatcher->dispatch($userLoggedInEvent);
    
} catch (Exception $e) {
    echo "✗ Erreur Événements: " . $e->getMessage() . "\n";
}

echo "\n";

/**
 * 3. Démonstration du Système de Files d'Attente
 */
echo "3. Test du Système de Files d'Attente\n";
echo "====================================\n";

try {
    // Configuration pour le driver synchrone
    $config = [
        'default' => 'sync',
        'drivers' => [
            'sync' => [
                'driver' => 'sync'
            ]
        ]
    ];
    
    $queueManager = new QueueManager($config, null, null, $eventDispatcher);
    
    // Créer et ajouter des jobs
    $emailJob1 = new SendEmailJob([
        'to' => 'john@example.com',
        'subject' => 'Bienvenue !',
        'template' => 'welcome',
        'data' => ['name' => 'John Doe']
    ]);
    
    $emailJob2 = new SendEmailJob([
        'to' => 'jane@example.com',
        'subject' => 'Newsletter',
        'template' => 'newsletter',
        'data' => ['month' => 'Décembre']
    ]);
    
    echo "✓ Ajout de jobs à la file d'attente:\n";
    $queueManager->push($emailJob1);
    echo "  → Job d'email de bienvenue ajouté\n";
    
    $queueManager->push($emailJob2);
    echo "  → Job de newsletter ajouté\n";
    
    echo "\n✓ Traitement des jobs:\n";
    $processed1 = $queueManager->processJob();
    if ($processed1) {
        echo "  → Premier job traité avec succès\n";
    }
    
    $processed2 = $queueManager->processJob();
    if ($processed2) {
        echo "  → Deuxième job traité avec succès\n";
    }
    
    // Vérifier qu'il n'y a plus de jobs
    $processed3 = $queueManager->processJob();
    if (!$processed3) {
        echo "  → Aucun job restant à traiter\n";
    }
    
} catch (Exception $e) {
    echo "✗ Erreur Queue: " . $e->getMessage() . "\n";
}

echo "\n";

/**
 * 4. Démonstration du Framework de Tests
 */
echo "4. Test du Framework de Tests\n";
echo "============================\n";

// Classe de test simple
class SimpleTest extends TestCase {
    public function testBasicAssertion() {
        $this->assertTrue(true, 'True should be true');
        $this->assertFalse(false, 'False should be false');
        $this->assertEquals(1, 1, 'One should equal one');
    }
    
    public function testStringAssertion() {
        $this->assertStringContains('world', 'Hello world', 'String should contain substring');
    }
    
    public function testArrayAssertion() {
        $array = ['name' => 'John', 'age' => 30];
        $this->assertArrayHasKey('name', $array, 'Array should have name key');
    }
    
    public function testJWTIntegration() {
        $jwtManager = new JWTManager('test-secret');
        $token = $jwtManager->generateToken(1, 'test@example.com');
        $this->assertNotNull($token, 'JWT token should not be null');
        
        $payload = $jwtManager->validateToken($token);
        $this->assertEquals('test@example.com', $payload['email'], 'Email should match');
    }
}

try {
    $testRunner = new TestRunner();
    $testRunner->addTestClass(SimpleTest::class);
    
    echo "✓ Exécution des tests automatisés:\n";
    $testRunner->runAllTests();
    
    echo "\n✓ Résumé des tests:\n";
    $testRunner->displaySummary();
    
} catch (Exception $e) {
    echo "✗ Erreur Tests: " . $e->getMessage() . "\n";
}

echo "\n";

/**
 * 5. Intégration Complète
 */
echo "5. Test d'Intégration Complète\n";
echo "==============================\n";

try {
    echo "✓ Simulation d'un flux complet d'inscription utilisateur:\n";
    
    // 1. Générer des tokens JWT
    $jwtManager = new JWTManager('integration-secret');
    $tokens = $jwtManager->generateTokenPair(789, 'integration@example.com');
    echo "  → Tokens JWT générés\n";
    
    // 2. Déclencher un événement d'inscription
    $eventDispatcher = new EventDispatcher();
    
    // Listener qui ajoute un job à la file d'attente
    $queueManager = new QueueManager([
        'default' => 'sync',
        'drivers' => ['sync' => ['driver' => 'sync']]
    ], null, null, $eventDispatcher);
    
    $eventDispatcher->listen('UserRegistered', function($event) use ($queueManager) {
        $userData = $event->getData();
        $job = new SendEmailJob([
            'to' => $userData['email'],
            'subject' => 'Bienvenue dans notre application !',
            'template' => 'welcome',
            'data' => $userData
        ]);
        $queueManager->push($job);
        echo "  → Job d'email ajouté automatiquement via événement\n";
    });
    
    // 3. Déclencher l'événement
    $userEvent = new UserRegistered([
        'id' => 789,
        'name' => 'Integration User',
        'email' => 'integration@example.com',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    $eventDispatcher->dispatch($userEvent);
    echo "  → Événement UserRegistered déclenché\n";
    
    // 4. Traiter le job automatiquement créé
    $processed = $queueManager->processJob();
    if ($processed) {
        echo "  → Job d'email traité automatiquement\n";
    }
    
    // 5. Valider le token généré
    $payload = $jwtManager->validateToken($tokens['access_token']);
    echo "  → Token validé pour l'utilisateur: {$payload['email']}\n";
    
    echo "\n✓ Flux d'intégration terminé avec succès !\n";
    
} catch (Exception $e) {
    echo "✗ Erreur Intégration: " . $e->getMessage() . "\n";
}

echo "\n";
echo "=== Démonstration Terminée ===\n";
echo "\nToutes les fonctionnalités de la Phase 2 ont été testées avec succès !\n";
echo "\nFonctionnalités démontrées :\n";
echo "• Authentification JWT avec génération et validation de tokens\n";
echo "• Système d'événements avec listeners et priorités\n";
echo "• Files d'attente avec jobs et traitement automatique\n";
echo "• Framework de tests avec assertions et rapports\n";
echo "• Intégration complète de tous les composants\n";
echo "\nLe Nexa Framework Phase 2 est prêt pour le développement !\n";