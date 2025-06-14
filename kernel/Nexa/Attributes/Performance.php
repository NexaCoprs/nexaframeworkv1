<?php

namespace Nexa\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Performance
{
    public function __construct(
        public bool $monitor = true,
        public int $threshold = 1000, // milliseconds
        public bool $log_slow = true,
        public bool $cache_metrics = true,
        public array $alerts = [],
        public string $metric_name = ''
    ) {}

    public function isMonitoringEnabled(): bool
    {
        return $this->monitor;
    }

    public function getThreshold(): int
    {
        return $this->threshold;
    }

    public function shouldLogSlow(): bool
    {
        return $this->log_slow;
    }

    public function shouldCacheMetrics(): bool
    {
        return $this->cache_metrics;
    }

    public function getAlerts(): array
    {
        return $this->alerts;
    }

    public function getMetricName(): string
    {
        return $this->metric_name ?: 'default';
    }
}