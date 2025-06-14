<?php

namespace Nexa\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class SmartCache
{
    public function __construct(
        public string $strategy = 'adaptive', // adaptive, time_based, usage_based
        public int $base_ttl = 3600,
        public float $usage_multiplier = 1.5,
        public int $max_ttl = 86400,
        public int $min_ttl = 300,
        public array $invalidate_on = [],
        public bool $auto_refresh = true,
        public string $key_pattern = '',
        public array $tags = [],
        public bool $compress = false
    ) {}

    public function getStrategy(): string
    {
        return $this->strategy;
    }

    public function getBaseTtl(): int
    {
        return $this->base_ttl;
    }

    public function getUsageMultiplier(): float
    {
        return $this->usage_multiplier;
    }

    public function getMaxTtl(): int
    {
        return $this->max_ttl;
    }

    public function getMinTtl(): int
    {
        return $this->min_ttl;
    }

    public function getInvalidateOn(): array
    {
        return $this->invalidate_on;
    }

    public function isAutoRefreshEnabled(): bool
    {
        return $this->auto_refresh;
    }

    public function getKeyPattern(): string
    {
        return $this->key_pattern;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function shouldCompress(): bool
    {
        return $this->compress;
    }
}