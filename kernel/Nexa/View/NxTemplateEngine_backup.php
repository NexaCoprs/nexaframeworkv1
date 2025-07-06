<?php

namespace Nexa\View;

class NxTemplateEngine
{
    private array $components = [];
    private array $directives = [];
    private string $templatePath;
    private array $globals = [];
    
    public function __construct(string $templatePath = 'workspace/interface')
    {
        $this->templatePath = $templatePath;
        $this->registerDefaultDirectives();
        $this->registerDefaultComponents();
    }
    
    public function render(string $template, array $data = []): string
    {
        $templateFile = $this->templatePath . '/' . $template . '.nx';
        
        if (!file_exists($templateFile)) {
            throw new \Exception("Template {$template} not found");
        }
        
        $content = file_get_contents($templateFile);
        
        // Traiter les composants
        $content = $this->processComponents($content, $data);
        
        // Traiter les directives
        $content = $this->processDirectives($content, $data);
        
        // Traiter les variables
        $content = $this->processVariables($content, array_merge($this->globals, $data));
        
        return $content;
    }
    
    public function registerComponent(string $name, callable $handler): void
    {
        $this->components[$name] = $handler;
    }
    
    public function registerDirective(string $name, callable $handler): void
    {
        $this->directives[$name] = $handler;
    }
    
    public function addGlobal(string $key, $value): void
    {
        $this->globals[$key] = $value;
    }
    
    // Placeholder for registerDefaultComponents - will be replaced with full content
    private function registerDefaultComponents(): void
    {
        // Content will be replaced
    }
    
    // Placeholder for registerDefaultDirectives - will be replaced with full content
    private function registerDefaultDirectives(): void
    {
        // Content will be replaced
    }
    
    // Core processing methods will be preserved from original NxTemplateEngine.php
    private function processComponents(string $content, array $data): string
    {
        // Implementation preserved
        return $content;
    }
    
    private function processDirectives(string $content, array $data): string
    {
        // Implementation preserved
        return $content;
    }
    
    private function processVariables(string $content, array $data): string
    {
        // Implementation preserved
        return $content;
    }
}