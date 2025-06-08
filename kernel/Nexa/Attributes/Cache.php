<?php

namespace Nexa\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Cache
{
    public function __construct(
        public string $key = '',
        public int $ttl = 3600,
        public array $tags = []
    ) {}

    public function getKey(): string
    {
        return $this->key;
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }

    public function getTags(): array
    {
        return $this->tags;
    }
}