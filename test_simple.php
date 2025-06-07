<?php

require_once 'vendor/autoload.php';

use PDO;
use Nexa\Database\Schema;
use Nexa\Database\Blueprint;

try {
    // Create database connection
    $pdo = new PDO('sqlite:test.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    $schema = new Schema($pdo);
    
    echo "Creating simple table...\n";
    
    $schema->create('simple_test', function (Blueprint $table) {
        $table->id();
        $table->string('name');
    });
    
    echo "Table created successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}