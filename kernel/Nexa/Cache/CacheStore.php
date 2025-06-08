<?php

namespace Nexa\Cache;

interface CacheStore
{
    /**
     * Retrieve an item from the cache by key.
     *
     * @param string $key
     * @return mixed
     */
    public function get($key);
    
    /**
     * Store an item in the cache for a given number of seconds.
     *
     * @param string $key
     * @param mixed $value
     * @param int $seconds
     * @return bool
     */
    public function put($key, $value, $seconds);
    
    /**
     * Store an item in the cache indefinitely.
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function forever($key, $value);
    
    /**
     * Remove an item from the cache.
     *
     * @param string $key
     * @return bool
     */
    public function forget($key);
    
    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush();
    
    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     *
     * @param string $key
     * @param int $seconds
     * @param callable $callback
     * @return mixed
     */
    public function remember($key, $seconds, callable $callback);
    
    /**
     * Get an item from the cache, or execute the given Closure and store the result forever.
     *
     * @param string $key
     * @param callable $callback
     * @return mixed
     */
    public function rememberForever($key, callable $callback);
}