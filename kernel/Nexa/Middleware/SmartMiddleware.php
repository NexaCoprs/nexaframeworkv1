<?php

namespace Nexa\Middleware;

use Nexa\Core\PerformanceMonitor;
use Nexa\Attributes\Performance;
use Nexa\Attributes\SmartCache;
use ReflectionClass;
use ReflectionMethod;

class SmartMiddleware
{
    private PerformanceMonitor $monitor;
    
    public function __construct()
    {
        $this->monitor = PerformanceMonitor::getInstance();
    }
    
    public function handle($request, $next)
    {
        // Démarrer le monitoring de la requête
        $requestId = 'request_' . uniqid();
        $this->monitor->startTimer($requestId);
        
        // Analyser le contrôleur et la méthode pour les attributs
        if (method_exists($request, 'route')) {
            $route = $request->route();
            if ($route && $route->getAction('controller')) {
                $this->processControllerAttributes($route->getAction('controller'));
            }
        }
        
        // Exécuter la requête
        $response = $next($request);
        
        // Terminer le monitoring
        $metrics = $this->monitor->endTimer($requestId);
        
        // Ajouter les headers de performance si en mode debug (seulement si c'est une Response)
        if (config('app.debug', false) && $response instanceof \Nexa\Http\Response) {
            $response->headers->set('X-Response-Time', $metrics['duration'] . 'ms');
            $response->headers->set('X-Memory-Usage', round($metrics['memory_used'] / 1024 / 1024, 2) . 'MB');
            $response->headers->set('X-Query-Count', $this->monitor->getTotalQueries());
        }
        
        return $response;
    }
    
    private function processControllerAttributes(string $controllerAction): void
    {
        [$controller, $method] = explode('@', $controllerAction);
        
        try {
            $reflectionClass = new ReflectionClass($controller);
            $reflectionMethod = $reflectionClass->getMethod($method);
            
            // Traiter les attributs de performance
            $this->processPerformanceAttributes($reflectionMethod);
            
            // Traiter les attributs de cache intelligent
            $this->processSmartCacheAttributes($reflectionMethod);
            
        } catch (\Exception $e) {
            // Log l'erreur mais continue l'exécution
            error_log("Erreur lors du traitement des attributs: " . $e->getMessage());
        }
    }
    
    private function processPerformanceAttributes(ReflectionMethod $method): void
    {
        $attributes = $method->getAttributes(Performance::class);
        
        foreach ($attributes as $attribute) {
            $performance = $attribute->newInstance();
            
            if ($performance->isMonitoringEnabled()) {
                // Configurer le monitoring selon les paramètres de l'attribut
                $this->configurePerformanceMonitoring($performance);
            }
        }
    }
    
    private function processSmartCacheAttributes(ReflectionMethod $method): void
    {
        $attributes = $method->getAttributes(SmartCache::class);
        
        foreach ($attributes as $attribute) {
            $smartCache = $attribute->newInstance();
            
            // Configurer le cache intelligent
            $this->configureSmartCache($smartCache);
        }
    }
    
    private function configurePerformanceMonitoring(Performance $performance): void
    {
        // Configuration du monitoring basée sur l'attribut
        if ($performance->shouldLogSlow()) {
            // Activer le logging des requêtes lentes
            ini_set('log_errors', '1');
        }
        
        if ($performance->shouldCacheMetrics()) {
            // Activer la mise en cache des métriques
            $this->enableMetricsCaching();
        }
    }
    
    private function configureSmartCache(SmartCache $smartCache): void
    {
        // Configuration du cache intelligent
        switch ($smartCache->getStrategy()) {
            case 'adaptive':
                $this->configureAdaptiveCache($smartCache);
                break;
            case 'time_based':
                $this->configureTimeBasedCache($smartCache);
                break;
            case 'usage_based':
                $this->configureUsageBasedCache($smartCache);
                break;
        }
    }
    
    private function configureAdaptiveCache(SmartCache $smartCache): void
    {
        // Logique de cache adaptatif
        // Ajuste automatiquement le TTL basé sur l'utilisation
    }
    
    private function configureTimeBasedCache(SmartCache $smartCache): void
    {
        // Logique de cache basé sur le temps
        // Utilise des TTL fixes basés sur l'heure
    }
    
    private function configureUsageBasedCache(SmartCache $smartCache): void
    {
        // Logique de cache basé sur l'utilisation
        // Ajuste le TTL selon la fréquence d'accès
    }
    
    private function enableMetricsCaching(): void
    {
        // Active la mise en cache des métriques de performance
    }

    /**
     * Adaptive cache decision based on request patterns
     */
    public function adaptiveCache($request): bool
    {
        // Analyze request patterns to decide caching strategy
        $method = $request->method ?? 'GET';
        $uri = $request->uri ?? '/';
        
        // Cache GET requests by default
        if ($method === 'GET') {
            return true;
        }
        
        // Don't cache POST, PUT, DELETE requests
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return false;
        }
        
        // Cache API endpoints with specific patterns
        if (strpos($uri, '/api/') === 0) {
            return $method === 'GET';
        }
        
        return false;
    }

    /**
     * Smart cache implementation
     */
    public function smartCache($request)
    {
        $shouldCache = $this->adaptiveCache($request);
        
        if ($shouldCache) {
            $cacheKey = $this->generateCacheKey($request);
            return cache()->remember($cacheKey, 3600, function() use ($request) {
                return $this->processRequest($request);
            });
        }
        
        return $this->processRequest($request);
    }

    /**
     * Generate cache key for request
     */
    private function generateCacheKey($request): string
    {
        $method = $request->method ?? 'GET';
        $uri = $request->uri ?? '/';
        $params = $request->params ?? [];
        
        return 'smart_cache:' . md5($method . $uri . serialize($params));
    }

    /**
     * Process request (placeholder)
     */
    private function processRequest($request)
    {
        // This would normally process the request
        return ['status' => 'processed', 'request' => $request];
    }

    /**
     * Monitor performance of request
     */
    public function monitorPerformance($request)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        // Process request
        $result = $this->processRequest($request);
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $metrics = [
            'execution_time' => ($endTime - $startTime) * 1000, // in milliseconds
            'memory_usage' => $endMemory - $startMemory,
            'timestamp' => time()
        ];
        
        // Log metrics if needed
        if (\Nexa\Core\Config::get('app.debug', false)) {
            error_log('Performance metrics: ' . json_encode($metrics));
        }
        
        return $result;
    }
}