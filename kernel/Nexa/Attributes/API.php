<?php

namespace Nexa\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class API
{
    public function __construct(
        public string $version = 'v1',
        public string $summary = '',
        public string $description = '',
        public array $tags = [],
        public bool $documentation = true,
        public array $responses = [],
        public array $parameters = []
    ) {}

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function hasDocumentation(): bool
    {
        return $this->documentation;
    }

    public function getResponses(): array
    {
        return $this->responses;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}