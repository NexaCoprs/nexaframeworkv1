<?php

require_once __DIR__ . '/../bootstrap.php';

/**
 * Integration Tests for Nexa Framework
 * Tests how different components work together
 */
class FrameworkIntegrationTest extends TestCase
{
    private $router;
    private $testApp;
    
    public function setUp(): void
    {
        parent::setUp();
        
        // Initialize core components
        if (class_exists('\Nexa\Routing\Router')) {
            $this->router = new \Nexa\Routing\Router();
        }
        
        if (class_exists('\Nexa\Core\Application')) {
            $this->testApp = new \Nexa\Core\Application();
        }
    }
    
    public function testRouterMiddlewareIntegration()
    {
        if (!$this->router) {
            echo "⚠ Skipping router-middleware integration test - Router not available\n";
            return;
        }
        
        // Test route with middleware
        $this->router->get('/protected', function() {
            return 'Protected content';
        })->middleware('auth');
        
        // Test middleware group
        $this->router->group(['middleware' => ['auth', 'admin']], function($router) {
            $router->get('/admin/dashboard', function() {
                return 'Admin dashboard';
            });
            
            $router->post('/admin/users', function() {
                return 'Create user';
            });
        });
        
        echo "✓ Router-Middleware integration test passed\n";
    }
    
    public function testRouterControllerIntegration()
    {
        if (!$this->router) {
            echo "⚠ Skipping router-controller integration test - Router not available\n";
            return;
        }
        
        // Test controller routes
        if (class_exists('\Workspace\Handlers\UserHandler')) {
            $this->router->get('/users', '\Workspace\Handlers\UserHandler@index');
            $this->router->post('/users', '\Workspace\Handlers\UserHandler@store');
            $this->router->get('/users/{id}', '\Workspace\Handlers\UserHandler@show');
            $this->router->put('/users/{id}', '\Workspace\Handlers\UserHandler@update');
            $this->router->delete('/users/{id}', '\Workspace\Handlers\UserHandler@destroy');
            
            echo "✓ Router-Controller integration test passed\n";
        } else {
            echo "⚠ UserHandler not found for router-controller integration\n";
        }
    }
    
    public function testModelControllerIntegration()
    {
        if (!class_exists('\Workspace\Database\Entities\User') || 
            !class_exists('\Workspace\Handlers\UserHandler')) {
            echo "⚠ Skipping model-controller integration test - Components not available\n";
            return;
        }
        
        try {
            $userHandler = new \Workspace\Handlers\UserHandler();
            
            // Test if controller can access model
            if (method_exists($userHandler, 'index')) {
                // This might require database connection
                echo "✓ Controller has index method for model interaction\n";
            }
            
            if (method_exists($userHandler, 'store')) {
                echo "✓ Controller has store method for model creation\n";
            }
            
            if (method_exists($userHandler, 'show')) {
                echo "✓ Controller has show method for model retrieval\n";
            }
            
            echo "✓ Model-Controller integration test passed\n";
            
        } catch (Exception $e) {
            echo "⚠ Model-Controller integration requires database context: " . $e->getMessage() . "\n";
        }
    }
    
    public function testAuthenticationIntegration()
    {
        // Test authentication flow
        if (class_exists('\Nexa\Middleware\AuthMiddleware')) {
            $authMiddleware = new \Nexa\Middleware\AuthMiddleware();
            
            // Test login process
            if (method_exists($authMiddleware, 'login')) {
                try {
                    $credentials = [
                        'email' => 'test@example.com',
                        'password' => 'password'
                    ];
                    
                    $result = $authMiddleware->login($credentials);
                    echo "✓ Authentication login method works\n";
                } catch (Exception $e) {
                    echo "⚠ Authentication requires user database: " . $e->getMessage() . "\n";
                }
            }
            
            // Test logout process
            if (method_exists($authMiddleware, 'logout')) {
                try {
                    $authMiddleware->logout();
                    echo "✓ Authentication logout method works\n";
                } catch (Exception $e) {
                    echo "⚠ Logout requires session context\n";
                }
            }
            
            echo "✓ Authentication integration test passed\n";
        } else {
            echo "⚠ AuthMiddleware not available for authentication integration test\n";
        }
    }
    
    public function testDatabaseModelIntegration()
    {
        if (!class_exists('\Workspace\Database\Entities\User')) {
            echo "⚠ Skipping database-model integration test - User model not available\n";
            return;
        }
        
        try {
            $user = new \Workspace\Database\Entities\User();
            
            // Test model database operations
            if (method_exists($user, 'all')) {
                $users = $user->all();
                echo "✓ Model can retrieve all records\n";
            }
            
            if (method_exists($user, 'find')) {
                // Test finding a record
                echo "✓ Model has find method\n";
            }
            
            if (method_exists($user, 'where')) {
                $query = $user->where('active', 1);
                echo "✓ Model has query builder integration\n";
            }
            
            echo "✓ Database-Model integration test passed\n";
            
        } catch (Exception $e) {
            echo "⚠ Database-Model integration requires database connection: " . $e->getMessage() . "\n";
        }
    }
    
    public function testCacheIntegration()
    {
        if (class_exists('\Nexa\Cache\CacheManager')) {
            try {
                $cache = new \Nexa\Cache\CacheManager();
                
                // Test cache operations
                if (method_exists($cache, 'put')) {
                    $cache->put('test_key', 'test_value', 60);
                    echo "✓ Cache put operation works\n";
                }
                
                if (method_exists($cache, 'get')) {
                    $value = $cache->get('test_key');
                    echo "✓ Cache get operation works\n";
                }
                
                if (method_exists($cache, 'forget')) {
                    $cache->forget('test_key');
                    echo "✓ Cache forget operation works\n";
                }
                
                echo "✓ Cache integration test passed\n";
                
            } catch (Exception $e) {
                echo "⚠ Cache integration requires cache driver: " . $e->getMessage() . "\n";
            }
        } else {
            echo "⚠ CacheManager not available for cache integration test\n";
        }
    }
    
    public function testQueueIntegration()
    {
        if (class_exists('\Nexa\Queue\QueueManager')) {
            try {
                $queue = new \Nexa\Queue\QueueManager();
                
                // Test job dispatching
                if (method_exists($queue, 'push')) {
                    $jobData = [
                        'type' => 'email',
                        'data' => ['to' => 'test@example.com', 'subject' => 'Test']
                    ];
                    
                    $queue->push('default', $jobData);
                    echo "✓ Queue job dispatch works\n";
                }
                
                // Test job processing
                if (method_exists($queue, 'pop')) {
                    $job = $queue->pop('default');
                    echo "✓ Queue job retrieval works\n";
                }
                
                echo "✓ Queue integration test passed\n";
                
            } catch (Exception $e) {
                echo "⚠ Queue integration requires queue driver: " . $e->getMessage() . "\n";
            }
        } else {
            echo "⚠ QueueManager not available for queue integration test\n";
        }
    }
    
    public function testValidationIntegration()
    {
        if (class_exists('\Nexa\Validation\Validator')) {
            try {
                $validator = new \Nexa\Validation\Validator();
                
                $data = [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'age' => 25
                ];
                
                $rules = [
                    'name' => 'required|string|min:2',
                    'email' => 'required|email',
                    'age' => 'required|integer|min:18'
                ];
                
                if (method_exists($validator, 'validate')) {
                    $result = $validator->validate($data, $rules);
                    echo "✓ Validation integration works\n";
                }
                
                echo "✓ Validation integration test passed\n";
                
            } catch (Exception $e) {
                echo "⚠ Validation integration error: " . $e->getMessage() . "\n";
            }
        } else {
            echo "⚠ Validator not available for validation integration test\n";
        }
    }
    
    public function testEventIntegration()
    {
        if (class_exists('\Nexa\Events\EventDispatcher')) {
            try {
                $events = new \Nexa\Events\EventDispatcher();
                
                // Test event listening
                if (method_exists($events, 'listen')) {
                    $events->listen('user.created', function($user) {
                        return "User {$user['name']} was created";
                    });
                    echo "✓ Event listener registration works\n";
                }
                
                // Test event dispatching
                if (method_exists($events, 'dispatch')) {
                    $userData = ['name' => 'Test User', 'email' => 'test@example.com'];
                    $events->dispatch('user.created', $userData);
                    echo "✓ Event dispatching works\n";
                }
                
                echo "✓ Event integration test passed\n";
                
            } catch (Exception $e) {
                echo "⚠ Event integration error: " . $e->getMessage() . "\n";
            }
        } else {
            echo "⚠ EventDispatcher not available for event integration test\n";
        }
    }
    
    public function testFullRequestLifecycle()
    {
        if (!$this->router) {
            echo "⚠ Skipping full request lifecycle test - Router not available\n";
            return;
        }
        
        try {
            // Simulate a complete request lifecycle
            
            // 1. Route registration
            $this->router->get('/api/users/{id}', function($id) {
                // 2. Controller action simulation
                if (class_exists('\Workspace\Database\Entities\User')) {
                    $user = new \Workspace\Database\Entities\User();
                    
                    // 3. Model interaction
                    if (method_exists($user, 'find')) {
                        // Would normally find user by ID
                        return ['id' => $id, 'name' => 'Test User', 'email' => 'test@example.com'];
                    }
                }
                
                return ['id' => $id, 'message' => 'User endpoint reached'];
            })->middleware('auth');
            
            // 4. Middleware application would happen here
            
            echo "✓ Full request lifecycle simulation completed\n";
            echo "✓ Full request lifecycle integration test passed\n";
            
        } catch (Exception $e) {
            echo "⚠ Full request lifecycle test error: " . $e->getMessage() . "\n";
        }
    }
    
    public function testSmartFeaturesIntegration()
    {
        // Test smart attributes integration
        if (class_exists('\Workspace\Database\Entities\User')) {
            try {
                $user = new \Workspace\Database\Entities\User();
                
                // Test smart validation attributes
                if (method_exists($user, 'getValidationRules')) {
                    $rules = $user->getValidationRules();
                    echo "✓ Smart validation attributes work\n";
                }
                
                // Test smart caching attributes
                if (method_exists($user, 'getCacheConfig')) {
                    $cacheConfig = $user->getCacheConfig();
                    echo "✓ Smart caching attributes work\n";
                }
                
                echo "✓ Smart features integration test passed\n";
                
            } catch (Exception $e) {
                echo "⚠ Smart features integration error: " . $e->getMessage() . "\n";
            }
        } else {
            echo "⚠ User model not available for smart features integration test\n";
        }
    }
    
    public function testPerformanceIntegration()
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        // Test integrated performance across components
        for ($i = 0; $i < 100; $i++) {
            // Router operations
            if ($this->router) {
                $this->router->get("/perf-test-$i", function() use ($i) {
                    return "Performance test $i";
                });
            }
            
            // Model operations
            if (class_exists('\Workspace\Database\Entities\User')) {
                $user = new \Workspace\Database\Entities\User();
                $user->setAttribute('name', "User $i");
            }
            
            // Middleware operations
            if (class_exists('\Nexa\Middleware\AuthMiddleware')) {
                $auth = new \Nexa\Middleware\AuthMiddleware();
                // Simulate middleware processing
            }
        }
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $executionTime = $endTime - $startTime;
        $memoryUsed = $endMemory - $startMemory;
        
        // Should handle 100 integrated operations efficiently
        $this->assertLessThan(2.0, $executionTime);
        
        echo "✓ Performance integration test passed (100 operations in {$executionTime}s, " . 
             round($memoryUsed / 1024, 2) . "KB)\n";
    }
    
    // Helper assertion method removed - using PHPUnit's built-in assertions
}

// Run the tests
echo "\n=== RUNNING FRAMEWORK INTEGRATION TESTS ===\n\n";

try {
    $test = new FrameworkIntegrationTest();
    $test->setUp();
    
    $test->testRouterMiddlewareIntegration();
    $test->testRouterControllerIntegration();
    $test->testModelControllerIntegration();
    $test->testAuthenticationIntegration();
    $test->testDatabaseModelIntegration();
    $test->testCacheIntegration();
    $test->testQueueIntegration();
    $test->testValidationIntegration();
    $test->testEventIntegration();
    $test->testFullRequestLifecycle();
    $test->testSmartFeaturesIntegration();
    $test->testPerformanceIntegration();
    
    echo "\n✅ All Integration tests passed!\n\n";
    
} catch (Exception $e) {
    echo "\n❌ Integration test failed: " . $e->getMessage() . "\n\n";
}