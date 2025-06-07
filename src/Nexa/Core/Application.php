<?php

namespace Nexa\Core;

use Nexa\Core\Config;
use Nexa\Core\Logger;
use Nexa\Core\Cache;
use Closure;

class Application
{
    protected $basePath;
    protected $config = [];
    protected $bindings = [];
    protected $instances = [];
    protected $serviceProviders = [];
    protected $booted = false;
    protected $middleware = [];
    
    // Magic methods for fluent API
    protected $magicMethods = [];

    public function __construct($basePath = null)
    {
        if ($basePath) {
            $this->setBasePath($basePath);
            $_ENV['APP_BASE_PATH'] = $this->basePath;
        }

        $this->bootstrap();
    }

    protected function bootstrap(): void
    {
        if ($this->booted) return;
        
        // Zero-config initialization
        $this->autoDiscoverServices()
             ->initializeCore()
             ->registerErrorHandler()
             ->loadConfiguration()
             ->registerServiceProviders();
             
        $this->booted = true;
    }
    
    protected function autoDiscoverServices(): self
    {
        // Auto-discover controllers, models, middleware
        $this->discoverControllers()
             ->discoverModels()
             ->discoverMiddleware();
        return $this;
    }
    
    protected function initializeCore(): self
    {
        $this->initializeConfig()
             ->initializeLogger()
             ->initializeCache();
        return $this;
    }

    protected function registerErrorHandler(): self
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        return $this;
    }

    protected function initializeConfig(): self
    {
        $configPath = $this->basePath . '/config';
        Config::init($configPath);
        return $this;
    }
    
    protected function initializeLogger(): self
    {
        $logPath = $this->basePath . '/storage/logs';
        $minLevel = Config::env('LOG_LEVEL', Logger::INFO);
        Logger::init($logPath, $minLevel);
        return $this;
    }
    
    protected function initializeCache(): self
    {
        $cachePath = $this->basePath . '/storage/cache';
        $prefix = Config::env('CACHE_PREFIX', 'nexa_');
        $defaultTtl = (int) Config::env('CACHE_DEFAULT_TTL', 3600);
        Cache::init($cachePath, $prefix, $defaultTtl);
        return $this;
    }
    
    protected function discoverControllers(): self
    {
        $controllerPath = $this->basePath . '/app/Http/Controllers';
        if (is_dir($controllerPath)) {
            foreach (glob($controllerPath . '/*.php') as $file) {
                $className = 'App\\Http\\Controllers\\' . basename($file, '.php');
                if (class_exists($className)) {
                    $this->bind($className, $className);
                }
            }
        }
        return $this;
    }
    
    protected function discoverModels(): self
    {
        $modelPath = $this->basePath . '/app/Models';
        if (is_dir($modelPath)) {
            foreach (glob($modelPath . '/*.php') as $file) {
                $className = 'App\\Models\\' . basename($file, '.php');
                if (class_exists($className)) {
                    $this->bind($className, $className);
                }
            }
        }
        return $this;
    }
    
    protected function discoverMiddleware(): self
    {
        $middlewarePath = $this->basePath . '/app/Http/Middleware';
        if (is_dir($middlewarePath)) {
            foreach (glob($middlewarePath . '/*.php') as $file) {
                $className = 'App\\Http\\Middleware\\' . basename($file, '.php');
                if (class_exists($className)) {
                    $this->bind($className, $className);
                }
            }
        }
        return $this;
    }

    protected function loadConfiguration(): self
    {
        // Configuration is now handled by Config class
        $this->config = Config::all();
        return $this;
    }

    public function setBasePath($basePath)
    {
        $this->basePath = rtrim($basePath, '\/');
        return $this;
    }

    public function handleError($level, $message, $file = '', $line = 0, $context = [])
    {
        $errorMessage = "Error [{$level}]: {$message} in {$file}:{$line}";
        Logger::error($errorMessage, $context);
        
        // En mode debug, afficher l'erreur
        if (Config::get('app.debug', false)) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
            echo "<strong>Error:</strong> {$errorMessage}";
            echo "</div>";
        }
    }

    public function handleException($exception)
    {
        Logger::exception($exception);
        
        // En mode debug, afficher l'exception complète
        if (Config::get('app.debug', false)) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
            echo "<h3>Exception: " . get_class($exception) . "</h3>";
            echo "<p><strong>Message:</strong> " . $exception->getMessage() . "</p>";
            echo "<p><strong>File:</strong> " . $exception->getFile() . ":" . $exception->getLine() . "</p>";
            echo "<details><summary>Stack Trace</summary><pre>" . $exception->getTraceAsString() . "</pre></details>";
            echo "</div>";
        } else {
            // En production, afficher une page d'erreur générique
            http_response_code(500);
            echo "<h1>500 - Internal Server Error</h1><p>Something went wrong. Please try again later.</p>";
        }
    }

    protected function registerServiceProviders()
    {
        $providers = $this->config['app']['providers'] ?? [];
        
        foreach ($providers as $providerClass) {
            $provider = new $providerClass($this);
            $provider->register();
            $this->serviceProviders[] = $provider;
        }
        
        // Boot all providers
        foreach ($this->serviceProviders as $provider) {
            $provider->boot();
        }
    }

    public function bind($abstract, $concrete = null)
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }
        
        $this->bindings[$abstract] = $concrete;
    }

    public function singleton($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete);
        $this->instances[$abstract] = null;
    }

    public function make($abstract)
    {
        if (isset($this->instances[$abstract]) && $this->instances[$abstract] !== null) {
            return $this->instances[$abstract];
        }
        
        if (isset($this->bindings[$abstract])) {
            $concrete = $this->bindings[$abstract];
            
            if (is_callable($concrete)) {
                $instance = $concrete();
            } else {
                $instance = new $concrete();
            }
            
            if (array_key_exists($abstract, $this->instances)) {
                $this->instances[$abstract] = $instance;
            }
            
            return $instance;
        }
        
        return new $abstract();
    }

    public function basePath($path = '')
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    public function run()
    {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            
            // Load routes with hot-reload support
            $webRouter = $this->loadRoutesWithHotReload();
            
            // Dispatch the request
            return $webRouter->dispatch($method, $uri);
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
    
    protected function loadRoutesWithHotReload()
    {
        $routeFile = $this->basePath . '/routes/web.php';
        
        // In development, check for file changes
        if (Config::get('app.debug', false)) {
            $cacheKey = 'routes_' . md5($routeFile);
            $lastModified = filemtime($routeFile);
            
            if (Cache::get($cacheKey) !== $lastModified) {
                Cache::forget('compiled_routes');
                Cache::put($cacheKey, $lastModified);
            }
        }
        
        return require $routeFile;
    }
    
    // Magic methods for fluent API
    public function __call($method, $arguments)
    {
        // Allow chaining of configuration methods
        if (strpos($method, 'with') === 0) {
            $property = lcfirst(substr($method, 4));
            if (property_exists($this, $property)) {
                $this->$property = $arguments[0] ?? true;
                return $this;
            }
        }
        
        // Custom magic methods
        if (isset($this->magicMethods[$method])) {
            return call_user_func_array($this->magicMethods[$method], $arguments);
        }
        
        throw new \BadMethodCallException("Method {$method} does not exist.");
    }
    
    public function macro($name, Closure $callback)
    {
        $this->magicMethods[$name] = $callback;
        return $this;
    }
    
    // Developer Experience improvements
    public function enableHotReload()
    {
        if (Config::get('app.debug', false)) {
            // Enable file watching for auto-reload
            $this->config['hot_reload'] = true;
        }
        return $this;
    }
    
    public function enableSmartErrors()
    {
        $this->config['smart_errors'] = true;
        return $this;
    }
}