<?php

if (!function_exists('cache')) {
    function cache($key = null, $value = null, $ttl = null)
    {
        $cache = app('cache');
        
        if ($key === null) {
            return $cache;
        }
        
        if ($value === null) {
            return $cache->get($key);
        }
        
        return $cache->put($key, $value, $ttl ?? 3600);
    }
}

if (!function_exists('request')) {
    function request()
    {
        return app('request') ?? new \Nexa\Http\Request();
    }
}

if (!function_exists('app')) {
    function app($abstract = null)
    {
        static $container = null;
        
        if ($container === null) {
            $container = new class {
                private $bindings = [];
                
                public function bind($abstract, $concrete)
                {
                    $this->bindings[$abstract] = $concrete;
                }
                
                public function get($abstract)
                {
                    if (isset($this->bindings[$abstract])) {
                        return $this->bindings[$abstract];
                    }
                    
                    // Default implementations
                    switch ($abstract) {
                        case 'cache':
                            return new \Nexa\Cache\CacheManager();
                        case 'request':
                            return new \Nexa\Http\Request();
                        default:
                            return null;
                    }
                }
            };
        }
        
        if ($abstract === null) {
            return $container;
        }
        
        return $container->get($abstract);
    }
}