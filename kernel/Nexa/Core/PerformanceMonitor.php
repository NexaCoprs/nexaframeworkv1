<?php

namespace Nexa\Core;

class PerformanceMonitor
{
    private static $instance = null;
    private array $metrics = [];
    private array $timers = [];
    private array $queries = [];
    private array $alerts = [];
    
    private function __construct() {}
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function startTimer(string $name): void
    {
        $this->timers[$name] = [
            'start' => microtime(true),
            'memory_start' => memory_get_usage(true)
        ];
    }
    
    public function endTimer(string $name): array
    {
        if (!isset($this->timers[$name])) {
            return ['error' => 'Timer not found'];
        }
        
        $timer = $this->timers[$name];
        $duration = (microtime(true) - $timer['start']) * 1000; // en millisecondes
        $memoryUsed = memory_get_usage(true) - $timer['memory_start'];
        
        $metric = [
            'name' => $name,
            'duration' => round($duration, 2),
            'memory_used' => $memoryUsed,
            'timestamp' => time()
        ];
        
        $this->metrics[] = $metric;
        unset($this->timers[$name]);
        
        // Vérifier les seuils d'alerte
        $this->checkAlerts($metric);
        
        return $metric;
    }
    
    public function recordQuery(string $sql, float $duration, array $bindings = []): void
    {
        $this->queries[] = [
            'sql' => $sql,
            'duration' => round($duration, 2),
            'bindings' => $bindings,
            'timestamp' => time()
        ];
    }
    
    public function recordMemoryUsage(string $checkpoint): void
    {
        $this->metrics[] = [
            'name' => "memory_{$checkpoint}",
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'timestamp' => time()
        ];
    }
    
    public function getMetrics(): array
    {
        return $this->metrics;
    }
    
    public function getQueries(): array
    {
        return $this->queries;
    }
    
    public function getSlowQueries(float $threshold = 100): array
    {
        return array_filter($this->queries, function($query) use ($threshold) {
            return $query['duration'] > $threshold;
        });
    }
    
    public function getAverageResponseTime(): float
    {
        $responseTimes = array_filter($this->metrics, function($metric) {
            return strpos($metric['name'], 'request_') === 0;
        });
        
        if (empty($responseTimes)) {
            return 0;
        }
        
        $total = array_sum(array_column($responseTimes, 'duration'));
        return round($total / count($responseTimes), 2);
    }
    
    public function getTotalQueries(): int
    {
        return count($this->queries);
    }
    
    public function getTotalQueryTime(): float
    {
        return round(array_sum(array_column($this->queries, 'duration')), 2);
    }
    
    public function generateReport(): array
    {
        return [
            'summary' => [
                'total_requests' => count($this->metrics),
                'average_response_time' => $this->getAverageResponseTime(),
                'total_queries' => $this->getTotalQueries(),
                'total_query_time' => $this->getTotalQueryTime(),
                'slow_queries' => count($this->getSlowQueries()),
                'memory_peak' => memory_get_peak_usage(true),
                'alerts' => count($this->alerts)
            ],
            'metrics' => $this->metrics,
            'queries' => $this->queries,
            'slow_queries' => $this->getSlowQueries(),
            'alerts' => $this->alerts
        ];
    }
    
    private function checkAlerts(array $metric): void
    {
        // Alerte si la durée dépasse 1 seconde
        if (isset($metric['duration']) && $metric['duration'] > 1000) {
            $this->alerts[] = [
                'type' => 'slow_request',
                'message' => "Requête lente détectée: {$metric['name']} ({$metric['duration']}ms)",
                'metric' => $metric,
                'timestamp' => time()
            ];
        }
        
        // Alerte si l'utilisation mémoire dépasse 50MB
        if (isset($metric['memory_used']) && $metric['memory_used'] > 50 * 1024 * 1024) {
            $this->alerts[] = [
                'type' => 'high_memory',
                'message' => "Utilisation mémoire élevée: {$metric['name']} (" . round($metric['memory_used'] / 1024 / 1024, 2) . "MB)",
                'metric' => $metric,
                'timestamp' => time()
            ];
        }
    }
    
    public function reset(): void
    {
        $this->metrics = [];
        $this->timers = [];
        $this->queries = [];
        $this->alerts = [];
    }
    
    public function exportToFile(string $filename = null): string
    {
        $filename = $filename ?: 'performance_report_' . date('Y-m-d_H-i-s') . '.json';
        $filepath = storage_path('logs/' . $filename);
        
        file_put_contents($filepath, json_encode($this->generateReport(), JSON_PRETTY_PRINT));
        
        return $filepath;
    }
}