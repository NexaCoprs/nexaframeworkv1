<?php

namespace Nexa\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Secure
{
    public function __construct(
        public bool $encryption = true,
        public string $level = 'high',
        public array $permissions = [],
        public bool $audit = true,
        public string $algorithm = 'AES-256-GCM'
    ) {}

    public function hasEncryption(): bool
    {
        return $this->encryption;
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function hasAudit(): bool
    {
        return $this->audit;
    }

    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }
}