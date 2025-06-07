<?php

namespace Nexa\Routing;

use Closure;

class Router
{
    protected $routes = [];
    protected $currentGroup = [];
    protected $middleware = [];
    protected $namedRoutes = [];
    protected $resourceRoutes = [];
    protected $config = [];

    // Modern HTTP methods
    public function get($uri, $action)
    {
        return $this->addRoute('GET', $uri, $action);
    }

    public function post($uri, $action)
    {
        return $this->addRoute('POST', $uri, $action);
    }

    public function put($uri, $action)
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    public function patch($uri, $action)
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    public function delete($uri, $action)
    {
        return $this->addRoute('DELETE', $uri, $action);
    }
    
    // Modern route methods
    public function any($uri, $action)
    {
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
        foreach ($methods as $method) {
            $this->addRoute($method, $uri, $action);
        }
        return $this;
    }
    
    public function match(array $methods, $uri, $action)
    {
        foreach ($methods as $method) {
            $this->addRoute(strtoupper($method), $uri, $action);
        }
        return $this;
    }
    
    // Resource routing (RESTful)
    public function resource($name, $controller)
    {
        $this->get($name, [$controller, 'index'])->name($name . '.index');
        $this->get($name . '/create', [$controller, 'create'])->name($name . '.create');
        $this->post($name, [$controller, 'store'])->name($name . '.store');
        $this->get($name . '/{id}', [$controller, 'show'])->name($name . '.show');
        $this->get($name . '/{id}/edit', [$controller, 'edit'])->name($name . '.edit');
        $this->put($name . '/{id}', [$controller, 'update'])->name($name . '.update');
        $this->delete($name . '/{id}', [$controller, 'destroy'])->name($name . '.destroy');
        return $this;
    }
    
    // API Resource routing
    public function apiResource($name, $controller)
    {
        $this->get($name, [$controller, 'index'])->name($name . '.index');
        $this->post($name, [$controller, 'store'])->name($name . '.store');
        $this->get($name . '/{id}', [$controller, 'show'])->name($name . '.show');
        $this->put($name . '/{id}', [$controller, 'update'])->name($name . '.update');
        $this->delete($name . '/{id}', [$controller, 'destroy'])->name($name . '.destroy');
        return $this;
    }

    public function group(array $attributes, \Closure $callback)
    {
        $this->currentGroup[] = $attributes;

        $callback($this);

        array_pop($this->currentGroup);
    }

    protected function addRoute($method, $uri, $action)
    {
        $route = $this->createRoute($method, $uri, $action);
        $this->routes[$method][] = $route;
        return $route;
    }

    protected function createRoute($method, $uri, $action)
    {
        $uri = $this->prefixUri($uri);
        $route = new Route($method, $uri, $action);
        
        // Apply current group middleware
        if (!empty($this->currentGroup)) {
            foreach ($this->currentGroup as $group) {
                if (isset($group['middleware'])) {
                    $route->middleware($group['middleware']);
                }
                if (isset($group['namespace'])) {
                    $route->namespace($group['namespace']);
                }
            }
        }
        
        return $route;
    }
    
    // Named routes
    public function name($name)
    {
        if (!empty($this->routes)) {
            $lastMethod = array_key_last($this->routes);
            $lastRoute = end($this->routes[$lastMethod]);
            if ($lastRoute instanceof Route) {
                $lastRoute->name($name);
                $this->namedRoutes[$name] = $lastRoute;
            }
        }
        return $this;
    }
    
    // Middleware support
    public function middleware($middleware)
    {
        if (!empty($this->routes)) {
            $lastMethod = array_key_last($this->routes);
            $lastRoute = end($this->routes[$lastMethod]);
            if ($lastRoute instanceof Route) {
                $lastRoute->middleware($middleware);
            }
        }
        return $this;
    }
    
    // Route caching for performance
    public function cache($enable = true)
    {
        $this->config['cache_routes'] = $enable;
        return $this;
    }

    protected function prefixUri($uri)
    {
        if (empty($this->currentGroup)) {
            return $uri;
        }

        $prefix = '';

        foreach ($this->currentGroup as $group) {
            if (isset($group['prefix'])) {
                $prefix = trim($group['prefix'], '/') . '/' . trim($prefix, '/');
            }
        }

        $prefix = trim($prefix, '/');
        $uri = trim($uri, '/');
        
        if (empty($prefix)) {
            return $uri ?: '/';
        }
        
        if (empty($uri)) {
            return '/' . $prefix;  // Ajout du slash initial
        }
        
        return '/' . $prefix . '/' . $uri;  // Ajout du slash initial
    }

    public function dispatch($method, $uri)
    {
        if (!isset($this->routes[$method])) {
            throw new \Exception("Route not found for method: $method");
        }

        foreach ($this->routes[$method] as $route) {
            if ($route->matches($uri)) {
                return $route->run();
            }
        }

        throw new \Exception("Route not found for URI: $uri");
    }

    /**
     * Merge routes from another router instance
     *
     * @param Router $router
     * @return void
     */
    public function mergeRouters(Router $router)
    {
        $otherRoutes = $router->getRoutes();
        
        foreach ($otherRoutes as $method => $routes) {
            if (!isset($this->routes[$method])) {
                $this->routes[$method] = [];
            }
            
            $this->routes[$method] = array_merge($this->routes[$method], $routes);
        }
    }

    /**
     * Get all routes
     *
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }
}