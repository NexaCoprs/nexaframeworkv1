<?php

namespace Nexa\Support;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Handler\SlackWebhookHandler;
use Monolog\Handler\MailHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\JsonFormatter;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\MemoryPeakUsageProcessor;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class Logger implements LoggerInterface
{
    /**
     * The Monolog logger instance
     */
    private MonologLogger $logger;

    /**
     * Logger configuration
     */
    private array $config;

    /**
     * Available log levels
     */
    private const LEVELS = [
        'emergency' => MonologLogger::EMERGENCY,
        'alert' => MonologLogger::ALERT,
        'critical' => MonologLogger::CRITICAL,
        'error' => MonologLogger::ERROR,
        'warning' => MonologLogger::WARNING,
        'notice' => MonologLogger::NOTICE,
        'info' => MonologLogger::INFO,
        'debug' => MonologLogger::DEBUG,
    ];

    public function __construct(string $name = 'nexa', array $config = [])
    {
        $this->config = array_merge([
            'default' => 'stack',
            'deprecations' => 'null',
            'channels' => [
                'stack' => [
                    'driver' => 'stack',
                    'channels' => ['single'],
                    'ignore_exceptions' => false,
                ],
                'single' => [
                    'driver' => 'single',
                    'path' => storage_path('logs/nexa.log'),
                    'level' => 'debug',
                    'replace_placeholders' => true,
                ],
                'daily' => [
                    'driver' => 'daily',
                    'path' => storage_path('logs/nexa.log'),
                    'level' => 'debug',
                    'days' => 14,
                    'replace_placeholders' => true,
                ],
                'slack' => [
                    'driver' => 'slack',
                    'url' => env('LOG_SLACK_WEBHOOK_URL'),
                    'username' => 'Nexa Log',
                    'emoji' => ':boom:',
                    'level' => 'critical',
                ],
                'papertrail' => [
                    'driver' => 'syslog',
                    'level' => 'debug',
                    'facility' => LOG_USER,
                ],
                'stderr' => [
                    'driver' => 'monolog',
                    'level' => 'debug',
                    'handler' => StreamHandler::class,
                    'formatter' => env('LOG_STDERR_FORMATTER'),
                    'with' => [
                        'stream' => 'php://stderr',
                    ],
                ],
                'syslog' => [
                    'driver' => 'syslog',
                    'level' => 'debug',
                    'facility' => LOG_USER,
                ],
                'errorlog' => [
                    'driver' => 'errorlog',
                    'level' => 'debug',
                    'replace_placeholders' => true,
                ],
                'null' => [
                    'driver' => 'monolog',
                    'handler' => \Monolog\Handler\NullHandler::class,
                ],
                'emergency' => [
                    'path' => storage_path('logs/nexa.log'),
                ],
            ],
        ], $config);

        $this->logger = new MonologLogger($name);
        $this->configureHandlers();
        $this->configureProcessors();
    }

    /**
     * Configure log handlers based on configuration
     */
    private function configureHandlers(): void
    {
        $defaultChannel = $this->config['default'] ?? 'single';
        $channelConfig = $this->config['channels'][$defaultChannel] ?? $this->config['channels']['single'];

        switch ($channelConfig['driver'] ?? 'single') {
            case 'single':
                $this->addSingleHandler($channelConfig);
                break;
            case 'daily':
                $this->addDailyHandler($channelConfig);
                break;
            case 'slack':
                $this->addSlackHandler($channelConfig);
                break;
            case 'syslog':
                $this->addSyslogHandler($channelConfig);
                break;
            case 'errorlog':
                $this->addErrorLogHandler($channelConfig);
                break;
            case 'stack':
                $this->addStackHandlers($channelConfig);
                break;
            default:
                $this->addSingleHandler($channelConfig);
        }
    }

    /**
     * Add single file handler
     */
    private function addSingleHandler(array $config): void
    {
        $path = $config['path'] ?? storage_path('logs/nexa.log');
        $level = self::LEVELS[$config['level'] ?? 'debug'];
        
        $handler = new StreamHandler($path, $level);
        $handler->setFormatter($this->getFormatter($config));
        
        $this->logger->pushHandler($handler);
    }

    /**
     * Add daily rotating file handler
     */
    private function addDailyHandler(array $config): void
    {
        $path = $config['path'] ?? storage_path('logs/nexa.log');
        $level = self::LEVELS[$config['level'] ?? 'debug'];
        $days = $config['days'] ?? 7;
        
        $handler = new RotatingFileHandler($path, $days, $level);
        $handler->setFormatter($this->getFormatter($config));
        
        $this->logger->pushHandler($handler);
    }

    /**
     * Add Slack webhook handler
     */
    private function addSlackHandler(array $config): void
    {
        if (empty($config['url'])) {
            return;
        }
        
        $level = self::LEVELS[$config['level'] ?? 'critical'];
        $username = $config['username'] ?? 'Nexa';
        $emoji = $config['emoji'] ?? ':warning:';
        
        $handler = new SlackWebhookHandler(
            $config['url'],
            null,
            $username,
            true,
            $emoji,
            true,
            true,
            $level
        );
        
        $this->logger->pushHandler($handler);
    }

    /**
     * Add syslog handler
     */
    private function addSyslogHandler(array $config): void
    {
        $level = self::LEVELS[$config['level'] ?? 'debug'];
        $facility = $config['facility'] ?? LOG_USER;
        
        $handler = new SyslogHandler('nexa', $facility, $level);
        $this->logger->pushHandler($handler);
    }

    /**
     * Add error log handler
     */
    private function addErrorLogHandler(array $config): void
    {
        $level = self::LEVELS[$config['level'] ?? 'debug'];
        
        $handler = new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, $level);
        $handler->setFormatter($this->getFormatter($config));
        
        $this->logger->pushHandler($handler);
    }

    /**
     * Add stack handlers
     */
    private function addStackHandlers(array $config): void
    {
        $channels = $config['channels'] ?? ['single'];
        
        foreach ($channels as $channel) {
            if (isset($this->config['channels'][$channel])) {
                $channelConfig = $this->config['channels'][$channel];
                $this->addHandlerByType($channelConfig);
            }
        }
    }

    /**
     * Add handler by type
     */
    private function addHandlerByType(array $config): void
    {
        switch ($config['driver'] ?? 'single') {
            case 'single':
                $this->addSingleHandler($config);
                break;
            case 'daily':
                $this->addDailyHandler($config);
                break;
            case 'slack':
                $this->addSlackHandler($config);
                break;
            case 'syslog':
                $this->addSyslogHandler($config);
                break;
            case 'errorlog':
                $this->addErrorLogHandler($config);
                break;
        }
    }

    /**
     * Configure processors
     */
    private function configureProcessors(): void
    {
        $this->logger->pushProcessor(new UidProcessor());
        $this->logger->pushProcessor(new WebProcessor());
        $this->logger->pushProcessor(new IntrospectionProcessor());
        $this->logger->pushProcessor(new MemoryUsageProcessor());
        $this->logger->pushProcessor(new MemoryPeakUsageProcessor());
    }

    /**
     * Get formatter based on configuration
     */
    private function getFormatter(array $config): \Monolog\Formatter\FormatterInterface
    {
        $format = $config['format'] ?? null;
        $dateFormat = $config['date_format'] ?? 'Y-m-d H:i:s';
        
        if ($format === 'json') {
            return new JsonFormatter();
        }
        
        $lineFormat = $format ?? "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
        
        return new LineFormatter($lineFormat, $dateFormat, true, true);
    }

    /**
     * System is unusable.
     */
    public function emergency($message, array $context = []): void
    {
        $this->logger->emergency($message, $context);
    }

    /**
     * Action must be taken immediately.
     */
    public function alert($message, array $context = []): void
    {
        $this->logger->alert($message, $context);
    }

    /**
     * Critical conditions.
     */
    public function critical($message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }

    /**
     * Runtime errors that do not require immediate action.
     */
    public function error($message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     */
    public function warning($message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    /**
     * Normal but significant events.
     */
    public function notice($message, array $context = []): void
    {
        $this->logger->notice($message, $context);
    }

    /**
     * Interesting events.
     */
    public function info($message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    /**
     * Detailed debug information.
     */
    public function debug($message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    /**
     * Logs with an arbitrary level.
     */
    public function log($level, $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }

    /**
     * Get the underlying Monolog instance
     */
    public function getMonolog(): MonologLogger
    {
        return $this->logger;
    }

    /**
     * Add a custom handler
     */
    public function pushHandler(\Monolog\Handler\HandlerInterface $handler): self
    {
        $this->logger->pushHandler($handler);
        return $this;
    }

    /**
     * Add a custom processor
     */
    public function pushProcessor(callable $processor): self
    {
        $this->logger->pushProcessor($processor);
        return $this;
    }

    /**
     * Create a new logger channel
     */
    public function channel(string $name): self
    {
        return new self($name, $this->config);
    }

    /**
     * Write a message to the log
     */
    public function write(string $level, string $message, array $context = []): void
    {
        $this->log($level, $message, $context);
    }

    /**
     * Log an exception
     */
    public function logException(\Throwable $exception, string $level = 'error', array $context = []): void
    {
        $context = array_merge($context, [
            'exception' => $exception,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);
        
        $this->log($level, $exception->getMessage(), $context);
    }

    /**
     * Log performance metrics
     */
    public function logPerformance(string $operation, float $duration, array $context = []): void
    {
        $context = array_merge($context, [
            'operation' => $operation,
            'duration' => $duration,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
        ]);
        
        $this->info("Performance: {$operation} completed in {$duration}ms", $context);
    }

    /**
     * Log security events
     */
    public function logSecurity(string $event, array $context = []): void
    {
        $context = array_merge($context, [
            'security_event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => time(),
        ]);
        
        $this->warning("Security Event: {$event}", $context);
    }
}

/**
 * Helper function to get storage path
 */
if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        $storagePath = dirname(__DIR__, 3) . '/storage';
        return $path ? $storagePath . '/' . ltrim($path, '/') : $storagePath;
    }
}

/**
 * Helper function to get environment variable
 */
if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}