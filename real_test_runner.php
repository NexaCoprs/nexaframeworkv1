<?php

/**
 * Real Test Runner for Nexa Framework
 * Uses the actual framework bootstrap and test classes
 */

// Include the bootstrap file which sets up the test environment
require_once __DIR__ . '/tests/bootstrap.php';

/**
 * Test Runner Class
 */
class RealTestRunner
{
    private $totalTests = 0;
    private $passedTests = 0;
    private $failedTests = 0;
    private $results = [];
    
    public function runAllTests()
    {
        echo "\n=== RUNNING REAL NEXA FRAMEWORK TESTS ===\n\n";
        
        // Run Unit Tests
        $this->runUnitTests();
        
        // Run Integration Tests
        $this->runIntegrationTests();
        
        // Run Feature Tests
        $this->runFeatureTests();
        
        // Run Performance Tests
        $this->runPerformanceTests();
        
        // Display summary
        $this->displaySummary();
    }
    
    private function runUnitTests()
    {
        echo "\n--- UNIT TESTS ---\n";
        
        $unitTests = [
            'RouterTest' => __DIR__ . '/tests/Unit/RouterTest.php',
            'ModelTest' => __DIR__ . '/tests/Unit/ModelTest.php',
            'MiddlewareTest' => __DIR__ . '/tests/Unit/MiddlewareTest.php',
            'AdvancedComponentsTest' => __DIR__ . '/tests/Unit/AdvancedComponentsTest.php'
        ];
        
        foreach ($unitTests as $testName => $testFile) {
            $this->runTestFile($testName, $testFile);
        }
    }
    
    private function runIntegrationTests()
    {
        echo "\n--- INTEGRATION TESTS ---\n";
        
        $integrationTests = [
            'FrameworkIntegrationTest' => __DIR__ . '/tests/Integration/FrameworkIntegrationTest.php'
        ];
        
        foreach ($integrationTests as $testName => $testFile) {
            $this->runTestFile($testName, $testFile);
        }
    }
    
    private function runFeatureTests()
    {
        echo "\n--- FEATURE TESTS ---\n";
        
        $featureTests = [
            'UserManagementTest' => __DIR__ . '/tests/Feature/UserManagementTest.php'
        ];
        
        foreach ($featureTests as $testName => $testFile) {
            $this->runTestFile($testName, $testFile);
        }
    }
    
    private function runPerformanceTests()
    {
        echo "\n--- PERFORMANCE TESTS ---\n";
        
        $performanceTests = [
            'FrameworkPerformanceTest' => __DIR__ . '/tests/Performance/FrameworkPerformanceTest.php'
        ];
        
        foreach ($performanceTests as $testName => $testFile) {
            $this->runTestFile($testName, $testFile);
        }
    }
    
    private function runTestFile($testName, $testFile)
    {
        echo "\nRunning: $testName\n";
        echo str_repeat('-', 50) . "\n";
        
        if (!file_exists($testFile)) {
            echo "❌ Test file not found: $testFile\n";
            $this->recordResult($testName, false, "Test file not found");
            return;
        }
        
        try {
            // Capture output
            ob_start();
            
            // Include and run the test file
            include $testFile;
            
            $output = ob_get_clean();
            
            // Check if the test class exists and run its methods
            if (class_exists($testName)) {
                $this->runTestClass($testName);
            } else {
                echo "Output from test file:\n$output\n";
            }
            
            echo "✅ $testName completed\n";
            $this->recordResult($testName, true);
            
        } catch (Exception $e) {
            ob_end_clean();
            echo "❌ $testName failed: " . $e->getMessage() . "\n";
            echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
            $this->recordResult($testName, false, $e->getMessage());
            
        } catch (Error $e) {
            ob_end_clean();
            echo "❌ $testName error: " . $e->getMessage() . "\n";
            echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
            $this->recordResult($testName, false, $e->getMessage());
        }
    }
    
    private function runTestClass($className)
    {
        try {
            $testInstance = new $className();
            
            // Get all test methods
            $reflection = new ReflectionClass($className);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            
            $testMethods = [];
            foreach ($methods as $method) {
                if (strpos($method->getName(), 'test') === 0) {
                    $testMethods[] = $method->getName();
                }
            }
            
            if (empty($testMethods)) {
                echo "No test methods found in $className\n";
                return;
            }
            
            // Run setUp if it exists
            if (method_exists($testInstance, 'setUp')) {
                try {
                    $testInstance->setUp();
                } catch (Exception $e) {
                    echo "  Warning: Could not run setUp: " . $e->getMessage() . "\n";
                }
            }
            
            // Run each test method
            foreach ($testMethods as $methodName) {
                echo "  Running: $methodName\n";
                try {
                    $testInstance->$methodName();
                    echo "  ✓ $methodName passed\n";
                } catch (Exception $e) {
                    echo "  ❌ $methodName failed: " . $e->getMessage() . "\n";
                    throw $e; // Re-throw to be caught by parent
                }
            }
            
            // Run tearDown if it exists
            if (method_exists($testInstance, 'tearDown')) {
                try {
                    $testInstance->tearDown();
                } catch (Exception $e) {
                    echo "  Warning: Could not run tearDown: " . $e->getMessage() . "\n";
                }
            }
            
        } catch (Exception $e) {
            throw $e; // Re-throw to be caught by parent
        }
    }
    
    private function recordResult($testName, $passed, $error = null)
    {
        $this->totalTests++;
        if ($passed) {
            $this->passedTests++;
        } else {
            $this->failedTests++;
        }
        
        $this->results[] = [
            'name' => $testName,
            'passed' => $passed,
            'error' => $error
        ];
    }
    
    private function displaySummary()
    {
        echo "\n\n" . str_repeat('=', 60) . "\n";
        echo "TEST SUMMARY\n";
        echo str_repeat('=', 60) . "\n";
        
        echo "Total Tests: {$this->totalTests}\n";
        echo "Passed: {$this->passedTests}\n";
        echo "Failed: {$this->failedTests}\n";
        
        if ($this->failedTests > 0) {
            echo "\nFailed Tests:\n";
            foreach ($this->results as $result) {
                if (!$result['passed']) {
                    echo "❌ {$result['name']}: {$result['error']}\n";
                }
            }
        }
        
        $successRate = $this->totalTests > 0 ? ($this->passedTests / $this->totalTests) * 100 : 0;
        echo "\nSuccess Rate: " . number_format($successRate, 1) . "%\n";
        
        if ($this->passedTests === $this->totalTests) {
            echo "\n🎉 All tests passed!\n";
        } else {
            echo "\n⚠️ Some tests failed. Please check the output above.\n";
        }
        
        echo str_repeat('=', 60) . "\n";
    }
}

// Run the tests
try {
    $runner = new RealTestRunner();
    $runner->runAllTests();
} catch (Exception $e) {
    echo "\n❌ Test runner failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}