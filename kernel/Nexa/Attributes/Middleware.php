<?php

namespace Nexa\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Middleware
{
    public function __construct(
        public array $middleware = [],
        public array $except = [],
        public array $only = []
    ) {}

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function getExcept(): array
    {
        return $this->except;
    }

    public function getOnly(): array
    {
        return $this->only;
    }
}