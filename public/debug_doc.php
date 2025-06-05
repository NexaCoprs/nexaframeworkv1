<?php
require __DIR__.'/../vendor/autoload.php';

use Nexa\Core\Application;

try {
    echo "Starting debug...\n";
    $app = new Application(__DIR__.'/..');
    echo "Application created...\n";
    
    // Simulate documentation request
    $_SERVER['REQUEST_URI'] = '/documentation';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    echo "Running application...\n";
    $app->run();
    echo "Application finished...\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>