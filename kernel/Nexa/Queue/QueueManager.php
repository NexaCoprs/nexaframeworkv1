<?php

namespace Nexa\Queue;

use Nexa\Support\Logger;
use Nexa\Events\EventDispatcher;
use Predis\Client as RedisClient;
use Nexa\Database\DatabaseManager;
use Nexa\Queue\Jobs\Job;
use Nexa\Queue\Jobs\DatabaseJob;
use Nexa\Queue\Jobs\RedisJob;
use Nexa\Queue\Jobs\SyncJob;
use Nexa\Queue\Drivers\DatabaseQueueDriver;
use Nexa\Queue\Drivers\RedisQueueDriver;
use Nexa\Queue\Drivers\SyncQueueDriver;
use Nexa\Queue\Contracts\QueueDriverInterface;
use Nexa\Queue\Contracts\JobInterface;
use Nexa\Queue\Exceptions\QueueException;
use Nexa\Queue\Exceptions\JobNotFoundException;
use Nexa\Queue\Exceptions\DriverNotFoundException;
use Carbon\Carbon;
use Exception;
use Throwable;

class QueueManager
{
    /**
     * Queue drivers
     */
    private array $drivers = [];

    /**
     * Default queue driver
     */
    private string $defaultDriver = 'database';

    /**
     * Logger instance
     */
    private Logger $logger;

    /**
     * Event dispatcher
     */
    private ?EventDispatcher $eventDispatcher;

    /**
     * Queue configuration
     */
    private array $config;

    /**
     * Database manager
     */
    private ?DatabaseManager $database;

    /**
     * Redis client
     */
    private ?RedisClient $redis;

    /**
     * Worker processes
     */
    private array $workers = [];

    /**
     * Queue statistics
     */
    private array $stats = [
        'processed' => 0,
        'failed' => 0,
        'retried' => 0,
        'started_at' => null,
    ];

    public function __construct(array $config = [], ?Logger $logger = null, ?EventDispatcher $eventDispatcher = null, ?DatabaseManager $database = null)
    {
        $this->config = array_merge([
            'default' => 'database',
            'connections' => [
                'database' => [
                    'driver' => 'database',
                    'table' => 'jobs',
                    'queue' => 'default',
                    'retry_after' => 90,
                    'max_tries' => 3,
                    'backoff' => [1, 5, 10],
                ],
                'redis' => [
                    'driver' => 'redis',
                    'connection' => 'default',
                    'queue' => 'default',
                    'retry_after' => 90,
                    'max_tries' => 3,
                    'backoff' => [1, 5, 10],
                    'host' => env('REDIS_HOST', '127.0.0.1'),
                    'port' => env('REDIS_PORT', 6379),
                    'password' => env('REDIS_PASSWORD'),
                    'database' => env('REDIS_DB', 0),
                ],
                'sync' => [
                    'driver' => 'sync'
                ]
            ]
        ], $config);

        $this->defaultDriver = $this->config['default'];
        $this->logger = $logger ?? new Logger('queue');
        $this->eventDispatcher = $eventDispatcher;
        $this->database = $database;
        $this->stats['started_at'] = Carbon::now();

        $this->initializeConnections();
        $this->initializeDrivers();
    }

    /**
     * Initialize external connections
     */
    private function initializeConnections(): void
    {
        // Initialize Redis connection if configured
        if (isset($this->config['connections']['redis'])) {
            $redisConfig = $this->config['connections']['redis'];
            try {
                $this->redis = new RedisClient([
                    'scheme' => 'tcp',
                    'host' => $redisConfig['host'],
                    'port' => $redisConfig['port'],
                    'password' => $redisConfig['password'],
                    'database' => $redisConfig['database'],
                    'timeout' => 5.0,
                    'read_write_timeout' => 0,
                    'persistent' => true,
                ]);
                
                // Test connection
                $this->redis->ping();
                $this->logger->info('Redis connection established for queue');
            } catch (Exception $e) {
                $this->logger->warning('Failed to connect to Redis for queue: ' . $e->getMessage());
                $this->redis = null;
            }
        }
    }

    /**
     * Initialize queue drivers
     */
    private function initializeDrivers(): void
    {
        foreach ($this->config['connections'] as $name => $config) {
            try {
                switch ($config['driver']) {
                    case 'database':
                        $this->drivers[$name] = new DatabaseQueueDriver($config, $this->logger, $this->database);
                        break;
                    case 'redis':
                        if ($this->redis) {
                            $this->drivers[$name] = new RedisQueueDriver($config, $this->logger, $this->redis);
                        } else {
                            $this->logger->warning("Redis not available for queue '{$name}', falling back to sync driver");
                            $this->drivers[$name] = new SyncQueueDriver($config, $this->logger);
                        }
                        break;
                    case 'sync':
                        $this->drivers[$name] = new SyncQueueDriver($config, $this->logger);
                        break;
                    default:
                        throw new DriverNotFoundException("Unsupported queue driver: {$config['driver']}");
                }
                
                $this->logger->info("Initialized queue driver '{$name}' with driver '{$config['driver']}'");
             } catch (Exception $e) {
                $this->logger->error("Failed to initialize queue driver '{$name}': " . $e->getMessage());
                // Fallback to sync driver
                $this->drivers[$name] = new SyncQueueDriver($config, $this->logger);
            }
        }
    }

    /**
     * Push a job onto the queue
     *
     * @param JobInterface $job
     * @param string|null $queue
     * @param string|null $connection
     * @return string Job ID
     */
    public function push(JobInterface $job, $queue = null, $connection = null)
    {
        $driver = $this->getDriver($connection);
        $queue = $queue ?: $job->getQueue();

        // Log job dispatch
        if ($this->logger) {
            $this->logger->info("Job dispatched: {$job->getName()}", [
                'job_id' => $job->getId(),
                'queue' => $queue,
                'connection' => $connection ?: $this->defaultDriver,
                'data' => $job->getData()
            ]);
        }

        // Dispatch job queued event
        if ($this->eventDispatcher) {
            $this->eventDispatcher->dispatch('job.queued', [
                'job' => $job,
                'queue' => $queue,
                'connection' => $connection ?: $this->defaultDriver
            ]);
        }

        return $driver->push($job, $queue);
    }

    /**
     * Push a job with delay
     *
     * @param JobInterface $job
     * @param int $delay Delay in seconds
     * @param string|null $queue
     * @param string|null $connection
     * @return string Job ID
     */
    public function later(JobInterface $job, $delay, $queue = null, $connection = null)
    {
        $job->setDelay($delay);
        return $this->push($job, $queue, $connection);
    }

    /**
     * Push a job with delay (alias for later method)
     *
     * @param JobInterface $job
     * @param string|null $queue
     * @param int $delay Delay in seconds
     * @param string|null $connection
     * @return string Job ID
     */
    public function pushWithDelay(JobInterface $job, $queue = null, $delay = 0, $connection = null)
    {
        return $this->later($job, $delay, $queue, $connection);
    }

    /**
     * Pop a job from the queue
     *
     * @param string|null $queue
     * @param string|null $connection
     * @return JobInterface|null
     */
    public function pop($queue = null, $connection = null)
    {
        $driver = $this->getDriver($connection);
        $queue = $queue ?: 'default';

        return $driver->pop($queue);
    }

    /**
     * Process a single job from the queue
     *
     * @param string|null $queue
     * @param string|null $connection
     * @return bool True if a job was processed, false if no jobs available
     */
    public function processJob($queue = null, $connection = null)
    {
        $driver = $this->getDriver($connection);
        $queue = $queue ?: 'default';
        
        $job = $driver->pop($queue);
        
        if (!$job) {
            return false;
        }
        
        $this->processJobInternal($job, $driver, $queue);
        return true;
    }

    /**
     * Process jobs from the queue
     *
     * @param string|null $queue
     * @param string|null $connection
     * @param int $maxJobs Maximum number of jobs to process
     * @param int $timeout Timeout in seconds
     * @return void
     */
    public function work($queue = null, $connection = null, $maxJobs = 0, $timeout = 60)
    {
        $driver = $this->getDriver($connection);
        $queue = $queue ?: 'default';
        $processedJobs = 0;
        $startTime = time();

        if ($this->logger) {
            $this->logger->info("Queue worker started", [
                'queue' => $queue,
                'connection' => $connection ?: $this->defaultDriver,
                'max_jobs' => $maxJobs,
                'timeout' => $timeout
            ]);
        }

        while (true) {
            // Check timeout
            if ($timeout > 0 && (time() - $startTime) >= $timeout) {
                if ($this->logger) {
                    $this->logger->info("Queue worker timeout reached");
                }
                break;
            }

            // Check max jobs limit
            if ($maxJobs > 0 && $processedJobs >= $maxJobs) {
                if ($this->logger) {
                    $this->logger->info("Queue worker max jobs limit reached");
                }
                break;
            }

            // Pop and process job
            $job = $this->pop($queue, $connection);
            
            if ($job) {
                $this->processJobInternal($job, $driver, $queue);
                $processedJobs++;
            } else {
                // No jobs available, sleep for a bit
                sleep(1);
            }
        }

        if ($this->logger) {
            $this->logger->info("Queue worker stopped", [
                'processed_jobs' => $processedJobs,
                'duration' => time() - $startTime
            ]);
        }
    }

    /**
     * Process a single job
     *
     * @param JobInterface $job
     * @param QueueDriverInterface $driver
     * @param string $queue
     * @return void
     */
    private function processJobInternal(JobInterface $job, $driver, $queue)
    {
        $startTime = microtime(true);
        
        try {
            // Dispatch job processing event
            if ($this->eventDispatcher) {
                $this->eventDispatcher->dispatch('job.processing', [
                    'job' => $job,
                    'queue' => $queue
                ]);
            }

            // Check timeout
            $timeout = $job->getTimeout();
            if ($timeout > 0) {
                set_time_limit($timeout);
            }

            // Execute job
            $job->handle();

            // Mark job as completed
            $driver->delete($job, $queue);

            $executionTime = microtime(true) - $startTime;

            // Log successful job execution
            if ($this->logger) {
                $this->logger->info("Job completed: {$job->getName()}", [
                    'job_id' => $job->getId(),
                    'execution_time' => $executionTime,
                    'attempts' => $job->getAttempts() + 1
                ]);
            }

            // Dispatch job processed event
            if ($this->eventDispatcher) {
                $this->eventDispatcher->dispatch('job.processed', [
                    'job' => $job,
                    'queue' => $queue,
                    'execution_time' => $executionTime
                ]);
            }

        } catch (\Exception $e) {
            $this->handleJobFailure($job, $e, $driver, $queue);
        }
    }

    /**
     * Handle job failure
     *
     * @param JobInterface $job
     * @param \Exception $exception
     * @param QueueDriverInterface $driver
     * @param string $queue
     * @return void
     */
    private function handleJobFailure(JobInterface $job, \Exception $exception, $driver, $queue)
    {
        $job->incrementAttempts();

        // Log job failure
        if ($this->logger) {
            $this->logger->error("Job failed: {$job->getName()}", [
                'job_id' => $job->getId(),
                'error' => $exception->getMessage(),
                'attempts' => $job->getAttempts(),
                'max_attempts' => $job->getMaxAttempts(),
                'trace' => $exception->getTraceAsString()
            ]);
        }

        // Check if job should be retried
        if ($job->shouldRetry($exception)) {
            // Retry job with exponential backoff
            $delay = pow(2, $job->getAttempts()) * 60; // 1min, 2min, 4min, etc.
            $job->setDelay($delay);
            $driver->push($job, $queue);

            if ($this->logger) {
                $this->logger->info("Job retried: {$job->getName()}", [
                    'job_id' => $job->getId(),
                    'attempt' => $job->getAttempts(),
                    'delay' => $delay
                ]);
            }
        } else {
            // Job failed permanently
            $job->failed($exception);
            $driver->fail($job, $queue, $exception);

            if ($this->logger) {
                $this->logger->error("Job failed permanently: {$job->getName()}", [
                    'job_id' => $job->getId(),
                    'final_attempt' => $job->getAttempts()
                ]);
            }
        }

        // Dispatch job failed event
        if ($this->eventDispatcher) {
            $this->eventDispatcher->dispatch('job.failed', [
                'job' => $job,
                'queue' => $queue,
                'exception' => $exception,
                'will_retry' => $job->shouldRetry($exception)
            ]);
        }
    }

    /**
     * Get queue driver
     *
     * @param string|null $connection
     * @return QueueDriverInterface
     */
    public function getDriver($connection = null)
    {
        $connection = $connection ?: $this->defaultDriver;
        
        if (!isset($this->drivers[$connection])) {
            throw new \InvalidArgumentException("Queue connection '{$connection}' not found");
        }

        return $this->drivers[$connection];
    }

    /**
     * Get queue size
     *
     * @param string|null $queue
     * @param string|null $connection
     * @return int
     */
    public function size($queue = null, $connection = null)
    {
        $driver = $this->getDriver($connection);
        $queue = $queue ?: 'default';
        
        return $driver->size($queue);
    }

    /**
     * Clear all jobs from queue
     *
     * @param string|null $queue
     * @param string|null $connection
     * @return int Number of jobs cleared
     */
    public function clear($queue = null, $connection = null)
    {
        $driver = $this->getDriver($connection);
        $queue = $queue ?: 'default';
        
        return $driver->clear($queue);
    }

    /**
     * Get failed jobs
     *
     * @param string|null $connection
     * @return array
     */
    public function getFailedJobs($connection = null)
    {
        $driver = $this->getDriver($connection);
        
        if (method_exists($driver, 'getFailedJobs')) {
            return $driver->getFailedJobs();
        }
        
        return [];
    }

    /**
     * Retry failed job
     *
     * @param string $jobId
     * @param string|null $connection
     * @return bool
     */
    public function retry($jobId, $connection = null)
    {
        $driver = $this->getDriver($connection);
        
        if (method_exists($driver, 'retry')) {
            return $driver->retry($jobId);
        }
        
        return false;
    }

    /**
     * Retry failed jobs
     *
     * @param string|null $queue
     * @param string|null $jobId
     * @param string|null $connection
     * @return int Number of jobs retried
     */
    public function retryFailedJobs($queue = null, $jobId = null, $connection = null)
    {
        $driver = $this->getDriver($connection);
        
        if (method_exists($driver, 'retryFailedJobs')) {
            return $driver->retry($queue, $jobId);
        }
        
        return 0;
    }
}