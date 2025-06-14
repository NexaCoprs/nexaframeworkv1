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
    
    private function registerDefaultComponents(): void
    {
        // Composant Card
        $this->registerComponent('card', function($attributes, $content) {
            $title = $attributes['title'] ?? '';
            $class = $attributes['class'] ?? 'card';
            
            return "
                <div class='{$class}'>
                    <div class='card-header'>
                        <h3>{$title}</h3>
                    </div>
                    <div class='card-body'>
                        {$content}
                    </div>
                </div>
            ";
        });
        
        // Composant Button
        $this->registerComponent('button', function($attributes, $content) {
            $type = $attributes['type'] ?? 'button';
            $class = $attributes['class'] ?? 'btn btn-primary';
            $onclick = $attributes['onclick'] ?? '';
            
            return "<button type='{$type}' class='{$class}' onclick='{$onclick}'>{$content}</button>";
        });
        
        // Composant Form
        $this->registerComponent('form', function($attributes, $content) {
            $action = $attributes['action'] ?? '';
            $method = $attributes['method'] ?? 'POST';
            $class = $attributes['class'] ?? 'form';
            
            $csrf = $method !== 'GET' ? "<input type='hidden' name='_token' value='" . csrf_token() . "'>" : '';
            
            return "
                <form action='{$action}' method='{$method}' class='{$class}'>
                    {$csrf}
                    {$content}
                </form>
            ";
        });
        
        // Composant Input
        $this->registerComponent('input', function($attributes, $content) {
            $type = $attributes['type'] ?? 'text';
            $name = $attributes['name'] ?? '';
            $value = $attributes['value'] ?? '';
            $placeholder = $attributes['placeholder'] ?? '';
            $class = $attributes['class'] ?? 'form-control';
            $required = isset($attributes['required']) ? 'required' : '';
            
            return "<input type='{$type}' name='{$name}' value='{$value}' placeholder='{$placeholder}' class='{$class}' {$required}>";
        });
        
        // Composant Alert
        $this->registerComponent('alert', function($attributes, $content) {
            $type = $attributes['type'] ?? 'info';
            $dismissible = isset($attributes['dismissible']) ? 'alert-dismissible' : '';
            
            $closeButton = $dismissible ? "<button type='button' class='btn-close' data-bs-dismiss='alert'></button>" : '';
            
            return "
                <div class='alert alert-{$type} {$dismissible}' role='alert'>
                    {$content}
                    {$closeButton}
                </div>
            ";
        });
    }
    
    private function registerDefaultDirectives(): void
    {
        // Directive @if
        $this->registerDirective('if', function($condition, $content) {
            return "<?php if({$condition}): ?>{$content}<?php endif; ?>";
        });
        
        // Directive @foreach
        $this->registerDirective('foreach', function($expression, $content) {
            return "<?php foreach({$expression}): ?>{$content}<?php endforeach; ?>";
        });
        
        // Directive @auth
        $this->registerDirective('auth', function($guard, $content) {
            $guardCheck = $guard ? "auth('{$guard}')->check()" : "auth()->check()";
            return "<?php if({$guardCheck}): ?>{$content}<?php endif; ?>";
        });
        
        // Directive @guest
        $this->registerDirective('guest', function($guard, $content) {
            $guardCheck = $guard ? "auth('{$guard}')->guest()" : "auth()->guest()";
            return "<?php if({$guardCheck}): ?>{$content}<?php endif; ?>";
        });
        
        // Directive @csrf
        $this->registerDirective('csrf', function($params, $content) {
            return "<input type='hidden' name='_token' value='<?php echo csrf_token(); ?>'>";
        });
        
        // Directive @method
        $this->registerDirective('method', function($method, $content) {
            return "<input type='hidden' name='_method' value='{$method}'>";
        });
    }
    
    private function processComponents(string $content, array $data): string
    {
        // Pattern pour les composants: <nx:component-name attr="value">content</nx:component-name>
        $pattern = '/<nx:([a-zA-Z0-9-_]+)([^>]*)>(.*?)<\/nx:\1>/s';
        
        return preg_replace_callback($pattern, function($matches) use ($data) {
            $componentName = $matches[1];
            $attributesString = $matches[2];
            $content = $matches[3];
            
            if (!isset($this->components[$componentName])) {
                return $matches[0]; // Retourner le contenu original si le composant n'existe pas
            }
            
            // Parser les attributs
            $attributes = $this->parseAttributes($attributesString);
            
            // Traiter le contenu du composant récursivement
            $content = $this->processComponents($content, $data);
            $content = $this->processDirectives($content, $data);
            $content = $this->processVariables($content, $data);
            
            // Appeler le handler du composant
            return $this->components[$componentName]($attributes, $content);
        }, $content);
    }
    
    private function processDirectives(string $content, array $data): string
    {
        // Pattern pour les directives: @directive(params)
        $pattern = '/@([a-zA-Z0-9_]+)\(([^)]*)\)([\s\S]*?)@end\1/s';
        
        return preg_replace_callback($pattern, function($matches) use ($data) {
            $directiveName = $matches[1];
            $params = $matches[2];
            $content = $matches[3];
            
            if (!isset($this->directives[$directiveName])) {
                return $matches[0];
            }
            
            return $this->directives[$directiveName]($params, $content);
        }, $content);
    }
    
    private function processVariables(string $content, array $data): string
    {
        // Pattern pour les variables: {{ $variable }}
        $pattern = '/\{\{\s*(.+?)\s*\}\}/';
        
        return preg_replace_callback($pattern, function($matches) use ($data) {
            $variable = trim($matches[1]);
            
            // Évaluer la variable dans le contexte des données
            ob_start();
            extract($data);
            eval("echo {$variable};");
            return ob_get_clean();
        }, $content);
    }
    
    private function parseAttributes(string $attributesString): array
    {
        $attributes = [];
        
        // Pattern pour les attributs: attr="value" ou attr='value'
        $pattern = '/([a-zA-Z0-9-_]+)=["\']([^"\']*)["\']/s';
        
        preg_match_all($pattern, $attributesString, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $attributes[$match[1]] = $match[2];
        }
        
        return $attributes;
    }
    
    public function compileTemplate(string $template): string
    {
        $templateFile = $this->templatePath . '/' . $template . '.nx';
        $compiledFile = storage_path('framework/views/' . str_replace('/', '_', $template) . '.php');
        
        if (!file_exists($templateFile)) {
            throw new \Exception("Template {$template} not found");
        }
        
        $content = file_get_contents($templateFile);
        
        // Compiler le template
        $compiled = $this->processComponents($content, []);
        $compiled = $this->processDirectives($compiled, []);
        
        // Sauvegarder le template compilé
        $dir = dirname($compiledFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($compiledFile, $compiled);
        
        return $compiledFile;
    }
}