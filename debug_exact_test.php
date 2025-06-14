<?php
require_once "tests/bootstrap.php";

echo "Testing SecurityMiddleware with exact same conditions as MiddlewareTest...\n";

try {
    // Set up globals like in createMockRequest
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/test';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_GET = [];
    $_POST = [];
    
    $middleware = new \Nexa\Middleware\SecurityMiddleware();
    $request = new \Nexa\Http\Request();
    
    echo "Request created successfully\n";
    
    // Use the exact same callback as in MiddlewareTest
    $response = $middleware->handle($request, function($req) { return $req; });
    
    echo "SecurityMiddleware works fine with exact test conditions\n";
    echo "Response type: " . gettype($response) . "\n";
    if (is_object($response)) {
        echo "Response class: " . get_class($response) . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}