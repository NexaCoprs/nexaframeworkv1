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
        
        // Composant Cluster
        $this->registerComponent('cluster', function($attributes, $content) {
            $justify = $attributes['justify'] ?? 'start';
            $align = $attributes['align'] ?? 'center';
            $gap = $attributes['gap'] ?? 'gap-2';
            $customClass = $attributes['class'] ?? '';
            
            $finalClass = trim("flex flex-wrap justify-{$justify} items-{$align} {$gap} {$customClass}");
            
            return "<div class='{$finalClass}'>{$content}</div>";
        });
        
        // Composant Switcher
        $this->registerComponent('switcher', function($attributes, $content) {
            $threshold = $attributes['threshold'] ?? 'md';
            $gap = $attributes['gap'] ?? 'gap-4';
            $customClass = $attributes['class'] ?? '';
            
            $finalClass = trim("flex flex-col {$threshold}:flex-row {$gap} {$customClass}");
            
            return "<div class='{$finalClass}'>{$content}</div>";
        });
        
        // Composant Cover
        $this->registerComponent('cover', function($attributes, $content) {
            $minHeight = $attributes['min-height'] ?? 'min-h-screen';
            $padding = $attributes['padding'] ?? 'p-4';
            $customClass = $attributes['class'] ?? '';
            
            $finalClass = trim("flex flex-col justify-center {$minHeight} {$padding} {$customClass}");
            
            return "<div class='{$finalClass}'>{$content}</div>";
        });
        
        // Composant Sidebar Layout
        $this->registerComponent('sidebar-layout', function($attributes, $content) {
            $sidebarWidth = $attributes['sidebar-width'] ?? 'w-64';
            $position = $attributes['position'] ?? 'left';
            $customClass = $attributes['class'] ?? '';
            
            $flexOrder = $position === 'right' ? 'flex-row-reverse' : 'flex-row';
            $finalClass = trim("flex {$flexOrder} {$customClass}");
            
            return "<div class='{$finalClass}'>{$content}</div>";
        });
        
        // Composant Reel (horizontal scroll)
        $this->registerComponent('reel', function($attributes, $content) {
            $gap = $attributes['gap'] ?? 'gap-4';
            $itemWidth = $attributes['item-width'] ?? 'min-w-80';
            $customClass = $attributes['class'] ?? '';
            
            $finalClass = trim("flex overflow-x-auto {$gap} {$customClass}");
            
            return "<div class='{$finalClass}'><div class='flex {$gap}'>{$content}</div></div>";
        });
        
        // Composant Imposter (centered overlay)
        $this->registerComponent('imposter', function($attributes, $content) {
            $position = $attributes['position'] ?? 'center';
            $customClass = $attributes['class'] ?? '';
            
            $positionClasses = [
                'center' => 'top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2',
                'top' => 'top-4 left-1/2 transform -translate-x-1/2',
                'bottom' => 'bottom-4 left-1/2 transform -translate-x-1/2',
                'top-left' => 'top-4 left-4',
                'top-right' => 'top-4 right-4',
                'bottom-left' => 'bottom-4 left-4',
                'bottom-right' => 'bottom-4 right-4'
            ];
            
            $positionClass = $positionClasses[$position] ?? $positionClasses['center'];
            $finalClass = trim("absolute {$positionClass} {$customClass}");
            
            return "<div class='{$finalClass}'>{$content}</div>";
        });
        
        // Composant Section
        $this->registerComponent('section', function($attributes, $content) {
            $id = $attributes['id'] ?? '';
            $bg = $attributes['bg'] ?? '';
            $padding = $attributes['padding'] ?? 'py-12';
            $customClass = $attributes['class'] ?? '';
            
            $idAttr = $id ? "id='{$id}'" : '';
            $finalClass = trim("section {$bg} {$padding} {$customClass}");
            
            return "<section class='{$finalClass}' {$idAttr}>{$content}</section>";
        });
        
        // Composant Article
        $this->registerComponent('article', function($attributes, $content) {
            $customClass = $attributes['class'] ?? 'prose prose-lg max-w-none';
            
            return "<article class='{$customClass}'>{$content}</article>";
        });
        
        // Composant Header
        $this->registerComponent('header', function($attributes, $content) {
            $sticky = isset($attributes['sticky']);
            $customClass = $attributes['class'] ?? '';
            
            $stickyClass = $sticky ? 'sticky top-0 z-50' : '';
            $finalClass = trim("header {$stickyClass} {$customClass}");
            
            return "<header class='{$finalClass}'>{$content}</header>";
        });
        
        // Composant Footer
        $this->registerComponent('footer', function($attributes, $content) {
            $fixed = isset($attributes['fixed']);
            $customClass = $attributes['class'] ?? '';
            
            $fixedClass = $fixed ? 'fixed bottom-0 left-0 right-0' : '';
            $finalClass = trim("footer {$fixedClass} {$customClass}");
            
            return "<footer class='{$finalClass}'>{$content}</footer>";
        });
        
        // Composant Main
        $this->registerComponent('main', function($attributes, $content) {
            $customClass = $attributes['class'] ?? 'flex-1';
            
            return "<main class='{$customClass}'>{$content}</main>";
        });
        
        // Composant Aside
        $this->registerComponent('aside', function($attributes, $content) {
            $position = $attributes['position'] ?? 'left';
            $width = $attributes['width'] ?? 'w-64';
            $customClass = $attributes['class'] ?? '';
            
            $finalClass = trim("aside {$width} {$customClass}");
            
            return "<aside class='{$finalClass}'>{$content}</aside>";
        });
        
        // Composant Div (wrapper générique)
        $this->registerComponent('div', function($attributes, $content) {
            $customClass = $attributes['class'] ?? '';
            $id = $attributes['id'] ?? '';
            
            $idAttr = $id ? "id='{$id}'" : '';
            
            return "<div class='{$customClass}' {$idAttr}>{$content}</div>";
        });
        
        // ==================== TYPOGRAPHY & CONTENT ====================
        
        // Composant Heading
        $this->registerComponent('heading', function($attributes, $content) {
            $level = $attributes['level'] ?? '1';
            $variant = $attributes['variant'] ?? 'default';
            $size = $attributes['size'] ?? '';
            
            $variantClasses = [
                'default' => 'text-gray-900 font-bold',
                'primary' => 'text-blue-600 font-bold',
                'secondary' => 'text-gray-600 font-semibold',
                'accent' => 'text-purple-600 font-bold'
            ];
            
            $defaultSizes = [
                '1' => 'text-4xl lg:text-5xl',
                '2' => 'text-3xl lg:text-4xl',
                '3' => 'text-2xl lg:text-3xl',
                '4' => 'text-xl lg:text-2xl',
                '5' => 'text-lg lg:text-xl',
                '6' => 'text-base lg:text-lg'
            ];
            
            $variantClass = $variantClasses[$variant] ?? $variantClasses['default'];
            $sizeClass = $size ?: ($defaultSizes[$level] ?? $defaultSizes['1']);
            $customClass = $attributes['class'] ?? '';
            
            $finalClass = trim("{$variantClass} {$sizeClass} {$customClass}");
            
            return "<h{$level} class='{$finalClass}'>{$content}</h{$level}>";
        });
        
        // Composant Typography
        $this->registerComponent('typography', function($attributes, $content) {
            $variant = $attributes['variant'] ?? 'body';
            $customClass = $attributes['class'] ?? '';
            
            $variantClasses = [
                'display' => 'text-6xl font-bold tracking-tight',
                'title' => 'text-4xl font-bold',
                'headline' => 'text-3xl font-semibold',
                'subheading' => 'text-xl font-medium',
                'body' => 'text-base',
                'caption' => 'text-sm text-gray-600',
                'overline' => 'text-xs uppercase tracking-wide text-gray-500'
            ];
            
            $variantClass = $variantClasses[$variant] ?? $variantClasses['body'];
            $finalClass = trim("{$variantClass} {$customClass}");
            
            return "<p class='{$finalClass}'>{$content}</p>";
        });
        
        // Composant Text
        $this->registerComponent('text', function($attributes, $content) {
            $variant = $attributes['variant'] ?? 'body';
            $color = $attributes['color'] ?? 'gray-900';
            
            $variantClasses = [
                'body' => 'text-base',
                'lead' => 'text-lg font-medium',
                'small' => 'text-sm',
                'caption' => 'text-xs text-gray-500'
            ];
            
            $variantClass = $variantClasses[$variant] ?? $variantClasses['body'];
            $colorClass = "text-{$color}";
            $customClass = $attributes['class'] ?? '';
            
            $finalClass = trim("{$variantClass} {$colorClass} {$customClass}");
            
            return "<p class='{$finalClass}'>{$content}</p>";
        });
        
        // Composant Link
        $this->registerComponent('link', function($attributes, $content) {
            $href = $attributes['href'] ?? '#';
            $type = $attributes['type'] ?? 'internal';
            $variant = $attributes['variant'] ?? 'default';
            
            $variantClasses = [
                'default' => 'text-blue-600 hover:text-blue-800 underline',
                'button' => 'inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700',
                'subtle' => 'text-gray-600 hover:text-gray-900'
            ];
            
            $variantClass = $variantClasses[$variant] ?? $variantClasses['default'];
            $customClass = $attributes['class'] ?? '';
            $finalClass = trim("{$variantClass} {$customClass}");
            
            $target = $type === 'external' ? 'target="_blank" rel="noopener noreferrer"' : '';
            $download = $type === 'download' ? 'download' : '';
            
            return "<a href='{$href}' class='{$finalClass}' {$target} {$download}>{$content}</a>";
        });
        
        // Composant Anchor
        $this->registerComponent('anchor', function($attributes, $content) {
            $id = $attributes['id'] ?? '';
            $offset = $attributes['offset'] ?? '-top-20';
            $customClass = $attributes['class'] ?? '';
            
            $finalClass = trim("block relative {$offset} {$customClass}");
            
            return "<div id='{$id}' class='{$finalClass}'>{$content}</div>";
        });
        
        // Composant Code Inline
        $this->registerComponent('code-inline', function($attributes, $content) {
            $customClass = $attributes['class'] ?? '';
            $finalClass = trim("px-1.5 py-0.5 bg-gray-100 text-gray-800 text-sm font-mono rounded {$customClass}");
            
            return "<code class='{$finalClass}'>{$content}</code>";
        });
        
        // Composant Code Block
        $this->registerComponent('code-block', function($attributes, $content) {
            $language = $attributes['language'] ?? '';
            $showLineNumbers = isset($attributes['line-numbers']);
            $customClass = $attributes['class'] ?? '';
            
            $finalClass = trim("block w-full p-4 bg-gray-900 text-gray-100 text-sm font-mono rounded-lg overflow-x-auto {$customClass}");
            $langClass = $language ? "language-{$language}" : '';
            
            return "<pre class='{$finalClass}'><code class='{$langClass}'>{$content}</code></pre>";
        });
        
        // Composant Pre
        $this->registerComponent('pre', function($attributes, $content) {
            $customClass = $attributes['class'] ?? 'whitespace-pre-wrap bg-gray-50 p-4 rounded-lg';
            
            return "<pre class='{$customClass}'>{$content}</pre>";
        });
        
        // Composant Blockquote
        $this->registerComponent('blockquote', function($attributes, $content) {
            $author = $attributes['author'] ?? '';
            $cite = $attributes['cite'] ?? '';
            $customClass = $attributes['class'] ?? '';
            
            $finalClass = trim("border-l-4 border-gray-300 pl-4 italic text-gray-600 {$customClass}");
            $authorHtml = $author ? "<footer class='mt-2 text-sm text-gray-500'>— {$author}</footer>" : '';
            $citeAttr = $cite ? "cite='{$cite}'" : '';
            
            return "<blockquote class='{$finalClass}' {$citeAttr}>{$content}{$authorHtml}</blockquote>";
        });
        
        // Composant Mark/Highlight
        $this->registerComponent('mark', function($attributes, $content) {
            $color = $attributes['color'] ?? 'yellow';
            $customClass = $attributes['class'] ?? '';
            
            $finalClass = trim("bg-{$color}-200 px-1 rounded {$customClass}");
            
            return "<mark class='{$finalClass}'>{$content}</mark>";
        });
        
        // Composant Abbreviation
        $this->registerComponent('abbreviation', function($attributes, $content) {
            $title = $attributes['title'] ?? '';
            $customClass = $attributes['class'] ?? 'border-b border-dotted border-gray-400 cursor-help';
            
            return "<abbr title='{$title}' class='{$customClass}'>{$content}</abbr>";
        });
        
        // Composant Address
        $this->registerComponent('address', function($attributes, $content) {
            $customClass = $attributes['class'] ?? 'not-italic text-gray-700';
            
            return "<address class='{$customClass}'>{$content}</address>";
        });
        
        // Composant Time
        $this->registerComponent('time', function($attributes, $content) {
            $datetime = $attributes['datetime'] ?? '';
            $customClass = $attributes['class'] ?? '';
            
            $datetimeAttr = $datetime ? "datetime='{$datetime}'" : '';
            
            return "<time {$datetimeAttr} class='{$customClass}'>{$content}</time>";
        });
        
        // Composant Kbd (keyboard)
        $this->registerComponent('kbd', function($attributes, $content) {
            $customClass = $attributes['class'] ?? 'px-2 py-1 bg-gray-100 border border-gray-300 rounded text-sm font-mono';
            
            return "<kbd class='{$customClass}'>{$content}</kbd>";
        });
        
        // Composant Prose
        $this->registerComponent('prose', function($attributes, $content) {
            $size = $attributes['size'] ?? 'base';
            $customClass = $attributes['class'] ?? '';
            
            $sizeClasses = [
                'sm' => 'prose-sm',
                'base' => '',
                'lg' => 'prose-lg',
                'xl' => 'prose-xl'
            ];
            
            $sizeClass = $sizeClasses[$size] ?? '';
            $finalClass = trim("prose {$sizeClass} max-w-none {$customClass}");
            
            return "<div class='{$finalClass}'>{$content}</div>";
        });
        
        // ==================== FORMS ====================
        
        // Composant Form
        $this->registerComponent('form', function($attributes, $content) {
            $action = $attributes['action'] ?? '';
            $method = $attributes['method'] ?? 'POST';
            $class = $attributes['class'] ?? 'space-y-6';
            $enctype = $attributes['enctype'] ?? '';
            
            $csrf = $method !== 'GET' ? "<input type='hidden' name='_token' value='" . (function_exists('csrf_token') ? csrf_token() : '') . "'>" : '';
            $enctypeAttr = $enctype ? "enctype='{$enctype}'" : '';
            
            return "
                <form action='{$action}' method='{$method}' class='{$class}' {$enctypeAttr}>
                    {$csrf}
                    {$content}
                </form>
            ";
        });
        
        // Composant Fieldset
        $this->registerComponent('fieldset', function($attributes, $content) {
            $disabled = isset($attributes['disabled']) ? 'disabled' : '';
            $customClass = $attributes['class'] ?? 'border border-gray-200 rounded-lg p-4';
            
            return "<fieldset class='{$customClass}' {$disabled}>{$content}</fieldset>";
        });
        
        // Composant Legend
        $this->registerComponent('legend', function($attributes, $content) {
            $customClass = $attributes['class'] ?? 'text-lg font-medium text-gray-900 px-2';
            
            return "<legend class='{$customClass}'>{$content}</legend>";
        });
        
        // Composant Label
        $this->registerComponent('label', function($attributes, $content) {
            $for = $attributes['for'] ?? '';
            $required = isset($attributes['required']);
            $customClass = $attributes['class'] ?? 'block text-sm font-medium text-gray-700';
            
            $forAttr = $for ? "for='{$for}'" : '';
            $requiredMark = $required ? "<span class='text-red-500'>*</span>" : '';
            
            return "<label {$forAttr} class='{$customClass}'>{$content}{$requiredMark}</label>";
        });
        
        // Composant Input
        $this->registerComponent('input', function($attributes, $content) {
            $type = $attributes['type'] ?? 'text';
            $name = $attributes['name'] ?? '';
            $value = $attributes['value'] ?? '';
            $placeholder = $attributes['placeholder'] ?? '';
            $required = isset($attributes['required']) ? 'required' : '';
            $disabled = isset($attributes['disabled']) ? 'disabled' : '';
            $readonly = isset($attributes['readonly']) ? 'readonly' : '';
            $id = $attributes['id'] ?? $name;
            
            $baseClass = 'block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500';
            $customClass = $attributes['class'] ?? '';
            $finalClass = trim("{$baseClass} {$customClass}");
            
            return "<input type='{$type}' id='{$id}' name='{$name}' value='{$value}' placeholder='{$placeholder}' class='{$finalClass}' {$required} {$disabled} {$readonly}>";
        });
        
        // Composant Input Group
        $this->registerComponent('input-group', function($attributes, $content) {
            $customClass = $attributes['class'] ?? 'relative';
            
            return "<div class='{$customClass}'>{$content}</div>";
        });
        
        // Composant Input Addon
        $this->registerComponent('input-addon', function($attributes, $content) {
            $position = $attributes['position'] ?? 'left';
            $type = $attributes['type'] ?? 'text';
            $customClass = $attributes['class'] ?? '';
            
            $positionClasses = [
                'left' => 'absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none',
                'right' => 'absolute inset-y-0 right-0 pr-3 flex items-center'
            ];
            
            $positionClass = $positionClasses[$position] ?? $positionClasses['left'];
            $finalClass = trim("{$positionClass} {$customClass}");
            
            if ($type === 'button') {
                return "<div class='{$finalClass}'><button type='button' class='text-gray-400 hover:text-gray-600'>{$content}</button></div>";
            } else {
                return "<div class='{$finalClass}'><span class='text-gray-500 sm:text-sm'>{$content}</span></div>";
            }
        });
        
        // Composant Textarea
        $this->registerComponent('textarea', function($attributes, $content) {
            $name = $attributes['name'] ?? '';
            $placeholder = $attributes['placeholder'] ?? '';
            $rows = $attributes['rows'] ?? '3';
            $required = isset($attributes['required']) ? 'required' : '';
            $disabled = isset($attributes['disabled']) ? 'disabled' : '';
            $readonly = isset($attributes['readonly']) ? 'readonly' : '';
            $id = $attributes['id'] ?? $name;
            $resize = $attributes['resize'] ?? 'vertical';
            
            $baseClass = 'block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500';
            $resizeClass = "resize-{$resize}";
            $customClass = $attributes['class'] ?? '';
            $finalClass = trim("{$baseClass} {$resizeClass} {$customClass}");
            
            return "<textarea id='{$id}' name='{$name}' class='{$finalClass}' placeholder='{$placeholder}' rows='{$rows}' {$required} {$disabled} {$readonly}>{$content}</textarea>";
        });
        
        // Composant Textarea Auto Resize
        $this->registerComponent('textarea-auto-resize', function($attributes, $content) {
            $name = $attributes['name'] ?? '';
            $placeholder = $attributes['placeholder'] ?? '';
            $minRows = $attributes['min-rows'] ?? '3';
            $maxRows = $attributes['max-rows'] ?? '10';
            $id = $attributes['id'] ?? $name;
            
            $baseClass = 'block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none';
            $customClass = $attributes['class'] ?? '';
            $finalClass = trim("{$baseClass} {$customClass}");
            
            return "<textarea id='{$id}' name='{$name}' class='{$finalClass}' placeholder='{$placeholder}' rows='{$minRows}' data-min-rows='{$minRows}' data-max-rows='{$maxRows}' oninput='autoResize(this)'>{$content}</textarea>";
        });
        
     // Suite du composant Select
     $this->registerComponent('select', function($attributes, $content) {
        $name = $attributes['name'] ?? '';
        $required = isset($attributes['required']) ? 'required' : '';
        $multiple = isset($attributes['multiple']) ? 'multiple' : '';
        $disabled = isset($attributes['disabled']) ? 'disabled' : '';
        $id = $attributes['id'] ?? $name;
        
        $baseClass = 'block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500';
        $customClass = $attributes['class'] ?? '';
        $finalClass = trim("{$baseClass} {$customClass}");
        
        return "<select id='{$id}' name='{$name}' class='{$finalClass}' {$required} {$multiple} {$disabled}>{$content}</select>";
    });
    
    // Composant Select Custom
    $this->registerComponent('select-custom', function($attributes, $content) {
        $name = $attributes['name'] ?? '';
        $placeholder = $attributes['placeholder'] ?? 'Sélectionner...';
        $searchable = isset($attributes['searchable']);
        $id = $attributes['id'] ?? $name;
        
        $searchInput = $searchable ? "<input type='text' class='w-full px-3 py-2 border-b border-gray-200 focus:outline-none' placeholder='Rechercher...'>" : '';
        
        return "
            <div class='relative' id='{$id}-container'>
                <button type='button' class='w-full px-3 py-2 text-left bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500' onclick='toggleSelect(\"{$id}\")'>
                    <span class='block truncate'>{$placeholder}</span>
                    <span class='absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none'>
                        <i class='fas fa-chevron-down text-gray-400'></i>
                    </span>
                </button>
                <div id='{$id}-dropdown' class='hidden absolute z-10 w-full mt-1 bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto'>
                    {$searchInput}
                    {$content}
                </div>
                <input type='hidden' name='{$name}' id='{$id}-input'>
            </div>
        ";
    });
    
    // Composant Multi Select
    $this->registerComponent('multi-select', function($attributes, $content) {
        $name = $attributes['name'] ?? '';
        $placeholder = $attributes['placeholder'] ?? 'Sélectionner...';
        $id = $attributes['id'] ?? $name;
        
        return "
            <div class='relative' id='{$id}-container'>
                <div class='w-full min-h-10 px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer' onclick='toggleMultiSelect(\"{$id}\")'>
                    <div id='{$id}-selected' class='flex flex-wrap gap-1'>
                        <span class='text-gray-500'>{$placeholder}</span>
                    </div>
                </div>
                <div id='{$id}-dropdown' class='hidden absolute z-10 w-full mt-1 bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto'>
                    {$content}
                </div>
            </div>
        ";
    });
    
    // Composant Combobox
    $this->registerComponent('combobox', function($attributes, $content) {
        $name = $attributes['name'] ?? '';
        $placeholder = $attributes['placeholder'] ?? 'Tapez pour rechercher...';
        $id = $attributes['id'] ?? $name;
        
        return "
            <div class='relative' id='{$id}-container'>
                <input type='text' id='{$id}' name='{$name}' class='w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500' placeholder='{$placeholder}' onkeyup='filterCombobox(\"{$id}\")' onfocus='showCombobox(\"{$id}\")'>
                <div id='{$id}-dropdown' class='hidden absolute z-10 w-full mt-1 bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto'>
                    {$content}
                </div>
            </div>
        ";
    });
    
    // Composant Listbox
    $this->registerComponent('listbox', function($attributes, $content) {
        $name = $attributes['name'] ?? '';
        $multiple = isset($attributes['multiple']);
        $size = $attributes['size'] ?? '5';
        $id = $attributes['id'] ?? $name;
        
        $multipleAttr = $multiple ? 'multiple' : '';
        $baseClass = 'block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500';
        
        return "<select id='{$id}' name='{$name}' size='{$size}' class='{$baseClass}' {$multipleAttr}>{$content}</select>";
    });
    
    // Composant Radio Group
    $this->registerComponent('radio-group', function($attributes, $content) {
        $name = $attributes['name'] ?? '';
        $direction = $attributes['direction'] ?? 'vertical';
        $customClass = $attributes['class'] ?? '';
        
        $directionClass = $direction === 'horizontal' ? 'flex space-x-4' : 'space-y-3';
        $finalClass = trim("{$directionClass} {$customClass}");
        
        return "<div class='{$finalClass}' role='radiogroup'>{$content}</div>";
    });
    
    // Composant Checkbox Group
    $this->registerComponent('checkbox-group', function($attributes, $content) {
        $name = $attributes['name'] ?? '';
        $direction = $attributes['direction'] ?? 'vertical';
        $customClass = $attributes['class'] ?? '';
        
        $directionClass = $direction === 'horizontal' ? 'flex flex-wrap gap-4' : 'space-y-3';
        $finalClass = trim("{$directionClass} {$customClass}");
        
        return "<div class='{$finalClass}'>{$content}</div>";
    });
    
    // Composant Switch Toggle
    $this->registerComponent('switch-toggle', function($attributes, $content) {
        $name = $attributes['name'] ?? '';
        $checked = isset($attributes['checked']) ? 'checked' : '';
        $disabled = isset($attributes['disabled']) ? 'disabled' : '';
        $id = $attributes['id'] ?? $name;
        $size = $attributes['size'] ?? 'md';
        
        $sizeClasses = [
            'sm' => 'w-8 h-4',
            'md' => 'w-11 h-6',
            'lg' => 'w-14 h-7'
        ];
        
        $thumbSizes = [
            'sm' => 'w-3 h-3',
            'md' => 'w-5 h-5',
            'lg' => 'w-6 h-6'
        ];
        
        $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
        $thumbSize = $thumbSizes[$size] ?? $thumbSizes['md'];
        
        return "
            <div class='flex items-center'>
                <input type='checkbox' id='{$id}' name='{$name}' class='sr-only' {$checked} {$disabled}>
                <label for='{$id}' class='relative inline-flex items-center cursor-pointer'>
                    <div class='relative {$sizeClass} bg-gray-200 rounded-full transition-colors duration-200 ease-in-out focus-within:ring-2 focus-within:ring-blue-500 focus-within:ring-offset-2'>
                        <div class='absolute left-0.5 top-0.5 {$thumbSize} bg-white rounded-full shadow transform transition-transform duration-200 ease-in-out'></div>
                    </div>
                    <span class='ml-3 text-sm font-medium text-gray-700'>{$content}</span>
                </label>
            </div>
        ";
    });
    
    // Composant File Input
    $this->registerComponent('file-input', function($attributes, $content) {
        $name = $attributes['name'] ?? '';
        $accept = $attributes['accept'] ?? '';
        $multiple = isset($attributes['multiple']) ? 'multiple' : '';
        $required = isset($attributes['required']) ? 'required' : '';
        $id = $attributes['id'] ?? $name;
        
        $acceptAttr = $accept ? "accept='{$accept}'" : '';
        
        return "
            <div class='flex items-center justify-center w-full'>
                <label for='{$id}' class='flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100'>
                    <div class='flex flex-col items-center justify-center pt-5 pb-6'>
                        <i class='fas fa-cloud-upload-alt text-gray-400 text-2xl mb-2'></i>
                        <p class='mb-2 text-sm text-gray-500'><span class='font-semibold'>{$content}</span></p>
                        <p class='text-xs text-gray-500'>SVG, PNG, JPG ou GIF</p>
                    </div>
                    <input id='{$id}' name='{$name}' type='file' class='hidden' {$acceptAttr} {$multiple} {$required}>
                </label>
            </div>
        ";
    });
    
    // Composant File Dropzone
    $this->registerComponent('file-dropzone', function($attributes, $content) {
        $name = $attributes['name'] ?? '';
        $accept = $attributes['accept'] ?? '';
        $multiple = isset($attributes['multiple']);
        $maxSize = $attributes['max-size'] ?? '10MB';
        $id = $attributes['id'] ?? $name;
        
        return "
            <div id='{$id}' class='w-full p-8 border-2 border-dashed border-gray-300 rounded-lg text-center hover:border-gray-400 transition-colors' ondragover='handleDragOver(event)' ondrop='handleDrop(event, \"{$id}\")'>
                <i class='fas fa-cloud-upload-alt text-4xl text-gray-400 mb-4'></i>
                <p class='text-lg font-medium text-gray-700 mb-2'>{$content}</p>
                <p class='text-sm text-gray-500 mb-4'>Glissez vos fichiers ici ou cliquez pour parcourir</p>
                <p class='text-xs text-gray-400'>Taille max: {$maxSize}</p>
                <input type='file' name='{$name}' accept='{$accept}' class='hidden' " . ($multiple ? 'multiple' : '') . ">
            </div>
        ";
    });
    
    // Composant Range Slider
    $this->registerComponent('range-slider', function($attributes, $content) {
        $name = $attributes['name'] ?? '';
        $min = $attributes['min'] ?? '0';
        $max = $attributes['max'] ?? '100';
        $value = $attributes['value'] ?? '50';
        $step = $attributes['step'] ?? '1';
        $id = $attributes['id'] ?? $name;
        
        return "
            <div class='w-full'>
                <label for='{$id}' class='block text-sm font-medium text-gray-700 mb-2'>{$content}</label>
                <input type='range' id='{$id}' name='{$name}' min='{$min}' max='{$max}' value='{$value}' step='{$step}' class='w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider'>
                <div class='flex justify-between text-xs text-gray-500 mt-1'>
                    <span>{$min}</span>
                    <span>{$max}</span>
                </div>
            </div>
        ";
    });
    
    // Composant Color Input
    $this->registerComponent('color-input', function($attributes, $content) {
        $name = $attributes['name'] ?? '';
        $value = $attributes['value'] ?? '#000000';
        $id = $attributes['id'] ?? $name;
        
        return "
            <div class='flex items-center space-x-3'>
                <input type='color' id='{$id}' name='{$name}' value='{$value}' class='h-10 w-16 border border-gray-300 rounded cursor-pointer'>
                <input type='text' value='{$value}' class='flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500' onchange='syncColorInput(\"{$id}\", this.value)'>
            </div>
        ";
    });
    
    // Composant Date Input
    $this->registerComponent('date-input', function($attributes, $content) {
        $name = $attributes['name'] ?? '';
        $value = $attributes['value'] ?? '';
        $min = $attributes['min'] ?? '';
        $max = $attributes['max'] ?? '';
        $id = $attributes['id'] ?? $name;
        
        $minAttr = $min ? "min='{$min}'" : '';
        $maxAttr = $max ? "max='{$max}'" : '';
        
        return "<input type='date' id='{$id}' name='{$name}' value='{$value}' class='block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500' {$minAttr} {$maxAttr}>";
    });
    
    // Composant Time Input
    $this->registerComponent('time-input', function($attributes, $content) {
        $name = $attributes['name'] ?? '';
        $value = $attributes['value'] ?? '';
        $id = $attributes['id'] ?? $name;
        
        return "<input type='time' id='{$id}' name='{$name}' value='{$value}' class='block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500'>";
    });
    
    // Composant Form Field
    $this->registerComponent('form-field', function($attributes, $content) {
        $label = $attributes['label'] ?? '';
        $required = isset($attributes['required']);
        $error = $attributes['error'] ?? '';
        $help = $attributes['help'] ?? '';
        $customClass = $attributes['class'] ?? '';
        
        $requiredMark = $required ? "<span class='text-red-500'>*</span>" : '';
        $labelHtml = $label ? "<label class='block text-sm font-medium text-gray-700 mb-1'>{$label}{$requiredMark}</label>" : '';
        $errorHtml = $error ? "<p class='mt-1 text-sm text-red-600'>{$error}</p>" : '';
        $helpHtml = $help ? "<p class='mt-1 text-sm text-gray-500'>{$help}</p>" : '';
        
        $finalClass = trim("form-field {$customClass}");
        
        return "
            <div class='{$finalClass}'>
                {$labelHtml}
                {$content}
                {$errorHtml}
                {$helpHtml}
            </div>
        ";
    });
    
    // Composant Field Group
    $this->registerComponent('field-group', function($attributes, $content) {
        $direction = $attributes['direction'] ?? 'vertical';
        $gap = $attributes['gap'] ?? 'gap-4';
        $customClass = $attributes['class'] ?? '';
        
        $directionClass = $direction === 'horizontal' ? 'flex' : 'space-y-4';
        $finalClass = trim("{$directionClass} {$gap} {$customClass}");
        
        return "<div class='{$finalClass}'>{$content}</div>";
    });
    
    // Composant Help Text
    $this->registerComponent('help-text', function($attributes, $content) {
        $customClass = $attributes['class'] ?? 'mt-1 text-sm text-gray-500';
        
        return "<p class='{$customClass}'>{$content}</p>";
    });
    
    // Composant Error Message
    $this->registerComponent('error-message', function($attributes, $content) {
        $customClass = $attributes['class'] ?? 'mt-1 text-sm text-red-600';
        
        return "<p class='{$customClass}' role='alert'>{$content}</p>";
    });
    
    // Composant Validation Message
    $this->registerComponent('validation-message', function($attributes, $content) {
        $type = $attributes['type'] ?? 'error';
        $customClass = $attributes['class'] ?? '';
        
        $typeClasses = [
            'error' => 'text-red-600',
            'success' => 'text-green-600',
            'warning' => 'text-yellow-600',
            'info' => 'text-blue-600'
        ];
        
        $typeClass = $typeClasses[$type] ?? $typeClasses['error'];
        $finalClass = trim("mt-1 text-sm {$typeClass} {$customClass}");
        
        return "<p class='{$finalClass}' role='alert'>{$content}</p>";
    });
    
    // ==================== BUTTONS & ACTIONS ====================
    
    // Composant Button
    $this->registerComponent('button', function($attributes, $content) {
        $type = $attributes['type'] ?? 'button';
        $variant = $attributes['variant'] ?? 'primary';
        $size = $attributes['size'] ?? 'md';
        $onclick = $attributes['onclick'] ?? '';
        $disabled = isset($attributes['disabled']) ? 'disabled' : '';
        $loading = isset($attributes['loading']);
        $icon = $attributes['icon'] ?? '';
        
        $baseClass = 'inline-flex items-center justify-center font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2';
        
        $variantClasses = [
            'primary' => 'bg-blue-600 hover:bg-blue-700 text-white focus:ring-blue-500',
            'secondary' => 'bg-gray-600 hover:bg-gray-700 text-white focus:ring-gray-500',
            'outline' => 'border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 focus:ring-blue-500',
            'ghost' => 'hover:bg-gray-100 text-gray-700 focus:ring-gray-500',
            'danger' => 'bg-red-600 hover:bg-red-700 text-white focus:ring-red-500',
            'success' => 'bg-green-600 hover:bg-green-700 text-white focus:ring-green-500',
            'warning' => 'bg-yellow-600 hover:bg-yellow-700 text-white focus:ring-yellow-500'
        ];
        
        $sizeClasses = [
            'xs' => 'px-2.5 py-1.5 text-xs',
            'sm' => 'px-3 py-1.5 text-sm',
            'md' => 'px-4 py-2 text-sm',
            'lg' => 'px-6 py-3 text-base',
            'xl' => 'px-8 py-4 text-lg'
        ];
        
        $variantClass = $variantClasses[$variant] ?? $variantClasses['primary'];
        $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
        $customClass = $attributes['class'] ?? '';
        
        $finalClass = trim("{$baseClass} {$variantClass} {$sizeClass} {$customClass}");
        
        $iconHtml = $icon ? "<i class='{$icon} mr-2'></i>" : '';
        $loadingHtml = $loading ? "<i class='fas fa-spinner fa-spin mr-2'></i>" : '';
        
        return "<button type='{$type}' class='{$finalClass}' onclick='{$onclick}' {$disabled}>{$loadingHtml}{$iconHtml}{$content}</button>";
    });
    
    // Composant Button Group
    $this->registerComponent('button-group', function($attributes, $content) {
        $orientation = $attributes['orientation'] ?? 'horizontal';
        $size = $attributes['size'] ?? 'md';
        $attached = isset($attributes['attached']);
        
        $orientationClass = $orientation === 'vertical' ? 'flex-col' : 'flex-row';
        $attachedClass = $attached ? '-space-x-px' : 'space-x-2';
        $customClass = $attributes['class'] ?? '';
        $finalClass = trim("inline-flex {$orientationClass} {$attachedClass} {$customClass}");
        
        return "<div class='{$finalClass}' role='group'>{$content}</div>";
    });
    
    // Composant Icon Button
    $this->registerComponent('icon-button', function($attributes, $content) {
        $icon = $attributes['icon'] ?? '';
        $size = $attributes['size'] ?? 'md';
        $variant = $attributes['variant'] ?? 'primary';
        $onclick = $attributes['onclick'] ?? '';
        $disabled = isset($attributes['disabled']) ? 'disabled' : '';
        $tooltip = $attributes['tooltip'] ?? '';
        
        $sizeClasses = [
            'xs' => 'p-1 text-xs',
            'sm' => 'p-1.5 text-sm',
            'md' => 'p-2 text-base',
            'lg' => 'p-3 text-lg',
            'xl' => 'p-4 text-xl'
        ];
        
        $variantClasses = [
            'primary' => 'bg-blue-600 hover:bg-blue-700 text-white',
            'secondary' => 'bg-gray-600 hover:bg-gray-700 text-white',
            'ghost' => 'hover:bg-gray-100 text-gray-700',
            'danger' => 'bg-red-600 hover:bg-red-700 text-white'
        ];
        
        $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
        $variantClass = $variantClasses[$variant] ?? $variantClasses['primary'];
        $customClass = $attributes['class'] ?? '';
        
        $finalClass = trim("inline-flex items-center justify-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 {$variantClass} {$sizeClass} {$customClass}");
        $iconHtml = $icon ? "<i class='{$icon}'></i>" : $content;
        $tooltipAttr = $tooltip ? "title='{$tooltip}'" : '';
        
        return "<button type='button' class='{$finalClass}' onclick='{$onclick}' {$disabled} {$tooltipAttr}>{$iconHtml}</button>";
    });
    
    // Composant Floating Action Button
    $this->registerComponent('floating-action-button', function($attributes, $content) {
        $icon = $attributes['icon'] ?? 'fas fa-plus';
        $position = $attributes['position'] ?? 'bottom-right';
        $size = $attributes['size'] ?? 'md';
        $onclick = $attributes['onclick'] ?? '';
        
        $positionClasses = [
            'bottom-right' => 'fixed bottom-6 right-6',
            'bottom-left' => 'fixed bottom-6 left-6',
            'top-right' => 'fixed top-6 right-6',
            'top-left' => 'fixed top-6 left-6'
        ];
        
        $sizeClasses = [
            'sm' => 'w-12 h-12 text-lg',
            'md' => 'w-16 h-16 text-xl',
            'lg' => 'w-20 h-20 text-2xl'
        ];
        
        $positionClass = $positionClasses[$position] ?? $positionClasses['bottom-right'];
        $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
        
        return "
            <button type='button' class='{$positionClass} {$sizeClass} bg-blue-600 hover:bg-blue-700 text-white rounded-full shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center z-50' onclick='{$onclick}'>
                <i class='{$icon}'></i>
            </button>
        ";
    });
    
    // Composant Split Button
    $this->registerComponent('split-button', function($attributes, $content) {
        $mainAction = $attributes['main-action'] ?? '';
        $mainOnclick = $attributes['main-onclick'] ?? '';
        $variant = $attributes['variant'] ?? 'primary';
        $size = $attributes['size'] ?? 'md';
        $id = $attributes['id'] ?? 'split-button';
        
        $variantClasses = [
            'primary' => 'bg-blue-600 hover:bg-blue-700 text-white',
            'secondary' => 'bg-gray-600 hover:bg-gray-700 text-white'
        ];
        
        $sizeClasses = [
            'sm' => 'px-3 py-1.5 text-sm',
            'md' => 'px-4 py-2 text-sm',
            'lg' => 'px-6 py-3 text-base'
        ];
        
        $variantClass = $variantClasses[$variant] ?? $variantClasses['primary'];
        $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
        
        return "
            <div class='inline-flex rounded-md shadow-sm' role='group'>
                <button type='button' class='{$variantClass} {$sizeClass} rounded-l-md border-r border-opacity-20' onclick='{$mainOnclick}'>
                    {$mainAction}
                </button>
                <button type='button' class='{$variantClass} px-2 py-2 rounded-r-md' onclick='toggleDropdown(\"{$id}-menu\")'>
                    <i class='fas fa-chevron-down'></i>
                </button>
                <div id='{$id}-menu' class='hidden absolute z-10 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5'>
                    <div class='py-1'>
                        {$content}
                    </div>
                </div>
            </div>
        ";
    });
    
    // Composant Toggle Button
    $this->registerComponent('toggle-button', function($attributes, $content) {
        $active = isset($attributes['active']);
        $variant = $attributes['variant'] ?? 'primary';
        $size = $attributes['size'] ?? 'md';
        $onclick = $attributes['onclick'] ?? '';
        $id = $attributes['id'] ?? '';
        
        $baseClass = 'inline-flex items-center justify-center font-medium rounded-md transition-colors focus:outline-none';
        
        $variantClasses = [
            'primary' => $active ? 'bg-blue-600 text-white' : 'bg-white text-blue-600 border border-blue-600 hover:bg-blue-50',
            'secondary' => $active ? 'bg-gray-600 text-white' : 'bg-white text-gray-600 border border-gray-600 hover:bg-gray-50'
        ];
        
        $sizeClasses = [
            'sm' => 'px-3 py-1.5 text-sm',
            'md' => 'px-4 py-2 text-sm',
            'lg' => 'px-6 py-3 text-base'
        ];
        
        $variantClass = $variantClasses[$variant] ?? $variantClasses['primary'];
        $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
        $customClass = $attributes['class'] ?? '';
        
        $finalClass = trim("{$baseClass} {$variantClass} {$sizeClass} {$customClass}");
        $idAttr = $id ? "id='{$id}'" : '';
        
        return "<button type='button' class='{$finalClass}' onclick='{$onclick}' {$idAttr}>{$content}</button>";
    });
    
    // Composant Copy Button
    $this->registerComponent('copy-button', function($attributes, $content) {
        $text = $attributes['text'] ?? '';
        $size = $attributes['size'] ?? 'sm';
        $variant = $attributes['variant'] ?? 'ghost';
        
        $sizeClasses = [
            'xs' => 'p-1 text-xs',
            'sm' => 'p-1.5 text-sm',
            'md' => 'p-2 text-base'
        ];
        
        $sizeClass = $sizeClasses[$size] ?? $sizeClasses['sm'];
        
        return "
            <button type='button' class='inline-flex items-center {$sizeClass} text-gray-500 hover:text-gray-700 focus:outline-none' onclick='copyToClipboard(\"{$text}\")' title='Copier'>
                <i class='fas fa-copy'></i>
                <span class='sr-only'>{$content}</span>
            </button>"


            // Composant Download Button
        $this->registerComponent('download-button', function($attributes, $content) {
            $href = $attributes['href'] ?? '#';
            $filename = $attributes['filename'] ?? '';
            $variant = $attributes['variant'] ?? 'primary';
            $size = $attributes['size'] ?? 'md';
            
            $variantClasses = [
                'primary' => 'bg-blue-600 hover:bg-blue-700 text-white',
                'secondary' => 'bg-gray-600 hover:bg-gray-700 text-white',
                'outline' => 'border border-gray-300 bg-white hover:bg-gray-50 text-gray-700'
            ];
            
            $sizeClasses = [
                'sm' => 'px-3 py-1.5 text-sm',
                'md' => 'px-4 py-2 text-sm',
                'lg' => 'px-6 py-3 text-base'
            ];
            
            $variantClass = $variantClasses[$variant] ?? $variantClasses['primary'];
            $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
            $downloadAttr = $filename ? "download='{$filename}'" : 'download';
            
            return "
                <a href='{$href}' class='inline-flex items-center justify-center font-medium rounded-md transition-colors {$variantClass} {$sizeClass}' {$downloadAttr}>
                    <i class='fas fa-download mr-2'></i>
                    {$content}
                </a>
            ";
        });
        
        // Composant Social Button
        $this->registerComponent('social-button', function($attributes, $content) {
            $platform = $attributes['platform'] ?? 'generic';
            $href = $attributes['href'] ?? '#';
            $size = $attributes['size'] ?? 'md';
            $variant = $attributes['variant'] ?? 'filled';
            
            $platformClasses = [
                'facebook' => ['icon' => 'fab fa-facebook-f', 'color' => 'bg-blue-600 hover:bg-blue-700'],
                'twitter' => ['icon' => 'fab fa-twitter', 'color' => 'bg-blue-400 hover:bg-blue-500'],
                'linkedin' => ['icon' => 'fab fa-linkedin-in', 'color' => 'bg-blue-700 hover:bg-blue-800'],
                'instagram' => ['icon' => 'fab fa-instagram', 'color' => 'bg-pink-600 hover:bg-pink-700'],
                'youtube' => ['icon' => 'fab fa-youtube', 'color' => 'bg-red-600 hover:bg-red-700'],
                'github' => ['icon' => 'fab fa-github', 'color' => 'bg-gray-800 hover:bg-gray-900'],
                'generic' => ['icon' => 'fas fa-share', 'color' => 'bg-gray-600 hover:bg-gray-700']
            ];
            
            $sizeClasses = [
                'sm' => 'w-8 h-8 text-sm',
                'md' => 'w-10 h-10 text-base',
                'lg' => 'w-12 h-12 text-lg'
            ];
            
            $platformData = $platformClasses[$platform] ?? $platformClasses['generic'];
            $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
            
            if ($variant === 'outline') {
                $colorClass = 'border-2 border-gray-300 text-gray-600 hover:bg-gray-50';
            } else {
                $colorClass = $platformData['color'] . ' text-white';
            }
            
            return "
                <a href='{$href}' class='inline-flex items-center justify-center rounded-full transition-colors {$sizeClass} {$colorClass}' target='_blank' rel='noopener noreferrer'>
                    <i class='{$platformData['icon']}'></i>
                    <span class='sr-only'>{$content}</span>
                </a>
            ";
        });
        
        // ==================== DATA DISPLAY ====================
        
        // Composant Table
        $this->registerComponent('table', function($attributes, $content) {
            $striped = isset($attributes['striped']);
            $bordered = isset($attributes['bordered']);
            $hover = isset($attributes['hover']);
            $responsive = isset($attributes['responsive']);
            $size = $attributes['size'] ?? 'md';
            
            $baseClass = 'min-w-full divide-y divide-gray-200';
            
            $sizeClasses = [
                'sm' => 'text-sm',
                'md' => 'text-base',
                'lg' => 'text-lg'
            ];
            
            $stripedClass = $striped ? 'striped-table' : '';
            $borderedClass = $bordered ? 'border border-gray-200' : '';
            $hoverClass = $hover ? 'hover-table' : '';
            $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
            $customClass = $attributes['class'] ?? '';
            
            $finalClass = trim("{$baseClass} {$stripedClass} {$borderedClass} {$hoverClass} {$sizeClass} {$customClass}");
            
            $table = "<table class='{$finalClass}'>{$content}</table>";
            
            return $responsive ? "<div class='overflow-x-auto shadow ring-1 ring-black ring-opacity-5 md:rounded-lg'>{$table}</div>" : $table;
        });
        
        // Composant Table Simple
        $this->registerComponent('table-simple', function($attributes, $content) {
            $customClass = $attributes['class'] ?? 'min-w-full divide-y divide-gray-200';
            
            return "<table class='{$customClass}'>{$content}</table>";
        });
        
        // Composant Table Sortable
        $this->registerComponent('table-sortable', function($attributes, $content) {
            $id = $attributes['id'] ?? 'sortable-table';
            $customClass = $attributes['class'] ?? 'min-w-full divide-y divide-gray-200';
            
            return "<table id='{$id}' class='{$customClass}' data-sortable='true'>{$content}</table>";
        });
        
        // Composant Table Expandable
        $this->registerComponent('table-expandable', function($attributes, $content) {
            $id = $attributes['id'] ?? 'expandable-table';
            $customClass = $attributes['class'] ?? 'min-w-full divide-y divide-gray-200';
            
            return "<table id='{$id}' class='{$customClass}' data-expandable='true'>{$content}</table>";
        });
        
        // Composant Data Grid
        $this->registerComponent('data-grid', function($attributes, $content) {
            $columns = $attributes['columns'] ?? '4';
            $gap = $attributes['gap'] ?? 'gap-4';
            $responsive = isset($attributes['responsive']);
            $customClass = $attributes['class'] ?? '';
            
            if ($responsive) {
                $gridClass = "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{$columns}";
            } else {
                $gridClass = "grid grid-cols-{$columns}";
            }
            
            $finalClass = trim("{$gridClass} {$gap} {$customClass}");
            
            return "<div class='{$finalClass}'>{$content}</div>";
        });
        
        // Composant Virtual Table
        $this->registerComponent('virtual-table', function($attributes, $content) {
            $id = $attributes['id'] ?? 'virtual-table';
            $height = $attributes['height'] ?? '400px';
            $itemHeight = $attributes['item-height'] ?? '50';
            $customClass = $attributes['class'] ?? '';
            
            return "
                <div id='{$id}' class='virtual-table-container {$customClass}' style='height: {$height}' data-item-height='{$itemHeight}'>
                    {$content}
                </div>
            ";
        });
        
        // Composant Stats Grid
        $this->registerComponent('stats-grid', function($attributes, $content) {
            $columns = $attributes['columns'] ?? '3';
            $gap = $attributes['gap'] ?? 'gap-6';
            $responsive = isset($attributes['responsive']);
            $customClass = $attributes['class'] ?? '';
            
            if ($responsive) {
                $gridClass = "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-{$columns}";
            } else {
                $gridClass = "grid grid-cols-{$columns}";
            }
            
            $finalClass = trim("{$gridClass} {$gap} {$customClass}");
            
            return "<div class='{$finalClass}'>{$content}</div>";
        });
        
        // Composant Metric Card
        $this->registerComponent('metric-card', function($attributes, $content) {
            $title = $attributes['title'] ?? '';
            $value = $attributes['value'] ?? '';
            $change = $attributes['change'] ?? '';
            $changeType = $attributes['change-type'] ?? 'neutral';
            $icon = $attributes['icon'] ?? '';
            $trend = $attributes['trend'] ?? '';
            
            $changeClasses = [
                'positive' => 'text-green-600',
                'negative' => 'text-red-600',
                'neutral' => 'text-gray-600'
            ];
            
            $changeClass = $changeClasses[$changeType] ?? $changeClasses['neutral'];
            $iconHtml = $icon ? "<div class='flex-shrink-0'><i class='{$icon} text-2xl text-gray-400'></i></div>" : '';
            $changeHtml = $change ? "<span class='inline-flex items-center text-sm {$changeClass}'>{$change}</span>" : '';
            $trendHtml = $trend ? "<div class='mt-4'>{$trend}</div>" : '';
            
            return "
                <div class='bg-white overflow-hidden shadow rounded-lg'>
                    <div class='p-5'>
                        <div class='flex items-center'>
                            <div class='flex-1'>
                                <dl>
                                    <dt class='text-sm font-medium text-gray-500 truncate'>{$title}</dt>
                                    <dd class='mt-1 text-3xl font-semibold text-gray-900'>{$value}</dd>
                                </dl>
                            </div>
                            {$iconHtml}
                        </div>
                        <div class='mt-4 flex items-center justify-between'>
                            {$changeHtml}
                            {$content}
                        </div>
                        {$trendHtml}
                    </div>
                </div>
            ";
        });
        
        // Composant Key Value Pair
        $this->registerComponent('key-value-pair', function($attributes, $content) {
            $key = $attributes['key'] ?? '';
            $direction = $attributes['direction'] ?? 'horizontal';
            $customClass = $attributes['class'] ?? '';
            
            if ($direction === 'vertical') {
                $containerClass = 'flex flex-col space-y-1';
                $keyClass = 'text-sm font-medium text-gray-500';
                $valueClass = 'text-sm text-gray-900';
            } else {
                $containerClass = 'flex justify-between items-center';
                $keyClass = 'text-sm font-medium text-gray-500';
                $valueClass = 'text-sm text-gray-900';
            }
            
            $finalClass = trim("{$containerClass} {$customClass}");
            
            return "
                <div class='{$finalClass}'>
                    <dt class='{$keyClass}'>{$key}</dt>
                    <dd class='{$valueClass}'>{$content}</dd>
                </div>
            ";
        });
        
        // Composant Timeline
        $this->registerComponent('timeline', function($attributes, $content) {
            $orientation = $attributes['orientation'] ?? 'vertical';
            $customClass = $attributes['class'] ?? '';
            
            if ($orientation === 'horizontal') {
                $finalClass = trim("flex overflow-x-auto space-x-8 pb-4 {$customClass}");
            } else {
                $finalClass = trim("flow-root {$customClass}");
            }
            
            return "<div class='{$finalClass}'>{$content}</div>";
        });
        
        // Composant Timeline Item
        $this->registerComponent('timeline-item', function($attributes, $content) {
            $icon = $attributes['icon'] ?? 'fas fa-circle';
            $color = $attributes['color'] ?? 'blue';
            $title = $attributes['title'] ?? '';
            $date = $attributes['date'] ?? '';
            $last = isset($attributes['last']);
            
            $colorClasses = [
                'blue' => 'bg-blue-600 text-white',
                'green' => 'bg-green-600 text-white',
                'red' => 'bg-red-600 text-white',
                'yellow' => 'bg-yellow-600 text-white',
                'purple' => 'bg-purple-600 text-white',
                'gray' => 'bg-gray-600 text-white'
            ];
            
            $colorClass = $colorClasses[$color] ?? $colorClasses['blue'];
            $lineClass = $last ? '' : 'absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200';
            $titleHtml = $title ? "<div class='min-w-0 flex-1'><h4 class='text-sm font-medium text-gray-900'>{$title}</h4>" : '';
            $dateHtml = $date ? "<p class='text-sm text-gray-500'>{$date}</p>" : '';
            $closeTitle = $title ? '</div>' : '';
            
            return "
                <li class='relative pb-8'>
                    <div class='{$lineClass}'></div>
                    <div class='relative flex space-x-3'>
                        <div>
                            <span class='h-8 w-8 rounded-full {$colorClass} flex items-center justify-center ring-8 ring-white'>
                                <i class='{$icon} text-sm'></i>
                            </span>
                        </div>
                        {$titleHtml}
                        {$dateHtml}
                        <div class='mt-2 text-sm text-gray-700'>{$content}</div>
                        {$closeTitle}
                    </div>
                </li>
            ";
        });
        
        // Composant Calendar
        $this->registerComponent('calendar', function($attributes, $content) {
            $id = $attributes['id'] ?? 'calendar';
            $view = $attributes['view'] ?? 'month';
            $customClass = $attributes['class'] ?? '';
            
            return "
                <div id='{$id}' class='calendar-container {$customClass}' data-view='{$view}'>
                    {$content}
                </div>
            ";
        });
        
        // Composant Avatar
        $this->registerComponent('avatar', function($attributes, $content) {
            $src = $attributes['src'] ?? '';
            $alt = $attributes['alt'] ?? '';
            $size = $attributes['size'] ?? 'md';
            $shape = $attributes['shape'] ?? 'circle';
            $initials = $attributes['initials'] ?? '';
            $status = $attributes['status'] ?? '';
            
            $sizeClasses = [
                'xs' => 'w-6 h-6 text-xs',
                'sm' => 'w-8 h-8 text-sm',
                'md' => 'w-10 h-10 text-base',
                'lg' => 'w-12 h-12 text-lg',
                'xl' => 'w-16 h-16 text-xl',
                '2xl' => 'w-20 h-20 text-2xl'
            ];
            
            $shapeClass = $shape === 'square' ? 'rounded-md' : 'rounded-full';
            $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
            $customClass = $attributes['class'] ?? '';
            
            $finalClass = trim("inline-flex items-center justify-center {$sizeClass} {$shapeClass} {$customClass}");
            
            $statusHtml = '';
            if ($status) {
                $statusClasses = [
                    'online' => 'bg-green-400',
                    'offline' => 'bg-gray-400',
                    'away' => 'bg-yellow-400',
                    'busy' => 'bg-red-400'
                ];
                $statusClass = $statusClasses[$status] ?? $statusClasses['offline'];
                $statusSize = in_array($size, ['xs', 'sm']) ? 'w-2 h-2' : 'w-3 h-3';
                $statusHtml = "<span class='absolute bottom-0 right-0 block {$statusSize} {$statusClass} rounded-full ring-2 ring-white'></span>";
            }
            
            if ($src) {
                return "<div class='relative'><img src='{$src}' alt='{$alt}' class='{$finalClass} object-cover'>{$statusHtml}</div>";
            } else {
                $bgColor = 'bg-gray-300';
                $textColor = 'text-gray-700';
                $displayText = $initials ?: strtoupper(substr($content, 0, 2));
                return "<div class='relative'><div class='{$finalClass} {$bgColor} {$textColor} font-medium'>{$displayText}</div>{$statusHtml}</div>";
            }
        });
        
        // Composant Avatar Group
        $this->registerComponent('avatar-group', function($attributes, $content) {
            $size = $attributes['size'] ?? 'md';
            $max = $attributes['max'] ?? '5';
            $spacing = $attributes['spacing'] ?? '-space-x-2';
            $customClass = $attributes['class'] ?? '';
            
            $finalClass = trim("flex {$spacing} {$customClass}");
            
            return "<div class='{$finalClass}'>{$content}</div>";
        });
        
        // Composant Image Gallery
        $this->registerComponent('image-gallery', function($attributes, $content) {
            $columns = $attributes['columns'] ?? '3';
            $gap = $attributes['gap'] ?? 'gap-4';
            $masonry = isset($attributes['masonry']);
            $customClass = $attributes['class'] ?? '';
            
            if ($masonry) {
                $gridClass = 'masonry-grid';
            } else {
                $gridClass = "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{$columns}";
            }
            
            $finalClass = trim("{$gridClass} {$gap} {$customClass}");
            
            return "<div class='{$finalClass}'>{$content}</div>";
        });
        
        // Composant Masonry Grid
        $this->registerComponent('masonry-grid', function($attributes, $content) {
            $columns = $attributes['columns'] ?? '3';
            $gap = $attributes['gap'] ?? 'gap-4';
            $customClass = $attributes['class'] ?? '';
            
            $finalClass = trim("columns-1 sm:columns-2 lg:columns-{$columns} {$gap} {$customClass}");
            
            return "<div class='{$finalClass}'>{$content}</div>";
        });
        
        // ==================== FEEDBACK ====================
        
        // Composant Alert
        $this->registerComponent('alert', function($attributes, $content) {
            $type = $attributes['type'] ?? 'info';
            $dismissible = isset($attributes['dismissible']);
            $title = $attributes['title'] ?? '';
            $icon = $attributes['icon'] ?? '';
            $border = $attributes['border'] ?? 'left';
            
            $typeClasses = [
                'success' => 'bg-green-50 border-green-200 text-green-800',
                'error' => 'bg-red-50 border-red-200 text-red-800',
                'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
                'info' => 'bg-blue-50 border-blue-200 text-blue-800'
            ];
            
            $iconClasses = [
                'success' => 'text-green-400 fas fa-check-circle',
                'error' => 'text-red-400 fas fa-exclamation-circle',
                'warning' => 'text-yellow-400 fas fa-exclamation-triangle',
                'info' => 'text-blue-400 fas fa-info-circle'
            ];
            
            $borderClasses = [
                'left' => 'border-l-4',
                'top' => 'border-t-4',
                'all' => 'border'
            ];
            
            $typeClass = $typeClasses[$type] ?? $typeClasses['info'];
            $iconClass = $icon ?: ($iconClasses[$type] ?? $iconClasses['info']);
            $borderClass = $borderClasses[$border] ?? $borderClasses['left'];
            
            $closeButton = $dismissible ? "<button type='button' class='ml-auto -mx-1.5 -my-1.5 rounded-lg p-1.5 inline-flex h-8 w-8 text-gray-500 hover:text-gray-900 hover:bg-gray-100' onclick='this.parentElement.remove()'><i class='fas fa-times'></i></button>" : '';
            
            $titleHtml = $title ? "<h3 class='text-sm font-medium mb-1'>{$title}</h3>" : '';
            
            return "
                <div class='flex p-4 {$borderClass} rounded-lg {$typeClass}' role='alert'>
                    <i class='{$iconClass} mr-3 mt-0.5 flex-shrink-0'></i>
                    <div class='flex-1'>
                        {$titleHtml}
                        <div class='text-sm'>{$content}</div>
                    </div>
                    {$closeButton}
                </div>
            ";
        });
        
        // Composant Alert Banner
        $this->registerComponent('alert-banner', function($attributes, $content) {
            $type = $attributes['type'] ?? 'info';
            $dismissible = isset($attributes['dismissible']);
            $centered = isset($attributes['centered']);
            $sticky = isset($attributes['sticky']);
            
            $typeClasses = [
                'success' => 'bg-green-600 text-white',
                'error' => 'bg-red-600 text-white',
                'warning' => 'bg-yellow-600 text-white',
                'info' => 'bg-blue-600 text-white'
            ];
            
            $typeClass = $typeClasses[$type] ?? $typeClasses['info'];
            $centeredClass = $centered ? 'text-center' : '';
            $stickyClass = $sticky ? 'sticky top-0 z-50' : '';
            $closeButton = $dismissible ? "<button type='button' class='ml-4 text-white hover:text-gray-200' onclick='this.parentElement.remove()'><i class='fas fa-times'></i></button>" : '';
            
            return "
                <div class='{$typeClass} {$stickyClass}'>
                    <div class='max-w-7xl mx-auto py-3 px-3 sm:px-6 lg:px-8'>
                        <div class='flex items-center justify-between flex-wrap'>
                            <div class='w-0 flex-1 flex items-center {$centeredClass}'>
                                <p class='font-medium'>{$content}</p>
                            </div>
                            {$closeButton}
                        </div>
                    </div>
                </div>
            ";
        });
        
        // Composant Toast Notification
        $this->registerComponent('toast', function($attributes, $content) {
            $type = $attributes['type'] ?? 'info';
            $position = $attributes['position'] ?? 'top-right';
            $duration = $attributes['duration'] ?? '5000';
            $dismissible = isset($attributes['dismissible']);
            $title = $attributes['title'] ?? '';
            $icon = $attributes['icon'] ?? '';
            
            $typeClasses = [
                'success' => 'bg-green-50 border-green-200 text-green-800',
                'error' => 'bg-red-50 border-red-200 text-red-800',
                'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
                'info' => 'bg-blue-50 border-blue-200 text-blue-800'
            ];
            
            $iconClasses = [
                'success' => 'text-green-400 fas fa-check-circle',
                'error' => 'text-red-400 fas fa-exclamation-circle',
                'warning' => 'text-yellow-400 fas fa-exclamation-triangle',
                'info' => 'text-blue-400 fas fa-info-circle'
            ];
            
            $positionClasses = [
                'top-right' => 'fixed top-4 right-4 z-50',
                'top-left' => 'fixed top-4 left-4 z-50',
                'bottom-right' => 'fixed bottom-4 right-4 z-50',
                'bottom-left' => 'fixed bottom-4 left-4 z-50',
                'top-center' => 'fixed top-4 left-1/2 transform -translate-x-1/2 z-50',
                'bottom-center' => 'fixed bottom-4 left-1/2 transform -translate-x-1/2 z-50'
            ];
            
            $typeClass = $typeClasses[$type] ?? $typeClasses['info'];
            $iconClass = $icon ?: ($iconClasses[$type] ?? $iconClasses['info']);
            $positionClass = $positionClasses[$position] ?? $positionClasses['top-right'];
            
            $closeButton = $dismissible ? "<button type='button' class='ml-auto -mx-1.5 -my-1.5 rounded-lg p-1.5 inline-flex h-8 w-8 text-gray-500 hover:text-gray-900 hover:bg-gray-100' onclick='this.parentElement.remove()'><i class='fas fa-times'></i></button>" : '';
            $titleHtml = $title ? "<h4 class='text-sm font-medium mb-1'>{$title}</h4>" : '';
            
            return "
                <div class='{$positionClass} max-w-sm w-full' data-duration='{$duration}'>
                    <div class='flex p-4 border rounded-lg shadow-lg {$typeClass}' role='alert'>
                        <i class='{$iconClass} mr-3 mt-0.5 flex-shrink-0'></i>
                        <div class='flex-1'>
                            {$titleHtml}
                            <div class='text-sm'>{$content}</div>
                        </div>
                        {$closeButton}
                    </div>
                </div>
            ";
        });
        
        // Composant Inline Notification
        $this->registerComponent('inline-notification', function($attributes, $content) {
            $type = $attributes['type'] ?? 'info';
            $compact = isset($attributes['compact']);
            $dismissible = isset($attributes['dismissible']);
            
            $typeClasses = [
                'success' => 'bg-green-100 border-green-300 text-green-700',
                'error' => 'bg-red-100 border-red-300 text-red-700',
                'warning' => 'bg-yellow-100 border-yellow-300 text-yellow-700',
                'info' => 'bg-blue-100 border-blue-300 text-blue-700'
            ];
            
            $typeClass = $typeClasses[$type] ?? $typeClasses['info'];
            $paddingClass = $compact ? 'px-3 py-2' : 'px-4 py-3';
            $closeButton = $dismissible ? "<button type='button' class='ml-auto text-current opacity-70 hover:opacity-100' onclick='this.parentElement.remove()'><i class='fas fa-times'></i></button>" : '';
            
            return "
                <div class='flex items-center {$paddingClass} border rounded-md {$typeClass}' role='alert'>
                    <div class='flex-1 text-sm'>{$content}</div>
                    {$closeButton}
                </div>
            ";
        });
        
        // Composant Status Badge
        $this->registerComponent('status-badge', function($attributes, $content) {
            $status = $attributes['status'] ?? 'default';
            $size = $attributes['size'] ?? 'md';
            $dot = isset($attributes['dot']);
            $pulse = isset($attributes['pulse']);
            
            $statusClasses = [
                'success' => 'bg-green-100 text-green-800',
                'error' => 'bg-red-100 text-red-800',
                'warning' => 'bg-yellow-100 text-yellow-800',
                'info' => 'bg-blue-100 text-blue-800',
                'default' => 'bg-gray-100 text-gray-800'
            ];
            
            $dotClasses = [
                'success' => 'bg-green-400',
                'error' => 'bg-red-400',
                'warning' => 'bg-yellow-400',
                'info' => 'bg-blue-400',
                'default' => 'bg-gray-400'
            ];
            
            $sizeClasses = [
                'sm' => 'px-2 py-0.5 text-xs',
                'md' => 'px-2.5 py-0.5 text-sm',
                'lg' => 'px-3 py-1 text-base'
            ];
            
            $statusClass = $statusClasses[$status] ?? $statusClasses['default'];
            $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
            $pulseClass = $pulse ? 'animate-pulse' : '';
            
            $dotHtml = '';
            if ($dot) {
                $dotClass =$dotHtml = '';
                if ($dot) {
                    $dotClass = $dotClasses[$status] ?? $dotClasses['default'];
                    $dotHtml = "<span class='inline-block w-2 h-2 {$dotClass} rounded-full mr-1.5 {$pulseClass}'></span>";
                }
                
                return "<span class='inline-flex items-center {$sizeClass} font-medium rounded-full {$statusClass}'>{$dotHtml}{$content}</span>";
            });
            
            // Composant Progress Bar
            $this->registerComponent('progress-bar', function($attributes, $content) {
                $value = $attributes['value'] ?? '0';
                $max = $attributes['max'] ?? '100';
                $color = $attributes['color'] ?? 'blue';
                $size = $attributes['size'] ?? 'md';
                $showLabel = isset($attributes['show-label']);
                $striped = isset($attributes['striped']);
                $animated = isset($attributes['animated']);
                
                $colorClasses = [
                    'blue' => 'bg-blue-600',
                    'green' => 'bg-green-600',
                    'red' => 'bg-red-600',
                    'yellow' => 'bg-yellow-600',
                    'purple' => 'bg-purple-600',
                    'gray' => 'bg-gray-600'
                ];
                
                $sizeClasses = [
                    'xs' => 'h-1',
                    'sm' => 'h-2',
                    'md' => 'h-3',
                    'lg' => 'h-4',
                    'xl' => 'h-6'
                ];
                
                $colorClass = $colorClasses[$color] ?? $colorClasses['blue'];
                $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
                $percentage = round(($value / $max) * 100, 1);
                
                $stripedClass = $striped ? 'bg-gradient-to-r from-transparent via-white to-transparent bg-[length:20px_20px] opacity-20' : '';
                $animatedClass = $animated ? 'animate-pulse' : '';
                
                $label = $showLabel ? "<div class='flex justify-between text-sm text-gray-600 mb-1'><span>{$content}</span><span>{$percentage}%</span></div>" : '';
                
                return "
                    <div>
                        {$label}
                        <div class='w-full bg-gray-200 rounded-full {$sizeClass} overflow-hidden'>
                            <div class='{$colorClass} {$sizeClass} rounded-full transition-all duration-500 ease-out relative {$animatedClass}' style='width: {$percentage}%' role='progressbar' aria-valuenow='{$value}' aria-valuemin='0' aria-valuemax='{$max}'>
                                <div class='absolute inset-0 {$stripedClass}'></div>
                            </div>
                        </div>
                    </div>
                ";
            });
            
            // Composant Progress Circle
            $this->registerComponent('progress-circle', function($attributes, $content) {
                $value = $attributes['value'] ?? '0';
                $max = $attributes['max'] ?? '100';
                $size = $attributes['size'] ?? 'md';
                $color = $attributes['color'] ?? 'blue';
                $thickness = $attributes['thickness'] ?? '4';
                $showValue = isset($attributes['show-value']);
                
                $sizeClasses = [
                    'sm' => 'w-16 h-16',
                    'md' => 'w-24 h-24',
                    'lg' => 'w-32 h-32',
                    'xl' => 'w-40 h-40'
                ];
                
                $colorClasses = [
                    'blue' => 'text-blue-600',
                    'green' => 'text-green-600',
                    'red' => 'text-red-600',
                    'yellow' => 'text-yellow-600',
                    'purple' => 'text-purple-600'
                ];
                
                $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
                $colorClass = $colorClasses[$color] ?? $colorClasses['blue'];
                $percentage = round(($value / $max) * 100, 1);
                $circumference = 2 * 3.14159 * 45; // rayon de 45
                $offset = $circumference - ($percentage / 100) * $circumference;
                
                $valueDisplay = $showValue ? "<div class='absolute inset-0 flex items-center justify-center text-sm font-semibold {$colorClass}'>{$percentage}%</div>" : '';
                
                return "
                    <div class='relative {$sizeClass}'>
                        <svg class='w-full h-full transform -rotate-90' viewBox='0 0 100 100'>
                            <circle cx='50' cy='50' r='45' stroke='currentColor' stroke-width='{$thickness}' fill='none' class='text-gray-200'></circle>
                            <circle cx='50' cy='50' r='45' stroke='currentColor' stroke-width='{$thickness}' fill='none' class='{$colorClass}' stroke-dasharray='{$circumference}' stroke-dashoffset='{$offset}' stroke-linecap='round' style='transition: stroke-dashoffset 0.5s ease-in-out'></circle>
                        </svg>
                        {$valueDisplay}
                        <div class='absolute inset-0 flex items-center justify-center text-center'>
                            <div class='text-xs'>{$content}</div>
                        </div>
                    </div>
                ";
            });
            
            // Composant Loading Spinner
            $this->registerComponent('loading-spinner', function($attributes, $content) {
                $size = $attributes['size'] ?? 'md';
                $color = $attributes['color'] ?? 'blue';
                $type = $attributes['type'] ?? 'spin';
                $overlay = isset($attributes['overlay']);
                
                $sizeClasses = [
                    'xs' => 'w-3 h-3',
                    'sm' => 'w-4 h-4',
                    'md' => 'w-6 h-6',
                    'lg' => 'w-8 h-8',
                    'xl' => 'w-12 h-12'
                ];
                
                $colorClasses = [
                    'blue' => 'text-blue-600',
                    'green' => 'text-green-600',
                    'red' => 'text-red-600',
                    'yellow' => 'text-yellow-600',
                    'purple' => 'text-purple-600',
                    'gray' => 'text-gray-600',
                    'white' => 'text-white'
                ];
                
                $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
                $colorClass = $colorClasses[$color] ?? $colorClasses['blue'];
                
                $animations = [
                    'spin' => 'animate-spin',
                    'pulse' => 'animate-pulse',
                    'bounce' => 'animate-bounce'
                ];
                
                $animation = $animations[$type] ?? $animations['spin'];
                
                $spinner = "
                    <div class='inline-flex items-center justify-center'>
                        <div class='{$sizeClass} {$colorClass} {$animation}' role='status' aria-label='Chargement'>
                            <svg class='w-full h-full' fill='none' viewBox='0 0 24 24'>
                                <circle class='opacity-25' cx='12' cy='12' r='10' stroke='currentColor' stroke-width='4'></circle>
                                <path class='opacity-75' fill='currentColor' d='M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z'></path>
                            </svg>
                        </div>
                        {$content}
                    </div>
                ";
                
                if ($overlay) {
                    return "
                        <div class='fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50'>
                            <div class='bg-white rounded-lg p-6 shadow-xl'>
                                {$spinner}
                            </div>
                        </div>
                    ";
                }
                
                return $spinner;
            });
            
            // Composant Loading Skeleton
            $this->registerComponent('loading-skeleton', function($attributes, $content) {
                $type = $attributes['type'] ?? 'text';
                $lines = $attributes['lines'] ?? '3';
                $avatar = isset($attributes['avatar']);
                $width = $attributes['width'] ?? 'w-full';
                $height = $attributes['height'] ?? 'h-4';
                $customClass = $attributes['class'] ?? '';
                
                $baseClass = 'animate-pulse bg-gray-200 rounded';
                
                if ($type === 'text') {
                    $skeletonLines = '';
                    for ($i = 0; $i < intval($lines); $i++) {
                        $lineWidth = $i === intval($lines) - 1 ? 'w-3/4' : 'w-full';
                        $skeletonLines .= "<div class='{$baseClass} {$height} {$lineWidth} mb-2'></div>";
                    }
                    return "<div class='{$customClass}'>{$skeletonLines}</div>";
                } elseif ($type === 'card') {
                    $avatarHtml = $avatar ? "<div class='{$baseClass} w-12 h-12 rounded-full mb-4'></div>" : '';
                    return "
                        <div class='p-4 {$customClass}'>
                            {$avatarHtml}
                            <div class='{$baseClass} h-6 w-3/4 mb-2'></div>
                            <div class='{$baseClass} h-4 w-full mb-2'></div>
                            <div class='{$baseClass} h-4 w-2/3'></div>
                        </div>
                    ";
                } elseif ($type === 'custom') {
                    return "<div class='{$baseClass} {$width} {$height} {$customClass}'></div>";
                }
                
                return "<div class='{$baseClass} {$customClass}'>{$content}</div>";
            });
            
            // Composant Empty State
            $this->registerComponent('empty-state', function($attributes, $content) {
                $icon = $attributes['icon'] ?? 'fas fa-inbox';
                $title = $attributes['title'] ?? 'Aucun élément';
                $description = $attributes['description'] ?? '';
                $action = $attributes['action'] ?? '';
                $actionText = $attributes['action-text'] ?? 'Ajouter un élément';
                $customClass = $attributes['class'] ?? '';
                
                $descriptionHtml = $description ? "<p class='mt-2 text-sm text-gray-500'>{$description}</p>" : '';
                $actionHtml = $action ? "<div class='mt-6'><a href='{$action}' class='inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700'>{$actionText}</a></div>" : '';
                
                return "
                    <div class='text-center py-12 {$customClass}'>
                        <i class='{$icon} text-4xl text-gray-400 mb-4'></i>
                        <h3 class='mt-2 text-sm font-medium text-gray-900'>{$title}</h3>
                        {$descriptionHtml}
                        {$actionHtml}
                        {$content}
                    </div>
                ";
            });
            
            // Composant Error Boundary
            $this->registerComponent('error-boundary', function($attributes, $content) {
                $title = $attributes['title'] ?? 'Une erreur est survenue';
                $description = $attributes['description'] ?? 'Quelque chose s\'est mal passé. Veuillez réessayer.';
                $retry = $attributes['retry'] ?? '';
                $customClass = $attributes['class'] ?? '';
                
                $retryHtml = $retry ? "<button type='button' onclick='{$retry}' class='mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700'>Réessayer</button>" : '';
                
                return "
                    <div class='text-center py-12 {$customClass}'>
                        <i class='fas fa-exclamation-triangle text-4xl text-red-400 mb-4'></i>
                        <h3 class='mt-2 text-lg font-medium text-gray-900'>{$title}</h3>
                        <p class='mt-2 text-sm text-gray-500'>{$description}</p>
                        {$retryHtml}
                        {$content}
                    </div>
                ";
            });
            
            // Composant Success State
            $this->registerComponent('success-state', function($attributes, $content) {
                $icon = $attributes['icon'] ?? 'fas fa-check-circle';
                $title = $attributes['title'] ?? 'Succès !';
                $description = $attributes['description'] ?? '';
                $action = $attributes['action'] ?? '';
                $actionText = $attributes['action-text'] ?? 'Continuer';
                $customClass = $attributes['class'] ?? '';
                
                $descriptionHtml = $description ? "<p class='mt-2 text-sm text-gray-500'>{$description}</p>" : '';
                $actionHtml = $action ? "<div class='mt-6'><a href='{$action}' class='inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700'>{$actionText}</a></div>" : '';
                
                return "
                    <div class='text-center py-12 {$customClass}'>
                        <i class='{$icon} text-4xl text-green-400 mb-4'></i>
                        <h3 class='mt-2 text-lg font-medium text-gray-900'>{$title}</h3>
                        {$descriptionHtml}
                        {$actionHtml}
                        {$content}
                    </div>
                ";
            });
            
            // Composant Warning State
            $this->registerComponent('warning-state', function($attributes, $content) {
                $icon = $attributes['icon'] ?? 'fas fa-exclamation-triangle';
                $title = $attributes['title'] ?? 'Attention !';
                $description = $attributes['description'] ?? '';
                $action = $attributes['action'] ?? '';
                $actionText = $attributes['action-text'] ?? 'Continuer quand même';
                $customClass = $attributes['class'] ?? '';
                
                $descriptionHtml = $description ? "<p class='mt-2 text-sm text-gray-500'>{$description}</p>" : '';
                $actionHtml = $action ? "<div class='mt-6'><a href='{$action}' class='inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700'>{$actionText}</a></div>" : '';
                
                return "
                    <div class='text-center py-12 {$customClass}'>
                        <i class='{$icon} text-4xl text-yellow-400 mb-4'></i>
                        <h3 class='mt-2 text-lg font-medium text-gray-900'>{$title}</h3>
                        {$descriptionHtml}
                        {$actionHtml}
                        {$content}
                    </div>
                ";
            });
            
            // ==================== OVERLAY ====================
            
            // Composant Modal
            $this->registerComponent('modal', function($attributes, $content) {
                $id = $attributes['id'] ?? 'modal';
                $title = $attributes['title'] ?? '';
                $size = $attributes['size'] ?? 'md';
                $closable = !isset($attributes['no-close']);
                $backdrop = $attributes['backdrop'] ?? 'true';
                $centered = isset($attributes['centered']);
                
                $sizeClasses = [
                    'xs' => 'max-w-xs',
                    'sm' => 'max-w-md',
                    'md' => 'max-w-lg',
                    'lg' => 'max-w-2xl',
                    'xl' => 'max-w-4xl',
                    '2xl' => 'max-w-6xl',
                    'full' => 'max-w-full mx-4'
                ];
                
                $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
                $centeredClass = $centered ? 'items-center' : 'items-end justify-center pt-4 px-4 pb-20 text-center sm:block sm:p-0';
                $closeButton = $closable ? "<button type='button' class='text-gray-400 hover:text-gray-600 focus:outline-none' onclick='closeModal(\"{$id}\")'><i class='fas fa-times'></i></button>" : '';
                $backdropClose = $backdrop === 'true' ? "onclick='closeModal(\"{$id}\")'" : '';
                
                return "
                    <div id='{$id}' class='fixed inset-0 z-50 overflow-y-auto hidden' aria-labelledby='modal-title' role='dialog' aria-modal='true'>
                        <div class='flex {$centeredClass} min-h-screen'>
                            <div class='fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity' {$backdropClose}></div>
                            <div class='inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle {$sizeClass} sm:w-full'>
                                <div class='bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4'>
                                    <div class='flex items-start justify-between mb-4'>
                                        <h3 class='text-lg font-medium text-gray-900' id='modal-title'>{$title}</h3>
                                        {$closeButton}
                                    </div>
                                    <div class='mt-2'>
                                        {$content}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                ";
            });
            
            // Composant Drawer Panel
            $this->registerComponent('drawer-panel', function($attributes, $content) {
                $id = $attributes['id'] ?? 'drawer';
                $position = $attributes['position'] ?? 'right';
                $size = $attributes['size'] ?? 'md';
                $title = $attributes['title'] ?? '';
                $closable = !isset($attributes['no-close']);
                
                $positionClasses = [
                    'left' => 'left-0 top-0 h-full',
                    'right' => 'right-0 top-0 h-full',
                    'top' => 'top-0 left-0 w-full',
                    'bottom' => 'bottom-0 left-0 w-full'
                ];
                
                $sizeClasses = [
                    'sm' => $position === 'left' || $position === 'right' ? 'w-64' : 'h-64',
                    'md' => $position === 'left' || $position === 'right' ? 'w-80' : 'h-80',
                    'lg' => $position === 'left' || $position === 'right' ? 'w-96' : 'h-96',
                    'xl' => $position === 'left' || $position === 'right' ? 'w-1/3' : 'h-1/3',
                    'full' => $position === 'left' || $position === 'right' ? 'w-full' : 'h-full'
                ];
                
                $positionClass = $positionClasses[$position] ?? $positionClasses['right'];
                $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
                $closeButton = $closable ? "<button type='button' class='text-gray-400 hover:text-gray-600 focus:outline-none' onclick='closeDrawer(\"{$id}\")'><i class='fas fa-times'></i></button>" : '';
                $titleHtml = $title ? "<h3 class='text-lg font-medium text-gray-900'>{$title}</h3>" : '';
                
                return "
                    <div id='{$id}' class='fixed inset-0 z-50 overflow-hidden hidden' aria-labelledby='slide-over-title' role='dialog' aria-modal='true'>
                        <div class='absolute inset-0 overflow-hidden'>
                            <div class='absolute inset-0 bg-gray-500 bg-opacity-75 transition-opacity' onclick='closeDrawer(\"{$id}\")'></div>
                            <div class='fixed {$positionClass} {$sizeClass}'>
                                <div class='h-full flex flex-col bg-white shadow-xl overflow-y-auto'>
                                    <div class='px-4 py-6 bg-gray-50 sm:px-6'>
                                        <div class='flex items-start justify-between'>
                                            {$titleHtml}
                                            {$closeButton}
                                        </div>
                                    </div>
                                    <div class='relative flex-1 px-4 py-6 sm:px-6'>
                                        {$content}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                ";
            });
            
            // Composant Popover Menu
            $this->registerComponent('popover-menu', function($attributes, $content) {
                $trigger = $attributes['trigger'] ?? '';
                $position = $attributes['position'] ?? 'bottom';
                $align = $attributes['align'] ?? 'left';
                $id = $attributes['id'] ?? 'popover';
                $arrow = isset($attributes['arrow']);
                
                $positionClasses = [
                    'top' => 'bottom-full mb-2',
                    'bottom' => 'top-full mt-2',
                    'left' => 'right-full mr-2',
                    'right' => 'left-full ml-2'
                ];
                
                $alignClasses = [
                    'left' => 'left-0',
                    'right' => 'right-0',
                    'center' => 'left-1/2 transform -translate-x-1/2'
                ];
                
                $positionClass = $positionClasses[$position] ?? $positionClasses['bottom'];
                $alignClass = $alignClasses[$align] ?? $alignClasses['left'];
                
                $arrowHtml = '';
                if ($arrow) {
                    $arrowPosition = [
                        'top' => 'top-full left-4',
                        'bottom' => 'bottom-full left-4',
                        'left' => 'left-full top-4',
                        'right' => 'right-full top-4'
                    ];
                    $arrowClass = $arrowPosition[$position] ?? $arrowPosition['bottom'];
                    $arrowHtml = "<div class='absolute {$arrowClass} w-2 h-2 bg-white transform rotate-45 border border-gray-200'></div>";
                }
                
                return "
                    <div class='relative inline-block'>
                        <button type='button' class='inline-flex items-center' onclick='togglePopover(\"{$id}\")'>
                            {$trigger}
                        </button>
                        <div id='{$id}' class='hidden absolute {$positionClass} {$alignClass} z-20 w-64 bg-white border border-gray-200 rounded-lg shadow-lg'>
                            {$arrowHtml}
                            <div class='p-4'>
                                {$content}
                            </div>
                        </div>
                    </div>
                ";
            });
            
            // Composant Tooltip
            $this->registerComponent('tooltip', function($attributes, $content) {
                $text = $attributes['text'] ?? '';
                $position = $attributes['position'] ?? 'top';
                $trigger = $attributes['trigger'] ?? 'hover';
                $delay = $attributes['delay'] ?? '0';
                
                $positionClasses = [
                    'top' => 'bottom-full left-1/2 transform -translate-x-1/2 mb-2',
                    'bottom' => 'top-full left-1/2 transform -translate-x-1/2 mt-2',
                    'left' => 'right-full top-1/2 transform -translate-y-1/2 mr-2',
                    'right' => 'left-full top-1/2 transform -translate-y-1/2 ml-2'
                ];
                
                $arrowClasses = [
                    'top' => 'top-full left-1/2 transform -translate-x-1/2',
                    'bottom' => 'bottom-full left-1/2 transform -translate-x-1/2',
                    'left' => 'left-full top-1/2 transform -translate-y-1/2',
                    'right' => 'right-full top-1/2 transform -translate-y-1/2'
                ];
                
                $positionClass = $positionClasses[$position] ?? $positionClasses['top'];
                $arrowClass = $arrowClasses[$position] ?? $arrowClasses['top'];
                $triggerClass = $trigger === 'click' ? 'cursor-pointer' : '';
                
                return "
                    <div class='relative inline-block group {$triggerClass}' data-tooltip-delay='{$delay}'>
                        {$content}
                        <div class='absolute {$positionClass} px-2 py-1 text-sm text-white bg-gray-900 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-30 whitespace-nowrap'>
                            {$text}
                            <div class='absolute {$arrowClass} w-2 h-2 bg-gray-900 transform rotate-45'></div>
                        </div>
                    </div>
                ";
            });
            
            // Composant Dropdown Menu
            $this->registerComponent('dropdown-menu', function($attributes, $content) {
                $toggle = $attributes['toggle'] ?? 'Options';
                $id = $attributes['id'] ?? 'dropdown';
                $align = $attributes['align'] ?? 'left';
                $buttonClass = $attributes['button-class'] ?? 'inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500';
                $menuClass = $attributes['menu-class'] ?? '';
                
                $alignClass = $align === 'right' ? 'right-0' : 'left-0';
                $finalMenuClass = trim("absolute {$alignClass} z-10 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none {$menuClass}");
                
                return "
                    <div class='relative inline-block text-left'>
                        <button type='button' class='{$buttonClass}' id='{$id}' onclick='toggleDropdown(\"{$id}-menu\")' aria-expanded='false' aria-haspopup='true'>
                            {$toggle}
                            <i class='ml-2 -mr-1 h-5 w-5 fas fa-chevron-down'></i>
                        </button>
                        <div id='{$id}-menu' class='hidden {$finalMenuClass}' role='menu' aria-orientation='vertical' aria-labelledby='{$id}'>
                            <div class='py-1' role='none'>
                                {$content}
                            </div>
                        </div>
                    </div>
                ";
            });
            
            // Composant Context Menu
            $this->registerComponent('context-menu', function($attributes, $content) {
                $target = $attributes['target'] ?? '';
                $id = $attributes['id'] ?? 'context-menu';
                
                return "
                    <div id='{$id}' class='hidden fixed z-50 w-48 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5' oncontextmenu='return false'>
                        <div class='py-1' role='menu'>
                            {$content}
                        </div>
                    </div>
                    <script>
                    document.addEventListener('contextmenu', function(e) {
                        if (e.target.closest('{$target}')) {
                            e.preventDefault();
                            showContextMenu('{$id}', e.pageX, e.pageY);
                        }
                    });
                    </script>
                ";
            });
            
            // Composant Sheet Dialog
            $this->registerComponent('sheet-dialog', function($attributes, $content) {
                $id = $attributes['id'] ?? 'sheet';
                $title = $attributes['title'] ?? '';
                $position = $attributes['position'] ?? 'bottom';
                $height = $attributes['height'] ?? 'auto';
                
                $positionClasses = [
                    'bottom' => 'bottom-0 left-0 right-0 rounded-t-lg',
                    'top' => 'top-0 left-0 right-0 rounded-b-lg'
                ];
                
                $heightClass = $height === 'full' ? 'h-full' : 'max-h-96';
                $positionClass = $positionClasses[$position] ?? $positionClasses['bottom'];
                $titleHtml = $title ? "<h3 class='text-lg font-medium text-gray-900 mb-4'>{$title}</h3>" : '';
                
                return "
                    <div id='{$id}' class='fixed inset-0 z-50 hidden' aria-labelledby='sheet-title' role='dialog' aria-modal='true'>
                        <div class='fixed inset-0 bg-black bg<div class='fixed inset-0 bg-black bg-opacity-50' onclick='closeSheet(\"{$id}\")'></div>
                    <div class='fixed {$positionClass} bg-white shadow-lg {$heightClass} overflow-y-auto transform transition-transform duration-300 ease-in-out'>
                        <div class='p-4'>
                            <div class='w-12 h-1 bg-gray-300 rounded-full mx-auto mb-4'></div>
                            {$titleHtml}
                            {$content}
                        </div>
                    </div>
                </div>
            ";
        });
        
        // Composant Command Dialog
        $this->registerComponent('command-dialog', function($attributes, $content) {
            $id = $attributes['id'] ?? 'command-dialog';
            $placeholder = $attributes['placeholder'] ?? 'Tapez une commande...';
            
            return "
                <div id='{$id}' class='fixed inset-0 z-50 hidden' role='dialog' aria-modal='true'>
                    <div class='fixed inset-0 bg-black bg-opacity-50' onclick='closeCommandDialog(\"{$id}\")'></div>
                    <div class='fixed top-1/4 left-1/2 transform -translate-x-1/2 w-full max-w-lg bg-white rounded-lg shadow-xl'>
                        <div class='p-4'>
                            <input type='text' class='w-full px-3 py-2 border-0 border-b-2 border-gray-200 focus:outline-none focus:border-blue-500 text-lg' placeholder='{$placeholder}' onkeyup='filterCommands(this.value)'>
                            <div class='mt-4 max-h-64 overflow-y-auto'>
                                {$content}
                            </div>
                        </div>
                    </div>
                </div>
            ";
        });
        
        // Composant Lightbox Gallery
        $this->registerComponent('lightbox-gallery', function($attributes, $content) {
            $id = $attributes['id'] ?? 'lightbox';
            
            return "
                <div id='{$id}' class='fixed inset-0 z-50 hidden bg-black bg-opacity-90' onclick='closeLightbox(\"{$id}\")'>
                    <div class='absolute top-4 right-4'>
                        <button type='button' class='text-white hover:text-gray-300 text-2xl' onclick='closeLightbox(\"{$id}\")'>&times;</button>
                    </div>
                    <div class='flex items-center justify-center h-full p-4'>
                        <div class='relative max-w-4xl max-h-full'>
                            <button type='button' class='absolute left-4 top-1/2 transform -translate-y-1/2 text-white text-2xl hover:text-gray-300' onclick='previousImage()'>&larr;</button>
                            <button type='button' class='absolute right-4 top-1/2 transform -translate-y-1/2 text-white text-2xl hover:text-gray-300' onclick='nextImage()'>&rarr;</button>
                            <div id='{$id}-content' class='text-center'>
                                {$content}
                            </div>
                        </div>
                    </div>
                </div>
            ";
        });
        
        // ==================== MEDIA & VISUAL ====================
        
        // Composant Image Responsive
        $this->registerComponent('image', function($attributes, $content) {
            $src = $attributes['src'] ?? '';
            $alt = $attributes['alt'] ?? '';
            $width = $attributes['width'] ?? '';
            $height = $attributes['height'] ?? '';
            $objectFit = $attributes['object-fit'] ?? 'cover';
            $rounded = $attributes['rounded'] ?? '';
            $lazy = isset($attributes['lazy']);
            $placeholder = $attributes['placeholder'] ?? '';
            
            $objectFitClass = "object-{$objectFit}";
            $roundedClass = $rounded ? "rounded-{$rounded}" : '';
            $customClass = $attributes['class'] ?? '';
            $lazyAttr = $lazy ? 'loading="lazy"' : '';
            
            $finalClass = trim("w-full h-auto {$objectFitClass} {$roundedClass} {$customClass}");
            $sizeAttrs = '';
            
            if ($width) $sizeAttrs .= " width='{$width}'";
            if ($height) $sizeAttrs .= " height='{$height}'";
            
            $placeholderAttr = $placeholder ? "data-placeholder='{$placeholder}'" : '';
            
            return "<img src='{$src}' alt='{$alt}' class='{$finalClass}'{$sizeAttrs} {$lazyAttr} {$placeholderAttr}>";
        });
        
        // Composant Figure
        $this->registerComponent('figure', function($attributes, $content) {
            $caption = $attributes['caption'] ?? '';
            $align = $attributes['align'] ?? 'center';
            $customClass = $attributes['class'] ?? '';
            
            $alignClass = "text-{$align}";
            $captionHtml = $caption ? "<figcaption class='mt-2 text-sm text-gray-500 {$alignClass}'>{$caption}</figcaption>" : '';
            
            return "<figure class='{$customClass}'>{$content}{$captionHtml}</figure>";
        });
        
        // Composant Figcaption
        $this->registerComponent('figcaption', function($attributes, $content) {
            $customClass = $attributes['class'] ?? 'mt-2 text-sm text-gray-500 text-center';
            
            return "<figcaption class='{$customClass}'>{$content}</figcaption>";
        });
        
        // Composant Image Thumbnail
        $this->registerComponent('image-thumbnail', function($attributes, $content) {
            $src = $attributes['src'] ?? '';
            $alt = $attributes['alt'] ?? '';
            $size = $attributes['size'] ?? 'md';
            $shape = $attributes['shape'] ?? 'square';
            $border = isset($attributes['border']);
            
            $sizeClasses = [
                'xs' => 'w-8 h-8',
                'sm' => 'w-12 h-12',
                'md' => 'w-16 h-16',
                'lg' => 'w-20 h-20',
                'xl' => 'w-24 h-24'
            ];
            
            $shapeClass = $shape === 'circle' ? 'rounded-full' : 'rounded-md';
            $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
            $borderClass = $border ? 'border-2 border-gray-200' : '';
            $customClass = $attributes['class'] ?? '';
            
            $finalClass = trim("{$sizeClass} {$shapeClass} {$borderClass} object-cover {$customClass}");
            
            return "<img src='{$src}' alt='{$alt}' class='{$finalClass}'>";
        });
        
        // Composant Video Player
        $this->registerComponent('video-player', function($attributes, $content) {
            $src = $attributes['src'] ?? '';
            $poster = $attributes['poster'] ?? '';
            $controls = !isset($attributes['no-controls']);
            $autoplay = isset($attributes['autoplay']);
            $loop = isset($attributes['loop']);
            $muted = isset($attributes['muted']);
            $width = $attributes['width'] ?? '';
            $height = $attributes['height'] ?? '';
            
            $controlsAttr = $controls ? 'controls' : '';
            $autoplayAttr = $autoplay ? 'autoplay' : '';
            $loopAttr = $loop ? 'loop' : '';
            $mutedAttr = $muted ? 'muted' : '';
            $posterAttr = $poster ? "poster='{$poster}'" : '';
            $widthAttr = $width ? "width='{$width}'" : '';
            $heightAttr = $height ? "height='{$height}'" : '';
            
            $customClass = $attributes['class'] ?? 'w-full h-auto';
            
            return "<video src='{$src}' class='{$customClass}' {$posterAttr} {$controlsAttr} {$autoplayAttr} {$loopAttr} {$mutedAttr} {$widthAttr} {$heightAttr}>{$content}</video>";
        });
        
        // Composant Audio Player
        $this->registerComponent('audio-player', function($attributes, $content) {
            $src = $attributes['src'] ?? '';
            $controls = !isset($attributes['no-controls']);
            $autoplay = isset($attributes['autoplay']);
            $loop = isset($attributes['loop']);
            $muted = isset($attributes['muted']);
            
            $controlsAttr = $controls ? 'controls' : '';
            $autoplayAttr = $autoplay ? 'autoplay' : '';
            $loopAttr = $loop ? 'loop' : '';
            $mutedAttr = $muted ? 'muted' : '';
            
            $customClass = $attributes['class'] ?? 'w-full';
            
            return "<audio src='{$src}' class='{$customClass}' {$controlsAttr} {$autoplayAttr} {$loopAttr} {$mutedAttr}>{$content}</audio>";
        });
        
        // Composant Icon Library
        $this->registerComponent('icon', function($attributes, $content) {
            $name = $attributes['name'] ?? '';
            $library = $attributes['library'] ?? 'fas';
            $size = $attributes['size'] ?? '';
            $color = $attributes['color'] ?? '';
            $spin = isset($attributes['spin']);
            $pulse = isset($attributes['pulse']);
            
            $sizeClass = $size ? "text-{$size}" : '';
            $colorClass = $color ? "text-{$color}" : '';
            $spinClass = $spin ? 'fa-spin' : '';
            $pulseClass = $pulse ? 'fa-pulse' : '';
            $customClass = $attributes['class'] ?? '';
            
            $finalClass = trim("{$library} fa-{$name} {$sizeClass} {$colorClass} {$spinClass} {$pulseClass} {$customClass}");
            
            return "<i class='{$finalClass}' aria-hidden='true'></i>";
        });
        
        // Composant Logo Mark
        $this->registerComponent('logo', function($attributes, $content) {
            $src = $attributes['src'] ?? '';
            $alt = $attributes['alt'] ?? 'Logo';
            $width = $attributes['width'] ?? 'w-auto';
            $height = $attributes['height'] ?? 'h-8';
            $link = $attributes['link'] ?? '';
            
            $customClass = $attributes['class'] ?? '';
            $finalClass = trim("{$width} {$height} {$customClass}");
            
            $logo = "<img src='{$src}' alt='{$alt}' class='{$finalClass}'>";
            
            if ($link) {
                return "<a href='{$link}'>{$logo}</a>";
            }
            
            return $logo;
        });
        
        // Composant Chart Container
        $this->registerComponent('chart-container', function($attributes, $content) {
            $id = $attributes['id'] ?? 'chart';
            $type = $attributes['type'] ?? 'line';
            $width = $attributes['width'] ?? 'w-full';
            $height = $attributes['height'] ?? 'h-64';
            
            $customClass = $attributes['class'] ?? '';
            $finalClass = trim("{$width} {$height} {$customClass}");
            
            return "<div id='{$id}' class='{$finalClass}' data-chart-type='{$type}'>{$content}</div>";
        });
        
        // Composant Visualization
        $this->registerComponent('visualization', function($attributes, $content) {
            $type = $attributes['type'] ?? 'chart';
            $id = $attributes['id'] ?? 'visualization';
            $data = $attributes['data'] ?? '';
            $config = $attributes['config'] ?? '';
            
            $customClass = $attributes['class'] ?? 'w-full h-64';
            
            return "<div id='{$id}' class='{$customClass}' data-viz-type='{$type}' data-viz-data='{$data}' data-viz-config='{$config}'>{$content}</div>";
        });
        
        // Composant Map Container
        $this->registerComponent('map-container', function($attributes, $content) {
            $id = $attributes['id'] ?? 'map';
            $provider = $attributes['provider'] ?? 'openstreetmap';
            $zoom = $attributes['zoom'] ?? '10';
            $lat = $attributes['lat'] ?? '0';
            $lng = $attributes['lng'] ?? '0';
            
            $customClass = $attributes['class'] ?? 'w-full h-64 rounded-lg';
            
            return "<div id='{$id}' class='{$customClass}' data-map-provider='{$provider}' data-map-zoom='{$zoom}' data-map-lat='{$lat}' data-map-lng='{$lng}'>{$content}</div>";
        });
        
        // ==================== INTERACTIVE ELEMENTS ====================
        
        // Composant Accordion
        $this->registerComponent('accordion', function($attributes, $content) {
            $id = $attributes['id'] ?? 'accordion';
            $allowMultiple = isset($attributes['allow-multiple']);
            $customClass = $attributes['class'] ?? '';
            
            $baseClass = 'divide-y divide-gray-200 border border-gray-200 rounded-md';
            $finalClass = trim("{$baseClass} {$customClass}");
            
            return "<div id='{$id}' class='{$finalClass}' data-allow-multiple='{$allowMultiple}'>{$content}</div>";
        });
        
        // Composant Accordion Item
        $this->registerComponent('accordion-item', function($attributes, $content) {
            $id = $attributes['id'] ?? 'item';
            $title = $attributes['title'] ?? '';
            $open = isset($attributes['open']);
            $icon = $attributes['icon'] ?? '';
            $disabled = isset($attributes['disabled']);
            
            $openClass = $open ? '' : 'hidden';
            $iconHtml = $icon ? "<i class='{$icon} mr-2'></i>" : '';
            $disabledClass = $disabled ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100';
            $ariaExpanded = $open ? 'true' : 'false';
            
            return "
                <div class='accordion-item'>
                    <button type='button' class='flex items-center justify-between w-full px-4 py-4 text-left text-gray-900 bg-gray-50 focus:outline-none focus:bg-gray-100 {$disabledClass}' onclick='toggleAccordionItem(\"{$id}\")' aria-expanded='{$ariaExpanded}' " . ($disabled ? 'disabled' : '') . ">
                        <span class='flex items-center font-medium'>
                            {$iconHtml}
                            {$title}
                        </span>
                        <i class='fas fa-chevron-down transform transition-transform duration-200' id='{$id}-icon'></i>
                    </button>
                    <div id='{$id}-content' class='px-4 py-4 text-gray-700 {$openClass}'>
                        {$content}
                    </div>
                </div>
            ";
        });
        
        // Composant Collapsible Section
        $this->registerComponent('collapsible', function($attributes, $content) {
            $title = $attributes['title'] ?? '';
            $open = isset($attributes['open']);
            $id = $attributes['id'] ?? 'collapsible';
            $variant = $attributes['variant'] ?? 'default';
            
            $openClass = $open ? '' : 'hidden';
            $ariaExpanded = $open ? 'true' : 'false';
            
            $variantClasses = [
                'default' => 'border border-gray-200 rounded-lg',
                'simple' => 'border-b border-gray-200',
                'card' => 'bg-white shadow rounded-lg border border-gray-200'
            ];
            
            $variantClass = $variantClasses[$variant] ?? $variantClasses['default'];
            
            return "
                <div class='{$variantClass}'>
                    <button type='button' class='flex items-center justify-between w-full px-4 py-3 text-left bg-gray-50 hover:bg-gray-100 focus:outline-none focus:bg-gray-100' onclick='toggleCollapsible(\"{$id}\")' aria-expanded='{$ariaExpanded}'>
                        <span class='font-medium text-gray-900'>{$title}</span>
                        <i class='fas fa-chevron-down transform transition-transform duration-200' id='{$id}-icon'></i>
                    </button>
                    <div id='{$id}-content' class='px-4 py-3 {$openClass}'>
                        {$content}
                    </div>
                </div>
            ";
        });
        
        // Composant Tab Panel
        $this->registerComponent('tab-panel', function($attributes, $content) {
            $id = $attributes['id'] ?? 'panel';
            $active = isset($attributes['active']);
            
            $activeClass = $active ? 'block' : 'hidden';
            
            return "<div id='{$id}' class='tab-panel {$activeClass}' role='tabpanel'>{$content}</div>";
        });
        
        // Composant Carousel Slide
        $this->registerComponent('carousel-slide', function($attributes, $content) {
            $id = $attributes['id'] ?? 'slide';
            $active = isset($attributes['active']);
            $image = $attributes['image'] ?? '';
            
            $activeClass = $active ? 'block' : 'hidden';
            $imageHtml = $image ? "<img src='{$image}' class='w-full h-full object-cover' alt='Slide'>" : '';
            
            return "
                <div id='{$id}' class='carousel-slide relative {$activeClass}'>
                    {$imageHtml}
                    <div class='absolute inset-0 flex items-center justify-center'>
                        {$content}
                    </div>
                </div>
            ";
        });
        
        // ==================== UTILITY COMPONENTS ====================
        
        // Composant Spacer
        $this->registerComponent('spacer', function($attributes, $content) {
            $size = $attributes['size'] ?? '4';
            $direction = $attributes['direction'] ?? 'both';
            
            $classes = [];
            if ($direction === 'horizontal' || $direction === 'both') {
                $classes[] = "w-{$size}";
            }
            if ($direction === 'vertical' || $direction === 'both') {
                $classes[] = "h-{$size}";
            }
            
            $finalClass = implode(' ', $classes);
            return "<div class='{$finalClass}' aria-hidden='true'></div>";
        });
        
        // Composant Divider
        $this->registerComponent('divider', function($attributes, $content) {
            $orientation = $attributes['orientation'] ?? 'horizontal';
            $color = $attributes['color'] ?? 'gray-200';
            $thickness = $attributes['thickness'] ?? '1';
            $spacing = $attributes['spacing'] ?? '4';
            $label = $attributes['label'] ?? '';
            
            if ($orientation === 'vertical') {
                $finalClass = "border-l border-{$color} h-full mx-{$spacing}";
                if ($thickness !== '1') $finalClass .= " border-l-{$thickness}";
                return "<div class='{$finalClass}' role='separator' aria-orientation='vertical'></div>";
            } else {
                if ($label) {
                    return "
                        <div class='relative my-{$spacing}'>
                            <div class='absolute inset-0 flex items-center'>
                                <div class='w-full border-t border-{$color}'></div>
                            </div>
                            <div class='relative flex justify-center text-sm'>
                                <span class='px-2 bg-white text-gray-500'>{$label}</span>
                            </div>
                        </div>
                    ";
                } else {
                    $finalClass = "border-t border-{$color} w-full my-{$spacing}";
                    if ($thickness !== '1') $finalClass .= " border-t-{$thickness}";
                    return "<hr class='{$finalClass}' role='separator'>";
                }
            }
        });
        
        // Composant Separator
        $this->registerComponent('separator', function($attributes, $content) {
            $type = $attributes['type'] ?? 'line';
            $spacing = $attributes['spacing'] ?? 'my-4';
            $customClass = $attributes['class'] ?? '';
            
            if ($type === 'dot') {
                return "<div class='flex justify-center {$spacing} {$customClass}'><span class='w-1 h-1 bg-gray-400 rounded-full'></span></div>";
            } elseif ($type === 'dots') {
                return "
                    <div class='flex justify-center space-x-2 {$spacing} {$customClass}'>
                        <span class='w-1 h-1 bg-gray-400 rounded-full'></span>
                        <span class='w-1 h-1 bg-gray-400 rounded-full'></span>
                        <span class='w-1 h-1 bg-gray-400 rounded-full'></span>
                    </div>
                ";
            } else {
                return "<hr class='border-gray-200 {$spacing} {$customClass}'>";
            }
        });
        
        // Composant Chip/Tag
        $this->registerComponent('chip', function($attributes, $content) {
            $variant = $attributes['variant'] ?? 'default';
            $size = $attributes['size'] ?? 'md';
            $removable = isset($attributes['removable']);
            $icon = $attributes['icon'] ?? '';
            $clickable = isset($attributes['clickable']);
            $onclick = $attributes['onclick'] ?? '';
            
            $variantClasses = [
                'default' => 'bg-gray-100 text-gray-800 hover:bg-gray-200',
                'primary' => 'bg-blue-100 text-blue-800 hover:bg-blue-200',
                'success' => 'bg-green-100 text-green-800 hover:bg-green-200',
                'warning' => 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200',
                'danger' => 'bg-red-100 text-red-800 hover:bg-red-200',
                'outline' => 'border border-gray-300 text-gray-700 hover:bg-gray-50'
            ];
            
            $sizeClasses = [
                'sm' => 'px-2 py-1 text-xs',
                'md' => 'px-2.5 py-1.5 text-sm',
                'lg' => 'px-3 py-2 text-base'
            ];
            
            $variantClass = $variantClasses[$variant] ?? $variantClasses['default'];
            $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
            $customClass = $attributes['class'] ?? '';
            
            $baseClass = 'inline-flex items-center font-medium rounded-full transition-colors';
            $clickableClass = $clickable ? 'cursor-pointer' : '';
            $finalClass = trim("{$baseClass} {$variantClass} {$sizeClass} {$clickableClass} {$customClass}");
            
            $iconHtml = $icon ? "<i class='{$icon} mr-1'></i>" : '';
            $removeButton = $removable ? "<button type='button' class='ml-1 text-current hover:text-gray-600' onclick='this.parentElement.remove()'><i class='fas fa-times text-xs'></i></button>" : '';
            $onclickAttr = $onclick ? "onclick='{$onclick}'" : '';
            
            $tag = $clickable ? 'button' : 'span';
            $typeAttr = $clickable ? "type='button'" : '';
            
            return "<{$tag} class='{$finalClass}' {$typeAttr} {$onclickAttr}>{$iconHtml}{$content}{$removeButton}</{$tag}>";
        });
        
        // ==================== ACCESSIBILITY ====================
        
        // Composant Skip Navigation
        $this->registerComponent('skip-nav', function($attributes, $content) {
            $href = $attributes['href'] ?? '#main';
            
            return "
                <a href='{$href}' class='sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-blue-600 text-white px-4 py-2 rounded-md z-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2'>
                    {$content}
                </a>
            ";
        });
        
        // Composant Screen Reader Only
        $this->registerComponent('sr-only', function($attributes, $content) {
            return "<span class='sr-only'>{$content}</span>";
        });
        
        // Composant Screen Reader Text
        $this->registerComponent('screen-reader-text', function($attributes, $content) {
            return "<span class='sr-only'>{$content}</span>";
        });
        
        // Composant Focus Visible
        $this->registerComponent('focus-visible', function($attributes, $content) {
            $customClass = $attributes['class'] ?? '';
            $finalClass = trim("focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 {$customClass}");
            
            return "<div class='{$finalClass}' tabindex='0'>{$content}</div>";
        });
        
        // Composant Aria Live Region
        $this->registerComponent('aria-live-region', function($attributes, $content) {
            $politeness = $attributes['politeness'] ?? 'polite';
            $atomic = isset($attributes['atomic']) ? 'true' : 'false';
            $relevant = $attributes['relevant'] ?? 'additions text';
            $id = $attributes['id'] ?? 'live-region';
            
            return "<div id='{$id}' aria-live='{$politeness}' aria-atomic='{$atomic}' aria-relevant='{$relevant}' class='sr-only'>{$content}</div>";
        });
        
        // ==================== MÉTHODES DE TRAITEMENT ====================
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
        
        // Directive @include
        $this->registerDirective('include', function($template, $content) {
            return "<?php include '{$template}'; ?>";
        });
        
        // Directive @extends
        $this->registerDirective('extends', function($layout, $content) {
            return "<?php \$this->layout = '{$layout}'; ?>";
        });
        
        // Directive @section
        $this->registerDirective('section', function($name, $content) {
            return "<?php \$this->startSection('{$name}'); ?>{$content}<?php \$this->endSection(); ?>";
        });
        
        // Directive @yield
        $this->registerDirective('yield', function($section, $content) {
            return "<?php echo \$this->yieldSection('{$section}'); ?>";
        });
        
        // Directive @stack
        $this->registerDirective('stack', function($name, $content) {
            return "<?php echo \$this->yieldStack('{$name}'); ?>";
        });
        
        // Directive @push
        $this->registerDirective('push', function($stack, $content) {
            return "<?php \$this->startPush('{$stack}'); ?>{$content}<?php \$this->endPush(); ?>";
        });
        
        // Directive @can
        $this->registerDirective('can', function($ability, $content) {
            return "<?php if(can('{$ability}')): ?>{$content}<?php endif; ?>";
        });
        
        // Directive @cannot
        $this->registerDirective('cannot', function($ability, $content) {
            return "<?php if(!can('{$ability}')): ?>{$content}<?php endif; ?>";
        });
        
        // Directive @error
        $this->registerDirective('error', function($field, $content) {
            return "<?php if(\$errors->has('{$field}')): ?>{$content}<?php endif; ?>";
        });
        
        // Directive @env
        $this->registerDirective('env', function($environment, $content) {
            return "<?php if(app()->environment('{$environment}')): ?>{$content}<?phpreturn "<?php if(app()->environment('{$environment}')): ?>{$content}<?php endif; ?>";
        });
        
        // Directive @production
        $this->registerDirective('production', function($params, $content) {
            return "<?php if(app()->environment('production')): ?>{$content}<?php endif; ?>";
        });
        
        // Directive @debug
        $this->registerDirective('debug', function($params, $content) {
            return "<?php if(config('app.debug')): ?>{$content}<?php endif; ?>";
        });
        
        // Directive @json
        $this->registerDirective('json', function($data, $content) {
            return "<?php echo json_encode({$data}); ?>";
        });
        
        // Directive @js
        $this->registerDirective('js', function($data, $content) {
            return "<script>window.{$data} = <?php echo json_encode({$data}); ?></script>";
        });
        
        // Directive @lang
        $this->registerDirective('lang', function($key, $content) {
            return "<?php echo __('{$key}'); ?>";
        });
        
        // Directive @choice
        $this->registerDirective('choice', function($params, $content) {
            return "<?php echo trans_choice({$params}); ?>";
        });
        
        // Directive @component
        $this->registerDirective('component', function($name, $content) {
            return "<?php echo \$this->renderComponent('{$name}', []); ?>";
        });
        
        // Directive @slot
        $this->registerDirective('slot', function($name, $content) {
            return "<?php \$this->slot('{$name}', function() { ?>{$content}<?php }); ?>";
        });
        
        // Directive @isset
        $this->registerDirective('isset', function($variable, $content) {
            return "<?php if(isset({$variable})): ?>{$content}<?php endif; ?>";
        });
        
        // Directive @empty
        $this->registerDirective('empty', function($variable, $content) {
            return "<?php if(empty({$variable})): ?>{$content}<?php endif; ?>";
        });
        
        // Directive @unless
        $this->registerDirective('unless', function($condition, $content) {
            return "<?php unless({$condition}): ?>{$content}<?php endunless; ?>";
        });
        
        // Directive @for
        $this->registerDirective('for', function($expression, $content) {
            return "<?php for({$expression}): ?>{$content}<?php endfor; ?>";
        });
        
        // Directive @while
        $this->registerDirective('while', function($condition, $content) {
            return "<?php while({$condition}): ?>{$content}<?php endwhile; ?>";
        });
        
        // Directive @switch
        $this->registerDirective('switch', function($expression, $content) {
            return "<?php switch({$expression}): ?>{$content}<?php endswitch; ?>";
        });
        
        // Directive @case
        $this->registerDirective('case', function($value, $content) {
            return "<?php case {$value}: ?>{$content}<?php break; ?>";
        });
        
        // Directive @default
        $this->registerDirective('default', function($params, $content) {
            return "<?php default: ?>{$content}";
        });
        
        // Directive @break
        $this->registerDirective('break', function($params, $content) {
            return "<?php break; ?>";
        });
        
        // Directive @continue
        $this->registerDirective('continue', function($params, $content) {
            return "<?php continue; ?>";
        });
        
        // Directive @php
        $this->registerDirective('php', function($params, $content) {
            return "<?php {$content} ?>";
        });
        
        // Directive @verbatim
        $this->registerDirective('verbatim', function($params, $content) {
            return $content;
        });
        
        // Directive @once
        $this->registerDirective('once', function($params, $content) {
            return "<?php if(!isset(\$__once_{$params})): \$__once_{$params} = true; ?>{$content}<?php endif; ?>";
        });
        
        // Directive @prepend
        $this->registerDirective('prepend', function($stack, $content) {
            return "<?php \$this->startPrepend('{$stack}'); ?>{$content}<?php \$this->endPrepend(); ?>";
        });
        
        // Directive @hasSection
        $this->registerDirective('hasSection', function($section, $content) {
            return "<?php if(\$this->hasSection('{$section}')): ?>{$content}<?php endif; ?>";
        });
        
        // Directive @sectionMissing
        $this->registerDirective('sectionMissing', function($section, $content) {
            return "<?php if(!\$this->hasSection('{$section}')): ?>{$content}<?php endif; ?>";
        });
    }
    
    private function processComponents(string $content, array $data): string
    {
        // Pattern pour les composants auto-fermants: <nx:component-name attr="value" />
        $selfClosingPattern = '/<nx:([a-zA-Z0-9-_]+)([^>]*?)\/>/s';
        
        $content = preg_replace_callback($selfClosingPattern, function($matches) use ($data) {
            $componentName = $matches[1];
            $attributesString = $matches[2];
            
            if (!isset($this->components[$componentName])) {
                return $matches[0];
            }
            
            $attributes = $this->parseAttributes($attributesString);
            return $this->components[$componentName]($attributes, '');
        }, $content);
        
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
        // Pattern pour les directives avec contenu: @directive(params)...@enddirective
        $blockPattern = '/@([a-zA-Z0-9_]+)\(([^)]*)\)([\s\S]*?)@end\1/s';
        
        $content = preg_replace_callback($blockPattern, function($matches) use ($data) {
            $directiveName = $matches[1];
            $params = $matches[2];
            $content = $matches[3];
            
            if (!isset($this->directives[$directiveName])) {
                return $matches[0];
            }
            
            return $this->directives[$directiveName]($params, $content);
        }, $content);
        
        // Pattern pour les directives simples: @directive(params)
        $simplePattern = '/@([a-zA-Z0-9_]+)(\([^)]*\))?/';
        
        return preg_replace_callback($simplePattern, function($matches) use ($data) {
            $directiveName = $matches[1];
            $params = isset($matches[2]) ? trim($matches[2], '()') : '';
            
            if (!isset($this->directives[$directiveName])) {
                return $matches[0];
            }
            
            return $this->directives[$directiveName]($params, '');
        }, $content);
    }
    
    private function processVariables(string $content, array $data): string
    {
        // Pattern pour les variables échappées: {{{ $variable }}}
        $unescapedPattern = '/\{\{\{\s*(.+?)\s*\}\}\}/';
        
        $content = preg_replace_callback($unescapedPattern, function($matches) use ($data) {
            $variable = trim($matches[1]);
            
            try {
                ob_start();
                extract($data);
                $result = eval("return {$variable};");
                ob_end_clean();
                return $result;
            } catch (Exception $e) {
                ob_end_clean();
                return '';
            }
        }, $content);
        
        // Pattern pour les variables échappées: {{ $variable }}
        $escapedPattern = '/\{\{\s*(.+?)\s*\}\}/';
        
        return preg_replace_callback($escapedPattern, function($matches) use ($data) {
            $variable = trim($matches[1]);
            
            try {
                ob_start();
                extract($data);
                $result = eval("return {$variable};");
                ob_end_clean();
                return htmlspecialchars($result ?? '', ENT_QUOTES, 'UTF-8');
            } catch (Exception $e) {
                ob_end_clean();
                return '';
            }
        }, $content);
    }
    
    private function processComments(string $content): string
    {
        // Supprimer les commentaires {{-- commentaire --}}
        return preg_replace('/\{\{--.*?--\}\}/s', '', $content);
    }
    
    private function parseAttributes(string $attributesString): array
    {
        $attributes = [];
        
        // Pattern pour les attributs: attr="value" ou attr='value' ou attr=value
        $pattern = '/([a-zA-Z0-9-_:@]+)(?:=(["\']?)([^"\'\s>]*)\2)?/s';
        
        preg_match_all($pattern, $attributesString, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $attrName = $match[1];
            $attrValue = isset($match[3]) ? $match[3] : true;
            
            // Convertir les booléens
            if ($attrValue === 'true') {
                $attrValue = true;
            } elseif ($attrValue === 'false') {
                $attrValue = false;
            } elseif (is_numeric($attrValue)) {
                $attrValue = is_float($attrValue) ? (float)$attrValue : (int)$attrValue;
            }
            
            $attributes[$attrName] = $attrValue;
        }
        
        return $attributes;
    }
    
    private function processSlots(string $content, array $data): string
    {
        // Pattern pour les slots: @slot('name')...@endslot
        $pattern = '/@slot\(["\']([^"\']+)["\']\)(.*?)@endslot/s';
        
        return preg_replace_callback($pattern, function($matches) use ($data) {
            $slotName = $matches[1];
            $slotContent = $matches[2];
            
            // Traiter le contenu du slot
            $slotContent = $this->processComponents($slotContent, $data);
            $slotContent = $this->processDirectives($slotContent, $data);
            $slotContent = $this->processVariables($slotContent, $data);
            
            // Stocker le slot pour utilisation ultérieure
            $this->globals['__slots'][$slotName] = $slotContent;
            
            return '';
        }, $content);
    }
    
    private function processIncludes(string $content, array $data): string
    {
        // Pattern pour les includes: @include('template', ['data' => 'value'])
        $pattern = '/@include\(["\']([^"\']+)["\'](?:,\s*(.+?))?\)/';
        
        return preg_replace_callback($pattern, function($matches) use ($data) {
            $templateName = $matches[1];
            $includeData = isset($matches[2]) ? $matches[2] : '[]';
            
            try {
                $includeDataArray = eval("return {$includeData};");
                $mergedData = array_merge($data, $includeDataArray);
                
                return $this->render($templateName, $mergedData);
            } catch (Exception $e) {
                return "<!-- Error including template: {$templateName} -->";
            }
        }, $content);
    }
    
    public function compileTemplate(string $template): string
    {
        $templateFile = $this->templatePath . '/' . $template . '.nx';
        $compiledFile = $this->getCacheDirectory() . '/' . str_replace(['/', '.'], '_', $template) . '.php';
        
        if (!file_exists($templateFile)) {
            throw new \Exception("Template {$template} not found");
        }
        
        // Vérifier si le template compilé existe et est plus récent
        if (file_exists($compiledFile) && filemtime($compiledFile) > filemtime($templateFile)) {
            return $compiledFile;
        }
        
        $content = file_get_contents($templateFile);
        
        // Traitement des commentaires
        $content = $this->processComments($content);
        
        // Compiler le template
        $compiled = $this->processComponents($content, []);
        $compiled = $this->processDirectives($compiled, []);
        $compiled = $this->processSlots($compiled, []);
        $compiled = $this->processIncludes($compiled, []);
        
        // Sauvegarder le template compilé
        $this->ensureCacheDirectoryExists();
        file_put_contents($compiledFile, $compiled);
        
        return $compiledFile;
    }
    
    private function getCacheDirectory(): string
    {
        return function_exists('storage_path') 
            ? storage_path('framework/views') 
            : sys_get_temp_dir() . '/nx_templates';
    }
    
    private function ensureCacheDirectoryExists(): void
    {
        $cacheDir = $this->getCacheDirectory();
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
    }
    
    public function clearCache(): void
    {
        $cacheDir = $this->getCacheDirectory();
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/*.php');
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }
    
    public function renderString(string $template, array $data = []): string
    {
        // Traitement direct d'une chaîne template
        $content = $this->processComments($template);
        $content = $this->processComponents($content, $data);
        $content = $this->processDirectives($content, $data);
        $content = $this->processVariables($content, array_merge($this->globals, $data));
        
        return $content;
    }
    
    public function setTemplatePath(string $path): void
    {
        $this->templatePath = $path;
    }
    
    public function getTemplatePath(): string
    {
        return $this->templatePath;
    }
    
    public function getRegisteredComponents(): array
    {
        return array_keys($this->components);
    }
    
    public function getRegisteredDirectives(): array
    {
        return array_keys($this->directives);
    }
    
    public function hasComponent(string $name): bool
    {
        return isset($this->components[$name]);
    }
    
    public function hasDirective(string $name): bool
    {
        return isset($this->directives[$name]);
    }
    
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
    
    public function extend(callable $callback): void
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