<?php

if (!function_exists('storage_path')) {
    /**
     * Get the path to the storage folder.
     *
     * @param string $path
     * @return string
     */
    function storage_path($path = '')
    {
        $basePath = $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__, 3);
        return $basePath . '/storage' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('app_path')) {
    /**
     * Get the path to the app folder.
     *
     * @param string $path
     * @return string
     */
    function app_path($path = '')
    {
        $basePath = $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__, 3);
        return $basePath . '/app' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('base_path')) {
    /**
     * Get the path to the base of the install.
     *
     * @param string $path
     * @return string
     */
    function base_path($path = '')
    {
        $basePath = $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__, 3);
        return $basePath . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('config_path')) {
    /**
     * Get the path to the config folder.
     *
     * @param string $path
     * @return string
     */
    function config_path($path = '')
    {
        $basePath = $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__, 3);
        return $basePath . '/config' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('public_path')) {
    /**
     * Get the path to the public folder.
     *
     * @param string $path
     * @return string
     */
    function public_path($path = '')
    {
        $basePath = $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__, 3);
        return $basePath . '/public' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('resource_path')) {
    /**
     * Get the path to the resources folder.
     *
     * @param string $path
     * @return string
     */
    function resource_path($path = '')
    {
        $basePath = $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__, 3);
        return $basePath . '/resources' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env($key, $default = null)
    {
        $value = $_ENV[$key] ?? getenv($key);
        
        if ($value === false) {
            return $default;
        }
        
        // Convert string representations of boolean values
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }
        
        // Handle quoted strings
        if (strlen($value) > 1 && $value[0] === '"' && $value[-1] === '"') {
            return substr($value, 1, -1);
        }
        
        return $value;
    }
}

// Modern helper functions for better DX
if (!function_exists('dd')) {
    /**
     * Dump and die - for debugging
     */
    function dd(...$vars)
    {
        echo '<style>pre { background: #1e1e1e; color: #dcdcdc; padding: 15px; border-radius: 5px; overflow: auto; }</style>';
        foreach ($vars as $var) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        }
        die();
    }
}

if (!function_exists('dump')) {
    /**
     * Dump variables for debugging
     */
    function dump(...$vars)
    {
        echo '<style>pre { background: #1e1e1e; color: #dcdcdc; padding: 15px; border-radius: 5px; overflow: auto; margin: 10px 0; }</style>';
        foreach ($vars as $var) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        }
    }
}

if (!function_exists('collect')) {
    /**
     * Create a collection from array
     */
    function collect($items = [])
    {
        return new \Nexa\Support\Collection($items);
    }
}

if (!function_exists('route')) {
    /**
     * Generate URL for named route
     */
    function route($name, $parameters = [])
    {
        // This would need to be implemented with a route resolver
        return "/{$name}" . (!empty($parameters) ? '?' . http_build_query($parameters) : '');
    }
}

if (!function_exists('asset')) {
    /**
     * Generate URL for asset
     */
    function asset($path)
    {
        $basePath = $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__, 3);
        return '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    /**
     * Generate full URL
     */
    function url($path = '')
    {
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . '/' . ltrim($path, '/');
    }
}

if (!function_exists('old')) {
    /**
     * Get old input value
     */
    function old($key, $default = null)
    {
        return $_SESSION['_old_input'][$key] ?? $default;
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Generate CSRF token
     */
    function csrf_token()
    {
        if (!isset($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_token'];
    }
}

if (!function_exists('now')) {
    /**
     * Get current timestamp
     */
    function now()
    {
        return new \DateTime();
    }
}

if (!function_exists('str')) {
    /**
     * String helper
     */
    function str($string = '')
    {
        return new \Nexa\Support\Str($string);
    }
}