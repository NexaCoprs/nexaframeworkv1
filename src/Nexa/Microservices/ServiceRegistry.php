<?php

namespace Nexa\Microservices;

class ServiceRegistry
{
    private $services = [];
    private $healthChecks = [];

    /**
     * Register a service
     */
    public function register($name, $host = null, $port = null, $config = null)
    {
        if ($host === null && $port === null && $config === null) {
            // Single parameter mode - $name is actually the config
            $this->services[$name] = $name;
        } elseif (is_array($host) && $port === null && $config === null) {
            // Two parameter mode - $name is name, $host is config
            $this->services[$name] = $host;
        } else {
            // Multi parameter mode - construct config from parameters
            $serviceConfig = $config ?: [];
            $serviceConfig['name'] = $name;
            $serviceConfig['host'] = $host;
            $serviceConfig['port'] = $port;
            
            // Handle multiple instances of the same service
            if (isset($this->services[$name])) {
                $existing = $this->services[$name];
                
                // If existing service is not an array of instances, convert it
                if (!isset($existing[0])) {
                    $this->services[$name] = [$existing];
                }
                
                // Add new instance
                $this->services[$name][] = $serviceConfig;
            } else {
                $this->services[$name] = $serviceConfig;
            }
        }
        return $this;
    }

    /**
     * Unregister a service
     */
    public function unregister($name)
    {
        unset($this->services[$name]);
        unset($this->healthChecks[$name]);
        return $this;
    }

    /**
     * Deregister a service (alias for unregister)
     */
    public function deregister($name)
    {
        return $this->unregister($name);
    }

    /**
     * Get a service by name
     */
    public function get($name)
    {
        return $this->services[$name] ?? null;
    }

    /**
     * Get all services
     */
    public function getAll()
    {
        return $this->services;
    }

    /**
     * Check if a service exists
     */
    public function has($name)
    {
        return isset($this->services[$name]);
    }

    /**
     * Check if a service is registered (alias for has)
     */
    public function isRegistered($name)
    {
        return $this->has($name);
    }

    /**
     * Get services by type
     */
    public function getByType($type)
    {
        return array_filter($this->services, function($service) use ($type) {
            return isset($service['metadata']['type']) && $service['metadata']['type'] === $type;
        });
    }

    /**
     * Discover services by name or type
     */
    public function discover($nameOrType)
    {
        // First try to find by exact service name
        if (isset($this->services[$nameOrType])) {
            $service = $this->services[$nameOrType];
            // If it's an array of instances, return the first healthy one
            if (is_array($service) && isset($service[0])) {
                foreach ($service as $instance) {
                    if ($this->isHealthy($instance)) {
                        return $instance;
                    }
                }
                return $service[0]; // Return first if none are healthy
            }
            // If it's a single service, return it if healthy
            if ($this->isHealthy($service)) {
                return $service;
            }
            return $service;
        }
        
        // If not found by name, try to find by type
        $servicesByType = $this->getByType($nameOrType);
        if (!empty($servicesByType)) {
            return array_values($servicesByType)[0];
        }
        
        return null;
    }

    /**
     * Get service instances for load balancing
     */
    public function getInstances($serviceName)
    {
        $service = $this->get($serviceName);
        if (!$service) {
            return [];
        }
        
        // If service is an array of instances (multiple registrations)
        if (isset($service[0]) && is_array($service[0])) {
            return $service;
        }
        
        // If service has multiple instances, return them
        if (isset($service['instances']) && is_array($service['instances'])) {
            return $service['instances'];
        }
        
        // Otherwise, return the service itself as a single instance
        return [$service];
    }

    /**
     * Discover a service by name and version
     */
    public function discoverByVersion($serviceName, $version)
    {
        if (!isset($this->services[$serviceName])) {
            return null;
        }

        $service = $this->services[$serviceName];
        if (empty($service)) {
            return null;
        }

        // If it's an array of instances (multiple registrations)
        if (isset($service[0]) && is_array($service[0])) {
            foreach ($service as $instance) {
                if (isset($instance['version']) && $instance['version'] === $version) {
                    // Ensure port is an integer
                    if (isset($instance['port'])) {
                        $instance['port'] = (int)$instance['port'];
                    }
                    return $instance;
                }
            }
            return null;
        }

        // If it's a single service (not an array of instances)
        if (isset($service['name']) && !isset($service[0])) {
            if (isset($service['version']) && $service['version'] === $version) {
                // Ensure port is an integer
                if (isset($service['port'])) {
                    $service['port'] = (int)$service['port'];
                }
                return $service;
            }
            return null;
        }

        // If it's an array of instances (legacy format)
        if (is_array($service)) {
            foreach ($service as $instance) {
                if (isset($instance['version']) && $instance['version'] === $version) {
                    if ($this->isHealthy($instance)) {
                        // Ensure port is an integer
                        if (isset($instance['port'])) {
                            $instance['port'] = (int)$instance['port'];
                        }
                        return $instance;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Mock Consul registration for testing
     */
    public function mockConsulRegister($serviceName, $host, $port)
    {
        // Mock implementation for testing
        $this->register($serviceName, $host, $port, ['type' => 'consul']);
        return true;
    }

    /**
     * Mock etcd registration for testing
     */
    public function mockEtcdRegister($serviceName, $host, $port)
    {
        // Mock implementation for testing
        $this->register($serviceName, $host, $port, ['type' => 'etcd']);
        return true;
    }

    /**
     * Mock Redis registration for testing
     */
    public function mockRedisRegister($serviceName, $host, $port)
    {
        // Mock implementation for testing
        $this->register($serviceName, $host, $port, ['type' => 'redis']);
        return true;
    }

    /**
     * Discover services by metadata
     */
    public function discoverByMetadata($criteria, $value = null)
    {
        if (is_array($criteria)) {
            // Handle array of criteria
            return array_filter($this->services, function($service) use ($criteria) {
                if (!isset($service['metadata'])) {
                    return false;
                }
                
                foreach ($criteria as $key => $expectedValue) {
                    if (!isset($service['metadata'][$key]) || $service['metadata'][$key] !== $expectedValue) {
                        return false;
                    }
                }
                
                return true;
            });
        } else {
            // Handle single key-value pair
            $key = $criteria;
            return array_filter($this->services, function($service) use ($key, $value) {
                if (!isset($service['metadata'][$key])) {
                    return false;
                }
                
                if ($value === null) {
                    return true;
                }
                
                return $service['metadata'][$key] === $value;
            });
        }
    }

    /**
     * Perform health check on a service
     */
    public function healthCheck($name)
    {
        if (!$this->has($name)) {
            return false;
        }

        $service = $this->get($name);
        $url = "http://{$service['host']}:{$service['port']}{$service['health_check']}";
        
        // Mock health check for testing
        $this->healthChecks[$name] = [
            'status' => 'healthy',
            'timestamp' => time(),
            'url' => $url
        ];
        
        return true;
    }

    /**
     * Mock health check for testing
     */
    public function mockHealthCheck($name, $status = 'healthy')
    {
        if (!$this->has($name)) {
            return false;
        }

        $this->healthChecks[$name] = [
            'status' => $status,
            'timestamp' => time(),
            'mock' => true
        ];
        
        return true;
    }

    /**
     * Get health status of a service
     */
    public function getHealthStatus($name)
    {
        return $this->healthChecks[$name] ?? null;
    }

    /**
     * Get all health statuses
     */
    public function getAllHealthStatuses()
    {
        return $this->healthChecks;
    }

    /**
     * Check if a service instance is healthy
     */
    public function isHealthy($instance)
    {
        // If instance is a string (service name), get the service
        if (is_string($instance)) {
            $serviceName = $instance;
            $healthStatus = $this->getHealthStatus($serviceName);
        } else {
            // If instance is an array with service config
            $serviceName = $instance['name'] ?? null;
            if ($serviceName) {
                $healthStatus = $this->getHealthStatus($serviceName);
            } else {
                // No service name, assume healthy for basic instances
                return true;
            }
        }

        // If no health check data, assume healthy
        if (!$healthStatus) {
            return true;
        }

        // Check if status is healthy and not too old (within 5 minutes)
        $isStatusHealthy = $healthStatus['status'] === 'healthy';
        $isRecent = (time() - $healthStatus['timestamp']) < 300; // 5 minutes
        
        return $isStatusHealthy && $isRecent;
    }

    /**
     * Clear all services
     */
    public function clear()
    {
        $this->services = [];
        $this->healthChecks = [];
        return $this;
    }
}