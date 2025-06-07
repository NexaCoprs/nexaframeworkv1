<?php

namespace Nexa\Cache;

class CacheManager
{
    protected $stores = [];
    protected $defaultStore = 'file';
    
    public function __construct()
    {
        $this->stores['file'] = new FileStore();
        $this->stores['array'] = new ArrayStore();
    }
    
    /**
     * Get a cache store instance.
     *
     * @param string|null $store
     * @return CacheStore
     */
    public function store($store = null)
    {
        $store = $store ?: $this->defaultStore;
        
        if (!isset($this->stores[$store])) {
            throw new \InvalidArgumentException("Cache store [{$store}] not found.");
        }
        
        return $this->stores[$store];
    }
    
    /**
     * Add a custom cache store.
     *
     * @param string $name
     * @param CacheStore $store
     * @return $this
     */
    public function extend($name, CacheStore $store)
    {
        $this->stores[$name] = $store;
        return $this;
    }
    
    /**
     * Set the default cache store.
     *
     * @param string $store
     * @return $this
     */
    public function setDefaultStore($store)
    {
        $this->defaultStore = $store;
        return $this;
    }
    
    /**
     * Dynamically call the default store.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->store()->$method(...$parameters);
    }
}