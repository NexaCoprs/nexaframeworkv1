<?php

namespace Nexa\Cache;

/**
 * Système de cache basé sur les fichiers pour Nexa Framework
 * 
 * Cette classe fournit un système de mise en cache simple et efficace
 * utilisant le système de fichiers pour stocker les données.
 */
class FileCache
{
    private $cachePath;
    private $defaultTtl = 3600; // 1 heure par défaut
    
    public function __construct(string $cachePath = null)
    {
        $this->cachePath = $cachePath ?: storage_path('cache');
        $this->ensureCacheDirectory();
    }
    
    /**
     * Stocker une valeur dans le cache
     */
    public function put(string $key, $value, int $ttl = null): bool
    {
        $ttl = $ttl ?: $this->defaultTtl;
        $expiry = time() + $ttl;
        
        $data = [
            'value' => $value,
            'expiry' => $expiry,
            'created' => time()
        ];
        
        $filename = $this->getFilename($key);
        $serialized = serialize($data);
        
        return file_put_contents($filename, $serialized, LOCK_EX) !== false;
    }
    
    /**
     * Récupérer une valeur du cache
     */
    public function get(string $key, $default = null)
    {
        $filename = $this->getFilename($key);
        
        if (!file_exists($filename)) {
            return $default;
        }
        
        $content = file_get_contents($filename);
        if ($content === false) {
            return $default;
        }
        
        $data = unserialize($content);
        if (!$data || !isset($data['expiry'])) {
            $this->forget($key);
            return $default;
        }
        
        // Vérifier l'expiration
        if (time() > $data['expiry']) {
            $this->forget($key);
            return $default;
        }
        
        return $data['value'];
    }
    
    /**
     * Vérifier si une clé existe dans le cache
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }
    
    /**
     * Supprimer une clé du cache
     */
    public function forget(string $key): bool
    {
        $filename = $this->getFilename($key);
        
        if (file_exists($filename)) {
            return unlink($filename);
        }
        
        return true;
    }
    
    /**
     * Vider tout le cache
     */
    public function flush(): bool
    {
        $files = glob($this->cachePath . '/*.cache');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        return true;
    }
    
    /**
     * Récupérer ou stocker une valeur
     */
    public function remember(string $key, callable $callback, int $ttl = null)
    {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->put($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * Incrémenter une valeur numérique
     */
    public function increment(string $key, int $value = 1): int
    {
        $current = $this->get($key, 0);
        $new = $current + $value;
        $this->put($key, $new);
        
        return $new;
    }
    
    /**
     * Décrémenter une valeur numérique
     */
    public function decrement(string $key, int $value = 1): int
    {
        return $this->increment($key, -$value);
    }
    
    /**
     * Obtenir plusieurs valeurs à la fois
     */
    public function many(array $keys): array
    {
        $result = [];
        
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        
        return $result;
    }
    
    /**
     * Stocker plusieurs valeurs à la fois
     */
    public function putMany(array $values, int $ttl = null): bool
    {
        $success = true;
        
        foreach ($values as $key => $value) {
            if (!$this->put($key, $value, $ttl)) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Nettoyer les entrées expirées
     */
    public function cleanup(): int
    {
        $files = glob($this->cachePath . '/*.cache');
        $cleaned = 0;
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }
            
            $data = unserialize($content);
            if (!$data || !isset($data['expiry'])) {
                unlink($file);
                $cleaned++;
                continue;
            }
            
            if (time() > $data['expiry']) {
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Obtenir des statistiques du cache
     */
    public function stats(): array
    {
        $files = glob($this->cachePath . '/*.cache');
        $totalSize = 0;
        $validEntries = 0;
        $expiredEntries = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
            
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }
            
            $data = unserialize($content);
            if (!$data || !isset($data['expiry'])) {
                $expiredEntries++;
                continue;
            }
            
            if (time() > $data['expiry']) {
                $expiredEntries++;
            } else {
                $validEntries++;
            }
        }
        
        return [
            'total_entries' => count($files),
            'valid_entries' => $validEntries,
            'expired_entries' => $expiredEntries,
            'total_size' => $totalSize,
            'cache_path' => $this->cachePath
        ];
    }
    
    /**
     * Générer le nom de fichier pour une clé
     */
    private function getFilename(string $key): string
    {
        $hash = md5($key);
        return $this->cachePath . '/' . $hash . '.cache';
    }
    
    /**
     * S'assurer que le répertoire de cache existe
     */
    private function ensureCacheDirectory(): void
    {
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }
    
    /**
     * Définir le TTL par défaut
     */
    public function setDefaultTtl(int $ttl): void
    {
        $this->defaultTtl = $ttl;
    }
    
    /**
     * Obtenir le TTL par défaut
     */
    public function getDefaultTtl(): int
    {
        return $this->defaultTtl;
    }
}