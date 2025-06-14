<?php

namespace Nexa\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class AutoCRUD
{
    public function __construct(
        public bool $create = true,
        public bool $read = true,
        public bool $update = true,
        public bool $delete = true,
        public array $fillable = [],
        public array $hidden = [],
        public array $validation_rules = [],
        public string $route_prefix = '',
        public array $middleware = [],
        public bool $api_resource = true,
        public bool $pagination = true,
        public int $per_page = 15
    ) {}

    public function getEnabledOperations(): array
    {
        return [
            'create' => $this->create,
            'read' => $this->read,
            'update' => $this->update,
            'delete' => $this->delete
        ];
    }

    public function getFillable(): array
    {
        return $this->fillable;
    }

    public function getHidden(): array
    {
        return $this->hidden;
    }

    public function getValidationRules(): array
    {
        return $this->validation_rules;
    }

    public function getRoutePrefix(): string
    {
        return $this->route_prefix;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function isApiResource(): bool
    {
        return $this->api_resource;
    }

    public function isPaginationEnabled(): bool
    {
        return $this->pagination;
    }

    public function getPerPage(): int
    {
        return $this->per_page;
    }
}