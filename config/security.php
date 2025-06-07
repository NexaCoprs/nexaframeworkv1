<?php

return [
    // Configuration de sécurité pour Nexa Framework
    
    'encryption' => [
        'key' => env('APP_KEY', 'base64:' . base64_encode(random_bytes(32))),
        'cipher' => 'AES-256-CBC',
    ],
    
    'password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => false,
    ],
    
    'session' => [
        'lifetime' => 120, // minutes
        'secure' => env('APP_ENV') === 'production',
        'http_only' => true,
        'same_site' => 'strict',
    ],
    
    // Protection CSRF
    'csrf' => [
        'enabled' => true,
        'exclude_api' => true,
        'token_name' => '_token',
        'header_name' => 'X-CSRF-TOKEN',
    ],
    
    // Protection XSS
    'xss' => [
        'enabled' => true,
        'auto_clean' => true,
        'allow_html' => false,
    ],
    
    // Limitation de taux
    'rate_limiting' => [
        'enabled' => true,
        'max_attempts' => 60,
        'decay_minutes' => 1,
        'storage' => 'file', // file, database, redis
    ],
    
    // Headers de sécurité
    'headers' => [
        'x_frame_options' => 'DENY',
        'x_content_type_options' => 'nosniff',
        'x_xss_protection' => '1; mode=block',
        'referrer_policy' => 'strict-origin-when-cross-origin',
        'content_security_policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';",
    ],
];