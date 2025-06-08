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

if (!function_exists('view')) {
    /**
     * Create a view response
     *
     * @param string $template
     * @param array $data
     * @return string
     */
    function view($template, $data = [])
    {
        // Simple template rendering
        $templatePath = dirname(__DIR__, 3) . '/workspace/interface/' . $template . '.php';
        
        if (file_exists($templatePath)) {
            extract($data);
            ob_start();
            include $templatePath;
            return ob_get_clean();
        }
        
        return "Template not found: {$template}";
    }
}

if (!function_exists('redirect')) {
    /**
     * Create a redirect response
     *
     * @param string $url
     * @param int $status
     * @return Response
     */
    function redirect($url, $status = 302)
    {
        return Response::redirect($url, $status);
    }
}

if (!function_exists('session')) {
    /**
     * Get/set session values
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    function session($key = null, $default = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if ($key === null) {
            return new SessionHelper();
        }
        
        if (func_num_args() === 1) {
            return $_SESSION[$key] ?? $default;
        }
        
        $_SESSION[$key] = $default;
        return $default;
    }
}

/**
 * Session helper class
 */
class SessionHelper
{
    public function flash($key, $value)
    {
        $_SESSION['_flash'][$key] = $value;
    }
    
    public function get($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }
    
    public function forget($key)
    {
        unset($_SESSION[$key]);
    }
}

if (!function_exists('config')) {
    /**
     * Get configuration value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function config($key, $default = null)
    {
        // Simple config implementation
        static $config = [
            'app.name' => 'Nexa Framework',
            'app.debug' => true,
            'database.connections.mysql.host' => 'localhost',
            'database.connections.mysql.port' => 3306,
        ];
        
        return $config[$key] ?? $default;
    }
}

if (!function_exists('env')) {
    /**
     * Get environment variable
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env($key, $default = null)
    {
        $value = getenv($key);
        
        if ($value === false) {
            return $default;
        }
        
        // Convert string booleans
        if (in_array(strtolower($value), ['true', 'false'])) {
            return strtolower($value) === 'true';
        }
        
        return $value;
    }
}

if (!function_exists('cache')) {
    /**
     * Cache helper function
     *
     * @param string|null $key
     * @param mixed $value
     * @param int $ttl
     * @return mixed
     */
    function cache($key = null, $value = null, $ttl = 3600)
    {
        static $cache = [];
        
        if ($key === null) {
            return new CacheHelper();
        }
        
        if (func_num_args() === 1) {
            return $cache[$key] ?? null;
        }
        
        $cache[$key] = $value;
        return $value;
    }
}

/**
 * Cache helper class
 */
class CacheHelper
{
    private static $cache = [];
    
    public function remember($key, $ttl, $callback)
    {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }
        
        $value = $callback();
        self::$cache[$key] = $value;
        return $value;
    }
    
    public function forget($key)
    {
        unset(self::$cache[$key]);
    }
    
    public function flush()
    {
        self::$cache = [];
    }
}

if (!function_exists('validate')) {
    /**
     * Validate data
     *
     * @param array $data
     * @param array $rules
     * @return ValidatorHelper
     */
    function validate($data, $rules)
    {
        return new ValidatorHelper($data, $rules);
    }
}

/**
 * Validator helper class
 */
class ValidatorHelper
{
    private $data;
    private $rules;
    private $errors = [];
    
    public function __construct($data, $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->validate();
    }
    
    private function validate()
    {
        foreach ($this->rules as $field => $rule) {
            $rules = explode('|', $rule);
            $value = $this->data[$field] ?? null;
            
            foreach ($rules as $r) {
                if ($r === 'required' && empty($value)) {
                    $this->errors[$field][] = "The {$field} field is required.";
                }
                if (strpos($r, 'min:') === 0 && strlen($value) < (int)substr($r, 4)) {
                    $this->errors[$field][] = "The {$field} field must be at least " . substr($r, 4) . " characters.";
                }
                if ($r === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field][] = "The {$field} field must be a valid email address.";
                }
            }
        }
    }
    
    public function fails()
    {
        return !empty($this->errors);
    }
    
    public function errors()
    {
        return $this->errors;
    }
}

if (!function_exists('queue')) {
    /**
     * Queue helper function
     *
     * @param mixed $job
     * @return QueueHelper
     */
    function queue($job = null)
    {
        if ($job === null) {
            return new QueueHelper();
        }
        
        // Simple implementation - execute immediately
        if (method_exists($job, 'handle')) {
            $job->handle();
        }
    }
}

/**
 * Queue helper class
 */
class QueueHelper
{
    public function later($delay, $job)
    {
        // Simple implementation - execute immediately
        if (method_exists($job, 'handle')) {
            $job->handle();
        }
    }
}

if (!function_exists('event')) {
    /**
     * Fire an event
     *
     * @param mixed $event
     * @param array $data
     * @return void
     */
    function event($event, $data = [])
    {
        // Simple event implementation
        if (is_string($event)) {
            error_log("Event fired: {$event} with data: " . json_encode($data));
        } else {
            error_log("Event fired: " . get_class($event));
        }
    }
}

if (!function_exists('jwt')) {
    /**
     * JWT helper function
     *
     * @return JwtHelper
     */
    function jwt()
    {
        return new JwtHelper();
    }
}

/**
 * JWT helper class
 */
class JwtHelper
{
    private $secret = 'your-secret-key';
    
    public function generate($userId, $email, $data = [])
    {
        $payload = [
            'user_id' => $userId,
            'email' => $email,
            'data' => $data,
            'exp' => time() + 3600
        ];
        
        return base64_encode(json_encode($payload));
    }
    
    public function verify($token)
    {
        $payload = json_decode(base64_decode($token), true);
        
        if ($payload && $payload['exp'] > time()) {
            return $payload;
        }
        
        return false;
    }
    
    public function refresh($token)
    {
        $payload = $this->verify($token);
        
        if ($payload) {
            return $this->generate($payload['user_id'], $payload['email'], $payload['data']);
        }
        
        return false;
    }
}

if (!function_exists('logger')) {
    /**
     * Logger helper function
     *
     * @return LoggerHelper
     */
    function logger()
    {
        return new LoggerHelper();
    }
}

/**
 * Logger helper class
 */
class LoggerHelper
{
    public function info($message, $context = [])
    {
        $this->log('INFO', $message, $context);
    }
    
    public function warning($message, $context = [])
    {
        $this->log('WARNING', $message, $context);
    }
    
    public function error($message, $context = [])
    {
        $this->log('ERROR', $message, $context);
    }
    
    private function log($level, $message, $context)
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = empty($context) ? '' : ' ' . json_encode($context);
        $logMessage = "[{$timestamp}] {$level}: {$message}{$contextStr}";
        
        error_log($logMessage);
    }
}