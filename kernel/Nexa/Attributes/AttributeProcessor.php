<?php

namespace Nexa\Attributes;

use ReflectionClass;
use ReflectionMethod;
use Nexa\Validation\Validator;
use Nexa\Core\Cache;
use Nexa\Attributes\Quantum;
use Nexa\Attributes\Secure;

class AttributeProcessor
{
    public static function processMethod(object $instance, string $method, array $args = [])
    {
        $reflection = new ReflectionMethod($instance, $method);
        $attributes = $reflection->getAttributes();
        
        $result = null;
        $cacheKey = null;
        $cacheTtl = null;
        
        // Process validation first
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === Validate::class) {
                $validateAttr = $attribute->newInstance();
                self::validateRequest($validateAttr->getRules(), $validateAttr->getMessages());
            }
        }
        
        // Check for cache
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === Cache::class) {
                $cacheAttr = $attribute->newInstance();
                $cacheKey = $cacheAttr->getKey();
                $cacheTtl = $cacheAttr->getTtl();
                
                // Try to get from cache
                $cached = cache()->get($cacheKey);
                if ($cached !== null) {
                    return $cached;
                }
                break;
            }
        }
        
        // Execute the method
        $result = $reflection->invokeArgs($instance, $args);
        
        // Store in cache if cache attribute was found
        if ($cacheKey && $result !== null) {
            cache()->put($cacheKey, $result, $cacheTtl);
        }
        
        return $result;
    }
    
    public static function processClass(string $className)
    {
        $reflection = new ReflectionClass($className);
        $attributes = $reflection->getAttributes();
        
        $metadata = [
            'routes' => [],
            'api' => [],
            'cache' => [],
            'validation' => [],
            'quantum' => [],
            'security' => []
        ];
        
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            
            switch ($attribute->getName()) {
                case Route::class:
                    $metadata['routes'][] = [
                        'method' => $instance->getMethod(),
                        'path' => $instance->getPath(),
                        'prefix' => $instance->getPrefix(),
                        'middleware' => $instance->getMiddleware()
                    ];
                    break;
                    
                case API::class:
                    $metadata['api'][] = [
                        'version' => $instance->getVersion(),
                        'summary' => $instance->getSummary(),
                        'tags' => $instance->getTags(),
                        'documentation' => $instance->hasDocumentation()
                    ];
                    break;
                    
                case Cache::class:
                    $metadata['cache'][] = [
                        'key' => $instance->getKey(),
                        'ttl' => $instance->getTtl(),
                        'tags' => $instance->getTags()
                    ];
                    break;
                    
                case Quantum::class:
                    $metadata['quantum'][] = [
                        'enabled' => $instance->isEnabled(),
                        'optimization' => $instance->getOptimization(),
                        'priority' => $instance->getPriority(),
                        'metrics' => $instance->getMetrics()
                    ];
                    break;
                    
                case Secure::class:
                    $metadata['security'][] = [
                        'encryption' => $instance->hasEncryption(),
                        'level' => $instance->getLevel(),
                        'permissions' => $instance->getPermissions(),
                        'audit' => $instance->hasAudit(),
                        'algorithm' => $instance->getAlgorithm()
                    ];
                    break;
            }
        }
        
        return $metadata;
    }
    
    private static function validateRequest(array $rules, array $messages = [])
    {
        if (empty($rules)) {
            return;
        }
        
        $request = request();
        $data = $request->all();
        
        $validator = Validator::make($data, $rules, $messages);
        
        if (!$validator->validate()) {
            throw new \Exception('Validation failed: ' . json_encode($validator->errors()));
        }
    }
}