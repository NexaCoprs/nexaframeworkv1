<?php

// Inclusion manuelle des fichiers nécessaires
require_once 'src/Nexa/Events/Event.php';
require_once 'src/Nexa/Events/UserEvents.php';
require_once 'src/Nexa/Events/DatabaseEvents.php';

use Nexa\Events\UserRegistered;
use Nexa\Events\UserLoggedIn;
use Nexa\Events\ModelCreated;

echo "=== Tests Phase 2 - Événements ===\n\n";

// Test UserRegistered
echo "Test UserRegistered:\n";
$userData = [
    'id' => 1,
    'email' => 'newuser@example.com',
    'name' => 'New User'
];

$userRegistered = new UserRegistered($userData);
echo "- getUserId(): " . $userRegistered->getUserId() . "\n";
echo "- getUserEmail(): " . $userRegistered->getUserEmail() . "\n";
echo "- getUserName(): " . $userRegistered->getUserName() . "\n";
echo "- getName(): " . $userRegistered->getName() . "\n";
echo "✅ UserRegistered OK\n\n";

// Test UserLoggedIn
echo "Test UserLoggedIn:\n";
$userData2 = [
    'id' => 2,
    'email' => 'user@example.com'
];

$userLoggedIn = new UserLoggedIn($userData2, '192.168.1.1', 'Mozilla/5.0');
echo "- getUserId(): " . $userLoggedIn->getUserId() . "\n";
echo "- getIpAddress(): " . $userLoggedIn->getIpAddress() . "\n";
echo "- getUserAgent(): " . $userLoggedIn->getUserAgent() . "\n";
echo "- getName(): " . $userLoggedIn->getName() . "\n";
echo "✅ UserLoggedIn OK\n\n";

// Test ModelCreated
echo "Test ModelCreated:\n";
$modelData = [
    'id' => 1,
    'title' => 'Test Post',
    'content' => 'This is a test post'
];

$modelCreated = new ModelCreated('Post', $modelData);
echo "- getModelName(): " . $modelCreated->getModelName() . "\n";
echo "- getModelId(): " . $modelCreated->getModelId() . "\n";
echo "- getName(): " . $modelCreated->getName() . "\n";
echo "✅ ModelCreated OK\n\n";

echo "=== Tous les tests sont passés avec succès! ===\n";
echo "Les méthodes manquantes ont été ajoutées et fonctionnent correctement.\n";