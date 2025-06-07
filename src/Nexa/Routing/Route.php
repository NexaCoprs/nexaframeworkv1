<?php

namespace Nexa\Routing;

use Nexa\Http\Request;
use Closure;

class Route
{
    protected $method;
    protected $uri;
    protected $action;
    protected $parameters = [];
    protected $middleware = [];
    protected $name;
    protected $namespace;
    protected $where = [];
    protected $defaults = [];

    public function __construct($method, $uri, $action)
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->action = $action;
    }
    
    // Fluent API methods
    public function name($name)
    {
        $this->name = $name;
        return $this;
    }
    
    public function middleware($middleware)
    {
        if (is_string($middleware)) {
            $this->middleware[] = $middleware;
        } elseif (is_array($middleware)) {
            $this->middleware = array_merge($this->middleware, $middleware);
        }
        return $this;
    }
    
    public function namespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }
    
    public function where($parameter, $pattern = null)
    {
        if (is_array($parameter)) {
            $this->where = array_merge($this->where, $parameter);
        } else {
            $this->where[$parameter] = $pattern;
        }
        return $this;
    }
    
    public function defaults($key, $value = null)
    {
        if (is_array($key)) {
            $this->defaults = array_merge($this->defaults, $key);
        } else {
            $this->defaults[$key] = $value;
        }
        return $this;
    }

    public function matches($uri)
    {
        // Extract parameter names from URI
        preg_match_all('/\{([^}]+)\}/', $this->uri, $paramNames);
        $paramNames = $paramNames[1];
        
        // Build pattern with constraints
        $pattern = $this->uri;
        foreach ($paramNames as $paramName) {
            $constraint = $this->where[$paramName] ?? '[^/]+';
            $pattern = str_replace('{' . $paramName . '}', '(' . $constraint . ')', $pattern);
        }
        
        $pattern = str_replace('/', '\/', $pattern);
        $pattern = '/^' . $pattern . '$/';
        
        if (preg_match($pattern, $uri, $matches)) {
            array_shift($matches);
            
            // Combine parameter names with values
            $this->parameters = [];
            foreach ($paramNames as $index => $name) {
                $this->parameters[$name] = $matches[$index] ?? $this->defaults[$name] ?? null;
            }
            
            return true;
        }
        
        return false;
    }

    public function run()
    {
        // Execute middleware before running the route
        $this->runMiddleware();
        
        if (is_string($this->action)) {
            return $this->runController($this->action);
        }
        
        if (is_array($this->action) && count($this->action) === 2) {
            list($class, $method) = $this->action;
            return $this->runControllerArray($class, $method);
        }
        
        if (is_callable($this->action)) {
            return call_user_func($this->action, ...$this->parameters);
        }
        
        throw new \Exception("Invalid route action");
    }
    
    protected function runMiddleware()
    {
        foreach ($this->middleware as $middleware) {
            if (is_string($middleware) && class_exists($middleware)) {
                $instance = new $middleware();
                if (method_exists($instance, 'handle')) {
                    $instance->handle();
                }
            } elseif (is_callable($middleware)) {
                call_user_func($middleware);
            }
        }
    }

    protected function runController($controller)
    {
        list($class, $method) = explode('@', $controller);
        
        if (!class_exists($class)) {
            throw new \Exception("Controller {$class} not found");
        }
        
        $instance = new $class;
        
        if (!method_exists($instance, $method)) {
            throw new \Exception("Method {$method} not found in controller {$class}");
        }
        
        return $instance->$method(...array_values($this->parameters));
    }
    
    protected function runControllerArray($class, $method)
    {
        if (!class_exists($class)) {
            throw new \Exception("Controller {$class} not found");
        }
        
        $instance = new $class;
        
        if (!method_exists($instance, $method)) {
            throw new \Exception("Method {$method} not found in controller {$class}");
        }
        
        // Create Request instance and inject it as the first parameter
        $request = Request::capture();
        $parameters = array_merge([$request], array_values($this->parameters));
        
        return $instance->$method(...$parameters);
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function getMethod()
    {
        return $this->method;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getMiddleware()
    {
        return $this->middleware;
    }
    
    public function getParameters()
    {
        return $this->parameters;
    }
    
    public function getNamespace()
    {
        return $this->namespace;
    }
    
    public function getWhere()
    {
        return $this->where;
    }
    
    public function getDefaults()
    {
        return $this->defaults;
    }
}