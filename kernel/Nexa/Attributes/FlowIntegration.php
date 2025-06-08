<?php

namespace Nexa\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class FlowIntegration
{
    public function __construct(
        public string $routes = '',
        public array $middleware = [],
        public array $dependencies = [],
        public bool $autoRegister = true
    ) {}

    public function getRoutes(): string
    {
        return $this->routes;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function shouldAutoRegister(): bool
    {
        return $this->autoRegister;
    }
}