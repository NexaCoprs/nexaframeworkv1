<?php
require_once __DIR__ . '/src/Nexa/Database/Column.php';
require_once __DIR__ . '/src/Nexa/Database/Schema.php';
require_once __DIR__ . '/src/Nexa/Database/Blueprint.php';
require_once __DIR__ . '/src/Nexa/Database/Model.php';
require_once __DIR__ . '/app/Models/User.php';

use Nexa\Database\Schema;
use Nexa\Database\Model;
use App\Models\User;

try {
    // Create database connection directly with PDO
    $connection = new PDO('sqlite:' . __DIR__ . '/test_simple.db');
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set the connection for Model
    Model::setConnection($connection);
    
    // Create schema
    $schema = new Schema($connection);
    
    // Drop table if exists
    $connection->exec('DROP TABLE IF EXISTS users');
    
    // Create users table
    $schema->create('users', function($table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamps();
    });
    
    echo "Table created successfully!\n";
    
    // Test inserting a user
    $user = new User();
    $user->fill([
        'name' => 'Test User',
        'email' => 'test@example.com'
    ]);
    
    $user->save();
    
    echo "User created successfully with ID: " . $user->id . "\n";
    
    // Test finding the user
    $foundUser = User::find(1);
    if ($foundUser) {
        echo "User found: " . $foundUser->name . " (" . $foundUser->email . ")\n";
    }
    
    echo "All tests passed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}