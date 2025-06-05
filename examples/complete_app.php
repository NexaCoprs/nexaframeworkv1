<?php

/**
 * Exemple d'Application Complète - Phase 2
 * 
 * Cet exemple démontre l'utilisation de toutes les fonctionnalités
 * introduites dans la Phase 2 du Nexa Framework :
 * - Authentification JWT
 * - Système d'événements
 * - Files d'attente
 * - Tests automatisés
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Nexa/Core/Application.php';
require_once __DIR__ . '/../src/Nexa/Auth/JWTManager.php';
require_once __DIR__ . '/../src/Nexa/Events/EventDispatcher.php';
require_once __DIR__ . '/../src/Nexa/Events/UserEvents.php';
require_once __DIR__ . '/../src/Nexa/Queue/QueueManager.php';
require_once __DIR__ . '/../src/Nexa/Queue/Jobs/SendEmailJob.php';
require_once __DIR__ . '/../src/Nexa/Database/QueryBuilder.php';
require_once __DIR__ . '/../src/Nexa/Routing/Router.php';
require_once __DIR__ . '/../src/Nexa/Http/Request.php';
require_once __DIR__ . '/../config/database.php';

use Nexa\Core\Application;
use Nexa\Auth\JWTManager as JWT;
use Nexa\Events\EventDispatcher;
use Nexa\Events\UserRegistered;
use Nexa\Events\UserLoggedIn;
use Nexa\Queue\QueueManager;
use Nexa\Queue\Jobs\SendEmailJob;
use Nexa\Database\QueryBuilder;
use Nexa\Routing\Router;
use Nexa\Http\Request;

/**
 * Configuration de l'application
 */
$config = [
    'database' => [
        'driver' => 'sqlite',
        'host' => __DIR__ . '/../storage/app.db',
        'database' => 'nexa_app',
        'username' => '',
        'password' => '',
    ],
    'jwt' => [
        'secret' => 'your-super-secret-jwt-key-change-this-in-production',
        'algorithm' => 'HS256',
        'access_token_ttl' => 3600,
        'refresh_token_ttl' => 604800,
    ],
    'queue' => [
        'default' => 'database',
        'drivers' => [
            'database' => [
                'driver' => 'database',
                'table' => 'jobs',
                'failed_table' => 'failed_jobs',
            ],
        ],
    ],
];

/**
 * Initialisation des composants
 */
$app = new Application();
$database = new Database($config['database']);
$jwt = new JWT($config['jwt']);
$eventDispatcher = new EventDispatcher();
$queueManager = new QueueManager($config['queue'], $database, null, $eventDispatcher);
$router = new Router();

/**
 * Modèle User simple
 */
class User {
    public $id;
    public $name;
    public $email;
    public $password;
    public $created_at;
    
    private $database;
    
    public function __construct($database = null) {
        $this->database = $database;
    }
    
    public function save() {
        if ($this->database) {
            if ($this->id) {
                // Update
                $stmt = $this->database->prepare(
                    "UPDATE users SET name = ?, email = ? WHERE id = ?"
                );
                $stmt->execute([$this->name, $this->email, $this->id]);
            } else {
                // Insert
                $stmt = $this->database->prepare(
                    "INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, ?)"
                );
                $this->created_at = date('Y-m-d H:i:s');
                $stmt->execute([$this->name, $this->email, $this->password, $this->created_at]);
                $this->id = $this->database->lastInsertId();
            }
        }
        return $this;
    }
    
    public static function findByEmail($email, $database) {
        $stmt = $database->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            $user = new self($database);
            $user->id = $data['id'];
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = $data['password'];
            $user->created_at = $data['created_at'];
            return $user;
        }
        
        return null;
    }
    
    public function toArray() {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at,
        ];
    }
}

/**
 * Service d'authentification
 */
class AuthService {
    private $database;
    private $jwt;
    private $eventDispatcher;
    
    public function __construct($database, $jwt, $eventDispatcher) {
        $this->database = $database;
        $this->jwt = $jwt;
        $this->eventDispatcher = $eventDispatcher;
    }
    
    public function register($name, $email, $password) {
        // Vérifier si l'utilisateur existe déjà
        $existingUser = User::findByEmail($email, $this->database);
        if ($existingUser) {
            throw new Exception('User already exists');
        }
        
        // Créer l'utilisateur
        $user = new User($this->database);
        $user->name = $name;
        $user->email = $email;
        $user->password = password_hash($password, PASSWORD_DEFAULT);
        $user->save();
        
        // Déclencher l'événement UserRegistered
        $event = new UserRegistered($user->toArray());
        $this->eventDispatcher->dispatch($event);
        
        return $user;
    }
    
    public function login($email, $password) {
        $user = User::findByEmail($email, $this->database);
        
        if (!$user || !password_verify($password, $user->password)) {
            throw new Exception('Invalid credentials');
        }
        
        // Générer les tokens JWT
        $tokens = $this->jwt->generateTokenPair([
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
        
        // Déclencher l'événement UserLoggedIn
        $event = new UserLoggedIn($user->toArray());
        $this->eventDispatcher->dispatch($event);
        
        return [
            'user' => $user->toArray(),
            'tokens' => $tokens,
        ];
    }
    
    public function validateToken($token) {
        try {
            $payload = $this->jwt->validateToken($token);
            $user = User::findByEmail($payload['email'], $this->database);
            return $user ? $user->toArray() : null;
        } catch (Exception $e) {
            return null;
        }
    }
}

/**
 * Middleware d'authentification JWT
 */
class JWTMiddleware {
    private $authService;
    
    public function __construct($authService) {
        $this->authService = $authService;
    }
    
    public function handle($request, $next) {
        $authHeader = $request->getHeader('Authorization');
        
        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return new Response(json_encode(['error' => 'Unauthorized']), 401);
        }
        
        $token = $matches[1];
        $user = $this->authService->validateToken($token);
        
        if (!$user) {
            return new Response(json_encode(['error' => 'Invalid token']), 401);
        }
        
        // Ajouter l'utilisateur à la requête
        $request->user = $user;
        
        return $next($request);
    }
}

/**
 * Configuration des listeners d'événements
 */

// Listener pour l'inscription d'utilisateur
$eventDispatcher->listen('UserRegistered', function($event) use ($queueManager) {
    // Ajouter un job d'envoi d'email de bienvenue à la file d'attente
    $userData = $event->getData();
    $job = new SendEmailJob([
        'to' => $userData['email'],
        'subject' => 'Bienvenue !',
        'template' => 'welcome',
        'data' => [
            'name' => $userData['name'],
            'email' => $userData['email'],
        ],
    ]);
    
    $queueManager->push($job, 'emails');
    
    echo "[EVENT] Job d'email de bienvenue ajouté à la file d'attente pour {$userData['email']}\n";
});

// Listener pour la connexion d'utilisateur
$eventDispatcher->listen('UserLoggedIn', function($event) {
    $userData = $event->getData();
    echo "[EVENT] Utilisateur connecté : {$userData['email']} à " . date('Y-m-d H:i:s') . "\n";
});

/**
 * Initialisation de la base de données
 */
function initializeDatabase($database) {
    // Créer la table users
    $database->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL
        )
    ");
    
    // Créer les tables pour les files d'attente
    $database->exec("
        CREATE TABLE IF NOT EXISTS jobs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            queue VARCHAR(255) NOT NULL DEFAULT 'default',
            payload TEXT NOT NULL,
            attempts INTEGER NOT NULL DEFAULT 0,
            reserved_at INTEGER NULL,
            available_at INTEGER NOT NULL,
            created_at INTEGER NOT NULL
        )
    ");
    
    $database->exec("
        CREATE TABLE IF NOT EXISTS failed_jobs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            queue VARCHAR(255) NOT NULL,
            payload TEXT NOT NULL,
            exception TEXT NOT NULL,
            failed_at INTEGER NOT NULL
        )
    ");
}

initializeDatabase($database->getConnection());

/**
 * Services
 */
$authService = new AuthService($database->getConnection(), $jwt, $eventDispatcher);
$jwtMiddleware = new JWTMiddleware($authService);

/**
 * Routes de l'API
 */

// Route d'inscription
$router->post('/api/register', function($request) use ($authService) {
    try {
        $data = json_decode($request->getBody(), true);
        
        if (!$data || !isset($data['name'], $data['email'], $data['password'])) {
            return new Response(
                json_encode(['error' => 'Missing required fields']), 
                400
            );
        }
        
        $user = $authService->register(
            $data['name'], 
            $data['email'], 
            $data['password']
        );
        
        return new Response(
            json_encode([
                'message' => 'User registered successfully',
                'user' => $user->toArray(),
            ]), 
            201
        );
        
    } catch (Exception $e) {
        return new Response(
            json_encode(['error' => $e->getMessage()]), 
            400
        );
    }
});

// Route de connexion
$router->post('/api/login', function($request) use ($authService) {
    try {
        $data = json_decode($request->getBody(), true);
        
        if (!$data || !isset($data['email'], $data['password'])) {
            return new Response(
                json_encode(['error' => 'Missing email or password']), 
                400
            );
        }
        
        $result = $authService->login($data['email'], $data['password']);
        
        return new Response(
            json_encode([
                'message' => 'Login successful',
                'user' => $result['user'],
                'access_token' => $result['tokens']['access_token'],
                'refresh_token' => $result['tokens']['refresh_token'],
            ]), 
            200
        );
        
    } catch (Exception $e) {
        return new Response(
            json_encode(['error' => $e->getMessage()]), 
            401
        );
    }
});

// Route protégée - Profil utilisateur
$router->get('/api/profile', function($request) {
    return new Response(
        json_encode([
            'message' => 'Profile retrieved successfully',
            'user' => $request->user,
        ]), 
        200
    );
})->middleware([$jwtMiddleware, 'handle']);

// Route protégée - Mise à jour du profil
$router->put('/api/profile', function($request) use ($database, $eventDispatcher) {
    try {
        $data = json_decode($request->getBody(), true);
        $userId = $request->user['id'];
        
        if (!$data || !isset($data['name'])) {
            return new Response(
                json_encode(['error' => 'Missing name field']), 
                400
            );
        }
        
        // Mettre à jour l'utilisateur
        $stmt = $database->getConnection()->prepare(
            "UPDATE users SET name = ? WHERE id = ?"
        );
        $stmt->execute([$data['name'], $userId]);
        
        // Récupérer l'utilisateur mis à jour
        $stmt = $database->getConnection()->prepare(
            "SELECT * FROM users WHERE id = ?"
        );
        $stmt->execute([$userId]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Déclencher un événement de mise à jour
        // (Vous pourriez créer un UserProfileUpdated event)
        
        return new Response(
            json_encode([
                'message' => 'Profile updated successfully',
                'user' => [
                    'id' => $userData['id'],
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'created_at' => $userData['created_at'],
                ],
            ]), 
            200
        );
        
    } catch (Exception $e) {
        return new Response(
            json_encode(['error' => $e->getMessage()]), 
            400
        );
    }
})->middleware([$jwtMiddleware, 'handle']);

// Route pour déclencher manuellement le traitement de la file d'attente
$router->post('/api/queue/process', function($request) use ($queueManager) {
    try {
        $processed = $queueManager->processJob();
        
        if ($processed) {
            return new Response(
                json_encode(['message' => 'Job processed successfully']), 
                200
            );
        } else {
            return new Response(
                json_encode(['message' => 'No jobs to process']), 
                200
            );
        }
        
    } catch (Exception $e) {
        return new Response(
            json_encode(['error' => $e->getMessage()]), 
            500
        );
    }
});

// Route pour voir les statistiques de la file d'attente
$router->get('/api/queue/stats', function($request) use ($queueManager) {
    try {
        $stats = [
            'pending_jobs' => $queueManager->size(),
            'failed_jobs' => count($queueManager->getFailedJobs()),
        ];
        
        return new Response(
            json_encode($stats), 
            200
        );
        
    } catch (Exception $e) {
        return new Response(
            json_encode(['error' => $e->getMessage()]), 
            500
        );
    }
});

/**
 * Fonction principale pour exécuter l'application
 */
function runApp($router) {
    // Simuler une requête (en réalité, cela viendrait du serveur web)
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $body = file_get_contents('php://input');
    $headers = getallheaders() ?: [];
    
    $request = new Request($method, $uri, $headers, $body);
    
    try {
        $response = $router->dispatch($request);
        
        // Envoyer la réponse
        http_response_code($response->getStatusCode());
        
        foreach ($response->getHeaders() as $name => $value) {
            header("$name: $value");
        }
        
        echo $response->getBody();
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
    }
}

/**
 * Fonction de démonstration
 */
function demonstrateFeatures($authService, $queueManager, $eventDispatcher) {
    echo "=== Démonstration des Fonctionnalités Phase 2 ===\n\n";
    
    try {
        // 1. Inscription d'un utilisateur
        echo "1. Inscription d'un utilisateur...\n";
        $user = $authService->register(
            'John Doe', 
            'john@example.com', 
            'password123'
        );
        echo "   Utilisateur créé : {$user->name} ({$user->email})\n\n";
        
        // 2. Connexion
        echo "2. Connexion de l'utilisateur...\n";
        $loginResult = $authService->login('john@example.com', 'password123');
        echo "   Connexion réussie, token généré\n";
        echo "   Access Token : " . substr($loginResult['tokens']['access_token'], 0, 50) . "...\n\n";
        
        // 3. Validation du token
        echo "3. Validation du token...\n";
        $validatedUser = $authService->validateToken($loginResult['tokens']['access_token']);
        echo "   Token valide pour : {$validatedUser['name']}\n\n";
        
        // 4. Traitement de la file d'attente
        echo "4. Traitement de la file d'attente...\n";
        echo "   Jobs en attente : " . $queueManager->size() . "\n";
        
        while ($queueManager->size() > 0) {
            $processed = $queueManager->processJob();
            if ($processed) {
                echo "   Job traité avec succès\n";
            }
        }
        
        echo "   Tous les jobs ont été traités\n\n";
        
        // 5. Statistiques finales
        echo "5. Statistiques finales...\n";
        echo "   Jobs en attente : " . $queueManager->size() . "\n";
        echo "   Jobs échoués : " . count($queueManager->getFailedJobs()) . "\n";
        
    } catch (Exception $e) {
        echo "Erreur : " . $e->getMessage() . "\n";
    }
    
    echo "\n=== Démonstration terminée ===\n";
}

/**
 * Point d'entrée principal
 */
if (php_sapi_name() === 'cli') {
    // Mode CLI - Démonstration
    demonstrateFeatures($authService, $queueManager, $eventDispatcher);
} else {
    // Mode Web - API
    runApp($router);
}

/**
 * Exemples d'utilisation en CLI :
 * 
 * php examples/complete_app.php
 * 
 * Exemples d'utilisation en Web :
 * 
 * POST /api/register
 * {
 *   "name": "John Doe",
 *   "email": "john@example.com",
 *   "password": "password123"
 * }
 * 
 * POST /api/login
 * {
 *   "email": "john@example.com",
 *   "password": "password123"
 * }
 * 
 * GET /api/profile
 * Authorization: Bearer <access_token>
 * 
 * PUT /api/profile
 * Authorization: Bearer <access_token>
 * {
 *   "name": "John Smith"
 * }
 * 
 * POST /api/queue/process
 * GET /api/queue/stats
 */