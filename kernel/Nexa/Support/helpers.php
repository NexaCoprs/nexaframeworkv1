<?php

use Nexa\Http\Response;

if (!function_exists('response')) {
    /**
     * Create a new response instance
     *
     * @param string $content
     * @param int $status
     * @param array $headers
     * @return Response
     */
    function response($content = '', $status = 200, array $headers = [])
    {
        if (func_num_args() === 0) {
            return new Response();
        }
        
        return new Response($content, $status, $headers);
    }
}

if (!function_exists('now')) {
    /**
     * Get current timestamp
     *
     * @return NowHelper
     */
    function now()
    {
        return new NowHelper();
    }
}

/**
 * Helper class for date/time operations
 */
class NowHelper
{
    private $timestamp;
    
    public function __construct($timestamp = null)
    {
        $this->timestamp = $timestamp ?: time();
    }
    
    public function addMinutes($minutes)
    {
        return new self($this->timestamp + ($minutes * 60));
    }
    
    public function addHours($hours)
    {
        return new self($this->timestamp + ($hours * 3600));
    }
    
    public function addDays($days)
    {
        return new self($this->timestamp + ($days * 86400));
    }
    
    public function subMonth()
    {
        return new self(strtotime('-1 month', $this->timestamp));
    }
    
    public function getMonth()
    {
        return (int) date('n', $this->timestamp);
    }
    
    public function __get($property)
    {
        if ($property === 'month') {
            return $this->getMonth();
        }
        return null;
    }
    
    public function format($format = 'Y-m-d H:i:s')
    {
        return date($format, $this->timestamp);
    }
    
    public function __toString()
    {
        return $this->format();
    }
}

if (!function_exists('app')) {
    /**
     * Get application instance or service from container
     *
     * @param string|null $abstract
     * @return mixed
     */
    function app($abstract = null)
    {
        // Simple mock implementation for now
        static $services = [];
        
        if ($abstract === null) {
            return null;
        }
        
        // Mock services for API routes
        if (!isset($services[$abstract])) {
            switch ($abstract) {
                case 'handler.registry':
                    $services[$abstract] = new class {
                        public function count() { return 5; }
                    };
                    break;
                case 'entity.registry':
                    $services[$abstract] = new class {
                        public function count() { return 10; }
                    };
                    break;
                case 'api.documentation':
                    $services[$abstract] = new class {
                        public function generate() {
                            return Response::json([
                                'message' => 'API Documentation',
                                'version' => '1.0.0',
                                'endpoints' => []
                            ]);
                        }
                    };
                    break;
                default:
                    $services[$abstract] = null;
            }
        }
        
        return $services[$abstract];
    }
}

if (!function_exists('public_path')) {
    /**
     * Get the path to the public folder
     *
     * @param string $path
     * @return string
     */
    function public_path($path = '')
    {
        $publicPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'workspace' . DIRECTORY_SEPARATOR . 'assets';
        
        if ($path) {
            return $publicPath . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
        }
        
        return $publicPath;
    }
}

if (!function_exists('dispatch')) {
    /**
     * Dispatch a job to the queue
     *
     * @param mixed $job
     * @return void
     */
    function dispatch($job)
    {
        // Simple implementation - just execute the job immediately
        // In a real application, this would queue the job
        if (method_exists($job, 'handle')) {
            $job->handle();
        }
    }
}