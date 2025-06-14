<?php
/**
 * Simple Test Runner for Nexa Framework
 * Runs tests without full framework initialization
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('UTC');

// Define constants only if not already defined
if (!defined('NEXA_ENV')) {
    define('NEXA_ENV', 'testing');
}
if (!defined('NEXA_ROOT')) {
    define('NEXA_ROOT', __DIR__);
}
if (!defined('NEXA_TESTS')) {
    define('NEXA_TESTS', __DIR__ . '/tests');
}

// Load bootstrap first to avoid conflicts
if (file_exists(NEXA_TESTS . '/bootstrap.php')) {
    require_once NEXA_TESTS . '/bootstrap.php';
}

echo "\n=== NEXA FRAMEWORK TEST RUNNER ===\n\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Test Environment: " . NEXA_ENV . "\n";
echo "Framework Root: " . NEXA_ROOT . "\n\n";

// Function to run a test file
function runTestFile($testFile)
{
    echo "\n=== Running " . basename($testFile) . " ===\n";
    
    try {
        // Capture output
        ob_start();
        include $testFile;
        $output = ob_get_clean();
        
        echo $output;
        echo "✅ Test completed successfully\n";
        return true;
        
    } catch (Exception $e) {
        ob_end_clean();
        echo "❌ Test failed: " . $e->getMessage() . "\n";
        echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        return false;
    } catch (Error $e) {
        ob_end_clean();
        echo "❌ Fatal error: " . $e->getMessage() . "\n";
        echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        return false;
    }
}

// Test files to run
$testFiles = [
    NEXA_TESTS . '/Unit/RouterTest.php',
    NEXA_TESTS . '/Unit/ModelTest.php',
    NEXA_TESTS . '/Unit/MiddlewareTest.php',
    NEXA_TESTS . '/Unit/AdvancedComponentsTest.php',
    NEXA_TESTS . '/Integration/FrameworkIntegrationTest.php',
    NEXA_TESTS . '/Feature/UserManagementTest.php',
    NEXA_TESTS . '/Performance/FrameworkPerformanceTest.php'
];

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

// Run all tests
foreach ($testFiles as $testFile) {
    if (file_exists($testFile)) {
        $totalTests++;
        if (runTestFile($testFile)) {
            $passedTests++;
        } else {
            $failedTests++;
        }
    } else {
        echo "⚠ Test file not found: " . basename($testFile) . "\n";
    }
}

// Summary
echo "\n=== TEST SUMMARY ===\n";
echo "Total Tests: $totalTests\n";
echo "Passed: $passedTests\n";
echo "Failed: $failedTests\n";

if ($failedTests === 0) {
    echo "\n🎉 All tests passed!\n";
} else {
    echo "\n⚠ Some tests failed. Please review the output above.\n";
}

echo "\n=== TEST RUN COMPLETED ===\n\n";