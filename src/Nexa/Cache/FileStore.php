<?php

namespace Nexa\Cache;

class FileStore implements CacheStore
{
    protected $directory;
    
    public function __construct($directory = null)
    {
        $this->directory = $directory ?: storage_path('cache');
        
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0755, true);
        }
    }
    
    /**
     * Retrieve an item from the cache by key.
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        $path = $this->path($key);
        
        if (!file_exists($path)) {
            return null;
        }
        
        $contents = file_get_contents($path);
        $payload = unserialize($contents);
        
        // Check if expired
        if ($payload['expires'] !== null && time() >= $payload['expires']) {
            $this->forget($key);
            return null;
        }
        
        return $payload['data'];
    }
    
    /**
     * Store an item in the cache for a given number of seconds.
     *
     * @param string $key
     * @param mixed $value
     * @param int $seconds
     * @return bool
     */
    public function put($key, $value, $seconds)
    {
        $path = $this->path($key);
        $expires = $seconds > 0 ? time() + $seconds : null;
        
        $payload = serialize([
            'data' => $value,
            'expires' => $expires,
            'created' => time()
        ]);
        
        return file_put_contents($path, $payload, LOCK_EX) !== false;
    }
    
    /**
     * Store an item in the cache indefinitely.
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function forever($key, $value)
    {
        return $this->put($key, $value, 0);
    }
    
    /**
     * Remove an item from the cache.
     *
     * @param string $key
     * @return bool
     */
    public function forget($key)
    {
        $path = $this->path($key);
        
        if (file_exists($path)) {
            return unlink($path);
        }
        
        return true;
    }
    
    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush()
    {
        $files = glob($this->directory . '/*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        return true;
    }
    
    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     *
     * @param string $key
     * @param int $seconds
     * @param callable $callback
     * @return mixed
     */
    public function remember($key, $seconds, callable $callback)
    {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->put($key, $value, $seconds);
        
        return $value;
    }
    
    /**
     * Get an item from the cache, or execute the given Closure and store the result forever.
     *
     * @param string $key
     * @param callable $callback
     * @return mixed
     */
    public function rememberForever($key, callable $callback)
    {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->forever($key, $value);
        
        return $value;
    }
    
    /**
     * Get the full path for the given cache key.
     *
     * @param string $key
     * @return string
     */
    protected function path($key)
    {
        $hash = sha1($key);
        return $this->directory . '/' . $hash . '.cache';
    }
    
    /**
     * Clean up expired cache files.
     *
     * @return int Number of files cleaned
     */
    public function cleanup()
    {
        $files = glob($this->directory . '/*.cache');
        $cleaned = 0;
        
        foreach ($files as $file) {
            $contents = file_get_contents($file);
            $payload = unserialize($contents);
            
            if ($payload['expires'] !== null && time() >= $payload['expires']) {
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
}