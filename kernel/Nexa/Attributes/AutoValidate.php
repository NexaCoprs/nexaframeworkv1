<?php

namespace Nexa\Attributes;

use Attribute;

/**
 * AutoValidate attribute for automatic validation
 * Enables automatic validation based on model properties
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
class AutoValidate
{
    public function __construct(
        public array $rules = [],
        public array $messages = [],
        public bool $enabled = true,
        public bool $strict = false
    ) {}

    /**
     * Get validation rules
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Get validation messages
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Check if validation is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Check if strict mode is enabled
     */
    public function isStrict(): bool
    {
        return $this->strict;
    }

    /**
     * Add validation rule
     */
    public function addRule(string $field, string $rule): void
    {
        $this->rules[$field] = $rule;
    }

    /**
     * Add validation message
     */
    public function addMessage(string $field, string $message): void
    {
        $this->messages[$field] = $message;
    }
}