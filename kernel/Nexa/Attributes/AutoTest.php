<?php

namespace Nexa\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class AutoTest
{
    public function __construct(
        public bool $unit = true,
        public bool $integration = false,
        public bool $feature = false,
        public array $test_cases = [],
        public array $mock_dependencies = [],
        public bool $generate_fixtures = true,
        public string $test_group = 'default',
        public array $assertions = [],
        public bool $performance_test = false,
        public int $performance_threshold = 1000
    ) {}

    public function shouldGenerateUnit(): bool
    {
        return $this->unit;
    }

    public function shouldGenerateIntegration(): bool
    {
        return $this->integration;
    }

    public function shouldGenerateFeature(): bool
    {
        return $this->feature;
    }

    public function getTestCases(): array
    {
        return $this->test_cases;
    }

    public function getMockDependencies(): array
    {
        return $this->mock_dependencies;
    }

    public function shouldGenerateFixtures(): bool
    {
        return $this->generate_fixtures;
    }

    public function getTestGroup(): string
    {
        return $this->test_group;
    }

    public function getAssertions(): array
    {
        return $this->assertions;
    }

    public function shouldPerformanceTest(): bool
    {
        return $this->performance_test;
    }

    public function getPerformanceThreshold(): int
    {
        return $this->performance_threshold;
    }
}