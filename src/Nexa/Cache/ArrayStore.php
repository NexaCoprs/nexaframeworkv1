<?php

namespace Nexa\Cache;

class ArrayStore implements CacheStore
{
    protected $storage = [];
    
    /**
     * Retrieve an item from the cache by key.
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        if (!isset($this->storage[$key])) {
            return null;
        }
        
        $item = $this->storage[$key];
        
        // Check if expired
        if ($item['expires'] !== null && time() >= $item['expires']) {
            unset($this->storage[$key]);
            return null;
        }
        
        return $item['data'];
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
        $expires = $seconds > 0 ? time() + $seconds : null;
        
        $this->storage[$key] = [
            'data' => $value,
            'expires' => $expires,
            'created' => time()
        ];
        
        return true;
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
        unset($this->storage[$key]);
        return true;
    }
    
    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush()
    {
        $this->storage = [];
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
     * Get all cached items.
     *
     * @return array
     */
    public function all()
    {
        $items = [];
        
        foreach ($this->storage as $key => $item) {
            if ($item['expires'] === null || time() < $item['expires']) {
                $items[$key] = $item['data'];
            }
        }
        
        return $items;
    }
    
    /**
     * Get the number of items in the cache.
     *
     * @return int
     */
    public function count()
    {
        $count = 0;
        
        foreach ($this->storage as $item) {
            if ($item['expires'] === null || time() < $item['expires']) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Clean up expired items.
     *
     * @return int Number of items cleaned
     */
    public function cleanup()
    {
        $cleaned = 0;
        
        foreach ($this->storage as $key => $item) {
            if ($item['expires'] !== null && time() >= $item['expires']) {
                unset($this->storage[$key]);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
}