<?php

namespace Nexa\Http\Middleware;

use Nexa\Http\Request;
use Nexa\Http\Response;

class ThrottleMiddleware
{
    /**
     * Rate limit storage
     *
     * @var array
     */
    protected static $requests = [];
    
    /**
     * Maximum requests per minute
     *
     * @var int
     */
    protected $maxRequests;
    
    /**
     * Time window in seconds
     *
     * @var int
     */
    protected $timeWindow;
    
    public function __construct($maxRequests = 60, $timeWindow = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->timeWindow = $timeWindow;
    }
    
    /**
     * Handle the request
     *
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function handle($request, $next)
    {
        $key = $this->getKey($request);
        $now = time();
        
        // Clean old requests
        $this->cleanOldRequests($key, $now);
        
        // Check rate limit
        if ($this->isRateLimited($key)) {
            return Response::json([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => $this->timeWindow
            ], 429);
        }
        
        // Record this request
        $this->recordRequest($key, $now);
        
        return $next($request);
    }
    
    /**
     * Get rate limit key for request
     *
     * @param Request $request
     * @return string
     */
    protected function getKey($request)
    {
        $ip = $request->getClientIp() ?? '127.0.0.1';
        return 'throttle:' . $ip;
    }
    
    /**
     * Clean old requests from storage
     *
     * @param string $key
     * @param int $now
     * @return void
     */
    protected function cleanOldRequests($key, $now)
    {
        if (!isset(static::$requests[$key])) {
            static::$requests[$key] = [];
        }
        
        static::$requests[$key] = array_filter(
            static::$requests[$key],
            function($timestamp) use ($now) {
                return ($now - $timestamp) < $this->timeWindow;
            }
        );
    }
    
    /**
     * Check if request is rate limited
     *
     * @param string $key
     * @return bool
     */
    protected function isRateLimited($key)
    {
        return count(static::$requests[$key] ?? []) >= $this->maxRequests;
    }
    
    /**
     * Record a request
     *
     * @param string $key
     * @param int $timestamp
     * @return void
     */
    protected function recordRequest($key, $timestamp)
    {
        if (!isset(static::$requests[$key])) {
            static::$requests[$key] = [];
        }
        
        static::$requests[$key][] = $timestamp;
    }
}