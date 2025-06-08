<?php

namespace Nexa\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Validation
{
    public function __construct(
        public array $rules = [],
        public array $messages = [],
        public bool $bail = false
    ) {}

    public function getRules(): array
    {
        return $this->rules;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function shouldBail(): bool
    {
        return $this->bail;
    }
}