<?php

namespace Nexa\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Route
{
    public function __construct(
        public string $method = 'GET',
        public string $path = '',
        public string $prefix = '',
        public array $middleware = [],
        public string $name = '',
        public array $where = []
    ) {}

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getWhere(): array
    {
        return $this->where;
    }
}