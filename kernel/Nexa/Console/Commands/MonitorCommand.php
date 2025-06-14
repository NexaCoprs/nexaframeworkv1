<?php

namespace Nexa\Console\Commands;

use Nexa\Console\Command;
use Nexa\Core\PerformanceMonitor;
use Nexa\Cache\SmartCache;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\ProgressBar;

class MonitorCommand extends Command
{
    private array $metrics = [];
    private bool $running = false;
    private int $refreshInterval = 5;
    
    protected function configure()
    {
        $this->setName('monitor:realtime')
             ->setDescription('Surveillance en temps réel des performances et métriques système')
             ->addOption('interval', 'i', InputOption::VALUE_OPTIONAL, 'Intervalle de rafraîchissement en secondes', 5)
             ->addOption('alerts', 'a', InputOption::VALUE_NONE, 'Activer les alertes automatiques')
             ->addOption('export', 'e', InputOption::VALUE_OPTIONAL, 'Exporter les métriques vers un fichier')
             ->addOption('threshold', 't', InputOption::VALUE_OPTIONAL, 'Seuil d\'alerte pour les métriques critiques', 80)
             ->addOption('dashboard', 'd', InputOption::VALUE_NONE, 'Mode dashboard avec interface enrichie');
    }

    protected function handle()
    {
        $this->refreshInterval = (int) $this->input->getOption('interval');
        $alerts = $this->input->getOption('alerts');
        $export = $this->input->getOption('export');
        $threshold = (int) $this->input->getOption('threshold');
        $dashboard = $this->input->getOption('dashboard');
        
        $this->info('🔍 Monitoring Intelligent Nexa Framework');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line('');
        
        if ($dashboard) {
            $this->runDashboardMode($alerts, $threshold, $export);
        } else {
            $this->runSimpleMode($alerts, $threshold, $export);
        }
    }
    
    private function runDashboardMode(bool $alerts, int $threshold, ?string $export): void
    {
        $this->line('<info>📊 Mode Dashboard Activé</info>');
        $this->line('<comment>Appuyez sur Ctrl+C pour arrêter le monitoring</comment>');
        $this->line('');
        
        $this->running = true;
        
        // Gestionnaire de signal pour arrêt propre
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, [$this, 'stopMonitoring']);
            pcntl_signal(SIGTERM, [$this, 'stopMonitoring']);
        }
        
        $startTime = time();
        $alertCount = 0;
        
        while ($this->running) {
            // Effacer l'écran
            $this->output->write("\033[2J\033[H");
            
            // Header avec informations de session
            $this->displayDashboardHeader($startTime, $alertCount);
            
            // Collecte des métriques
            $this->collectMetrics();
            
            // Affichage du dashboard
            $this->displayDashboard();
            
            // Vérification des alertes
            if ($alerts) {
                $newAlerts = $this->checkAlerts($threshold);
                $alertCount += count($newAlerts);
                if (!empty($newAlerts)) {
                    $this->displayAlerts($newAlerts);
                }
            }
            
            // Export des métriques si demandé
            if ($export) {
                $this->exportMetrics($export);
            }
            
            // Attendre avant le prochain rafraîchissement
            sleep($this->refreshInterval);
            
            // Traitement des signaux
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
        }
        
        $this->line('');
        $this->success('✅ Monitoring arrêté proprement.');
    }
    
    private function runSimpleMode(bool $alerts, int $threshold, ?string $export): void
    {
        $this->line('<info>📈 Mode Simple - Snapshot des Métriques</info>');
        $this->line('');
        
        $this->collectMetrics();
        $this->displaySimpleMetrics();
        
        if ($alerts) {
            $alertsFound = $this->checkAlerts($threshold);
            if (!empty($alertsFound)) {
                $this->displayAlerts($alertsFound);
            } else {
                $this->success('✅ Aucune alerte détectée.');
            }
        }
        
        if ($export) {
            $this->exportMetrics($export);
            $this->success("📁 Métriques exportées vers: {$export}");
        }
    }
    
    private function displayDashboardHeader(int $startTime, int $alertCount): void
    {
        $uptime = $this->formatDuration(time() - $startTime);
        $timestamp = date('Y-m-d H:i:s');
        
        $this->line('🔍 <info>Nexa Framework - Dashboard de Monitoring</info>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line("📅 {$timestamp} | ⏱️ Uptime: {$uptime} | 🚨 Alertes: {$alertCount} | 🔄 Refresh: {$this->refreshInterval}s");
        $this->line('');
    }
    
    private function collectMetrics(): void
    {
        $this->metrics = [
            'system' => $this->getSystemMetrics(),
            'application' => $this->getApplicationMetrics(),
            'database' => $this->getDatabaseMetrics(),
            'cache' => $this->getCacheMetrics(),
            'security' => $this->getSecurityMetrics(),
            'performance' => $this->getPerformanceMetrics()
        ];
    }
    
    private function getSystemMetrics(): array
    {
        $load = sys_getloadavg();
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = ini_get('memory_limit');
        
        return [
            'cpu_load_1min' => round($load[0], 2),
            'cpu_load_5min' => round($load[1], 2),
            'cpu_load_15min' => round($load[2], 2),
            'memory_usage' => $this->formatBytes($memoryUsage),
            'memory_peak' => $this->formatBytes($memoryPeak),
            'memory_limit' => $memoryLimit,
            'memory_percent' => round(($memoryUsage / $this->parseBytes($memoryLimit)) * 100, 1),
            'disk_usage' => $this->getDiskUsage(),
            'php_version' => PHP_VERSION,
            'uptime' => $this->getSystemUptime()
        ];
    }
    
    private function getApplicationMetrics(): array
    {
        return [
            'active_connections' => $this->getActiveConnections(),
            'requests_per_minute' => $this->getRequestsPerMinute(),
            'average_response_time' => $this->getAverageResponseTime(),
            'error_rate' => $this->getErrorRate(),
            'active_sessions' => $this->getActiveSessions(),
            'queue_size' => $this->getQueueSize(),
            'failed_jobs' => $this->getFailedJobs(),
            'log_errors_last_hour' => $this->getLogErrors()
        ];
    }
    
    private function getDatabaseMetrics(): array
    {
        return [
            'active_connections' => $this->getDbActiveConnections(),
            'slow_queries' => $this->getSlowQueries(),
            'queries_per_second' => $this->getQueriesPerSecond(),
            'average_query_time' => $this->getAverageQueryTime(),
            'deadlocks' => $this->getDeadlocks(),
            'table_locks' => $this->getTableLocks(),
            'database_size' => $this->getDatabaseSize(),
            'index_usage' => $this->getIndexUsage()
        ];
    }
    
    private function getCacheMetrics(): array
    {
        return [
            'hit_rate' => $this->getCacheHitRate(),
            'miss_rate' => $this->getCacheMissRate(),
            'memory_usage' => $this->getCacheMemoryUsage(),
            'keys_count' => $this->getCacheKeysCount(),
            'expired_keys' => $this->getExpiredKeys(),
            'evicted_keys' => $this->getEvictedKeys(),
            'operations_per_second' => $this->getCacheOpsPerSecond(),
            'average_ttl' => $this->getAverageTTL()
        ];
    }
    
    private function getSecurityMetrics(): array
    {
        return [
            'failed_logins_last_hour' => $this->getFailedLogins(),
            'blocked_ips' => $this->getBlockedIPs(),
            'suspicious_requests' => $this->getSuspiciousRequests(),
            'rate_limit_hits' => $this->getRateLimitHits(),
            'csrf_failures' => $this->getCSRFFailures(),
            'sql_injection_attempts' => $this->getSQLInjectionAttempts(),
            'xss_attempts' => $this->getXSSAttempts(),
            'security_score' => $this->calculateSecurityScore()
        ];
    }
    
    private function getPerformanceMetrics(): array
    {
        return [
            'response_time_p50' => $this->getResponseTimePercentile(50),
            'response_time_p95' => $this->getResponseTimePercentile(95),
            'response_time_p99' => $this->getResponseTimePercentile(99),
            'throughput' => $this->getThroughput(),
            'error_rate_percent' => $this->getErrorRatePercent(),
            'apdex_score' => $this->getApdexScore(),
            'bottlenecks' => $this->detectBottlenecks(),
            'optimization_score' => $this->calculateOptimizationScore()
        ];
    }
    
    private function displayDashboard(): void
    {
        // Métriques système
        $this->displaySystemSection();
        $this->line('');
        
        // Métriques application
        $this->displayApplicationSection();
        $this->line('');
        
        // Métriques base de données
        $this->displayDatabaseSection();
        $this->line('');
        
        // Métriques cache
        $this->displayCacheSection();
        $this->line('');
        
        // Métriques sécurité
        $this->displaySecuritySection();
        $this->line('');
        
        // Métriques performance
        $this->displayPerformanceSection();
    }
    
    private function displaySystemSection(): void
    {
        $system = $this->metrics['system'];
        
        $this->line('🖥️  <info>SYSTÈME</info>');
        $this->line('─────────────────────────────────────────────────────────────────────────────');
        
        $cpuStatus = $this->getStatusIcon($system['cpu_load_1min'], 1.0, 2.0);
        $memoryStatus = $this->getStatusIcon($system['memory_percent'], 70, 90);
        
        $this->line("CPU Load: {$cpuStatus} {$system['cpu_load_1min']} (1m) | {$system['cpu_load_5min']} (5m) | {$system['cpu_load_15min']} (15m)");
        $this->line("Memory: {$memoryStatus} {$system['memory_usage']} / {$system['memory_limit']} ({$system['memory_percent']}%)");
        $this->line("Disk: {$system['disk_usage']} | PHP: {$system['php_version']} | Uptime: {$system['uptime']}");
    }
    
    private function displayApplicationSection(): void
    {
        $app = $this->metrics['application'];
        
        $this->line('🚀 <info>APPLICATION</info>');
        $this->line('─────────────────────────────────────────────────────────────────────────────');
        
        $errorStatus = $this->getStatusIcon($app['error_rate'], 1, 5);
        $responseStatus = $this->getStatusIcon($app['average_response_time'], 200, 500);
        
        $this->line("Connexions: {$app['active_connections']} | Requêtes/min: {$app['requests_per_minute']}");
        $this->line("Temps réponse: {$responseStatus} {$app['average_response_time']}ms | Erreurs: {$errorStatus} {$app['error_rate']}%");
        $this->line("Sessions: {$app['active_sessions']} | Queue: {$app['queue_size']} | Jobs échoués: {$app['failed_jobs']}");
    }
    
    private function displayDatabaseSection(): void
    {
        $db = $this->metrics['database'];
        
        $this->line('🗄️  <info>BASE DE DONNÉES</info>');
        $this->line('─────────────────────────────────────────────────────────────────────────────');
        
        $queryStatus = $this->getStatusIcon($db['average_query_time'], 50, 100);
        $slowStatus = $this->getStatusIcon($db['slow_queries'], 5, 20);
        
        $this->line("Connexions: {$db['active_connections']} | Requêtes/sec: {$db['queries_per_second']}");
        $this->line("Temps moyen: {$queryStatus} {$db['average_query_time']}ms | Lentes: {$slowStatus} {$db['slow_queries']}");
        $this->line("Deadlocks: {$db['deadlocks']} | Taille: {$db['database_size']} | Index: {$db['index_usage']}%");
    }
    
    private function displayCacheSection(): void
    {
        $cache = $this->metrics['cache'];
        
        $this->line('💾 <info>CACHE</info>');
        $this->line('─────────────────────────────────────────────────────────────────────────────');
        
        $hitStatus = $this->getStatusIcon($cache['hit_rate'], 80, 95, true);
        $memoryStatus = $this->getStatusIcon($cache['memory_usage'], 70, 90);
        
        $this->line("Hit Rate: {$hitStatus} {$cache['hit_rate']}% | Miss Rate: {$cache['miss_rate']}%");
        $this->line("Mémoire: {$memoryStatus} {$cache['memory_usage']}% | Clés: {$cache['keys_count']}");
        $this->line("Ops/sec: {$cache['operations_per_second']} | TTL moyen: {$cache['average_ttl']}s");
    }
    
    private function displaySecuritySection(): void
    {
        $security = $this->metrics['security'];
        
        $this->line('🔒 <info>SÉCURITÉ</info>');
        $this->line('─────────────────────────────────────────────────────────────────────────────');
        
        $securityStatus = $this->getStatusIcon($security['security_score'], 80, 95, true);
        $failedStatus = $this->getStatusIcon($security['failed_logins_last_hour'], 10, 50);
        
        $this->line("Score sécurité: {$securityStatus} {$security['security_score']}/100");
        $this->line("Échecs login: {$failedStatus} {$security['failed_logins_last_hour']} | IPs bloquées: {$security['blocked_ips']}");
        $this->line("Requêtes suspectes: {$security['suspicious_requests']} | Rate limit: {$security['rate_limit_hits']}");
    }
    
    private function displayPerformanceSection(): void
    {
        $perf = $this->metrics['performance'];
        
        $this->line('⚡ <info>PERFORMANCE</info>');
        $this->line('─────────────────────────────────────────────────────────────────────────────');
        
        $apdexStatus = $this->getStatusIcon($perf['apdex_score'], 0.7, 0.9, true);
        $optimizationStatus = $this->getStatusIcon($perf['optimization_score'], 70, 90, true);
        
        $this->line("Temps réponse: P50={$perf['response_time_p50']}ms | P95={$perf['response_time_p95']}ms | P99={$perf['response_time_p99']}ms");
        $this->line("Apdex: {$apdexStatus} {$perf['apdex_score']} | Throughput: {$perf['throughput']} req/s");
        $this->line("Score optimisation: {$optimizationStatus} {$perf['optimization_score']}/100 | Goulots: {$perf['bottlenecks']}");
    }
    
    private function displaySimpleMetrics(): void
    {
        $table = new Table($this->output);
        $table->setHeaders(['Catégorie', 'Métrique', 'Valeur', 'Statut']);
        
        foreach ($this->metrics as $category => $metrics) {
            foreach ($metrics as $key => $value) {
                $status = $this->getMetricStatus($category, $key, $value);
                $table->addRow([
                    ucfirst($category),
                    str_replace('_', ' ', ucfirst($key)),
                    $value,
                    $status
                ]);
            }
        }
        
        $table->render();
    }
    
    private function checkAlerts(int $threshold): array
    {
        $alerts = [];
        
        // Alertes système
        if ($this->metrics['system']['cpu_load_1min'] > 2.0) {
            $alerts[] = [
                'type' => 'critical',
                'category' => 'system',
                'message' => "Charge CPU élevée: {$this->metrics['system']['cpu_load_1min']}",
                'recommendation' => 'Vérifier les processus consommateurs'
            ];
        }
        
        if ($this->metrics['system']['memory_percent'] > $threshold) {
            $alerts[] = [
                'type' => 'warning',
                'category' => 'system',
                'message' => "Utilisation mémoire élevée: {$this->metrics['system']['memory_percent']}%",
                'recommendation' => 'Optimiser l\'utilisation mémoire ou augmenter la limite'
            ];
        }
        
        // Alertes application
        if ($this->metrics['application']['error_rate'] > 5) {
            $alerts[] = [
                'type' => 'critical',
                'category' => 'application',
                'message' => "Taux d\'erreur élevé: {$this->metrics['application']['error_rate']}%",
                'recommendation' => 'Vérifier les logs d\'erreurs et corriger les problèmes'
            ];
        }
        
        // Alertes base de données
        if ($this->metrics['database']['slow_queries'] > 20) {
            $alerts[] = [
                'type' => 'warning',
                'category' => 'database',
                'message' => "Requêtes lentes détectées: {$this->metrics['database']['slow_queries']}",
                'recommendation' => 'Optimiser les requêtes et ajouter des index'
            ];
        }
        
        // Alertes cache
        if ($this->metrics['cache']['hit_rate'] < 80) {
            $alerts[] = [
                'type' => 'warning',
                'category' => 'cache',
                'message' => "Taux de hit cache faible: {$this->metrics['cache']['hit_rate']}%",
                'recommendation' => 'Revoir la stratégie de cache et les TTL'
            ];
        }
        
        // Alertes sécurité
        if ($this->metrics['security']['failed_logins_last_hour'] > 50) {
            $alerts[] = [
                'type' => 'critical',
                'category' => 'security',
                'message' => "Tentatives de connexion suspectes: {$this->metrics['security']['failed_logins_last_hour']}",
                'recommendation' => 'Activer le blocage automatique et vérifier les logs'
            ];
        }
        
        return $alerts;
    }
    
    private function displayAlerts(array $alerts): void
    {
        if (empty($alerts)) return;
        
        $this->line('');
        $this->line('🚨 <error>ALERTES DÉTECTÉES</error>');
        $this->line('─────────────────────────────────────────────────────────────────────────────');
        
        foreach ($alerts as $alert) {
            $icon = match($alert['type']) {
                'critical' => '🔴',
                'warning' => '🟡',
                'info' => '🔵',
                default => '⚪'
            };
            
            $this->line("{$icon} [{$alert['category']}] {$alert['message']}");
            $this->line("   💡 {$alert['recommendation']}");
            $this->line('');
        }
    }
    
    private function exportMetrics(string $filename): void
    {
        $data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'metrics' => $this->metrics
        ];
        
        $json = json_encode($data, JSON_PRETTY_PRINT);
        file_put_contents($filename, $json);
    }
    
    private function getStatusIcon(float $value, float $warning, float $critical, bool $reverse = false): string
    {
        if ($reverse) {
            if ($value >= $warning) return '🟢';
            if ($value >= $critical) return '🟡';
            return '🔴';
        } else {
            if ($value <= $warning) return '🟢';
            if ($value <= $critical) return '🟡';
            return '🔴';
        }
    }
    
    private function getMetricStatus(string $category, string $key, $value): string
    {
        // Logique simplifiée pour déterminer le statut
        return '🟢 OK';
    }
    
    // Méthodes utilitaires pour collecter les vraies métriques
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    private function parseBytes(string $val): int
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int) $val;
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;
        }
        return $val;
    }
    
    private function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }
    
    // Méthodes simulées pour les métriques (à remplacer par de vraies implémentations)
    private function getDiskUsage(): string { return '45.2GB / 100GB (45%)'; }
    private function getSystemUptime(): string { return '5d 12h 30m'; }
    private function getActiveConnections(): int { return rand(50, 200); }
    private function getRequestsPerMinute(): int { return rand(100, 500); }
    private function getAverageResponseTime(): int { return rand(50, 300); }
    private function getErrorRate(): float { return round(rand(0, 100) / 100, 2); }
    private function getActiveSessions(): int { return rand(20, 100); }
    private function getQueueSize(): int { return rand(0, 50); }
    private function getFailedJobs(): int { return rand(0, 10); }
    private function getLogErrors(): int { return rand(0, 20); }
    private function getDbActiveConnections(): int { return rand(5, 50); }
    private function getSlowQueries(): int { return rand(0, 30); }
    private function getQueriesPerSecond(): int { return rand(10, 100); }
    private function getAverageQueryTime(): int { return rand(10, 200); }
    private function getDeadlocks(): int { return rand(0, 5); }
    private function getTableLocks(): int { return rand(0, 10); }
    private function getDatabaseSize(): string { return '2.5GB'; }
    private function getIndexUsage(): int { return rand(70, 95); }
    private function getCacheHitRate(): int { return rand(70, 98); }
    private function getCacheMissRate(): int { return 100 - $this->getCacheHitRate(); }
    private function getCacheMemoryUsage(): int { return rand(30, 80); }
    private function getCacheKeysCount(): int { return rand(1000, 10000); }
    private function getExpiredKeys(): int { return rand(10, 100); }
    private function getEvictedKeys(): int { return rand(0, 50); }
    private function getCacheOpsPerSecond(): int { return rand(100, 1000); }
    private function getAverageTTL(): int { return rand(300, 3600); }
    private function getFailedLogins(): int { return rand(0, 100); }
    private function getBlockedIPs(): int { return rand(0, 20); }
    private function getSuspiciousRequests(): int { return rand(0, 50); }
    private function getRateLimitHits(): int { return rand(0, 30); }
    private function getCSRFFailures(): int { return rand(0, 10); }
    private function getSQLInjectionAttempts(): int { return rand(0, 5); }
    private function getXSSAttempts(): int { return rand(0, 5); }
    private function calculateSecurityScore(): int { return rand(70, 95); }
    private function getResponseTimePercentile(int $percentile): int { return rand(50, 500); }
    private function getThroughput(): int { return rand(50, 200); }
    private function getErrorRatePercent(): float { return round(rand(0, 500) / 100, 2); }
    private function getApdexScore(): float { return round(rand(70, 95) / 100, 2); }
    private function detectBottlenecks(): int { return rand(0, 5); }
    private function calculateOptimizationScore(): int { return rand(60, 90); }
    
    public function stopMonitoring(): void
    {
        $this->running = false;
    }
}