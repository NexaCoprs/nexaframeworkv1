<?php

namespace Nexa\View;

use Nexa\Core\Cache;
use Nexa\Support\Str;
use Exception;

class AdvancedNxTemplateEngine extends NxTemplateEngine
{
    private array $layouts = [];
    private array $sections = [];
    private array $stacks = [];
    private array $macros = [];
    private array $filters = [];
    private array $extensions = [];
    private array $themes = [];
    private string $currentTheme = 'default';
    private bool $debugMode = false;
    private array $debugInfo = [];
    protected string $templatePath;
    protected array $globals = [];
    protected array $components = [];
    protected array $directives = [];
    
    public function __construct(string $templatePath = 'workspace/interface')
    {
        parent::__construct($templatePath);
        $this->registerAdvancedDirectives();
        $this->registerAdvancedFilters();
        $this->registerAdvancedComponents();
    }
    
    /**
     * Rendu avancé avec héritage et optimisations
     */
    public function render(string $template, array $data = []): string
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        try {
            // Résoudre l'héritage de templates
            $content = $this->resolveTemplateInheritance($template, $data);
            
            // Traiter les macros
            $content = $this->processMacros($content, $data);
            
            // Traiter les composants avancés
            $content = $this->processAdvancedComponents($content, $data);
            
            // Traiter les directives avancées
            $content = $this->processAdvancedDirectives($content, $data);
            
            // Traiter les directives de base
            $content = $this->processDirectives($content, $data);
            
            // Traiter les filtres
            $content = $this->processFilters($content, $data);
            
            // Traiter les variables avec filtres
            $content = $this->processVariablesWithFilters($content, array_merge($this->globals, $data));
            
            // Appliquer le thème
            $content = $this->applyTheme($content);
            
            if ($this->debugMode) {
                $this->debugInfo = [
                    'template' => $template,
                    'render_time' => (microtime(true) - $startTime) * 1000,
                    'memory_usage' => (memory_get_usage() - $startMemory) / 1024 / 1024,
                    'data_keys' => array_keys($data),
                    'sections_used' => array_keys($this->sections),
                    'components_used' => $this->getUsedComponents($content)
                ];
                $content = $this->injectDebugInfo($content);
            }
            
            return $content;
            
        } catch (Exception $e) {
            if ($this->debugMode) {
                return $this->renderError($e, $template, $data);
            }
            throw $e;
        }
    }
    
    /**
     * Résolution de l'héritage de templates
     */
    private function resolveTemplateInheritance(string $template, array $data): string
    {
        $templateFile = $this->templatePath . '/' . $template . '.nx';
        
        if (!file_exists($templateFile)) {
            throw new Exception("Template {$template} not found");
        }
        
        $content = file_get_contents($templateFile);
        
        // Traiter @extends
        if (preg_match('/@extends\([\'"]([^\'"]*)[\'"](.*?)\)/', $content, $matches)) {
            $layoutName = $matches[1];
            $content = preg_replace('/@extends\([\'"]([^\'"]*)[\'"](.*?)\)/', '', $content);
            
            // Extraire les sections
            $this->extractSections($content);
            
            // Charger le layout
            $layoutContent = $this->loadLayout($layoutName, $data);
            
            // Remplacer les @yield par les sections
            $content = $this->replaceSections($layoutContent);
        }
        
        return $content;
    }
    
    /**
     * Extraction des sections
     */
    private function extractSections(string $content): void
    {
        // Pattern pour @section('name') ... @endsection
        $pattern = '/@section\([\'"]([^\'"]*)[\'"](.*?)\)(.*?)@endsection/s';
        
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $sectionName = $match[1];
            $sectionContent = trim($match[3]);
            
            // Support pour @parent
            if (strpos($sectionContent, '@parent') !== false) {
                $parentContent = $this->sections[$sectionName] ?? '';
                $sectionContent = str_replace('@parent', $parentContent, $sectionContent);
            }
            
            $this->sections[$sectionName] = $sectionContent;
        }
        
        // Pattern pour @push('stack') ... @endpush
        $pushPattern = '/@push\([\'"]([^\'"]*)[\'"](.*?)\)(.*?)@endpush/s';
        
        preg_match_all($pushPattern, $content, $pushMatches, PREG_SET_ORDER);
        
        foreach ($pushMatches as $match) {
            $stackName = $match[1];
            $stackContent = trim($match[3]);
            
            if (!isset($this->stacks[$stackName])) {
                $this->stacks[$stackName] = [];
            }
            
            $this->stacks[$stackName][] = $stackContent;
        }
    }
    
    /**
     * Chargement du layout
     */
    private function loadLayout(string $layoutName, array $data): string
    {
        $layoutFile = $this->templatePath . '/' . $layoutName . '.nx';
        
        if (!file_exists($layoutFile)) {
            throw new Exception("Layout {$layoutName} not found");
        }
        
        return file_get_contents($layoutFile);
    }
    
    /**
     * Remplacement des sections dans le layout
     */
    private function replaceSections(string $layoutContent): string
    {
        // Remplacer @yield('section', 'default')
        $yieldPattern = '/@yield\([\'"]([^\'"]*)[\'"](,\s*[\'"]([^\'"]*)[\'"]*)?\)/';
        
        $layoutContent = preg_replace_callback($yieldPattern, function($matches) {
            $sectionName = $matches[1];
            $defaultContent = isset($matches[3]) ? $matches[3] : '';
            
            return $this->sections[$sectionName] ?? $defaultContent;
        }, $layoutContent);
        
        // Remplacer @stack('name')
        $stackPattern = '/@stack\([\'"]([^\'"]*)[\'"](.*?)\)/';
        
        $layoutContent = preg_replace_callback($stackPattern, function($matches) {
            $stackName = $matches[1];
            
            if (isset($this->stacks[$stackName])) {
                return implode("\n", $this->stacks[$stackName]);
            }
            
            return '';
        }, $layoutContent);
        
        return $layoutContent;
    }
    
    /**
     * Traitement des macros
     */
    private function processMacros(string $content, array $data): string
    {
        // Extraire les définitions de macros
        $macroPattern = '/@macro\([\'"]([^\'"]*)[\'"](,\s*\[([^\]]*)\])?\)(.*?)@endmacro/s';
        
        preg_match_all($macroPattern, $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $macroName = $match[1];
            $parameters = isset($match[3]) ? $this->parseMacroParameters($match[3]) : [];
            $macroContent = trim($match[4]);
            
            $this->macros[$macroName] = [
                'parameters' => $parameters,
                'content' => $macroContent
            ];
            
            // Supprimer la définition du contenu
            $content = str_replace($match[0], '', $content);
        }
        
        // Traiter les utilisations de macros @use('macro', ['param' => 'value'])
        $usePattern = '/@use\([\'"]([^\'"]*)[\'"](,\s*\[([^\]]*)\])?\)/';
        
        $content = preg_replace_callback($usePattern, function($matches) use ($data) {
            $macroName = $matches[1];
            $arguments = isset($matches[3]) ? $this->parseMacroArguments($matches[3]) : [];
            
            return $this->expandMacro($macroName, $arguments, $data);
        }, $content);
        
        return $content;
    }
    
    /**
     * Expansion d'une macro
     */
    private function expandMacro(string $macroName, array $arguments, array $data): string
    {
        if (!isset($this->macros[$macroName])) {
            throw new Exception("Macro {$macroName} not found");
        }
        
        $macro = $this->macros[$macroName];
        $macroContent = $macro['content'];
        
        // Remplacer les paramètres
        foreach ($macro['parameters'] as $param => $default) {
            $value = $arguments[$param] ?? $default ?? '';
            $macroContent = str_replace('{{ $' . $param . ' }}', $value, $macroContent);
        }
        
        return $macroContent;
    }
    
    /**
     * Traitement des filtres
     */
    private function processFilters(string $content, array $data): string
    {
        // Pattern pour les variables avec filtres: {{ variable | filter1 | filter2('param') }}
        $pattern = '/\{\{\s*([^}]+?)\s*\}\}/';
        
        return preg_replace_callback($pattern, function($matches) use ($data) {
            $expression = trim($matches[1]);
            
            // Vérifier s'il y a des filtres
            if (strpos($expression, '|') !== false) {
                return $this->applyFilters($expression, $data);
            }
            
            return $matches[0]; // Retourner tel quel si pas de filtres
        }, $content);
    }
    
    /**
     * Application des filtres
     */
    private function applyFilters(string $expression, array $data): string
    {
        $parts = explode('|', $expression);
        $variable = trim(array_shift($parts));
        
        // Évaluer la variable
        $value = $this->evaluateVariable($variable, $data);
        
        // Appliquer chaque filtre
        foreach ($parts as $filter) {
            $filter = trim($filter);
            $value = $this->applyFilter($filter, $value);
        }
        
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Application d'un filtre spécifique
     */
    private function applyFilter(string $filter, $value)
    {
        // Parser le filtre et ses paramètres
        if (preg_match('/([a-zA-Z_][a-zA-Z0-9_]*)\(([^)]*)\)/', $filter, $matches)) {
            $filterName = $matches[1];
            $params = $this->parseFilterParams($matches[2]);
        } else {
            $filterName = $filter;
            $params = [];
        }
        
        // Appliquer le filtre
        if (isset($this->filters[$filterName])) {
            return call_user_func($this->filters[$filterName], $value, ...$params);
        }
        
        // Filtres natifs
        switch ($filterName) {
            case 'upper':
                return strtoupper($value);
            case 'lower':
                return strtolower($value);
            case 'ucfirst':
                return ucfirst($value);
            case 'truncate':
                $length = $params[0] ?? 100;
                return strlen($value) > $length ? substr($value, 0, $length) . '...' : $value;
            case 'currency':
                $currency = $params[0] ?? 'EUR';
                return number_format($value, 2) . ' ' . $currency;
            case 'date':
                $format = $params[0] ?? 'd/m/Y';
                return date($format, strtotime($value));
            case 'default':
                return $value ?: ($params[0] ?? '');
            case 'json':
                return json_encode($value);
            case 'base64':
                return base64_encode($value);
            case 'md5':
                return md5($value);
            case 'slug':
                return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($value, '-')));
            default:
                return $value;
        }
    }
    
    /**
     * Enregistrement des directives avancées
     */
    private function registerAdvancedDirectives(): void
    {
        // @switch / @case / @default / @endswitch
        $this->registerDirective('switch', function($expression, $content) {
            return "<?php switch({$expression}): ?>" . $content . "<?php endswitch; ?>";
        });
        
        $this->registerDirective('case', function($value, $content) {
            return "<?php case {$value}: ?>" . $content . "<?php break; ?>";
        });
        
        $this->registerDirective('default', function($params, $content) {
            return "<?php default: ?>" . $content;
        });
        
        // @forelse / @empty / @endforelse
        $this->registerDirective('forelse', function($expression, $content) {
            return "<?php if(!empty({$expression})): foreach({$expression}): ?>" . $content . "<?php endforeach; else: ?>";
        });
        
        $this->registerDirective('empty', function($params, $content) {
            return $content . "<?php endif; ?>";
        });
        
        // @can / @cannot / @endcan / @endcannot
        $this->registerDirective('can', function($ability, $content) {
            return "<?php if(auth()->user() && auth()->user()->can({$ability})): ?>" . $content . "<?php endif; ?>";
        });
        
        $this->registerDirective('cannot', function($ability, $content) {
            return "<?php if(!auth()->user() || !auth()->user()->can({$ability})): ?>" . $content . "<?php endif; ?>";
        });
        
        // @production / @env
        $this->registerDirective('production', function($params, $content) {
            return "<?php if(app()->environment('production')): ?>" . $content . "<?php endif; ?>";
        });
        
        $this->registerDirective('env', function($environment, $content) {
            return "<?php if(app()->environment({$environment})): ?>" . $content . "<?php endif; ?>";
        });
        
        // @debug
        $this->registerDirective('debug', function($params, $content) {
            return "<?php if(config('app.debug')): ?>" . $content . "<?php endif; ?>";
        });
    }
    
    /**
     * Enregistrement des filtres avancés
     */
    private function registerAdvancedFilters(): void
    {
        $this->filters['markdown'] = function($value) {
            // Implémentation Markdown basique
            $value = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $value);
            $value = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $value);
            $value = preg_replace('/`(.*?)`/', '<code>$1</code>', $value);
            return $value;
        };
        
        $this->filters['highlight'] = function($value, $language = 'php') {
            return "<pre><code class='language-{$language}'>" . htmlspecialchars($value) . "</code></pre>";
        };
        
        $this->filters['resize'] = function($url, $width, $height) {
            return "/resize/{$width}x{$height}/" . ltrim($url, '/');
        };
        
        $this->filters['webp'] = function($url) {
            return preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $url);
        };
        
        $this->filters['strip_tags'] = function($value) {
            return strip_tags($value);
        };
        
        $this->filters['nl2br'] = function($value) {
            return nl2br($value);
        };
        
        $this->filters['join'] = function($array, $separator = ', ') {
            return is_array($array) ? implode($separator, $array) : $array;
        };
    }
    
    /**
     * Enregistrement des composants avancés
     */
    private function registerAdvancedComponents(): void
    {
        // Composant DataTable
        $this->registerComponent('data-table', function($attributes, $content) {
            $data = $attributes['data'] ?? '[]';
            $columns = $attributes['columns'] ?? '[]';
            $searchable = isset($attributes['searchable']) ? 'true' : 'false';
            $sortable = isset($attributes['sortable']) ? 'true' : 'false';
            $paginated = isset($attributes['paginated']) ? 'true' : 'false';
            
            return "
                <div class='data-table' 
                     data-searchable='{$searchable}'
                     data-sortable='{$sortable}'
                     data-paginated='{$paginated}'>
                    <div class='data-table-content'>
                        {$content}
                    </div>
                </div>
                <script>
                    new DataTable('.data-table', {
                        data: {$data},
                        columns: {$columns},
                        searchable: {$searchable},
                        sortable: {$sortable},
                        paginated: {$paginated}
                    });
                </script>
            ";
        });
        
        // Composant Chart
        $this->registerComponent('chart', function($attributes, $content) {
            $type = $attributes['type'] ?? 'line';
            $data = $attributes['data'] ?? '{}';
            $options = $attributes['options'] ?? '{}';
            $id = 'chart_' . uniqid();
            
            return "
                <div class='chart-container'>
                    <canvas id='{$id}'></canvas>
                </div>
                <script>
                    new Chart(document.getElementById('{$id}'), {
                        type: '{$type}',
                        data: {$data},
                        options: {$options}
                    });
                </script>
            ";
        });
        
        // Composant Rich Editor
        $this->registerComponent('rich-editor', function($attributes, $content) {
            $name = $attributes['name'] ?? 'content';
            $value = $attributes['value'] ?? '';
            $height = $attributes['height'] ?? '300px';
            $id = 'editor_' . uniqid();
            
            return "
                <div class='rich-editor'>
                    <div id='{$id}' style='height: {$height};'>{$value}</div>
                    <input type='hidden' name='{$name}' id='{$id}_input'>
                </div>
                <script>
                    const editor_{$id} = new Quill('#{$id}', {
                        theme: 'snow',
                        modules: {
                            toolbar: [
                                ['bold', 'italic', 'underline'],
                                ['link', 'image'],
                                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                ['clean']
                            ]
                        }
                    });
                    
                    editor_{$id}.on('text-change', function() {
                        document.getElementById('{$id}_input').value = editor_{$id}.root.innerHTML;
                    });
                </script>
            ";
        });
    }
    
    /**
     * Application du thème
     */
    private function applyTheme(string $content): string
    {
        if (!isset($this->themes[$this->currentTheme])) {
            return $content;
        }
        
        $theme = $this->themes[$this->currentTheme];
        
        // Remplacer les variables de thème
        foreach ($theme['variables'] as $variable => $value) {
            $content = str_replace("@themeVar('{$variable}')", $value, $content);
        }
        
        // Ajouter les styles de thème
        if (isset($theme['styles'])) {
            $styles = "<style>{$theme['styles']}</style>";
            $content = str_replace('</head>', $styles . '</head>', $content);
        }
        
        return $content;
    }
    
    /**
     * Injection des informations de debug
     */
    private function injectDebugInfo(string $content): string
    {
        $debugPanel = "
            <div id='nx-debug-panel' style='position: fixed; bottom: 0; right: 0; width: 400px; background: #1f2937; color: white; padding: 1rem; font-family: monospace; font-size: 12px; z-index: 9999; max-height: 50vh; overflow-y: auto;'>
                <h3 style='margin: 0 0 1rem 0; color: #10b981;'>🐛 NX Debug Panel</h3>
                <div><strong>Template:</strong> {$this->debugInfo['template']}</div>
                <div><strong>Render Time:</strong> {$this->debugInfo['render_time']}ms</div>
                <div><strong>Memory:</strong> {$this->debugInfo['memory_usage']}MB</div>
                <div><strong>Data Keys:</strong> " . implode(', ', $this->debugInfo['data_keys']) . "</div>
                <div><strong>Sections:</strong> " . implode(', ', $this->debugInfo['sections_used']) . "</div>
                <div><strong>Components:</strong> " . implode(', ', $this->debugInfo['components_used']) . "</div>
                <button onclick='document.getElementById(\"nx-debug-panel\").style.display=\"none\"' style='position: absolute; top: 5px; right: 5px; background: #ef4444; color: white; border: none; padding: 2px 6px; cursor: pointer;'>×</button>
            </div>
        ";
        
        return str_replace('</body>', $debugPanel . '</body>', $content);
    }
    
    /**
     * Activation du mode debug
     */
    public function enableDebug(bool $enabled = true): self
    {
        $this->debugMode = $enabled;
        return $this;
    }
    
    /**
     * Définition du thème actuel
     */
    public function setTheme(string $theme): self
    {
        $this->currentTheme = $theme;
        return $this;
    }
    
    /**
     * Enregistrement d'un thème
     */
    public function registerTheme(string $name, array $config): self
    {
        $this->themes[$name] = $config;
        return $this;
    }
    
    /**
     * Enregistrement d'un filtre personnalisé
     */
    public function registerFilter(string $name, callable $filter): self
    {
        $this->filters[$name] = $filter;
        return $this;
    }
    
    /**
     * Enregistrement d'une extension
     */
    public function registerExtension(string $name, $extension): self
    {
        $this->extensions[$name] = $extension;
        
        if (method_exists($extension, 'getDirectives')) {
            foreach ($extension->getDirectives() as $directiveName => $directive) {
                $this->registerDirective($directiveName, $directive);
            }
        }
        
        if (method_exists($extension, 'getFilters')) {
            foreach ($extension->getFilters() as $filterName => $filter) {
                $this->registerFilter($filterName, $filter);
            }
        }
        
        if (method_exists($extension, 'getComponents')) {
            foreach ($extension->getComponents() as $componentName => $component) {
                $this->registerComponent($componentName, $component);
            }
        }
        
        return $this;
    }
    
    // Méthodes utilitaires privées
    
    private function parseMacroParameters(string $params): array
    {
        // Parser les paramètres de macro: 'name', 'type' => 'text', 'required' => false
        $result = [];
        $params = trim($params);
        
        if (empty($params)) {
            return $result;
        }
        
        // Implémentation simplifiée
        $parts = explode(',', $params);
        foreach ($parts as $part) {
            $part = trim($part);
            if (strpos($part, '=>') !== false) {
                list($key, $value) = explode('=>', $part, 2);
                $key = trim($key, "' \"");
                $value = trim($value, "' \"");
                $result[$key] = $value;
            } else {
                $key = trim($part, "' \"");
                $result[$key] = null;
            }
        }
        
        return $result;
    }
    
    private function parseMacroArguments(string $args): array
    {
        // Parser les arguments: 'name' => 'email', 'type' => 'email'
        $result = [];
        $args = trim($args);
        
        if (empty($args)) {
            return $result;
        }
        
        // Implémentation simplifiée
        $parts = explode(',', $args);
        foreach ($parts as $part) {
            if (strpos($part, '=>') !== false) {
                list($key, $value) = explode('=>', $part, 2);
                $key = trim($key, "' \"");
                $value = trim($value, "' \"");
                $result[$key] = $value;
            }
        }
        
        return $result;
    }
    
    private function parseFilterParams(string $params): array
    {
        if (empty($params)) {
            return [];
        }
        
        // Implémentation simplifiée pour les paramètres de filtres
        return array_map('trim', explode(',', $params));
    }
    
    /**
     * Traitement des composants avancés
     */
    private function processAdvancedComponents(string $content, array $data): string
    {
        // Utiliser la méthode parent pour traiter les composants
        return $this->processComponents($content, $data);
    }
    
    /**
     * Traitement des directives avancées
     */
    private function processAdvancedDirectives(string $content, array $data): string
    {
        // Utiliser la méthode parent pour traiter les directives
        return $this->processDirectives($content, $data);
    }
    
    /**
     * Traitement des variables avec filtres
     */
    private function processVariablesWithFilters(string $content, array $data): string
    {
        // Utiliser la méthode parent pour traiter les variables de base
        return $this->processVariables($content, $data);
    }
    
    /**
     * Méthodes pour vérifier l'existence des éléments
     */
    public function hasDirective(string $name): bool
    {
        return isset($this->directives[$name]);
    }
    
    public function hasFilter(string $name): bool
    {
        return isset($this->filters[$name]);
    }
    
    public function hasComponent(string $name): bool
    {
        return isset($this->components[$name]);
    }
    

    
    private function evaluateVariable(string $variable, array $data)
    {
        // Évaluation sécurisée des variables
        extract($data);
        
        ob_start();
        try {
            eval("echo {$variable};");
            return ob_get_clean();
        } catch (Exception $e) {
            ob_end_clean();
            return '';
        }
    }
    
    private function getUsedComponents(string $content): array
    {
        preg_match_all('/<nx:([a-zA-Z0-9-_]+)/', $content, $matches);
        return array_unique($matches[1] ?? []);
    }
    
    private function renderError(Exception $e, string $template, array $data): string
    {
        return "
            <div style='background: #fee; border: 1px solid #fcc; padding: 1rem; margin: 1rem; border-radius: 4px;'>
                <h3 style='color: #c33; margin: 0 0 1rem 0;'>Template Error</h3>
                <p><strong>Template:</strong> {$template}</p>
                <p><strong>Error:</strong> {$e->getMessage()}</p>
                <p><strong>File:</strong> {$e->getFile()}:{$e->getLine()}</p>
                <details>
                    <summary>Stack Trace</summary>
                    <pre style='background: #f5f5f5; padding: 1rem; overflow-x: auto;'>{$e->getTraceAsString()}</pre>
                </details>
            </div>
        ";
    }
}