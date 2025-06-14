<?php

/**
 * Bootstrap file for Nexa Framework Tests
 * Initializes the testing environment
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('UTC');

// Define test environment
define('NEXA_ENV', 'testing');
define('NEXA_ROOT', dirname(__DIR__));
define('NEXA_TESTS', __DIR__);

// Load the framework core components
// Note: We don't include index.php to avoid web output in tests
require_once NEXA_ROOT . '/vendor/autoload.php';

// Load core classes manually
if (file_exists(NEXA_ROOT . '/kernel/Nexa/Support/helpers.php')) {
    require_once NEXA_ROOT . '/kernel/Nexa/Support/helpers.php';
}
if (file_exists(NEXA_ROOT . '/kernel/Nexa/Core/helpers.php')) {
    require_once NEXA_ROOT . '/kernel/Nexa/Core/helpers.php';
}
if (file_exists(NEXA_ROOT . '/kernel/Nexa/Core/Application.php')) {
    require_once NEXA_ROOT . '/kernel/Nexa/Core/Application.php';
}
if (file_exists(NEXA_ROOT . '/kernel/Nexa/Routing/Router.php')) {
    require_once NEXA_ROOT . '/kernel/Nexa/Routing/Router.php';
}
if (file_exists(NEXA_ROOT . '/kernel/Nexa/Routing/Route.php')) {
    require_once NEXA_ROOT . '/kernel/Nexa/Routing/Route.php';
}
if (file_exists(NEXA_ROOT . '/kernel/Nexa/Database/Model.php')) {
    require_once NEXA_ROOT . '/kernel/Nexa/Database/Model.php';
}
if (file_exists(NEXA_ROOT . '/kernel/Nexa/Http/Request.php')) {
    require_once NEXA_ROOT . '/kernel/Nexa/Http/Request.php';
}
if (file_exists(NEXA_ROOT . '/kernel/Nexa/Http/Response.php')) {
    require_once NEXA_ROOT . '/kernel/Nexa/Http/Response.php';
}
if (file_exists(NEXA_ROOT . '/kernel/Nexa/Core/Config.php')) {
    require_once NEXA_ROOT . '/kernel/Nexa/Core/Config.php';
    // Initialize Config with workspace config directory
    \Nexa\Core\Config::init(NEXA_ROOT . '/workspace/config');
}

// Test utilities
class TestCase extends \PHPUnit\Framework\TestCase
{
    protected static $app;
    protected $db;
    
    public static function setUpBeforeClass(): void
    {
        // Initialize test application
        static::$app = new \Nexa\Core\Application();
    }
    
    protected function setUp(): void
    {
        // Setup test database
        $this->setupTestDatabase();
        
        // Clear caches
        $this->clearCaches();
    }
    
    protected function tearDown(): void
    {
        // Cleanup after each test
        $this->cleanupTestData();
    }
    
    protected function setupTestDatabase(): void
    {
        try {
            // Use in-memory SQLite for testing
            $this->db = $this->createInMemoryDatabase();
            // Create test tables if needed
            $this->createTestTables();
        } catch (Exception $e) {
            echo "Warning: Could not setup test database: " . $e->getMessage() . "\n";
            $this->db = null;
        }
    }
    
    protected function createTestTables(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS test_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            
            CREATE TABLE IF NOT EXISTS test_posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title VARCHAR(255) NOT NULL,
                content TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES test_users(id)
            );
        ";
        
        if ($this->db) {
            try {
                $this->db->exec($sql);
            } catch (Exception $e) {
                echo "Warning: Could not create test tables: " . $e->getMessage() . "\n";
            }
        }
    }
    
    protected function createInMemoryDatabase()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }
    
    protected function clearCaches(): void
    {
        // Clear various caches
        if (class_exists('\Nexa\Cache\CacheManager')) {
            try {
                $cache = new \Nexa\Cache\CacheManager();
                $cache->flush();
            } catch (Exception $e) {
                // Ignore cache errors in tests
            }
        }
    }
    
    protected function cleanupTestData(): void
    {
        if ($this->db) {
            try {
                $this->db->exec('DELETE FROM test_posts');
                $this->db->exec('DELETE FROM test_users');
            } catch (Exception $e) {
                // Ignore cleanup errors
            }
        }
    }
    
    // Helper methods for assertions
    protected function assertDatabaseHas(string $table, array $data): bool
    {
        if (!$this->db) return false;
        
        $conditions = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            $conditions[] = "$key = ?";
            $params[] = $value;
        }
        
        $sql = "SELECT COUNT(*) FROM $table WHERE " . implode(' AND ', $conditions);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn() > 0;
    }
    
    protected function createTestUser(array $data = []): array
    {
        $defaultData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password', PASSWORD_DEFAULT)
        ];
        
        $userData = array_merge($defaultData, $data);
        
        if ($this->db) {
            $sql = "INSERT INTO test_users (name, email, password) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userData['name'], $userData['email'], $userData['password']]);
            $userData['id'] = $this->db->lastInsertId();
        }
        
        return $userData;
    }
    
    protected function measureExecutionTime(callable $callback): float
    {
        $start = microtime(true);
        $callback();
        return microtime(true) - $start;
    }
    
    protected function measureMemoryUsage(callable $callback): int
    {
        $startMemory = memory_get_usage(true);
        $callback();
        return memory_get_usage(true) - $startMemory;
    }
    
    // Custom test utilities (PHPUnit assertions are inherited)
}

// Global test helpers
function assertArrayHasKeys(array $array, array $keys): bool
{
    foreach ($keys as $key) {
        if (!array_key_exists($key, $array)) {
            return false;
        }
    }
    return true;
}

function assertClassExists(string $className): bool
{
    return class_exists($className);
}

function assertMethodExists(string $className, string $methodName): bool
{
    return method_exists($className, $methodName);
}

echo "\n=== NEXA FRAMEWORK TEST ENVIRONMENT INITIALIZED ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Test Environment: " . NEXA_ENV . "\n";
echo "Framework Root: " . NEXA_ROOT . "\n\n";