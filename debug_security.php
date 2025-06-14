<?php
require_once "tests/bootstrap.php";
try {
    $middleware = new \Nexa\Middleware\SecurityMiddleware();
    $request = new \Nexa\Http\Request();
    $response = $middleware->handle($request, function($req) {
        return new \Nexa\Http\Response("test");
    });
    echo "SecurityMiddleware works fine\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}