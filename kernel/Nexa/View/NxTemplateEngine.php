<?php

namespace Nexa\View;

class NxTemplateEngine
{
    private array $components = [];
    private array $directives = [];
    private string $templatePath;
    private array $globals = [];
    private ?ExtensionManager $extensionManager = null;
    
    // Propriétés pour l'héritage de templates
    private array $sections = [];
    private array $sectionStack = [];
    private array $pushStack = [];
    private ?string $extendedLayout = null;
    
    // Propriétés pour les macros
    private array $macros = [];
    
    // Propriétés pour les filtres
    private array $filters = [];
    
    public function __construct(string $templatePath = 'workspace/interface')
    {
        $this->templatePath = $templatePath;
        $this->registerDefaultDirectives();
        $this->registerDefaultComponents();
        $this->registerDefaultFilters();
    }
    
    public function render(string $template, array $data = []): string
    {
        $templateFile = $this->templatePath . '/' . $template . '.nx';
        
        if (!file_exists($templateFile)) {
            throw new \Exception("Template {$template} not found");
        }
        
        $content = file_get_contents($templateFile);
        
        // Traitement des macros
        $content = $this->processMacros($content);
        
        // Traitement des filtres
        $content = $this->processFilters($content);
        
        // Traiter les composants
        $content = $this->processComponents($content, $data);
        
        // Traiter les directives
        $content = $this->processDirectives($content, $data);
        
        // Traiter les variables
        $content = $this->processVariables($content, array_merge($this->globals, $data));
        
        // Injecter automatiquement les scripts JavaScript si ExtensionManager est configuré
        $content = $this->injectJavaScriptAssets($content);
        
        // Gestion de l'héritage de templates
        if ($this->extendedLayout) {
            $layoutContent = $this->render($this->extendedLayout, $data);
            // Remplacer les sections dans le layout
            foreach ($this->sections as $sectionName => $sectionContent) {
                $layoutContent = str_replace(
                    "<?php echo \$this->yieldSection('{$sectionName}'); ?>",
                    $sectionContent,
                    $layoutContent
                );
            }
            return $layoutContent;
        }
        
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
    
    private function registerDefaultDirectives(): void
    {
        // Directive @if
        $this->registerDirective('if', function($params, $content) {
            // Évaluer la condition
            $condition = eval("return {$params};");
            return $condition ? $content : '';
        });
        
        // Directive @foreach
        $this->registerDirective('foreach', function($params, $content) {
            $result = '';
            // Parser les paramètres: $items as $item
            if (preg_match('/\$([a-zA-Z_][a-zA-Z0-9_]*) as \$([a-zA-Z_][a-zA-Z0-9_]*)/', $params, $matches)) {
                $arrayVar = $matches[1];
                $itemVar = $matches[2];
                
                // Récupérer le tableau depuis les globals
                $items = $this->globals[$arrayVar] ?? [];
                
                foreach ($items as $item) {
                    // Remplacer les variables dans le contenu
                    $itemContent = str_replace('{{$' . $itemVar . '}}', $item, $content);
                    $result .= $itemContent;
                }
            }
            return $result;
        });
        
        // Directive @include
        $this->registerDirective('include', function($params, $content) {
            $templateName = trim($params, '"\' ');
            return $this->render($templateName);
        });
        
        // ==================== DIRECTIVES AVANCÉES ====================
        
        // Directives de contrôle avancées
        $this->registerDirective('switch', function($expression, $content) {
            return "<?php switch({$expression}): ?>" . $content . "<?php endswitch; ?>";
        });
        
        $this->registerDirective('case', function($value, $content) {
            return "<?php case {$value}: ?>" . $content . "<?php break; ?>";
        });
        
        $this->registerDirective('default', function($params, $content) {
            return "<?php default: ?>" . $content;
        });
        
        $this->registerDirective('break', function($params, $content) {
            return "<?php break; ?>";
        });
        
        // Directives de boucle avancées
        $this->registerDirective('forelse', function($expression, $content) {
            return "<?php if(!empty({$expression})): foreach({$expression}): ?>" . $content . "<?php endforeach; else: ?>";
        });
        
        $this->registerDirective('empty', function($params, $content) {
            return $content . "<?php endif; ?>";
        });
        
        $this->registerDirective('for', function($expression, $content) {
            return "<?php for({$expression}): ?>" . $content . "<?php endfor; ?>";
        });
        
        $this->registerDirective('while', function($condition, $content) {
            return "<?php while({$condition}): ?>" . $content . "<?php endwhile; ?>";
        });
        
        // Directives de sécurité et autorisation
        $this->registerDirective('can', function($ability, $content) {
            return "<?php if(auth()->user() && auth()->user()->can({$ability})): ?>" . $content . "<?php endif; ?>";
        });
        
        $this->registerDirective('cannot', function($ability, $content) {
            return "<?php if(!auth()->user() || !auth()->user()->can({$ability})): ?>" . $content . "<?php endif; ?>";
        });
        
        $this->registerDirective('auth', function($guard, $content) {
            $guardCheck = $guard ? "auth('{$guard}')->check()" : "auth()->check()";
            return "<?php if({$guardCheck}): ?>" . $content . "<?php endif; ?>";
        });
        
        $this->registerDirective('guest', function($guard, $content) {
            $guardCheck = $guard ? "auth('{$guard}')->guest()" : "auth()->guest()";
            return "<?php if({$guardCheck}): ?>" . $content . "<?php endif; ?>";
        });
        
        // Directives d'environnement
        $this->registerDirective('production', function($params, $content) {
            return "<?php if(app()->environment('production')): ?>" . $content . "<?php endif; ?>";
        });
        
        $this->registerDirective('env', function($environment, $content) {
            return "<?php if(app()->environment({$environment})): ?>" . $content . "<?php endif; ?>";
        });
        
        $this->registerDirective('debug', function($params, $content) {
            return "<?php if(config('app.debug')): ?>" . $content . "<?php endif; ?>";
        });
        
        // Directives d'inclusion avancées
        $this->registerDirective('includeIf', function($template, $content) {
            return "<?php if(view()->exists({$template})): ?>" . "<?php echo view({$template})->render(); ?>" . "<?php endif; ?>";
        });
        
        $this->registerDirective('includeWhen', function($condition, $content) {
            $parts = explode(',', $condition, 2);
            $cond = trim($parts[0]);
            $template = trim($parts[1] ?? '');
            return "<?php if({$cond}): ?>" . "<?php echo view({$template})->render(); ?>" . "<?php endif; ?>";
        });
        
        $this->registerDirective('includeUnless', function($condition, $content) {
            $parts = explode(',', $condition, 2);
            $cond = trim($parts[0]);
            $template = trim($parts[1] ?? '');
            return "<?php if(!({$cond})): ?>" . "<?php echo view({$template})->render(); ?>" . "<?php endif; ?>";
        });
        
        $this->registerDirective('includeFirst', function($templates, $content) {
            return "<?php echo view()->first({$templates})->render(); ?>";
        });
        
        // Directives utilitaires
        $this->registerDirective('isset', function($variable, $content) {
            return "<?php if(isset({$variable})): ?>" . $content . "<?php endif; ?>";
        });
        
        $this->registerDirective('unless', function($condition, $content) {
            return "<?php unless({$condition}): ?>" . $content . "<?php endunless; ?>";
        });
        
        $this->registerDirective('json', function($data, $content) {
            return "<?php echo json_encode({$data}); ?>";
        });
        
        $this->registerDirective('js', function($data, $content) {
            return "<script>window.{$data} = <?php echo json_encode({$data}); ?></script>";
        });
        
        // Directives de sécurité web
        $this->registerDirective('csrf', function($params, $content) {
            return "<input type='hidden' name='_token' value='<?php echo csrf_token(); ?>'>";
        });
        
        $this->registerDirective('method', function($method, $content) {
            return "<input type='hidden' name='_method' value='{$method}'>";
        });
        
        // Directives personnalisées
        $this->registerDirective('datetime', function($date, $content) {
            $format = $content ?: 'd/m/Y H:i';
            return "<?php echo date('{$format}', strtotime({$date})); ?>";
        });
        
        $this->registerDirective('money', function($amount, $content) {
            $currency = $content ?: 'EUR';
            return "<?php echo number_format({$amount}, 2) . ' {$currency}'; ?>";
        });
        
        $this->registerDirective('avatar', function($user, $content) {
            $size = $content ?: 'md';
            return "<?php echo avatar({$user}, '{$size}'); ?>";
        });
        
        // Directives d'héritage de templates
        $this->registerDirective('extends', function($layout, $content) {
            return "<?php \$this->extend({$layout}); ?>";
        });
        
        $this->registerDirective('section', function($name, $content) {
            return "<?php \$this->startSection({$name}); ?>" . $content . "<?php \$this->endSection(); ?>";
        });
        
        $this->registerDirective('yield', function($section, $content) {
            $default = $content ?: "''";
            return "<?php echo \$this->yieldSection({$section}, {$default}); ?>";
        });
        
        $this->registerDirective('push', function($stack, $content) {
            return "<?php \$this->startPush({$stack}); ?>" . $content . "<?php \$this->endPush(); ?>";
        });
        
        $this->registerDirective('stack', function($name, $content) {
             return "<?php echo \$this->yieldStack({$name}); ?>";
         });
     }
     
     // ==================== MÉTHODES POUR L'HÉRITAGE DE TEMPLATES ====================
     
     public function extend(string $layout): void
     {
         $this->extendedLayout = $layout;
     }
     
     public function startSection(string $name): void
     {
         $this->sectionStack[] = $name;
         ob_start();
     }
     
     public function endSection(): void
     {
         if (empty($this->sectionStack)) {
             throw new \Exception('No section started');
         }
         
         $name = array_pop($this->sectionStack);
         $content = ob_get_clean();
         $this->sections[$name] = $content;
     }
     
     public function yieldSection(string $name, string $default = ''): string
     {
         return $this->sections[$name] ?? $default;
     }
     
     public function startPush(string $stack): void
     {
         $this->sectionStack[] = $stack;
         ob_start();
     }
     
     public function endPush(): void
     {
         if (empty($this->sectionStack)) {
             throw new \Exception('No push started');
         }
         
         $stack = array_pop($this->sectionStack);
         $content = ob_get_clean();
         
         if (!isset($this->pushStack[$stack])) {
             $this->pushStack[$stack] = [];
         }
         
         $this->pushStack[$stack][] = $content;
     }
     
     public function yieldStack(string $name): string
     {
         if (!isset($this->pushStack[$name])) {
             return '';
         }
         
         return implode('\n', $this->pushStack[$name]);
     }
     
     // ==================== MÉTHODES POUR LES MACROS ====================
     
     public function registerMacro(string $name, callable $callback): void
     {
         $this->macros[$name] = $callback;
     }
     
     public function callMacro(string $name, array $params = []): string
     {
         if (!isset($this->macros[$name])) {
             throw new \Exception("Macro '{$name}' not found");
         }
         
         return call_user_func_array($this->macros[$name], $params);
     }
     
     public function hasMacro(string $name): bool
     {
         return isset($this->macros[$name]);
     }
     
     private function processMacros(string $content): string
     {
         // Traiter les définitions de macros @macro
         $content = preg_replace_callback(
             '/@macro\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(([^)]*)\)\s*\{([^}]*)\}/s',
             function($matches) {
                 $name = $matches[1];
                 $params = array_map('trim', explode(',', $matches[2]));
                 $body = $matches[3];
                 
                 $this->registerMacro($name, function(...$args) use ($params, $body) {
                     $replacements = [];
                     foreach ($params as $i => $param) {
                         $replacements[$param] = $args[$i] ?? '';
                     }
                     
                     return str_replace(array_keys($replacements), array_values($replacements), $body);
                 });
                 
                 return ''; // Supprimer la définition du contenu
             },
             $content
         );
         
         // Traiter les appels de macros @call
         $content = preg_replace_callback(
             '/@call\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(([^)]*)\)/',
             function($matches) {
                 $name = $matches[1];
                 $params = array_map('trim', explode(',', $matches[2]));
                 
                 if ($this->hasMacro($name)) {
                     return $this->callMacro($name, $params);
                 }
                 
                 return $matches[0]; // Retourner tel quel si macro non trouvée
             },
             $content
         );
         
         return $content;
     }
     
     // ==================== MÉTHODES POUR LES FILTRES ====================
     
     public function registerFilter(string $name, callable $callback): void
     {
         $this->filters[$name] = $callback;
     }
     
     public function applyFilter(string $name, $value, array $params = [])
     {
         if (!isset($this->filters[$name])) {
             throw new \Exception("Filter '{$name}' not found");
         }
         
         return call_user_func_array($this->filters[$name], array_merge([$value], $params));
     }
     
     public function hasFilter(string $name): bool
     {
         return isset($this->filters[$name]);
     }
     
     private function registerDefaultFilters(): void
     {
         // Filtres de base
         $this->registerFilter('upper', function($value) {
             return strtoupper($value);
         });
         
         $this->registerFilter('lower', function($value) {
             return strtolower($value);
         });
         
         $this->registerFilter('capitalize', function($value) {
             return ucfirst($value);
         });
         
         $this->registerFilter('title', function($value) {
             return ucwords($value);
         });
         
         $this->registerFilter('length', function($value) {
             return is_array($value) ? count($value) : strlen($value);
         });
         
         $this->registerFilter('reverse', function($value) {
             return is_array($value) ? array_reverse($value) : strrev($value);
         });
         
         $this->registerFilter('sort', function($value) {
             if (is_array($value)) {
                 sort($value);
                 return $value;
             }
             return $value;
         });
         
         $this->registerFilter('join', function($value, $separator = ', ') {
             return is_array($value) ? implode($separator, $value) : $value;
         });
         
         $this->registerFilter('split', function($value, $separator = ',') {
             return explode($separator, $value);
         });
         
         $this->registerFilter('trim', function($value) {
             return trim($value);
         });
         
         $this->registerFilter('strip_tags', function($value) {
             return strip_tags($value);
         });
         
         $this->registerFilter('nl2br', function($value) {
             return nl2br($value);
         });
         
         $this->registerFilter('escape', function($value) {
             return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
         });
         
         $this->registerFilter('raw', function($value) {
             return $value; // Pas d'échappement
         });
         
         // Filtres de formatage
         $this->registerFilter('date', function($value, $format = 'd/m/Y') {
             return date($format, is_numeric($value) ? $value : strtotime($value));
         });
         
         $this->registerFilter('number', function($value, $decimals = 0) {
             return number_format($value, $decimals);
         });
         
         $this->registerFilter('currency', function($value, $currency = 'EUR') {
             return number_format($value, 2) . ' ' . $currency;
         });
         
         $this->registerFilter('percentage', function($value, $decimals = 1) {
             return number_format($value * 100, $decimals) . '%';
         });
         
         // Filtres avancés
         $this->registerFilter('truncate', function($value, $length = 100, $suffix = '...') {
             return strlen($value) > $length ? substr($value, 0, $length) . $suffix : $value;
         });
         
         $this->registerFilter('slug', function($value) {
             return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $value));
         });
         
         $this->registerFilter('markdown', function($value) {
             // Implémentation basique de markdown
             $value = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $value);
             $value = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $value);
             $value = preg_replace('/`(.*?)`/', '<code>$1</code>', $value);
             return $value;
         });
         
         $this->registerFilter('highlight', function($value, $search) {
             return str_replace($search, '<mark>' . $search . '</mark>', $value);
         });
     }
     
     private function processFilters(string $content): string
     {
         // Traiter les filtres dans les interpolations {{ value | filter }}
         return preg_replace_callback(
             '/\{\{\s*([^|]+?)\s*\|\s*([^}]+?)\s*\}\}/',
             function($matches) {
                 $value = trim($matches[1]);
                 $filterChain = trim($matches[2]);
                 
                 // Parser la chaîne de filtres
                 $filters = explode('|', $filterChain);
                 $result = $value;
                 
                 foreach ($filters as $filter) {
                     $filter = trim($filter);
                     if (preg_match('/([a-zA-Z_][a-zA-Z0-9_]*)\s*\(([^)]*)\)/', $filter, $filterMatches)) {
                         $filterName = $filterMatches[1];
                         $params = array_map('trim', explode(',', $filterMatches[2]));
                         $result = "\$this->applyFilter('{$filterName}', {$result}, [" . implode(', ', $params) . "])";
                     } else {
                         $result = "\$this->applyFilter('{$filter}', {$result})";
                     }
                 }
                 
                 return "<?php echo {$result}; ?>";
             },
             $content
         );
     }
     
     // ==================== MÉTHODES UTILITAIRES ====================
     
     public function hasDirective(string $name): bool
     {
         return isset($this->directives[$name]);
     }
     
     public function hasComponent(string $name): bool
     {
         return isset($this->components[$name]);
     }
     
     private function registerDefaultComponents(): void
    {
        // ==================== LAYOUT & CONTAINER ====================
        
        // Composant Container
        $this->registerComponent('container', function($attributes, $content) {
            $type = $attributes['type'] ?? 'responsive';
            $size = $attributes['size'] ?? 'max-w-7xl';
            $padding = $attributes['padding'] ?? 'px-4 sm:px-6 lg:px-8';
            
            $typeClasses = [
                'responsive' => $size . ' mx-auto',
                'fixed' => 'w-full max-w-none',
                'full' => 'w-full'
            ];
            
            $typeClass = $typeClasses[$type] ?? $typeClasses['responsive'];
            $customClass = $attributes['class'] ?? '';
            $finalClass = trim("{$typeClass} {$padding} {$customClass}");
            
            return "<div class='{$finalClass}'>{$content}</div>";
        });
        
        // Composant Grid
        $this->registerComponent('grid', function($attributes, $content) {
            $cols = $attributes['cols'] ?? 'auto-fit';
            $gap = $attributes['gap'] ?? 'gap-4';
            $minWidth = $attributes['min-width'] ?? '250px';
            
            if ($cols === 'auto-fit') {
                $gridClass = "grid-cols-[repeat(auto-fit,minmax({$minWidth},1fr))]";
            } elseif ($cols === 'auto-fill') {
                $gridClass = "grid-cols-[repeat(auto-fill,minmax({$minWidth},1fr))]";
            } else {
                $gridClass = "grid-cols-{$cols}";
            }
            
            $customClass = $attributes['class'] ?? '';
            $finalClass = trim("grid {$gridClass} {$gap} {$customClass}");
            
            return "<div class='{$finalClass}'>{$content}</div>";
        });
        
        // Composant Auto Grid
        $this->registerComponent('auto-grid', function($attributes, $content) {
            $minWidth = $attributes['min-width'] ?? '250px';
            $gap = $attributes['gap'] ?? 'gap-4';
            $customClass = $attributes['class'] ?? '';
            
            $finalClass = trim("grid grid-cols-[repeat(auto-fit,minmax({$minWidth},1fr))] {$gap} {$customClass}");
            
            return "<div class='{$finalClass}'>{$content}</div>";
        });
        
        // Composant Flex Container
        $this->registerComponent('flex-container', function($attributes, $content) {
            $direction = $attributes['direction'] ?? 'row';
            $justify = $attributes['justify'] ?? 'start';
            $align = $attributes['align'] ?? 'stretch';
            $wrap = $attributes['wrap'] ?? 'nowrap';
            $gap = $attributes['gap'] ?? 'gap-4';
            
            $directionClass = "flex-{$direction}";
            $justifyClass = "justify-{$justify}";
            $alignClass = "items-{$align}";
            $wrapClass = $wrap === 'wrap' ? 'flex-wrap' : 'flex-nowrap';
            
            $customClass = $attributes['class'] ?? '';
            $finalClass = trim("flex {$directionClass} {$justifyClass} {$alignClass} {$wrapClass} {$gap} {$customClass}");
            
            return "<div class='{$finalClass}'>{$content}</div>";
        });
        
        // Composant Stack
        $this->registerComponent('stack', function($attributes, $content) {
            $direction = $attributes['direction'] ?? 'vertical';
            $spacing = $attributes['spacing'] ?? 'space-y-4';
            $align = $attributes['align'] ?? 'stretch';
            
            if ($direction === 'horizontal') {
                $flexClass = 'flex flex-row';
                $spacingClass = str_replace('y-', 'x-', $spacing);
                $alignClass = "items-{$align}";
            } else {
                $flexClass = 'flex flex-col';
                $spacingClass = $spacing;
                $alignClass = "items-{$align}";
            }
            
            $customClass = $attributes['class'] ?? '';
            $finalClass = trim("{$flexClass} {$spacingClass} {$alignClass} {$customClass}");
            
            return "<div class='{$finalClass}'>{$content}</div>";
        });
        
        // Composant Card
        $this->registerComponent('card', function($attributes, $content) {
            $title = $attributes['title'] ?? '';
            $class = $attributes['class'] ?? 'bg-white rounded-lg shadow-md border border-gray-200';
            $headerClass = $attributes['header-class'] ?? 'px-6 py-4 border-b border-gray-200';
            $bodyClass = $attributes['body-class'] ?? 'p-6';
            
            $header = $title ? "<div class='{$headerClass}'><h3 class='text-lg font-semibold text-gray-900'>{$title}</h3></div>" : '';
            
            return "
                <div class='{$class}'>
                    {$header}
                    <div class='{$bodyClass}'>
                        {$content}
                    </div>
                </div>
            ";
        });
        
        // Composant Button
        $this->registerComponent('button', function($attributes, $content) {
            $type = $attributes['type'] ?? 'button';
            $variant = $attributes['variant'] ?? 'primary';
            $size = $attributes['size'] ?? 'md';
            $disabled = isset($attributes['disabled']);
            
            $variantClasses = [
                'primary' => 'bg-blue-600 hover:bg-blue-700 text-white',
                'secondary' => 'bg-gray-600 hover:bg-gray-700 text-white',
                'outline' => 'border border-blue-600 text-blue-600 hover:bg-blue-50',
                'ghost' => 'text-blue-600 hover:bg-blue-50',
                'danger' => 'bg-red-600 hover:bg-red-700 text-white'
            ];
            
            $sizeClasses = [
                'sm' => 'px-3 py-1.5 text-sm',
                'md' => 'px-4 py-2 text-base',
                'lg' => 'px-6 py-3 text-lg'
            ];
            
            $variantClass = $variantClasses[$variant] ?? $variantClasses['primary'];
            $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
            $disabledClass = $disabled ? 'opacity-50 cursor-not-allowed' : '';
            $customClass = $attributes['class'] ?? '';
            
            $finalClass = trim("inline-flex items-center justify-center font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 {$variantClass} {$sizeClass} {$disabledClass} {$customClass}");
            $disabledAttr = $disabled ? 'disabled' : '';
            
            return "<button type='{$type}' class='{$finalClass}' {$disabledAttr}>{$content}</button>";
        });
        
        // ==================== TYPOGRAPHY ====================
        
        // Composant Heading
        $this->registerComponent('heading', function($attributes, $content) {
            $level = $attributes['level'] ?? '1';
            $size = $attributes['size'] ?? null;
            $weight = $attributes['weight'] ?? 'font-bold';
            $color = $attributes['color'] ?? 'text-gray-900';
            
            $defaultSizes = [
                '1' => 'text-4xl',
                '2' => 'text-3xl',
                '3' => 'text-2xl',
                '4' => 'text-xl',
                '5' => 'text-lg',
                '6' => 'text-base'
            ];
            
            $sizeClass = $size ?? $defaultSizes[$level] ?? 'text-xl';
            $customClass = $attributes['class'] ?? '';
            $finalClass = trim("{$sizeClass} {$weight} {$color} {$customClass}");
            
            return "<h{$level} class='{$finalClass}'>{$content}</h{$level}>";
        });
        
        // Composant Text
        $this->registerComponent('text', function($attributes, $content) {
            $size = $attributes['size'] ?? 'text-base';
            $weight = $attributes['weight'] ?? 'font-normal';
            $color = $attributes['color'] ?? 'text-gray-700';
            $align = $attributes['align'] ?? '';
            
            $alignClass = $align ? "text-{$align}" : '';
            $customClass = $attributes['class'] ?? '';
            $finalClass = trim("{$size} {$weight} {$color} {$alignClass} {$customClass}");
            
            return "<p class='{$finalClass}'>{$content}</p>";
        });
        
        // Composant Link
        $this->registerComponent('link', function($attributes, $content) {
            $href = $attributes['href'] ?? '#';
            $target = $attributes['target'] ?? '';
            $variant = $attributes['variant'] ?? 'default';
            
            $variantClasses = [
                'default' => 'text-blue-600 hover:text-blue-800 hover:underline',
                'button' => 'inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700',
                'subtle' => 'text-gray-600 hover:text-gray-900'
            ];
            
            $variantClass = $variantClasses[$variant] ?? $variantClasses['default'];
            $targetAttr = $target ? "target='{$target}'" : '';
            $customClass = $attributes['class'] ?? '';
            $finalClass = trim("{$variantClass} {$customClass}");
            
            return "<a href='{$href}' {$targetAttr} class='{$finalClass}'>{$content}</a>";
        });
        
        // ==================== FORMS ====================
        
        // Composant Form
        $this->registerComponent('form', function($attributes, $content) {
            $method = $attributes['method'] ?? 'POST';
            $action = $attributes['action'] ?? '';
            $enctype = $attributes['enctype'] ?? '';
            
            $actionAttr = $action ? "action='{$action}'" : '';
            $enctypeAttr = $enctype ? "enctype='{$enctype}'" : '';
            $customClass = $attributes['class'] ?? 'space-y-6';
            
            return "<form method='{$method}' {$actionAttr} {$enctypeAttr} class='{$customClass}'>{$content}</form>";
        });
        
        // Composant Input
        $this->registerComponent('input', function($attributes, $content) {
            $type = $attributes['type'] ?? 'text';
            $name = $attributes['name'] ?? '';
            $placeholder = $attributes['placeholder'] ?? '';
            $value = $attributes['value'] ?? '';
            $required = isset($attributes['required']) ? 'required' : '';
            $disabled = isset($attributes['disabled']) ? 'disabled' : '';
            
            $nameAttr = $name ? "name='{$name}'" : '';
            $placeholderAttr = $placeholder ? "placeholder='{$placeholder}'" : '';
            $valueAttr = $value ? "value='{$value}'" : '';
            $customClass = $attributes['class'] ?? 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500';
            
            return "<input type='{$type}' {$nameAttr} {$placeholderAttr} {$valueAttr} {$required} {$disabled} class='{$customClass}' />";
        });
        
        // Composant Textarea
        $this->registerComponent('textarea', function($attributes, $content) {
            $name = $attributes['name'] ?? '';
            $placeholder = $attributes['placeholder'] ?? '';
            $rows = $attributes['rows'] ?? '4';
            $required = isset($attributes['required']) ? 'required' : '';
            $disabled = isset($attributes['disabled']) ? 'disabled' : '';
            
            $nameAttr = $name ? "name='{$name}'" : '';
            $placeholderAttr = $placeholder ? "placeholder='{$placeholder}'" : '';
            $customClass = $attributes['class'] ?? 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500';
            
            return "<textarea {$nameAttr} {$placeholderAttr} rows='{$rows}' {$required} {$disabled} class='{$customClass}'>{$content}</textarea>";
        });
        
        // Composant Select
        $this->registerComponent('select', function($attributes, $content) {
            $name = $attributes['name'] ?? '';
            $required = isset($attributes['required']) ? 'required' : '';
            $disabled = isset($attributes['disabled']) ? 'disabled' : '';
            
            $nameAttr = $name ? "name='{$name}'" : '';
            $customClass = $attributes['class'] ?? 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500';
            
            return "<select {$nameAttr} {$required} {$disabled} class='{$customClass}'>{$content}</select>";
        });
        
        // ==================== NAVIGATION ====================
        
        // Composant Navigation
        $this->registerComponent('navigation', function($attributes, $content) {
            $variant = $attributes['variant'] ?? 'horizontal';
            $customClass = $attributes['class'] ?? '';
            
            $variantClasses = [
                'horizontal' => 'flex space-x-4',
                'vertical' => 'flex flex-col space-y-2',
                'pills' => 'flex space-x-2'
            ];
            
            $variantClass = $variantClasses[$variant] ?? $variantClasses['horizontal'];
            $finalClass = trim("{$variantClass} {$customClass}");
            
            return "<nav class='{$finalClass}'>{$content}</nav>";
        });
        
        // Composant Breadcrumb
        $this->registerComponent('breadcrumb', function($attributes, $content) {
            $separator = $attributes['separator'] ?? '/';
            $customClass = $attributes['class'] ?? 'flex items-center space-x-2 text-sm text-gray-600';
            
            return "<nav aria-label='Breadcrumb' class='{$customClass}'>{$content}</nav>";
        });
        
        // ==================== FEEDBACK ====================
        
        // Composant Alert
        $this->registerComponent('alert', function($attributes, $content) {
            $variant = $attributes['variant'] ?? 'info';
            $dismissible = isset($attributes['dismissible']);
            
            $variantClasses = [
                'info' => 'bg-blue-50 border-blue-200 text-blue-800',
                'success' => 'bg-green-50 border-green-200 text-green-800',
                'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
                'error' => 'bg-red-50 border-red-200 text-red-800'
            ];
            
            $variantClass = $variantClasses[$variant] ?? $variantClasses['info'];
            $customClass = $attributes['class'] ?? '';
            $finalClass = trim("border rounded-md p-4 {$variantClass} {$customClass}");
            
            $dismissButton = $dismissible ? "<button type='button' class='float-right text-lg font-bold'>&times;</button>" : '';
            
            return "<div class='{$finalClass}' role='alert'>{$dismissButton}{$content}</div>";
        });
        
        // Composant Badge
        $this->registerComponent('badge', function($attributes, $content) {
            $variant = $attributes['variant'] ?? 'default';
            $size = $attributes['size'] ?? 'md';
            
            $variantClasses = [
                'default' => 'bg-gray-100 text-gray-800',
                'primary' => 'bg-blue-100 text-blue-800',
                'success' => 'bg-green-100 text-green-800',
                'warning' => 'bg-yellow-100 text-yellow-800',
                'error' => 'bg-red-100 text-red-800'
            ];
            
            $sizeClasses = [
                'sm' => 'px-2 py-1 text-xs',
                'md' => 'px-2.5 py-0.5 text-sm',
                'lg' => 'px-3 py-1 text-base'
            ];
            
            $variantClass = $variantClasses[$variant] ?? $variantClasses['default'];
            $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
            $customClass = $attributes['class'] ?? '';
            $finalClass = trim("inline-flex items-center font-medium rounded-full {$variantClass} {$sizeClass} {$customClass}");
            
            return "<span class='{$finalClass}'>{$content}</span>";
        });
        
        // ==================== DATA DISPLAY ====================
        
        // Composant Table
        $this->registerComponent('table', function($attributes, $content) {
            $striped = isset($attributes['striped']);
            $bordered = isset($attributes['bordered']);
            $hover = isset($attributes['hover']);
            
            $baseClass = 'min-w-full divide-y divide-gray-200';
            $stripedClass = $striped ? 'divide-y divide-gray-200' : '';
            $borderedClass = $bordered ? 'border border-gray-200' : '';
            $hoverClass = $hover ? 'hover:bg-gray-50' : '';
            
            $customClass = $attributes['class'] ?? '';
            $finalClass = trim("{$baseClass} {$stripedClass} {$borderedClass} {$customClass}");
            
            return "<div class='overflow-x-auto'><table class='{$finalClass}'>{$content}</table></div>";
        });
        
        // Composant Avatar
        $this->registerComponent('avatar', function($attributes, $content) {
            $src = $attributes['src'] ?? '';
            $alt = $attributes['alt'] ?? 'Avatar';
            $size = $attributes['size'] ?? 'md';
            $initials = $attributes['initials'] ?? '';
            
            $sizeClasses = [
                'sm' => 'w-8 h-8 text-sm',
                'md' => 'w-10 h-10 text-base',
                'lg' => 'w-12 h-12 text-lg',
                'xl' => 'w-16 h-16 text-xl'
            ];
            
            $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
            $customClass = $attributes['class'] ?? '';
            $finalClass = trim("inline-flex items-center justify-center rounded-full bg-gray-300 {$sizeClass} {$customClass}");
            
            if ($src) {
                return "<img src='{$src}' alt='{$alt}' class='{$finalClass}' />";
            } elseif ($initials) {
                return "<div class='{$finalClass} font-medium text-gray-700'>{$initials}</div>";
            } else {
                return "<div class='{$finalClass} font-medium text-gray-700'>{$content}</div>";
            }
        });
        
        // ==================== OVERLAY ====================
        
        // Composant Modal
        $this->registerComponent('modal', function($attributes, $content) {
            $id = $attributes['id'] ?? 'modal-' . uniqid();
            $size = $attributes['size'] ?? 'md';
            $customClass = $attributes['class'] ?? '';
            
            $sizeClasses = [
                'sm' => 'max-w-sm',
                'md' => 'max-w-md',
                'lg' => 'max-w-lg',
                'xl' => 'max-w-xl',
                '2xl' => 'max-w-2xl'
            ];
            
            $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
            $finalClass = trim("{$sizeClass} {$customClass}");
            
            return "
                <div data-modal id='{$id}' class='fixed inset-0 z-50 hidden overflow-y-auto' aria-labelledby='{$id}-title' role='dialog' aria-modal='true'>
                    <div class='flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0'>
                        <div data-modal-overlay class='fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity' aria-hidden='true'></div>
                        <span class='hidden sm:inline-block sm:align-middle sm:h-screen' aria-hidden='true'>&#8203;</span>
                        <div data-modal-content class='inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle {$finalClass} w-full'>
                            {$content}
                        </div>
                    </div>
                </div>
            ";
        });
        
        // Composant Modal Header
        $this->registerComponent('modal-header', function($attributes, $content) {
            $customClass = $attributes['class'] ?? 'px-6 py-4 border-b border-gray-200';
            
            return "
                <div data-modal-header class='{$customClass}'>
                    <div class='flex items-center justify-between'>
                        <h3 class='text-lg font-medium text-gray-900'>{$content}</h3>
                        <button data-modal-close class='text-gray-400 hover:text-gray-600 focus:outline-none'>
                            <svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'></path>
                            </svg>
                        </button>
                    </div>
                </div>
            ";
        });
        
        // Composant Modal Body
        $this->registerComponent('modal-body', function($attributes, $content) {
            $customClass = $attributes['class'] ?? 'px-6 py-4';
            
            return "<div data-modal-body class='{$customClass}'>{$content}</div>";
        });
        
        // Composant Modal Footer
        $this->registerComponent('modal-footer', function($attributes, $content) {
            $customClass = $attributes['class'] ?? 'px-6 py-4 border-t border-gray-200 flex justify-end space-x-3';
            
            return "<div data-modal-footer class='{$customClass}'>{$content}</div>";
        });
        
        // Composant Dropdown
        $this->registerComponent('dropdown', function($attributes, $content) {
            $trigger = $attributes['trigger'] ?? 'Click me';
            $position = $attributes['position'] ?? 'bottom-left';
            $variant = $attributes['variant'] ?? 'default';
            
            $positionClasses = [
                'bottom-left' => 'origin-top-left left-0',
                'bottom-right' => 'origin-top-right right-0',
                'top-left' => 'origin-bottom-left left-0 bottom-full',
                'top-right' => 'origin-bottom-right right-0 bottom-full'
            ];
            
            $variantClasses = [
                'default' => 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
                'primary' => 'border border-transparent bg-blue-600 text-white hover:bg-blue-700',
                'secondary' => 'border border-gray-300 bg-gray-100 text-gray-700 hover:bg-gray-200'
            ];
            
            $positionClass = $positionClasses[$position] ?? $positionClasses['bottom-left'];
            $variantClass = $variantClasses[$variant] ?? $variantClasses['default'];
            $customClass = $attributes['class'] ?? '';
            
            return "
                <div data-dropdown class='relative inline-block text-left'>
                    <button data-dropdown-trigger type='button' class='inline-flex justify-center w-full rounded-md shadow-sm px-4 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 {$variantClass}'>
                        {$trigger}
                        <svg class='-mr-1 ml-2 h-5 w-5 transition-transform duration-200' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='currentColor'>
                            <path fill-rule='evenodd' d='M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z' clip-rule='evenodd' />
                        </svg>
                    </button>
                    <div data-dropdown-menu class='absolute z-10 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 {$positionClass} {$customClass}'>
                        <div class='py-1' role='menu'>
                            {$content}
                        </div>
                    </div>
                </div>
            ";
        });
        
        // Composant Dropdown Item
        $this->registerComponent('dropdown-item', function($attributes, $content) {
            $href = $attributes['href'] ?? '#';
            $customClass = $attributes['class'] ?? '';
            $finalClass = trim("block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {$customClass}");
            
            return "<a href='{$href}' class='{$finalClass}'>{$content}</a>";
        });
        
        // Composant Dropdown Divider
        $this->registerComponent('dropdown-divider', function($attributes, $content) {
            return "<hr class='my-1 border-gray-200'>";
        });
        
        // ==================== INTERACTIVE ====================
        
        // Composant Accordion
        $this->registerComponent('accordion', function($attributes, $content) {
            $multiple = isset($attributes['multiple']);
            $customClass = $attributes['class'] ?? 'divide-y divide-gray-200';
            $multipleAttr = $multiple ? 'data-multiple' : '';
            
            return "<div data-accordion class='{$customClass}' {$multipleAttr}>{$content}</div>";
        });
        
        // Composant Accordion Item
        $this->registerComponent('accordion-item', function($attributes, $content) {
            $open = isset($attributes['open']);
            $customClass = $attributes['class'] ?? '';
            $openClass = $open ? 'open' : '';
            $finalClass = trim("{$openClass} {$customClass}");
            
            return "<div data-accordion-item class='{$finalClass}'>{$content}</div>";
        });
        
        // Composant Accordion Header
        $this->registerComponent('accordion-header', function($attributes, $content) {
            $customClass = $attributes['class'] ?? 'px-6 py-4 cursor-pointer hover:bg-gray-50 flex justify-between items-center';
            
            return "
                <div data-accordion-header class='{$customClass}'>
                    <span class='font-medium text-gray-900'>{$content}</span>
                    <svg class='w-5 h-5 text-gray-500 transition-transform duration-200' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'></path>
                    </svg>
                </div>
            ";
        });
        
        // Composant Accordion Content
        $this->registerComponent('accordion-content', function($attributes, $content) {
            $customClass = $attributes['class'] ?? 'px-6 py-4 text-gray-700';
            
            return "<div data-accordion-content class='{$customClass}' style='max-height: 0; overflow: hidden;'>{$content}</div>";
        });
        
        // Composant Tabs
        $this->registerComponent('tabs', function($attributes, $content) {
            $variant = $attributes['variant'] ?? 'default';
            $customClass = $attributes['class'] ?? '';
            
            $variantClasses = [
                'default' => 'border-b border-gray-200',
                'pills' => 'bg-gray-100 rounded-lg p-1',
                'underline' => 'border-b-2 border-transparent'
            ];
            
            $variantClass = $variantClasses[$variant] ?? $variantClasses['default'];
            $finalClass = trim("{$variantClass} {$customClass}");
            
            return "<div data-tabs class='w-full'><div class='flex {$finalClass}' role='tablist'>{$content}</div></div>";
        });
        
        // Composant Tab
        $this->registerComponent('tab', function($attributes, $content) {
            $title = $attributes['title'] ?? 'Tab';
            $active = isset($attributes['active']);
            $customClass = $attributes['class'] ?? '';
            $activeClass = $active ? 'active' : '';
            $finalClass = trim("px-4 py-2 text-sm font-medium cursor-pointer border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300 {$activeClass} {$customClass}");
            
            return "<button data-tab class='{$finalClass}' role='tab'>{$title}</button>";
        });
        
        // Composant Tab Panel
        $this->registerComponent('tab-panel', function($attributes, $content) {
            $active = isset($attributes['active']);
            $customClass = $attributes['class'] ?? 'mt-4';
            $activeClass = $active ? 'active' : '';
            $finalClass = trim("{$activeClass} {$customClass}");
            
            return "<div data-tab-panel class='{$finalClass}' role='tabpanel'>{$content}</div>";
        });
        
        // ==================== MEDIA ====================
        
        // Composant Image
        $this->registerComponent('image', function($attributes, $content) {
            $src = $attributes['src'] ?? '';
            $alt = $attributes['alt'] ?? '';
            $width = $attributes['width'] ?? '';
            $height = $attributes['height'] ?? '';
            $lazy = isset($attributes['lazy']);
            
            $widthAttr = $width ? "width='{$width}'" : '';
            $heightAttr = $height ? "height='{$height}'" : '';
            $loadingAttr = $lazy ? "loading='lazy'" : '';
            $customClass = $attributes['class'] ?? 'max-w-full h-auto';
            
            return "<img src='{$src}' alt='{$alt}' {$widthAttr} {$heightAttr} {$loadingAttr} class='{$customClass}' />";
        });
        
        // Composant Video
        $this->registerComponent('video', function($attributes, $content) {
            $src = $attributes['src'] ?? '';
            $poster = $attributes['poster'] ?? '';
            $controls = isset($attributes['controls']) ? 'controls' : '';
            $autoplay = isset($attributes['autoplay']) ? 'autoplay' : '';
            $muted = isset($attributes['muted']) ? 'muted' : '';
            $loop = isset($attributes['loop']) ? 'loop' : '';
            
            $posterAttr = $poster ? "poster='{$poster}'" : '';
            $customClass = $attributes['class'] ?? 'w-full h-auto';
            
            return "<video src='{$src}' {$posterAttr} {$controls} {$autoplay} {$muted} {$loop} class='{$customClass}'>{$content}</video>";
        });
        
        // ==================== LAYOUT ADVANCED ====================
        
        // Composant Cluster
        $this->registerComponent('cluster', function($attributes, $content) {
            $justify = $attributes['justify'] ?? 'flex-start';
            $align = $attributes['align'] ?? 'center';
            $space = $attributes['space'] ?? '1rem';
            $customClass = $attributes['class'] ?? '';
            
            $justifyClass = [
                'start' => 'justify-start',
                'center' => 'justify-center',
                'end' => 'justify-end',
                'between' => 'justify-between',
                'around' => 'justify-around',
                'evenly' => 'justify-evenly'
            ][$justify] ?? 'justify-start';
            
            $alignClass = [
                'start' => 'items-start',
                'center' => 'items-center',
                'end' => 'items-end',
                'stretch' => 'items-stretch'
            ][$align] ?? 'items-center';
            
            $finalClass = trim("flex flex-wrap {$justifyClass} {$alignClass} {$customClass}");
            $style = "gap: {$space};";
            
            return "<div class='{$finalClass}' style='{$style}'>{$content}</div>";
        });
        
        // Composant Switcher
        $this->registerComponent('switcher', function($attributes, $content) {
            $threshold = $attributes['threshold'] ?? '30rem';
            $space = $attributes['space'] ?? '1rem';
            $limit = $attributes['limit'] ?? '4';
            $customClass = $attributes['class'] ?? '';
            
            $finalClass = trim("flex flex-wrap {$customClass}");
            $style = "gap: {$space}; --threshold: {$threshold}; --limit: {$limit};";
            
            return "<div class='{$finalClass}' style='{$style}'>{$content}</div>";
        });
        
        // Composant Cover
        $this->registerComponent('cover', function($attributes, $content) {
            $centered = $attributes['centered'] ?? '';
            $space = $attributes['space'] ?? '1rem';
            $minHeight = $attributes['min-height'] ?? '100vh';
            $customClass = $attributes['class'] ?? '';
            
            $finalClass = trim("flex flex-col {$customClass}");
            $style = "gap: {$space}; min-height: {$minHeight};";
            
            return "<div class='{$finalClass}' style='{$style}'>{$content}</div>";
        });
        
        // Composant Sidebar Layout
        $this->registerComponent('sidebar-layout', function($attributes, $content) {
            $side = $attributes['side'] ?? 'left';
            $sideWidth = $attributes['side-width'] ?? '250px';
            $contentMin = $attributes['content-min'] ?? '50%';
            $space = $attributes['space'] ?? '1rem';
            $customClass = $attributes['class'] ?? '';
            
            $flexDirection = $side === 'right' ? 'flex-row-reverse' : 'flex-row';
            $finalClass = trim("flex {$flexDirection} {$customClass}");
            $style = "gap: {$space}; --side-width: {$sideWidth}; --content-min: {$contentMin};";
            
            return "<div class='{$finalClass}' style='{$style}'>{$content}</div>";
        });
        
        // Composant Reel
        $this->registerComponent('reel', function($attributes, $content) {
            $itemWidth = $attributes['item-width'] ?? '250px';
            $space = $attributes['space'] ?? '1rem';
            $height = $attributes['height'] ?? 'auto';
            $customClass = $attributes['class'] ?? '';
            
            $finalClass = trim("flex overflow-x-auto {$customClass}");
            $style = "gap: {$space}; height: {$height}; --item-width: {$itemWidth};";
            
            return "<div class='{$finalClass}' style='{$style}'>{$content}</div>";
        });
        
        // Composant Imposter
        $this->registerComponent('imposter', function($attributes, $content) {
            $margin = $attributes['margin'] ?? '0';
            $customClass = $attributes['class'] ?? '';
            
            $finalClass = trim("absolute inset-0 {$customClass}");
            $style = "margin: {$margin};";
            
            return "<div class='{$finalClass}' style='{$style}'>{$content}</div>";
        });
        
        // ==================== SEMANTIC ELEMENTS ====================
        
        // Composant Section
        $this->registerComponent('section', function($attributes, $content) {
            $customClass = $attributes['class'] ?? '';
            $id = $attributes['id'] ?? '';
            $idAttr = $id ? "id='{$id}'" : '';
            
            return "<section class='{$customClass}' {$idAttr}>{$content}</section>";
        });
        
        // Composant Article
        $this->registerComponent('article', function($attributes, $content) {
            $customClass = $attributes['class'] ?? '';
            $id = $attributes['id'] ?? '';
            $idAttr = $id ? "id='{$id}'" : '';
            
            return "<article class='{$customClass}' {$idAttr}>{$content}</article>";
        });
        
        // Composant Header
        $this->registerComponent('header', function($attributes, $content) {
            $customClass = $attributes['class'] ?? '';
            $id = $attributes['id'] ?? '';
            $idAttr = $id ? "id='{$id}'" : '';
            
            return "<header class='{$customClass}' {$idAttr}>{$content}</header>";
        });
        
        // Composant Footer
        $this->registerComponent('footer', function($attributes, $content) {
            $customClass = $attributes['class'] ?? '';
            $id = $attributes['id'] ?? '';
            $idAttr = $id ? "id='{$id}'" : '';
            
            return "<footer class='{$customClass}' {$idAttr}>{$content}</footer>";
        });
        
        // Composant Main
        $this->registerComponent('main', function($attributes, $content) {
            $customClass = $attributes['class'] ?? '';
            $id = $attributes['id'] ?? '';
            $idAttr = $id ? "id='{$id}'" : '';
            
            return "<main class='{$customClass}' {$idAttr}>{$content}</main>";
        });
        
        // Composant Aside
        $this->registerComponent('aside', function($attributes, $content) {
            $customClass = $attributes['class'] ?? '';
            $id = $attributes['id'] ?? '';
            $idAttr = $id ? "id='{$id}'" : '';
            
            return "<aside class='{$customClass}' {$idAttr}>{$content}</aside>";
        });
        
        // Composant Div
        $this->registerComponent('div', function($attributes, $content) {
            $customClass = $attributes['class'] ?? '';
            $id = $attributes['id'] ?? '';
            $idAttr = $id ? "id='{$id}'" : '';
            
            return "<div class='{$customClass}' {$idAttr}>{$content}</div>";
        });
        
        // ==================== TYPOGRAPHY ADVANCED ====================
        
        // Composant Typography
        $this->registerComponent('typography', function($attributes, $content) {
            $variant = $attributes['variant'] ?? 'body';
            $size = $attributes['size'] ?? '';
            $weight = $attributes['weight'] ?? '';
            $color = $attributes['color'] ?? '';
            $align = $attributes['align'] ?? '';
            $customClass = $attributes['class'] ?? '';
            
            $variantClasses = [
                'h1' => 'text-4xl font-bold',
                'h2' => 'text-3xl font-bold',
                'h3' => 'text-2xl font-bold',
                'h4' => 'text-xl font-bold',
                'h5' => 'text-lg font-bold',
                'h6' => 'text-base font-bold',
                'body' => 'text-base',
                'caption' => 'text-sm text-gray-600',
                'overline' => 'text-xs uppercase tracking-wide'
            ];
            
            $variantClass = $variantClasses[$variant] ?? $variantClasses['body'];
            $sizeClass = $size ? "text-{$size}" : '';
            $weightClass = $weight ? "font-{$weight}" : '';
            $colorClass = $color ? "text-{$color}" : '';
            $alignClass = $align ? "text-{$align}" : '';
            
            $finalClass = trim("{$variantClass} {$sizeClass} {$weightClass} {$colorClass} {$alignClass} {$customClass}");
            
            $tag = in_array($variant, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6']) ? $variant : 'p';
            
            return "<{$tag} class='{$finalClass}'>{$content}</{$tag}>";
        });
        
        // Composant Anchor
        $this->registerComponent('anchor', function($attributes, $content) {
            $href = $attributes['href'] ?? '#';
            $target = $attributes['target'] ?? '';
            $rel = $attributes['rel'] ?? '';
            $customClass = $attributes['class'] ?? 'text-blue-600 hover:text-blue-800 underline';
            
            $targetAttr = $target ? "target='{$target}'" : '';
            $relAttr = $rel ? "rel='{$rel}'" : '';
            
            return "<a href='{$href}' class='{$customClass}' {$targetAttr} {$relAttr}>{$content}</a>";
        });
        
        // Composant Code Inline
        $this->registerComponent('code-inline', function($attributes, $content) {
            $customClass = $attributes['class'] ?? 'bg-gray-100 text-gray-800 px-1 py-0.5 rounded text-sm font-mono';
            
            return "<code class='{$customClass}'>{$content}</code>";
        });
        
        // Composant Code Block
        $this->registerComponent('code-block', function($attributes, $content) {
            $language = $attributes['language'] ?? '';
            $customClass = $attributes['class'] ?? 'bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto';
            $langClass = $language ? "language-{$language}" : '';
            
            return "<pre class='{$customClass}'><code class='{$langClass}'>{$content}</code></pre>";
        });
        
        // Composant Pre
        $this->registerComponent('pre', function($attributes, $content) {
            $customClass = $attributes['class'] ?? 'bg-gray-100 p-4 rounded-lg overflow-x-auto whitespace-pre';
            
            return "<pre class='{$customClass}'>{$content}</pre>";
        });
        
        // Composant Blockquote
        $this->registerComponent('blockquote', function($attributes, $content) {
            $cite = $attributes['cite'] ?? '';
            $author = $attributes['author'] ?? '';
            $customClass = $attributes['class'] ?? 'border-l-4 border-gray-300 pl-4 italic text-gray-700';
            
            $citeAttr = $cite ? "cite='{$cite}'" : '';
            $authorHtml = $author ? "<cite class='block mt-2 text-sm not-italic'>— {$author}</cite>" : '';
            
            return "<blockquote class='{$customClass}' {$citeAttr}>{$content}{$authorHtml}</blockquote>";
        });
        
        // Composant Mark/Highlight
        $this->registerComponent('mark', function($attributes, $content) {
            $color = $attributes['color'] ?? 'yellow';
            $customClass = $attributes['class'] ?? "bg-{$color}-200 px-1 rounded";
            
            return "<mark class='{$customClass}'>{$content}</mark>";
        });
        
        // Composant Abbreviation
        $this->registerComponent('abbr', function($attributes, $content) {
            $title = $attributes['title'] ?? '';
            $customClass = $attributes['class'] ?? 'border-b border-dotted border-gray-400 cursor-help';
            
            return "<abbr class='{$customClass}' title='{$title}'>{$content}</abbr>";
        });
        
        // Composant Address
        $this->registerComponent('address', function($attributes, $content) {
            $customClass = $attributes['class'] ?? 'not-italic';
            
            return "<address class='{$customClass}'>{$content}</address>";
        });
        
        // Composant Time
        $this->registerComponent('time', function($attributes, $content) {
            $datetime = $attributes['datetime'] ?? '';
            $customClass = $attributes['class'] ?? '';
            
            $datetimeAttr = $datetime ? "datetime='{$datetime}'" : '';
            
            return "<time class='{$customClass}' {$datetimeAttr}>{$content}</time>";
        });
        
        // Composant Kbd (keyboard)
        $this->registerComponent('kbd', function($attributes, $content) {
            $customClass = $attributes['class'] ?? 'bg-gray-100 border border-gray-300 rounded px-2 py-1 text-sm font-mono';
            
            return "<kbd class='{$customClass}'>{$content}</kbd>";
        });
        
        // Composant Prose
        $this->registerComponent('prose', function($attributes, $content) {
            $size = $attributes['size'] ?? 'base';
            $customClass = $attributes['class'] ?? '';
            
            $sizeClasses = [
                'sm' => 'prose-sm',
                'base' => 'prose',
                'lg' => 'prose-lg',
                'xl' => 'prose-xl',
                '2xl' => 'prose-2xl'
            ];
            
            $sizeClass = $sizeClasses[$size] ?? $sizeClasses['base'];
            $finalClass = trim("{$sizeClass} {$customClass}");
            
            return "<div class='{$finalClass}'>{$content}</div>";
        });
        
        // ==================== FORMS ADVANCED ====================
        
        // Composant Fieldset
        $this->registerComponent('fieldset', function($attributes, $content) {
            $disabled = isset($attributes['disabled']);
            $customClass = $attributes['class'] ?? 'border border-gray-300 rounded-lg p-4';
            
            $disabledAttr = $disabled ? 'disabled' : '';
            
            return "<fieldset class='{$customClass}' {$disabledAttr}>{$content}</fieldset>";
        });
        
        // Composant Legend
        $this->registerComponent('legend', function($attributes, $content) {
            $customClass = $attributes['class'] ?? 'text-sm font-medium text-gray-700 px-2';
            
            return "<legend class='{$customClass}'>{$content}</legend>";
        });
        
        // Composant Label
        $this->registerComponent('label', function($attributes, $content) {
            $for = $attributes['for'] ?? '';
            $required = isset($attributes['required']);
            $customClass = $attributes['class'] ?? 'block text-sm font-medium text-gray-700';
            
            $forAttr = $for ? "for='{$for}'" : '';
            $requiredMark = $required ? "<span class='text-red-500'>*</span>" : '';
            
            return "<label class='{$customClass}' {$forAttr}>{$content}{$requiredMark}</label>";
        });
        
        // Composant Input Group
        $this->registerComponent('input-group', function($attributes, $content) {
            $customClass = $attributes['class'] ?? 'flex';
            
            return "<div class='{$customClass}'>{$content}</div>";
        });
        
        // Composant Input Addon
        $this->registerComponent('input-addon', function($attributes, $content) {
            $position = $attributes['position'] ?? 'left';
            $customClass = $attributes['class'] ?? '';
            
            $positionClasses = [
                'left' => 'rounded-l-md border-r-0',
                'right' => 'rounded-r-md border-l-0'
            ];
            
            $positionClass = $positionClasses[$position] ?? $positionClasses['left'];
            $finalClass = trim("inline-flex items-center px-3 border border-gray-300 bg-gray-50 text-gray-500 text-sm {$positionClass} {$customClass}");
            
            return "<span class='{$finalClass}'>{$content}</span>";
        });
        
        // Composant Textarea Auto Resize
        $this->registerComponent('textarea-auto-resize', function($attributes, $content) {
            $name = $attributes['name'] ?? '';
            $id = $attributes['id'] ?? '';
            $placeholder = $attributes['placeholder'] ?? '';
            $rows = $attributes['rows'] ?? '3';
            $maxRows = $attributes['max-rows'] ?? '10';
            $required = isset($attributes['required']);
            $disabled = isset($attributes['disabled']);
            $readonly = isset($attributes['readonly']);
            $customClass = $attributes['class'] ?? 'block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500';
            
            $nameAttr = $name ? "name='{$name}'" : '';
            $idAttr = $id ? "id='{$id}'" : '';
            $placeholderAttr = $placeholder ? "placeholder='{$placeholder}'" : '';
            $requiredAttr = $required ? 'required' : '';
            $disabledAttr = $disabled ? 'disabled' : '';
            $readonlyAttr = $readonly ? 'readonly' : '';
            
            return "<textarea class='{$customClass}' {$nameAttr} {$idAttr} {$placeholderAttr} rows='{$rows}' data-max-rows='{$maxRows}' {$requiredAttr} {$disabledAttr} {$readonlyAttr} oninput='autoResize(this)'>{$content}</textarea>";
        });
        
        // Composant Select Custom
        $this->registerComponent('select-custom', function($attributes, $content) {
            $name = $attributes['name'] ?? '';
            $id = $attributes['id'] ?? '';
            $placeholder = $attributes['placeholder'] ?? 'Sélectionner une option';
            $searchable = isset($attributes['searchable']);
            $multiple = isset($attributes['multiple']);
            $customClass = $attributes['class'] ?? '';
            
            $searchInput = $searchable ? "<input type='text' class='w-full px-3 py-2 border-b border-gray-200 focus:outline-none' placeholder='Rechercher...'>" : '';
            $multipleAttr = $multiple ? 'multiple' : '';
            
            return "
                <div class='relative {$customClass}'>
                    <button type='button' class='w-full px-3 py-2 text-left bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500' onclick='toggleCustomSelect(this)'>
                        <span class='block truncate'>{$placeholder}</span>
                        <span class='absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none'>
                            <i class='fas fa-chevron-down text-gray-400'></i>
                        </span>
                    </button>
                    <div class='hidden absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg'>
                        {$searchInput}
                        <div class='max-h-60 overflow-auto'>
                            {$content}
                        </div>
                    </div>
                    <select name='{$name}' id='{$id}' class='hidden' {$multipleAttr}></select>
                </div>
            ";
        });
        
        // Composant Multi Select
        $this->registerComponent('multi-select', function($attributes, $content) {
            $name = $attributes['name'] ?? '';
            $id = $attributes['id'] ?? '';
            $placeholder = $attributes['placeholder'] ?? 'Sélectionner des options';
            $customClass = $attributes['class'] ?? '';
            
            return "
                <div class='relative {$customClass}'>
                    <div class='min-h-[2.5rem] px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500 cursor-text' onclick='toggleMultiSelect(this)'>
                        <div class='flex flex-wrap gap-1'>
                            <span class='text-gray-500 select-none' data-placeholder='{$placeholder}'>{$placeholder}</span>
                        </div>
                    </div>
                    <div class='hidden absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg'>
                        <div class='p-2'>
                            <input type='text' class='w-full px-2 py-1 border border-gray-200 rounded text-sm' placeholder='Rechercher...' oninput='filterMultiSelectOptions(this)'>
                        </div>
                        <div class='max-h-60 overflow-auto'>
                            {$content}
                        </div>
                    </div>
                    <select name='{$name}[]' id='{$id}' class='hidden' multiple></select>
                </div>
            ";
        });
        
        // Composant Combobox
        $this->registerComponent('combobox', function($attributes, $content) {
            $name = $attributes['name'] ?? '';
            $id = $attributes['id'] ?? '';
            $placeholder = $attributes['placeholder'] ?? 'Tapez pour rechercher...';
            $customClass = $attributes['class'] ?? '';
            
            return "
                <div class='relative {$customClass}'>
                    <input type='text' name='{$name}' id='{$id}' class='w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500' placeholder='{$placeholder}' autocomplete='off' oninput='filterComboboxOptions(this)' onfocus='showComboboxOptions(this)' onblur='hideComboboxOptions(this)'>
                    <div class='hidden absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto'>
                        {$content}
                    </div>
                </div>
            ";
        });
        
        // Composant Listbox
        $this->registerComponent('listbox', function($attributes, $content) {
            $name = $attributes['name'] ?? '';
            $id = $attributes['id'] ?? '';
            $multiple = isset($attributes['multiple']);
            $size = $attributes['size'] ?? '5';
            $customClass = $attributes['class'] ?? 'w-full border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500';
            
            $multipleAttr = $multiple ? 'multiple' : '';
            
            return "<select name='{$name}' id='{$id}' class='{$customClass}' size='{$size}' {$multipleAttr}>{$content}</select>";
        });
        
        // Composant Radio Group
        $this->registerComponent('radio-group', function($attributes, $content) {
            $name = $attributes['name'] ?? '';
            $legend = $attributes['legend'] ?? '';
            $required = isset($attributes['required']);
            $customClass = $attributes['class'] ?? '';
            
            $legendHtml = $legend ? "<legend class='text-sm font-medium text-gray-700 mb-2'>{$legend}</legend>" : '';
            $requiredAttr = $required ? 'required' : '';
            
            return "
                <fieldset class='{$customClass}' {$requiredAttr}>
                    {$legendHtml}
                    <div class='space-y-2' data-radio-group='{$name}'>
                        {$content}
                    </div>
                </fieldset>
            ";
        });
        
        // Composant Checkbox Group
        $this->registerComponent('checkbox-group', function($attributes, $content) {
            $name = $attributes['name'] ?? '';
            $legend = $attributes['legend'] ?? '';
            $customClass = $attributes['class'] ?? '';
            
            $legendHtml = $legend ? "<legend class='text-sm font-medium text-gray-700 mb-2'>{$legend}</legend>" : '';
            
            return "
                <fieldset class='{$customClass}'>
                    {$legendHtml}
                    <div class='space-y-2' data-checkbox-group='{$name}'>
                        {$content}
                    </div>
                </fieldset>
            ";
        });
        
        // Composant Switch Toggle
        $this->registerComponent('switch-toggle', function($attributes, $content) {
            $name = $attributes['name'] ?? '';
            $id = $attributes['id'] ?? '';
            $checked = isset($attributes['checked']);
            $disabled = isset($attributes['disabled']);
            $size = $attributes['size'] ?? 'md';
            $color = $attributes['color'] ?? 'blue';
            $customClass = $attributes['class'] ?? '';
            
            $sizeClasses = [
                'sm' => 'w-8 h-4',
                'md' => 'w-11 h-6',
                'lg' => 'w-14 h-8'
            ];
            
            $thumbSizes = [
                'sm' => 'w-3 h-3',
                'md' => 'w-5 h-5',
                'lg' => 'w-7 h-7'
            ];
            
            $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
            $thumbSize = $thumbSizes[$size] ?? $thumbSizes['md'];
            $checkedAttr = $checked ? 'checked' : '';
            $disabledAttr = $disabled ? 'disabled' : '';
            
            return "
                <label class='inline-flex items-center cursor-pointer {$customClass}'>
                    <input type='checkbox' name='{$name}' id='{$id}' class='sr-only peer' {$checkedAttr} {$disabledAttr}>
                    <div class='relative {$sizeClass} bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-{$color}-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[\"\"] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:{$thumbSize} after:transition-all peer-checked:bg-{$color}-600'></div>
                    <span class='ml-3 text-sm font-medium text-gray-900'>{$content}</span>
                </label>
            ";
        });
        
        // ==================== UTILITIES ====================
        
        // Composant Icon
        $this->registerComponent('icon', function($attributes, $content) {
            $name = $attributes['name'] ?? 'star';
            $size = $attributes['size'] ?? '24';
            $color = $attributes['color'] ?? 'currentColor';
            $customClass = $attributes['class'] ?? '';
            
            $finalClass = trim("w-{$size} h-{$size} {$customClass}");
            
            // Icônes SVG basiques
            $icons = [
                'star' => "<path fill-rule='evenodd' d='M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z' clip-rule='evenodd' />",
                'heart' => "<path d='M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z' />",
                'check' => "<path fill-rule='evenodd' d='M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z' clip-rule='evenodd' />",
                'x' => "<path fill-rule='evenodd' d='M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z' clip-rule='evenodd' />"
            ];
            
            $iconPath = $icons[$name] ?? $icons['star'];
            
            return "<svg class='{$finalClass}' fill='{$color}' viewBox='0 0 20 20' xmlns='http://www.w3.org/2000/svg'>{$iconPath}</svg>";
        });
        
        // Composant Divider
        $this->registerComponent('divider', function($attributes, $content) {
            $orientation = $attributes['orientation'] ?? 'horizontal';
            $variant = $attributes['variant'] ?? 'solid';
            $customClass = $attributes['class'] ?? '';
            
            if ($orientation === 'vertical') {
                $baseClass = 'border-l h-full';
            } else {
                $baseClass = 'border-t w-full';
            }
            
            $variantClasses = [
                'solid' => 'border-gray-300',
                'dashed' => 'border-gray-300 border-dashed',
                'dotted' => 'border-gray-300 border-dotted'
            ];
            
            $variantClass = $variantClasses[$variant] ?? $variantClasses['solid'];
            $finalClass = trim("{$baseClass} {$variantClass} {$customClass}");
            
            if ($content) {
                return "<div class='relative flex items-center'><div class='flex-grow {$finalClass}'></div><span class='px-3 text-gray-500 bg-white'>{$content}</span><div class='flex-grow {$finalClass}'></div></div>";
            }
            
            return "<div class='{$finalClass}'></div>";
        });
        
        // Composant Spacer
        $this->registerComponent('spacer', function($attributes, $content) {
            $size = $attributes['size'] ?? '4';
            $direction = $attributes['direction'] ?? 'vertical';
            
            if ($direction === 'horizontal') {
                $class = "w-{$size}";
            } else {
                $class = "h-{$size}";
            }
            
            $customClass = $attributes['class'] ?? '';
            $finalClass = trim("{$class} {$customClass}");
            
            return "<div class='{$finalClass}'></div>";
        });
        
        // ==================== FORMS INPUTS ADVANCED ====================
        
        // Composant File Input
        $this->registerComponent('file-input', function($attributes, $content) {
            $name = $attributes['name'] ?? '';
            $id = $attributes['id'] ?? '';
            $accept = $attributes['accept'] ?? '';
            $multiple = isset($attributes['multiple']);
            $required = isset($attributes['required']);
            $disabled = isset($attributes['disabled']);
            $customClass = $attributes['class'] ?? 'block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100';
            
            $acceptAttr = $accept ? "accept='{$accept}'" : '';
            $multipleAttr = $multiple ? 'multiple' : '';
            $requiredAttr = $required ? 'required' : '';
            $disabledAttr = $disabled ? 'disabled' : '';
            
            return "<input type='file' name='{$name}' id='{$id}' class='{$customClass}' {$acceptAttr} {$multipleAttr} {$requiredAttr} {$disabledAttr}>";
        });
        
        // Composant File Dropzone
        $this->registerComponent('file-dropzone', function($attributes, $content) {
            $name = $attributes['name'] ?? '';
            $id = $attributes['id'] ?? '';
            $accept = $attributes['accept'] ?? '';
            $multiple = isset($attributes['multiple']);
            $maxSize = $attributes['max-size'] ?? '10MB';
            $customClass = $attributes['class'] ?? '';
            
            $acceptAttr = $accept ? "accept='{$accept}'" : '';
            $multipleAttr = $multiple ? 'multiple' : '';
            
            return "
                <div class='border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors {$customClass}' ondrop='handleDrop(event)' ondragover='handleDragOver(event)' ondragleave='handleDragLeave(event)'>
                    <input type='file' name='{$name}' id='{$id}' class='hidden' {$acceptAttr} {$multipleAttr} onchange='handleFileSelect(this)'>
                    <div class='space-y-2'>
                        <i class='fas fa-cloud-upload-alt text-4xl text-gray-400'></i>
                        <div>
                            <label for='{$id}' class='cursor-pointer text-blue-600 hover:text-blue-500'>
                                Cliquez pour sélectionner
                            </label>
                            <span class='text-gray-500'> ou glissez-déposez vos fichiers ici</span>
                        </div>
                        <p class='text-xs text-gray-500'>Taille maximale: {$maxSize}</p>
                    </div>
                </div>
            ";
        });
        
        // Composant Range Slider
        $this->registerComponent('range-slider', function($attributes, $content) {
            $name = $attributes['name'] ?? '';
            $id = $attributes['id'] ?? '';
            $min = $attributes['min'] ?? '0';
            $max = $attributes['max'] ?? '100';
            $value = $attributes['value'] ?? '50';
            $step = $attributes['step'] ?? '1';
            $showValue = isset($attributes['show-value']);
            $customClass = $attributes['class'] ?? 'w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer';
            
            $valueDisplay = $showValue ? "<span class='text-sm text-gray-600' id='{$id}-value'>{$value}</span>" : '';
            $onInput = $showValue ? "oninput='document.getElementById(\"{$id}-value\").textContent = this.value'" : '';
            
            return "
                <div class='space-y-2'>
                    <div class='flex justify-between items-center'>
                        <label for='{$id}' class='text-sm font-medium text-gray-700'>{$content}</label>
                        {$valueDisplay}
                    </div>
                    <input type='range' name='{$name}' id='{$id}' class='{$customClass}' min='{$min}' max='{$max}' value='{$value}' step='{$step}' {$onInput}>
                    <div class='flex justify-between text-xs text-gray-500'>
                        <span>{$min}</span>
                        <span>{$max}</span>
                    </div>
                </div>
            ";
        });
        
        // Composant Color Input
        $this->registerComponent('color-input', function($attributes, $content) {
            $name = $attributes['name'] ?? '';
            $id = $attributes['id'] ?? '';
            $value = $attributes['value'] ?? '#000000';
            $customClass = $attributes['class'] ?? 'h-10 w-20 border border-gray-300 rounded cursor-pointer';
            
            return "
                <div class='flex items-center space-x-2'>
                    <input type='color' name='{$name}' id='{$id}' class='{$customClass}' value='{$value}'>
                    <label for='{$id}' class='text-sm font-medium text-gray-700'>{$content}</label>
                </div>
            ";
        });
        
        // Composant Date Input
        $this->registerComponent('date-input', function($attributes, $content) {
            $name = $attributes['name'] ?? '';
            $id = $attributes['id'] ?? '';
            $value = $attributes['value'] ?? '';
            $min = $attributes['min'] ?? '';
            $max = $attributes['max'] ?? '';
            $required = isset($attributes['required']);
            $disabled = isset($attributes['disabled']);
            $customClass = $attributes['class'] ?? 'block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500';
            
            $valueAttr = $value ? "value='{$value}'" : '';
            $minAttr = $min ? "min='{$min}'" : '';
            $maxAttr = $max ? "max='{$max}'" : '';
            $requiredAttr = $required ? 'required' : '';
            $disabledAttr = $disabled ? 'disabled' : '';
            
            return "<input type='date' name='{$name}' id='{$id}' class='{$customClass}' {$valueAttr} {$minAttr} {$maxAttr} {$requiredAttr} {$disabledAttr}>";
        });
        
        // Composant Time Input
        $this->registerComponent('time-input', function($attributes, $content) {
            $name = $attributes['name'] ?? '';
            $id = $attributes['id'] ?? '';
            $value = $attributes['value'] ?? '';
            $min = $attributes['min'] ?? '';
            $max = $attributes['max'] ?? '';
            $step = $attributes['step'] ?? '';
            $required = isset($attributes['required']);
            $disabled = isset($attributes['disabled']);
            $customClass = $attributes['class'] ?? 'block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500';
            
            $valueAttr = $value ? "value='{$value}'" : '';
            $minAttr = $min ? "min='{$min}'" : '';
            $maxAttr = $max ? "max='{$max}'" : '';
            $stepAttr = $step ? "step='{$step}'" : '';
            $requiredAttr = $required ? 'required' : '';
            $disabledAttr = $disabled ? 'disabled' : '';
            
            return "<input type='time' name='{$name}' id='{$id}' class='{$customClass}' {$valueAttr} {$minAttr} {$maxAttr} {$stepAttr} {$requiredAttr} {$disabledAttr}>";
        });
        
        // ==================== DATA DISPLAY ADVANCED ====================
        
        // Composant Table Simple
        $this->registerComponent('table-simple', function($attributes, $content) {
            $striped = isset($attributes['striped']);
            $bordered = isset($attributes['bordered']);
            $hover = isset($attributes['hover']);
            $size = $attributes['size'] ?? 'md';
            $customClass = $attributes['class'] ?? '';
            
            $baseClass = 'min-w-full divide-y divide-gray-200';
            $stripedClass = $striped ? 'table-striped' : '';
            $borderedClass = $bordered ? 'border border-gray-300' : '';
            $hoverClass = $hover ? 'table-hover' : '';
            
            $sizeClasses = [
                'sm' => 'text-sm',
                'md' => 'text-base',
                'lg' => 'text-lg'
            ];
            
            $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
            $finalClass = trim("{$baseClass} {$stripedClass} {$borderedClass} {$hoverClass} {$sizeClass} {$customClass}");
            
            return "<table class='{$finalClass}'>{$content}</table>";
        });
        
        // Composant Table Sortable
        $this->registerComponent('table-sortable', function($attributes, $content) {
            $customClass = $attributes['class'] ?? 'min-w-full divide-y divide-gray-200';
            
            return "
                <div class='overflow-x-auto'>
                    <table class='{$customClass}' data-sortable='true'>
                        {$content}
                    </table>
                </div>
            ";
        });
        
        // Composant Stats Grid
        $this->registerComponent('stats-grid', function($attributes, $content) {
            $columns = $attributes['columns'] ?? '3';
            $gap = $attributes['gap'] ?? '6';
            $customClass = $attributes['class'] ?? '';
            
            $gridClass = "grid grid-cols-1 md:grid-cols-{$columns} gap-{$gap}";
            $finalClass = trim("{$gridClass} {$customClass}");
            
            return "<div class='{$finalClass}'>{$content}</div>";
        });
        
        // Composant Metric Card
        $this->registerComponent('metric-card', function($attributes, $content) {
            $title = $attributes['title'] ?? '';
            $value = $attributes['value'] ?? '';
            $change = $attributes['change'] ?? '';
            $trend = $attributes['trend'] ?? 'neutral';
            $icon = $attributes['icon'] ?? '';
            $customClass = $attributes['class'] ?? '';
            
            $trendClasses = [
                'up' => 'text-green-600',
                'down' => 'text-red-600',
                'neutral' => 'text-gray-600'
            ];
            
            $trendIcons = [
                'up' => 'fa-arrow-up',
                'down' => 'fa-arrow-down',
                'neutral' => 'fa-minus'
            ];
            
            $trendClass = $trendClasses[$trend] ?? $trendClasses['neutral'];
            $trendIcon = $trendIcons[$trend] ?? $trendIcons['neutral'];
            
            $iconHtml = $icon ? "<i class='fas fa-{$icon} text-2xl text-gray-400'></i>" : '';
            $changeHtml = $change ? "<div class='flex items-center {$trendClass}'><i class='fas {$trendIcon} mr-1'></i><span class='text-sm font-medium'>{$change}</span></div>" : '';
            
            return "
                <div class='bg-white overflow-hidden shadow rounded-lg {$customClass}'>
                    <div class='p-5'>
                        <div class='flex items-center'>
                            <div class='flex-shrink-0'>
                                {$iconHtml}
                            </div>
                            <div class='ml-5 w-0 flex-1'>
                                <dl>
                                    <dt class='text-sm font-medium text-gray-500 truncate'>{$title}</dt>
                                    <dd class='flex items-baseline'>
                                        <div class='text-2xl font-semibold text-gray-900'>{$value}</div>
                                        <div class='ml-2'>{$changeHtml}</div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    {$content}
                </div>
            ";
        });
        
        // Composant Key Value Pair
        $this->registerComponent('key-value', function($attributes, $content) {
            $key = $attributes['key'] ?? '';
            $value = $attributes['value'] ?? '';
            $orientation = $attributes['orientation'] ?? 'horizontal';
            $customClass = $attributes['class'] ?? '';
            
            if ($orientation === 'vertical') {
                $containerClass = 'space-y-1';
                $keyClass = 'text-sm font-medium text-gray-500';
                $valueClass = 'text-sm text-gray-900';
            } else {
                $containerClass = 'flex justify-between';
                $keyClass = 'text-sm font-medium text-gray-500';
                $valueClass = 'text-sm text-gray-900';
            }
            
            $finalClass = trim("{$containerClass} {$customClass}");
            
            return "
                <div class='{$finalClass}'>
                    <dt class='{$keyClass}'>{$key}</dt>
                    <dd class='{$valueClass}'>{$value}</dd>
                </div>
            ";
        });
        
        // Composant Timeline
        $this->registerComponent('timeline', function($attributes, $content) {
            $customClass = $attributes['class'] ?? 'flow-root';
            
            return "<div class='{$customClass}'><ul class='-mb-8'>{$content}</ul></div>";
        });
        
        // Composant Timeline Item
        $this->registerComponent('timeline-item', function($attributes, $content) {
            $icon = $attributes['icon'] ?? 'circle';
            $color = $attributes['color'] ?? 'gray';
            $time = $attributes['time'] ?? '';
            $title = $attributes['title'] ?? '';
            $customClass = $attributes['class'] ?? '';
            
            $timeHtml = $time ? "<time class='text-sm text-gray-500'>{$time}</time>" : '';
            $titleHtml = $title ? "<h3 class='text-sm font-medium text-gray-900'>{$title}</h3>" : '';
            
            return "
                <li class='{$customClass}'>
                    <div class='relative pb-8'>
                        <span class='absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200' aria-hidden='true'></span>
                        <div class='relative flex space-x-3'>
                            <div>
                                <span class='h-8 w-8 rounded-full bg-{$color}-500 flex items-center justify-center ring-8 ring-white'>
                                    <i class='fas fa-{$icon} text-white text-sm'></i>
                                </span>
                            </div>
                            <div class='min-w-0 flex-1 pt-1.5 flex justify-between space-x-4'>
                                <div>
                                    {$titleHtml}
                                    <div class='text-sm text-gray-500'>{$content}</div>
                                </div>
                                <div class='text-right text-sm whitespace-nowrap'>
                                    {$timeHtml}
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            ";
        });
        
        // Composant Calendar
        $this->registerComponent('calendar', function($attributes, $content) {
            $month = $attributes['month'] ?? date('n');
            $year = $attributes['year'] ?? date('Y');
            $customClass = $attributes['class'] ?? '';
            
            $monthNames = [
                1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
                5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
                9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
            ];
            
            $monthName = $monthNames[$month];
            
            return "
                <div class='bg-white shadow rounded-lg {$customClass}'>
                    <div class='px-4 py-5 sm:p-6'>
                        <div class='flex items-center justify-between mb-4'>
                            <h3 class='text-lg font-medium text-gray-900'>{$monthName} {$year}</h3>
                            <div class='flex space-x-2'>
                                <button type='button' class='p-1 text-gray-400 hover:text-gray-600'>
                                    <i class='fas fa-chevron-left'></i>
                                </button>
                                <button type='button' class='p-1 text-gray-400 hover:text-gray-600'>
                                    <i class='fas fa-chevron-right'></i>
                                </button>
                            </div>
                        </div>
                        <div class='grid grid-cols-7 gap-1 text-center text-sm'>
                            <div class='font-medium text-gray-500 py-2'>Dim</div>
                            <div class='font-medium text-gray-500 py-2'>Lun</div>
                            <div class='font-medium text-gray-500 py-2'>Mar</div>
                            <div class='font-medium text-gray-500 py-2'>Mer</div>
                            <div class='font-medium text-gray-500 py-2'>Jeu</div>
                            <div class='font-medium text-gray-500 py-2'>Ven</div>
                            <div class='font-medium text-gray-500 py-2'>Sam</div>
                            {$content}
                        </div>
                    </div>
                </div>
            ";
        });
        
        // Composant Avatar Group
        $this->registerComponent('avatar-group', function($attributes, $content) {
            $max = $attributes['max'] ?? '5';
            $size = $attributes['size'] ?? 'md';
            $customClass = $attributes['class'] ?? '';
            
            $sizeClasses = [
                'sm' => 'w-6 h-6',
                'md' => 'w-8 h-8',
                'lg' => 'w-10 h-10',
                'xl' => 'w-12 h-12'
            ];
            
            $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
            
            return "<div class='flex -space-x-2 overflow-hidden {$customClass}' data-max='{$max}' data-size='{$sizeClass}'>{$content}</div>";
        });
        
        // ==================== FEEDBACK ADVANCED ====================
        
        // Composant Alert Banner
        $this->registerComponent('alert-banner', function($attributes, $content) {
            $type = $attributes['type'] ?? 'info';
            $dismissible = isset($attributes['dismissible']);
            $icon = $attributes['icon'] ?? '';
            $customClass = $attributes['class'] ?? '';
            
            $typeClasses = [
                'success' => 'bg-green-50 border-green-200 text-green-800',
                'error' => 'bg-red-50 border-red-200 text-red-800',
                'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
                'info' => 'bg-blue-50 border-blue-200 text-blue-800'
            ];
            
            $defaultIcons = [
                'success' => 'check-circle',
                'error' => 'exclamation-circle',
                'warning' => 'exclamation-triangle',
                'info' => 'info-circle'
            ];
            
            $typeClass = $typeClasses[$type] ?? $typeClasses['info'];
            $iconName = $icon ?: $defaultIcons[$type];
            $dismissButton = $dismissible ? "<button type='button' class='ml-auto -mx-1.5 -my-1.5 rounded-lg p-1.5 inline-flex h-8 w-8 hover:bg-gray-100' onclick='this.parentElement.remove()'><i class='fas fa-times'></i></button>" : '';
            
            $finalClass = trim("border-l-4 p-4 {$typeClass} {$customClass}");
            
            return "
                <div class='{$finalClass}' role='alert'>
                    <div class='flex'>
                        <div class='flex-shrink-0'>
                            <i class='fas fa-{$iconName}'></i>
                        </div>
                        <div class='ml-3 flex-1'>
                            {$content}
                        </div>
                        {$dismissButton}
                    </div>
                </div>
            ";
        });
        
        // Composant Toast Notification
        $this->registerComponent('toast', function($attributes, $content) {
            $type = $attributes['type'] ?? 'info';
            $position = $attributes['position'] ?? 'top-right';
            $duration = $attributes['duration'] ?? '5000';
            $title = $attributes['title'] ?? '';
            $customClass = $attributes['class'] ?? '';
            
            $typeClasses = [
                'success' => 'bg-green-500 text-white',
                'error' => 'bg-red-500 text-white',
                'warning' => 'bg-yellow-500 text-white',
                'info' => 'bg-blue-500 text-white'
            ];
            
            $positionClasses = [
                'top-right' => 'fixed top-4 right-4',
                'top-left' => 'fixed top-4 left-4',
                'bottom-right' => 'fixed bottom-4 right-4',
                'bottom-left' => 'fixed bottom-4 left-4'
            ];
            
            $typeClass = $typeClasses[$type] ?? $typeClasses['info'];
            $positionClass = $positionClasses[$position] ?? $positionClasses['top-right'];
            $titleHtml = $title ? "<div class='font-medium'>{$title}</div>" : '';
            
            $finalClass = trim("max-w-sm w-full shadow-lg rounded-lg pointer-events-auto {$typeClass} {$positionClass} {$customClass}");
            
            return "
                <div class='{$finalClass}' data-duration='{$duration}' style='z-index: 9999;'>
                    <div class='p-4'>
                        <div class='flex items-start'>
                            <div class='flex-1'>
                                {$titleHtml}
                                <div class='text-sm'>{$content}</div>
                            </div>
                            <div class='ml-4 flex-shrink-0 flex'>
                                <button class='inline-flex text-white hover:text-gray-200 focus:outline-none' onclick='this.closest(\".max-w-sm\").remove()'>
                                    <i class='fas fa-times'></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            ";
        });
        
        // Composant Progress Bar
        $this->registerComponent('progress-bar', function($attributes, $content) {
            $value = $attributes['value'] ?? '0';
            $max = $attributes['max'] ?? '100';
            $color = $attributes['color'] ?? 'blue';
            $size = $attributes['size'] ?? 'md';
            $showLabel = isset($attributes['show-label']);
            $animated = isset($attributes['animated']);
            $customClass = $attributes['class'] ?? '';
            
            $sizeClasses = [
                'sm' => 'h-2',
                'md' => 'h-4',
                'lg' => 'h-6'
            ];
            
            $percentage = ($value / $max) * 100;
            $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
            $animatedClass = $animated ? 'progress-bar-animated' : '';
            $labelHtml = $showLabel ? "<span class='text-sm font-medium text-gray-700'>{$percentage}%</span>" : '';
            
            $finalClass = trim("w-full bg-gray-200 rounded-full {$sizeClass} {$customClass}");
            
            return "
                <div class='space-y-2'>
                    <div class='flex justify-between items-center'>
                        <span class='text-sm font-medium text-gray-700'>{$content}</span>
                        {$labelHtml}
                    </div>
                    <div class='{$finalClass}'>
                        <div class='bg-{$color}-600 {$sizeClass} rounded-full {$animatedClass}' style='width: {$percentage}%'></div>
                    </div>
                </div>
            ";
        });
        
        // Composant Loading Spinner
        $this->registerComponent('loading-spinner', function($attributes, $content) {
            $size = $attributes['size'] ?? 'md';
            $color = $attributes['color'] ?? 'blue';
            $customClass = $attributes['class'] ?? '';
            
            $sizeClasses = [
                'sm' => 'w-4 h-4',
                'md' => 'w-8 h-8',
                'lg' => 'w-12 h-12',
                'xl' => 'w-16 h-16'
            ];
            
            $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
            $finalClass = trim("animate-spin rounded-full border-2 border-gray-300 border-t-{$color}-600 {$sizeClass} {$customClass}");
            
            return "<div class='{$finalClass}' role='status' aria-label='Chargement'></div>";
        });
        
        // Composant Empty State
        $this->registerComponent('empty-state', function($attributes, $content) {
            $icon = $attributes['icon'] ?? 'inbox';
            $title = $attributes['title'] ?? 'Aucun élément';
            $description = $attributes['description'] ?? '';
            $actionText = $attributes['action-text'] ?? '';
            $actionUrl = $attributes['action-url'] ?? '#';
            $customClass = $attributes['class'] ?? '';
            
            $descriptionHtml = $description ? "<p class='mt-2 text-sm text-gray-500'>{$description}</p>" : '';
            $actionHtml = $actionText ? "<div class='mt-6'><a href='{$actionUrl}' class='inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700'>{$actionText}</a></div>" : '';
            
            return "
                <div class='text-center {$customClass}'>
                    <i class='fas fa-{$icon} text-6xl text-gray-400 mb-4'></i>
                    <h3 class='text-lg font-medium text-gray-900'>{$title}</h3>
                    {$descriptionHtml}
                    {$actionHtml}
                    {$content}
                </div>
            ";
        });
    }
    
    private function processComponents(string $content, array $data): string
    {
        // Pattern pour les composants: <nx:component-name attributes>content</nx:component-name>
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
    
    // Méthodes supplémentaires de exemple.php
    public function removeComponent(string $name): void
    {
        unset($this->components[$name]);
    }
    
    public function removeDirective(string $name): void
    {
        unset($this->directives[$name]);
    }
    
    public function getGlobals(): array
    {
        return $this->globals;
    }
    
    public function removeGlobal(string $key): void
    {
        unset($this->globals[$key]);
    }
    
    public function renderWithLayout(string $template, string $layout, array $data = []): string
    {
        // Charger le contenu principal
        $content = $this->render($template, $data);
        
        // Charger le layout avec le contenu
        $layoutData = array_merge($data, ['content' => $content]);
        return $this->render($layout, $layoutData);
    }
    
    public function addComponentAlias(string $alias, string $componentName): void
    {
        if (isset($this->components[$componentName])) {
            $this->components[$alias] = $this->components[$componentName];
        }
    }
    
    public function addDirectiveAlias(string $alias, string $directiveName): void
    {
        if (isset($this->directives[$directiveName])) {
            $this->directives[$alias] = $this->directives[$directiveName];
        }
    }
    
    public function extendEngine(callable $callback): void
    {
        $callback($this);
    }
    
    public function macro(string $name, callable $callback): void
    {
        $this->registerComponent($name, $callback);
    }
    
    public function share(array $data): void
    {
        $this->globals = array_merge($this->globals, $data);
    }
    
    public function composer(string $template, callable $callback): void
    {
        // Enregistrer un callback à exécuter avant le rendu d'un template spécifique
        $this->globals['__composers'][$template] = $callback;
    }
    
    public function creator(string $template, callable $callback): void
    {
        // Enregistrer un callback à exécuter lors de la création d'un template
        $this->globals['__creators'][$template] = $callback;
    }
    
    public function getVersion(): string
    {
        return '2.0.0';
    }
    
    public function getComponentUsage(): array
    {
        // Retourner les statistiques d'utilisation des composants
        return $this->globals['__component_usage'] ?? [];
    }
    
    public function enableDebugMode(): void
    {
        $this->globals['__debug'] = true;
    }
    
    public function disableDebugMode(): void
    {
        $this->globals['__debug'] = false;
    }
    
    public function isDebugMode(): bool
    {
        return $this->globals['__debug'] ?? false;
    }
    
    /**
     * Configurer l'ExtensionManager pour l'injection automatique des assets
     */
    public function setExtensionManager(ExtensionManager $extensionManager): void
    {
        $this->extensionManager = $extensionManager;
    }
    
    /**
     * Obtenir l'ExtensionManager configuré
     */
    public function getExtensionManager(): ?ExtensionManager
    {
        return $this->extensionManager;
    }
    
    /**
     * Injecter les assets JavaScript dans le HTML final
     * Cette méthode utilise l'ExtensionManager pour générer et injecter
     * automatiquement les balises <script> nécessaires
     */
    private function injectJavaScriptAssets(string $content): string
    {
        // Si aucun ExtensionManager n'est configuré, retourner le contenu tel quel
        if (!$this->extensionManager) {
            return $content;
        }
        
        // Générer les balises script pour les assets JavaScript
        $jsAssets = $this->extensionManager->renderJsAssets();
        
        // Si aucun asset JavaScript, retourner le contenu tel quel
        if (empty($jsAssets)) {
            return $content;
        }
        
        // Injecter les scripts avant la fermeture du tag </body>
        if (strpos($content, '</body>') !== false) {
            $content = str_replace('</body>', $jsAssets . '</body>', $content);
        } else {
            // Si pas de tag </body>, ajouter à la fin du contenu
            $content .= $jsAssets;
        }
        
        return $content;
    }
    
    /**
     * Injecter manuellement des scripts JavaScript spécifiques
     * Utile pour ajouter des scripts personnalisés pour les composants interactifs
     */
    public function injectCustomJavaScript(string $content, array $scripts = []): string
    {
        if (empty($scripts)) {
            // Scripts par défaut pour les composants interactifs NX
            $scripts = [
                '/assets/js/nx-components.js', // Script principal pour les composants
                '/assets/js/nx-interactions.js' // Script pour les interactions
            ];
        }
        
        $jsHtml = '';
        foreach ($scripts as $script) {
            $jsHtml .= "<script src=\"{$script}\"></script>\n";
        }
        
        // Injecter avant la fermeture du tag </body>
        if (strpos($content, '</body>') !== false) {
            $content = str_replace('</body>', $jsHtml . '</body>', $content);
        } else {
            $content .= $jsHtml;
        }
        
        return $content;
    }
}

// Fonctions helpers pour compatibilité
if (!function_exists('csrf_token')) {
    function csrf_token() {
        return 'dummy_csrf_token';
    }
}

if (!function_exists('storage_path')) {
    function storage_path($path = '') {
        return sys_get_temp_dir() . '/nx_storage' . ($path ? '/' . $path : '');
    }
}