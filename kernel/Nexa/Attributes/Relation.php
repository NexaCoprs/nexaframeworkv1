<?php

namespace Nexa\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Relation
{
    public function __construct(
        public string $type,
        public string $related,
        public string $foreignKey = '',
        public string $localKey = 'id',
        public bool $cache = false,
        public bool $eager = false,
        public int $cacheTtl = 3600
    ) {}

    public function getType(): string
    {
        return $this->type;
    }

    public function getRelated(): string
    {
        return $this->related;
    }

    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    public function getLocalKey(): string
    {
        return $this->localKey;
    }

    public function shouldCache(): bool
    {
        return $this->cache;
    }

    public function isEager(): bool
    {
        return $this->eager;
    }

    public function getCacheTtl(): int
    {
        return $this->cacheTtl;
    }
}