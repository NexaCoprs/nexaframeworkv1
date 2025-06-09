<?php

namespace Nexa\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Quantum
{
    public function __construct(
        public bool $enabled = true,
        public string $optimization = 'auto',
        public int $priority = 1,
        public array $metrics = []
    ) {}

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getOptimization(): string
    {
        return $this->optimization;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getMetrics(): array
    {
        return $this->metrics;
    }
}