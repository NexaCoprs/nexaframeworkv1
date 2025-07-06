<?php

namespace Nexa\View;

use Nexa\View\Extensions\NxExtension;
use Exception;

class ExtensionManager
{
    private array $extensions = [];
    private array $loadedExtensions = [];
    private AdvancedNxTemplateEngine $templateEngine;
    private array $config = [];
    
    public function __construct(AdvancedNxTemplateEngine $templateEngine, array $config = [])
    {
        $this->templateEngine = $templateEngine;
        $this->config = $config;
    }
    
    /**
     * Enregistrement d'une extension
     */
    public function register(string $name, NxExtension $extension): self
    {
        // Vérifier les dépendances
        $dependencies = $extension->checkDependencies();
        foreach ($dependencies as $dependency) {
            if (!$this->isDependencyMet($dependency)) {
                throw new Exception("Dependency '{$dependency}' not met for extension '{$name}'");
            }
        }
        
        $this->extensions[$name] = $extension;
        return $this;
    }
    
    /**
     * Chargement d'une extension
     */
    public function load(string $name): self
    {
        if (!isset($this->extensions[$name])) {
            throw new Exception("Extension '{$name}' not registered");
        }
        
        if (isset($this->loadedExtensions[$name])) {
            return $this; // Déjà chargée
        }
        
        $extension = $this->extensions[$name];
        $extensionConfig = $this->config[$name] ?? $extension->getDefaultConfig();
        
        // Initialiser l'extension
        $extension->boot($extensionConfig);
        
        // Enregistrer les directives
        foreach ($extension->getDirectives() as $directiveName => $directive) {
            $this->templateEngine->registerDirective($directiveName, $directive);
        }
        
        // Enregistrer les filtres
        foreach ($extension->getFilters() as $filterName => $filter) {
            $this->templateEngine->registerFilter($filterName, $filter);
        }
        
        // Enregistrer les composants
        foreach ($extension->getComponents() as $componentName => $component) {
            $this->templateEngine->registerComponent($componentName, $component);
        }
        
        // Enregistrer les fonctions globales
        foreach ($extension->getFunctions() as $functionName => $function) {
            $this->templateEngine->addGlobal($functionName, $function);
        }
        
        $this->loadedExtensions[$name] = $extension;
        
        return $this;
    }
    
    /**
     * Chargement de toutes les extensions enregistrées
     */
    public function loadAll(): self
    {
        foreach (array_keys($this->extensions) as $name) {
            $this->load($name);
        }
        
        return $this;
    }
    
    /**
     * Déchargement d'une extension
     */
    public function unload(string $name): self
    {
        if (isset($this->loadedExtensions[$name])) {
            unset($this->loadedExtensions[$name]);
        }
        
        return $this;
    }
    
    /**
     * Vérification si une extension est chargée
     */
    public function isLoaded(string $name): bool
    {
        return isset($this->loadedExtensions[$name]);
    }
    
    /**
     * Obtenir une extension chargée
     */
    public function getExtension(string $name): ?NxExtension
    {
        return $this->loadedExtensions[$name] ?? null;
    }
    
    /**
     * Obtenir toutes les extensions chargées
     */
    public function getLoadedExtensions(): array
    {
        return $this->loadedExtensions;
    }
    
    /**
     * Obtenir les informations sur toutes les extensions
     */
    public function getExtensionsInfo(): array
    {
        $info = [];
        
        foreach ($this->extensions as $name => $extension) {
            $info[$name] = [
                'name' => $extension->getName(),
                'version' => $extension->getVersion(),
                'description' => $extension->getDescription(),
                'loaded' => $this->isLoaded($name),
                'directives' => array_keys($extension->getDirectives()),
                'filters' => array_keys($extension->getFilters()),
                'components' => array_keys($extension->getComponents()),
                'functions' => array_keys($extension->getFunctions()),
                'assets' => $extension->getAssets()
            ];
        }
        
        return $info;
    }
    
    /**
     * Génération des assets pour toutes les extensions chargées
     */
    public function generateAssets(): array
    {
        $assets = [
            'css' => [],
            'js' => []
        ];
        
        foreach ($this->loadedExtensions as $extension) {
            $extensionAssets = $extension->getAssets();
            
            if (isset($extensionAssets['css'])) {
                $assets['css'] = array_merge($assets['css'], $extensionAssets['css']);
            }
            
            if (isset($extensionAssets['js'])) {
                $assets['js'] = array_merge($assets['js'], $extensionAssets['js']);
            }
        }
        
        // Supprimer les doublons
        $assets['css'] = array_unique($assets['css']);
        $assets['js'] = array_unique($assets['js']);
        
        return $assets;
    }
    
    /**
     * Génération du HTML pour les assets CSS
     */
    public function renderCssAssets(): string
    {
        $assets = $this->generateAssets();
        $html = '';
        
        foreach ($assets['css'] as $css) {
            $html .= "<link rel=\"stylesheet\" href=\"{$css}\">\n";
        }
        
        return $html;
    }
    
    /**
     * Génération du HTML pour les assets JS
     */
    public function renderJsAssets(): string
    {
        $assets = $this->generateAssets();
        $html = '';
        
        foreach ($assets['js'] as $js) {
            $html .= "<script src=\"{$js}\"></script>\n";
        }
        
        return $html;
    }
    
    /**
     * Auto-découverte et chargement des extensions
     */
    public function autoDiscover(string $extensionsPath = null): self
    {
        $extensionsPath = $extensionsPath ?? __DIR__ . '/Extensions';
        
        if (!is_dir($extensionsPath)) {
            return $this;
        }
        
        $files = glob($extensionsPath . '/*.php');
        
        foreach ($files as $file) {
            $className = basename($file, '.php');
            $fullClassName = "Nexa\\View\\Extensions\\{$className}";
            
            if (class_exists($fullClassName) && is_subclass_of($fullClassName, NxExtension::class)) {
                try {
                    $extension = new $fullClassName();
                    $this->register($extension->getName(), $extension);
                } catch (Exception $e) {
                    // Log l'erreur mais continue
                    error_log("Failed to auto-discover extension {$className}: " . $e->getMessage());
                }
            }
        }
        
        return $this;
    }
    
    /**
     * Validation d'une extension
     */
    public function validateExtension(NxExtension $extension): array
    {
        $errors = [];
        
        // Vérifier le nom
        if (empty($extension->getName())) {
            $errors[] = 'Extension name cannot be empty';
        }
        
        // Vérifier les conflits de noms
        foreach ($extension->getDirectives() as $name => $directive) {
            if ($this->templateEngine->hasDirective($name)) {
                $errors[] = "Directive '{$name}' already exists";
            }
        }
        
        foreach ($extension->getFilters() as $name => $filter) {
            if ($this->templateEngine->hasFilter($name)) {
                $errors[] = "Filter '{$name}' already exists";
            }
        }
        
        foreach ($extension->getComponents() as $name => $component) {
            if ($this->templateEngine->hasComponent($name)) {
                $errors[] = "Component '{$name}' already exists";
            }
        }
        
        // Vérifier les dépendances
        foreach ($extension->checkDependencies() as $dependency) {
            if (!$this->isDependencyMet($dependency)) {
                $errors[] = "Dependency '{$dependency}' not met";
            }
        }
        
        return $errors;
    }
    
    /**
     * Installation d'une extension depuis un package
     */
    public function installFromPackage(string $packagePath): self
    {
        if (!file_exists($packagePath)) {
            throw new Exception("Package file '{$packagePath}' not found");
        }
        
        // Charger le package (JSON ou ZIP)
        $packageInfo = $this->loadPackageInfo($packagePath);
        
        // Valider le package
        $this->validatePackage($packageInfo);
        
        // Installer les fichiers
        $this->installPackageFiles($packageInfo, $packagePath);
        
        // Enregistrer l'extension
        $extensionClass = $packageInfo['extension_class'];
        if (class_exists($extensionClass)) {
            $extension = new $extensionClass();
            $this->register($extension->getName(), $extension);
        }
        
        return $this;
    }
    
    /**
     * Mise à jour d'une extension
     */
    public function updateExtension(string $name, string $packagePath): self
    {
        if (!isset($this->extensions[$name])) {
            throw new Exception("Extension '{$name}' not found");
        }
        
        // Décharger l'ancienne version
        $this->unload($name);
        
        // Installer la nouvelle version
        $this->installFromPackage($packagePath);
        
        // Recharger
        $this->load($name);
        
        return $this;
    }
    
    /**
     * Désinstallation d'une extension
     */
    public function uninstall(string $name): self
    {
        if (isset($this->loadedExtensions[$name])) {
            $this->unload($name);
        }
        
        if (isset($this->extensions[$name])) {
            unset($this->extensions[$name]);
        }
        
        return $this;
    }
    
    /**
     * Sauvegarde de la configuration des extensions
     */
    public function saveConfig(string $configPath): self
    {
        $config = [
            'extensions' => [],
            'auto_load' => []
        ];
        
        foreach ($this->extensions as $name => $extension) {
            $config['extensions'][$name] = [
                'class' => get_class($extension),
                'version' => $extension->getVersion(),
                'config' => $this->config[$name] ?? $extension->getDefaultConfig()
            ];
            
            if ($this->isLoaded($name)) {
                $config['auto_load'][] = $name;
            }
        }
        
        file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT));
        
        return $this;
    }
    
    /**
     * Chargement de la configuration des extensions
     */
    public function loadConfig(string $configPath): self
    {
        if (!file_exists($configPath)) {
            return $this;
        }
        
        $config = json_decode(file_get_contents($configPath), true);
        
        if (!$config) {
            return $this;
        }
        
        // Charger les extensions
        foreach ($config['extensions'] ?? [] as $name => $extensionConfig) {
            $className = $extensionConfig['class'];
            
            if (class_exists($className)) {
                $extension = new $className();
                $this->register($name, $extension);
                $this->config[$name] = $extensionConfig['config'] ?? [];
            }
        }
        
        // Auto-charger les extensions marquées
        foreach ($config['auto_load'] ?? [] as $name) {
            if (isset($this->extensions[$name])) {
                $this->load($name);
            }
        }
        
        return $this;
    }
    
    // Méthodes privées
    
    private function isDependencyMet(string $dependency): bool
    {
        // Vérifier si une dépendance est satisfaite
        // Peut être une classe, une extension, un package Composer, etc.
        
        if (class_exists($dependency)) {
            return true;
        }
        
        if (function_exists($dependency)) {
            return true;
        }
        
        if (extension_loaded($dependency)) {
            return true;
        }
        
        if (isset($this->loadedExtensions[$dependency])) {
            return true;
        }
        
        return false;
    }
    
    private function loadPackageInfo(string $packagePath): array
    {
        $extension = pathinfo($packagePath, PATHINFO_EXTENSION);
        
        switch ($extension) {
            case 'json':
                return json_decode(file_get_contents($packagePath), true);
                
            case 'zip':
                // Extraire et lire le manifest
                $zip = new \ZipArchive();
                if ($zip->open($packagePath) === TRUE) {
                    $manifest = $zip->getFromName('manifest.json');
                    $zip->close();
                    return json_decode($manifest, true);
                }
                break;
        }
        
        throw new Exception("Unsupported package format: {$extension}");
    }
    
    private function validatePackage(array $packageInfo): void
    {
        $required = ['name', 'version', 'extension_class'];
        
        foreach ($required as $field) {
            if (!isset($packageInfo[$field])) {
                throw new Exception("Package missing required field: {$field}");
            }
        }
    }
    
    private function installPackageFiles(array $packageInfo, string $packagePath): void
    {
        // Implémentation de l'installation des fichiers
        // Dépend du format du package (ZIP, etc.)
    }
}