#!/usr/bin/env php
<?php

/**
 * Nexa Framework Test Runner
 * 
 * This script runs all tests for the Nexa Framework, including Phase 3 components.
 * 
 * Usage:
 *   php run_tests.php [options]
 * 
 * Options:
 *   --phase3     Run only Phase 3 tests
 *   --all        Run all tests (default)
 *   --report     Generate detailed test report
 *   --validate   Validate Phase 3 requirements only
 *   --help       Show this help message
 */

require_once __DIR__ . '/vendor/autoload.php';

// Parse command line arguments
$options = getopt('', ['phase3', 'all', 'report', 'validate', 'help']);

if (isset($options['help'])) {
    showHelp();
    exit(0);
}

echo "\n";
echo "███╗   ██╗███████╗██╗  ██╗ █████╗     ████████╗███████╗███████╗████████╗███████╗\n";
echo "████╗  ██║██╔════╝╚██╗██╔╝██╔══██╗    ╚══██╔══╝██╔════╝██╔════╝╚══██╔══╝██╔════╝\n";
echo "██╔██╗ ██║█████╗   ╚███╔╝ ███████║       ██║   █████╗  ███████╗   ██║   ███████╗\n";
echo "██║╚██╗██║██╔══╝   ██╔██╗ ██╔══██║       ██║   ██╔══╝  ╚════██║   ██║   ╚════██║\n";
echo "██║ ╚████║███████╗██╔╝ ██╗██║  ██║       ██║   ███████╗███████║   ██║   ███████║\n";
echo "╚═╝  ╚═══╝╚══════╝╚═╝  ╚═╝╚═╝  ╚═╝       ╚═╝   ╚══════╝╚══════╝   ╚═╝   ╚══════╝\n";
echo "\n";
echo "Nexa Framework Test Suite\n";
echo "Version: 3.0.0 (Phase 3)\n";
echo "\n";

try {
    // Initialize test environment
    initializeTestEnvironment();
    
    if (isset($options['validate'])) {
        runValidationOnly();
    } elseif (isset($options['phase3'])) {
        runPhase3Tests($options);
    } else {
        runAllTests($options);
    }
    
} catch (Exception $e) {
    echo "\n❌ Test execution failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

function showHelp()
{
    echo "\nNexa Framework Test Runner\n";
    echo "=========================\n\n";
    echo "Usage: php run_tests.php [options]\n\n";
    echo "Options:\n";
    echo "  --phase3     Run only Phase 3 tests\n";
    echo "  --all        Run all tests (default)\n";
    echo "  --report     Generate detailed test report\n";
    echo "  --validate   Validate Phase 3 requirements only\n";
    echo "  --help       Show this help message\n\n";
    echo "Examples:\n";
    echo "  php run_tests.php                    # Run all tests\n";
    echo "  php run_tests.php --phase3           # Run Phase 3 tests only\n";
    echo "  php run_tests.php --phase3 --report  # Run Phase 3 tests with report\n";
    echo "  php run_tests.php --validate         # Validate requirements only\n\n";
}

function initializeTestEnvironment()
{
    echo "🔧 Initializing test environment...\n";
    
    // Set test environment variables
    $_ENV['APP_ENV'] = 'testing';
    $_ENV['APP_DEBUG'] = 'true';
    $_ENV['JWT_SECRET'] = 'test_secret_key_for_testing_purposes_only';
    
    // Create test directories if they don't exist
    $testDirs = [
        __DIR__ . '/storage/logs',
        __DIR__ . '/storage/cache',
        __DIR__ . '/tests/fixtures'
    ];
    
    foreach ($testDirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo "  📁 Created directory: {$dir}\n";
        }
    }
    
    echo "✅ Test environment initialized\n\n";
}

function runValidationOnly()
{
    echo "🔍 Validating Phase 3 requirements...\n\n";
    
    $requirements = [
        'Plugin System' => [
            'src/Plugins/Plugin.php',
            'src/Plugins/PluginManager.php',
            'config/plugins.php'
        ],
        'Module System' => [
            'src/Modules/Module.php',
            'src/Modules/ModuleManager.php',
            'config/modules.php'
        ],
        'GraphQL System' => [
            'src/GraphQL/GraphQLManager.php',
            'src/GraphQL/Type.php',
            'src/GraphQL/Query.php',
            'src/GraphQL/Mutation.php',
            'config/graphql.php'
        ],
        'WebSocket System' => [
            'src/WebSockets/WebSocketServer.php',
            'src/WebSockets/WebSocketClient.php',
            'config/websockets.php'
        ],
        'Microservice System' => [
            'src/Microservices/ServiceRegistry.php',
            'src/Microservices/ServiceClient.php',
            'config/microservices.php'
        ]
    ];
    
    $allValid = true;
    
    foreach ($requirements as $system => $files) {
        echo "{$system}:\n";
        foreach ($files as $file) {
            $fullPath = __DIR__ . '/' . $file;
            if (file_exists($fullPath)) {
                echo "  ✅ " . basename($file) . " exists\n";
            } else {
                echo "  ❌ " . basename($file) . " missing\n";
                $allValid = false;
            }
        }
        echo "\n";
    }
    
    if ($allValid) {
        echo "✅ All Phase 3 requirements are met!\n";
        exit(0);
    } else {
        echo "❌ Some requirements are missing\n";
        exit(1);
    }
}

function runPhase3Tests($options)
{
    echo "🚀 Running Phase 3 tests...\n";
    
    // Validate requirements first
    $requirementsValid = validateRequirements();
    
    if (!$requirementsValid) {
        echo "\n⚠️  Some requirements are not met, but continuing with tests...\n";
    }
    
    // Run the tests
     require_once __DIR__ . '/tests/SimpleTestSuite.php';
     $testSuite = new Tests\SimpleTestSuite();
     $result = $testSuite->runAllTests();
    
    // Generate report if requested
    if (isset($options['report'])) {
        $reportPath = __DIR__ . '/storage/test_report_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($reportPath, json_encode($testSuite->getTestResults(), JSON_PRETTY_PRINT));
        echo "\n📊 Test report generated: {$reportPath}\n";
    }
    
    if ($result) {
        echo "\n🎉 All Phase 3 tests passed!\n";
        exit(0);
    } else {
        echo "\n❌ Some Phase 3 tests failed. Please check the implementation.\n";
        exit(1);
    }
}

function validateRequirements()
{
    $requirements = [
        'Plugin System' => [
            'src/Plugins/Plugin.php',
            'src/Plugins/PluginManager.php',
            'config/plugins.php'
        ],
        'Module System' => [
            'src/Modules/Module.php',
            'src/Modules/ModuleManager.php',
            'config/modules.php'
        ],
        'GraphQL System' => [
            'src/GraphQL/GraphQLManager.php',
            'src/GraphQL/Type.php',
            'src/GraphQL/Query.php',
            'src/GraphQL/Mutation.php',
            'config/graphql.php'
        ],
        'WebSocket System' => [
            'src/WebSockets/WebSocketServer.php',
            'src/WebSockets/WebSocketClient.php',
            'config/websockets.php'
        ],
        'Microservice System' => [
            'src/Microservices/ServiceRegistry.php',
            'src/Microservices/ServiceClient.php',
            'config/microservices.php'
        ]
    ];
    
    $allValid = true;
    
    foreach ($requirements as $system => $files) {
        echo "{$system}:\n";
        foreach ($files as $file) {
            $fullPath = __DIR__ . '/' . $file;
            if (file_exists($fullPath)) {
                echo "  ✅ " . basename($file) . " exists\n";
            } else {
                echo "  ❌ " . basename($file) . " missing\n";
                $allValid = false;
            }
        }
        echo "\n";
    }
    
    return $allValid;
}

function runAllTests($options)
{
    echo "🚀 Running all framework tests...\n";
    
    $testClasses = [
        // Phase 1 & 2 tests
        'AuthTest' => 'Authentication System',
        'EventTest' => 'Event System',
        'QueueTest' => 'Queue System',
        
        // Phase 3 tests
        'PluginTest' => 'Plugin System',
        'ModuleTest' => 'Module System',
        'GraphQLTest' => 'GraphQL System',
        'WebSocketTest' => 'WebSocket System',
        'MicroserviceTest' => 'Microservice System'
    ];
    
    $totalPassed = 0;
    $totalTests = 0;
    $allResults = [];
    
    foreach ($testClasses as $className => $description) {
        echo "\n--- Testing {$description} ---\n";
        
        $fullClassName = "Tests\\{$className}";
        
        if (!class_exists($fullClassName)) {
            echo "⚠️  Test class {$fullClassName} not found, skipping...\n";
            continue;
        }
        
        try {
            $testInstance = new $fullClassName();
            $methods = get_class_methods($testInstance);
            
            $classPassed = 0;
            $classTotal = 0;
            
            foreach ($methods as $method) {
                if (strpos($method, 'test') === 0) {
                    $classTotal++;
                    $totalTests++;
                    
                    try {
                        if (method_exists($testInstance, 'setUp')) {
                            $testInstance->setUp();
                        }
                        
                        $testInstance->$method();
                        
                        if (method_exists($testInstance, 'tearDown')) {
                            $testInstance->tearDown();
                        }
                        
                        echo "✅ {$method}\n";
                        $classPassed++;
                        $totalPassed++;
                        
                    } catch (Exception $e) {
                        echo "❌ {$method}: {$e->getMessage()}\n";
                        $allResults[] = [
                            'class' => $className,
                            'method' => $method,
                            'error' => $e->getMessage()
                        ];
                    }
                }
            }
            
            echo "Class Summary: {$classPassed}/{$classTotal} tests passed\n";
            
        } catch (Exception $e) {
            echo "❌ Failed to run {$className}: {$e->getMessage()}\n";
        }
    }
    
    // Display final summary
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "COMPLETE TEST SUITE SUMMARY\n";
    echo str_repeat("=", 60) . "\n";
    echo "Total Tests: {$totalTests}\n";
    echo "Passed: {$totalPassed}\n";
    echo "Failed: " . ($totalTests - $totalPassed) . "\n";
    
    $successRate = $totalTests > 0 ? round(($totalPassed / $totalTests) * 100, 2) : 0;
    echo "Success Rate: {$successRate}%\n";
    
    // Run Phase 3 integration tests if all basic tests pass
    if ($totalPassed === $totalTests) {
        echo "\n🔗 Running Phase 3 integration tests...\n";
        require_once __DIR__ . '/tests/Phase3TestSuite.php';
        $testSuite = new Tests\Phase3TestSuite();
        $testSuite->testPhase3Integration();
    }
    
    // Generate report if requested
    if (isset($options['report'])) {
        generateCompleteReport($totalTests, $totalPassed, $allResults);
    }
    
    if ($totalPassed === $totalTests) {
        echo "\n🎉 ALL TESTS PASSED! Nexa Framework is working perfectly!\n";
        exit(0);
    } else {
        echo "\n⚠️  Some tests failed. Please review the implementation.\n";
        
        // Display failed test details
        if (!empty($allResults)) {
            echo "\n📋 FAILED TESTS DETAILS:\n";
            echo str_repeat("-", 60) . "\n";
            foreach ($allResults as $result) {
                echo "❌ {$result['class']}::{$result['method']}\n";
                echo "   Error: {$result['error']}\n\n";
            }
        }
        
        exit(1);
    }
}

function generateCompleteReport($totalTests, $totalPassed, $results)
{
    echo "\n📊 Generating complete test report...\n";
    
    $report = [
        'timestamp' => date('Y-m-d H:i:s'),
        'framework_version' => '3.0.0',
        'test_type' => 'Complete Test Suite',
        'total_tests' => $totalTests,
        'passed_tests' => $totalPassed,
        'failed_tests' => $totalTests - $totalPassed,
        'success_rate' => $totalTests > 0 ? round(($totalPassed / $totalTests) * 100, 2) : 0,
        'failed_test_details' => $results,
        'components_tested' => [
            'Authentication System',
            'Event System',
            'Queue System',
            'Plugin System',
            'Module System',
            'GraphQL System',
            'WebSocket System',
            'Microservice System'
        ]
    ];
    
    $reportPath = __DIR__ . '/storage/logs/complete_test_report.json';
    file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
    
    echo "📄 Complete test report saved to: {$reportPath}\n";
}

echo "\n";