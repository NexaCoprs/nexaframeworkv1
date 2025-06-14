<?php

namespace Nexa\WebSockets;

require_once __DIR__ . '/RatchetInterfaces.php';

use Exception;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Socket\SocketServer;
use React\Socket\ConnectionInterface;
use React\Stream\WritableResourceStream;
use Ratchet\ConnectionInterface as RatchetConnectionInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\Server\IoServer;
use Ratchet\Server\WsServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServerInterface;
use Nexa\Support\Logger;
use Nexa\WebSockets\Contracts\WebSocketHandlerInterface;
use Nexa\WebSockets\Events\ConnectionOpened;
use Nexa\WebSockets\Events\ConnectionClosed;
use Nexa\WebSockets\Events\MessageReceived;
use Nexa\WebSockets\Exceptions\WebSocketException;
use SplObjectStorage;
use stdClass;

/**
 * WebSocket Server implementation using ReactPHP and Ratchet
 */
class WebSocketServer implements MessageComponentInterface
{
    private string $host;
    private int $port;
    private ?SocketServer $socket = null;
    private bool $running = false;
    private SplObjectStorage $clients;
    private array $callbacks = [];
    private array $eventHandlers = [];
    private array $config = [];
    private array $channelMembers = [];
    private array $channels = [];
    private ?LoopInterface $loop = null;
    private ?Logger $logger = null;
    private ?IoServer $server = null;
    private array $middleware = [];
    private array $routes = [];
    private array $stats = [
        'connections' => 0,
        'messages_sent' => 0,
        'messages_received' => 0,
        'started_at' => null,
        'uptime' => 0,
    ];
    
    public function __construct(string $host = '127.0.0.1', int $port = 8080, array $config = [])
    {
        $this->host = $host;
        $this->port = $port;
        $this->clients = new SplObjectStorage();
        $this->loop = Loop::get();
        $this->logger = new Logger('websocket');
        
        $this->config = array_merge([
            'heartbeat_interval' => 30,
            'max_connections' => 100,
            'timeout' => 60,
            'ping_interval' => 25,
            'pong_timeout' => 5,
            'max_frame_size' => 2097152, // 2MB
            'max_message_size' => 8388608, // 8MB
            'enable_compression' => true,
            'compression_threshold' => 1024,
            'ssl' => false,
            'ssl_cert' => null,
            'ssl_key' => null,
            'allowed_origins' => ['*'],
            'sub_protocols' => [],
        ], $config);
        
        $this->stats['started_at'] = time();
    }
    
    public function getHost(): string
    {
        return $this->host;
    }
    
    public function getPort(): int
    {
        return $this->port;
    }
    

    
    public function getHeartbeatInterval(): int
    {
        return $this->config['heartbeat_interval'];
    }
    
    public function getMaxConnections(): int
    {
        return $this->config['max_connections'];
    }
    
    public function getTimeout(): int
    {
        return $this->config['timeout'];
    }
    
    /**
     * Start the WebSocket server
     */
    public function start(): bool
    {
        try {
            if ($this->running) {
                throw new WebSocketException('Server is already running');
            }
            
            $this->logger->info("Starting WebSocket server on {$this->host}:{$this->port}");
            
            // Create the WebSocket server
            $wsServer = new WsServer($this);
            $wsServer->enableKeepAlive($this->loop, $this->config['ping_interval']);
            
            // Create HTTP server with WebSocket support
            $httpServer = new HttpServer($wsServer);
            
            // Create the socket server
            $this->server = IoServer::factory(
                $httpServer,
                $this->port,
                $this->host
            );
            
            $this->running = true;
            $this->stats['started_at'] = time();
            
            // Setup heartbeat
            $this->setupHeartbeat();
            
            // Setup periodic tasks
            $this->setupPeriodicTasks();
            
            $this->logger->info('WebSocket server started successfully');
            
            // Run the server
            $this->server->run();
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to start WebSocket server: ' . $e->getMessage());
            $this->running = false;
            return false;
        }
    }
    
    /**
     * Stop the WebSocket server
     */
    public function stop(): void
    {
        if (!$this->running) {
            return;
        }
        
        $this->logger->info('Stopping WebSocket server');
        
        // Close all client connections
        foreach ($this->clients as $client) {
            $client->close();
        }
        
        // Stop the server
        if ($this->server) {
            $this->server->socket->close();
        }
        
        $this->running = false;
        $this->clients = new SplObjectStorage();
        $this->channels = [];
        $this->channelMembers = [];
        
        $this->logger->info('WebSocket server stopped');
    }
    
    /**
     * Setup heartbeat mechanism
     */
    private function setupHeartbeat(): void
    {
        $this->loop->addPeriodicTimer($this->config['heartbeat_interval'], function () {
            foreach ($this->clients as $client) {
                try {
                    $client->ping();
                } catch (Exception $e) {
                    $this->logger->warning('Failed to ping client: ' . $e->getMessage());
                    $this->onClose($client);
                }
            }
        });
    }
    
    /**
     * Setup periodic tasks
     */
    private function setupPeriodicTasks(): void
    {
        // Update stats every minute
        $this->loop->addPeriodicTimer(60, function () {
            $this->stats['uptime'] = time() - $this->stats['started_at'];
            $this->stats['connections'] = count($this->clients);
            
            $this->logger->info('WebSocket server stats', $this->stats);
        });
        
        // Cleanup disconnected clients every 5 minutes
        $this->loop->addPeriodicTimer(300, function () {
            $this->cleanupDisconnectedClients();
        });
    }
    
    /**
     * Cleanup disconnected clients
     */
    private function cleanupDisconnectedClients(): void
    {
        $disconnected = [];
        
        foreach ($this->clients as $client) {
            if ($client->readyState !== $client::OPEN) {
                $disconnected[] = $client;
            }
        }
        
        foreach ($disconnected as $client) {
            $this->clients->detach($client);
            $this->removeFromChannels($client);
        }
        
        if (count($disconnected) > 0) {
            $this->logger->info('Cleaned up ' . count($disconnected) . ' disconnected clients');
        }
    }
    

    

    
    /**
     * Ratchet MessageComponentInterface implementation
     */
    public function onOpen(RatchetConnectionInterface $conn): void
    {
        $this->clients->attach($conn);
        $this->stats['connections']++;
        
        $conn->clientId = uniqid('client_', true);
        $conn->channels = [];
        $conn->lastPing = time();
        
        $this->logger->info("New WebSocket connection: {$conn->clientId} from {$conn->remoteAddress}");
        
        $this->triggerEvent('connection.opened', $conn);
        
        // Send welcome message
        $this->sendToClient($conn, [
            'type' => 'welcome',
            'clientId' => $conn->clientId,
            'timestamp' => time(),
        ]);
    }
    
    public function onMessage(RatchetConnectionInterface $from, $msg): void
    {
        $this->stats['messages_received']++;
        
        try {
            $data = json_decode($msg, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new WebSocketException('Invalid JSON message');
            }
            
            $this->logger->debug("Message received from {$from->clientId}", $data);
            
            $this->handleMessage($from, $data);
            
            $this->triggerEvent('message.received', $from, $data);
            
        } catch (Exception $e) {
            $this->logger->error("Error processing message from {$from->clientId}: " . $e->getMessage());
            
            $this->sendToClient($from, [
                'type' => 'error',
                'message' => 'Invalid message format',
                'timestamp' => time(),
            ]);
        }
    }
    
    public function onClose(RatchetConnectionInterface $conn): void
    {
        $this->clients->detach($conn);
        $this->removeFromChannels($conn);
        
        $this->logger->info("Connection closed: {$conn->clientId}");
        
        $this->triggerEvent('connection.closed', $conn);
    }
    
    public function onError(RatchetConnectionInterface $conn, Exception $e): void
    {
        $this->logger->error("WebSocket error for {$conn->clientId}: " . $e->getMessage());
        
        $this->triggerEvent('connection.error', $conn, $e);
        
        $conn->close();
    }
    
    /**
     * Handle incoming messages
     */
    private function handleMessage(RatchetConnectionInterface $from, array $data): void
    {
        $type = $data['type'] ?? null;
        
        switch ($type) {
            case 'join_channel':
                $this->joinChannel($from, $data['channel'] ?? null);
                break;
                
            case 'leave_channel':
                $this->leaveChannel($from, $data['channel'] ?? null);
                break;
                
            case 'broadcast':
                $this->broadcastToChannel($data['channel'] ?? null, $data['message'] ?? null, $from);
                break;
                
            case 'private_message':
                $this->sendPrivateMessage($from, $data['target'] ?? null, $data['message'] ?? null);
                break;
                
            case 'ping':
                $this->handlePing($from);
                break;
                
            default:
                $this->logger->warning("Unknown message type '{$type}' from {$from->clientId}");
        }
    }
    
    /**
     * Join a channel
     */
    public function joinChannel(RatchetConnectionInterface $client, ?string $channel): void
    {
        if (!$channel) {
            return;
        }
        
        if (!isset($this->channels[$channel])) {
            $this->channels[$channel] = new SplObjectStorage();
        }
        
        if (!$this->channels[$channel]->contains($client)) {
            $this->channels[$channel]->attach($client);
            $client->channels[] = $channel;
            
            $this->logger->info("Client {$client->clientId} joined channel '{$channel}'");
            
            // Notify client
            $this->sendToClient($client, [
                'type' => 'channel_joined',
                'channel' => $channel,
                'timestamp' => time(),
            ]);
            
            // Notify other channel members
            $this->broadcastToChannel($channel, [
                'type' => 'user_joined',
                'clientId' => $client->clientId,
                'channel' => $channel,
                'timestamp' => time(),
            ], $client);
        }
    }
    
    /**
     * Leave a channel
     */
    public function leaveChannel(RatchetConnectionInterface $client, ?string $channel): void
    {
        if (!$channel || !isset($this->channels[$channel])) {
            return;
        }
        
        if ($this->channels[$channel]->contains($client)) {
            $this->channels[$channel]->detach($client);
            $client->channels = array_filter($client->channels, fn($c) => $c !== $channel);
            
            $this->logger->info("Client {$client->clientId} left channel '{$channel}'");
            
            // Notify client
            $this->sendToClient($client, [
                'type' => 'channel_left',
                'channel' => $channel,
                'timestamp' => time(),
            ]);
            
            // Notify other channel members
            $this->broadcastToChannel($channel, [
                'type' => 'user_left',
                'clientId' => $client->clientId,
                'channel' => $channel,
                'timestamp' => time(),
            ]);
            
            // Remove empty channels
            if (count($this->channels[$channel]) === 0) {
                unset($this->channels[$channel]);
            }
        }
    }
    
    /**
     * Remove client from all channels
     */
    private function removeFromChannels(RatchetConnectionInterface $client): void
    {
        foreach ($client->channels ?? [] as $channel) {
            $this->leaveChannel($client, $channel);
        }
    }
    
    /**
     * Get channel members
     */
    public function getChannelMembers(string $channel): array
    {
        if (!isset($this->channels[$channel])) {
            return [];
        }
        
        $members = [];
        foreach ($this->channels[$channel] as $client) {
            $members[] = $client->clientId;
        }
        
        return $members;
    }
    
    /**
     * Broadcast message to channel
     */
    public function broadcastToChannel(string $channel, $message, ?RatchetConnectionInterface $exclude = null): void
    {
        if (!isset($this->channels[$channel])) {
            return;
        }
        
        foreach ($this->channels[$channel] as $client) {
            if ($exclude && $client === $exclude) {
                continue;
            }
            
            $this->sendToClient($client, $message);
        }
    }
    
    /**
     * Send private message
     */
    public function sendPrivateMessage(RatchetConnectionInterface $from, ?string $targetId, $message): void
    {
        if (!$targetId || !$message) {
            return;
        }
        
        $target = $this->findClientById($targetId);
        
        if ($target) {
            $this->sendToClient($target, [
                'type' => 'private_message',
                'from' => $from->clientId,
                'message' => $message,
                'timestamp' => time(),
            ]);
            
            // Confirm to sender
            $this->sendToClient($from, [
                'type' => 'message_sent',
                'to' => $targetId,
                'timestamp' => time(),
            ]);
        } else {
            $this->sendToClient($from, [
                'type' => 'error',
                'message' => 'Target client not found',
                'timestamp' => time(),
            ]);
        }
    }
    
    /**
     * Find client by ID
     */
    private function findClientById(string $clientId): ?RatchetConnectionInterface
    {
        foreach ($this->clients as $client) {
            if ($client->clientId === $clientId) {
                return $client;
            }
        }
        
        return null;
    }
    
    /**
     * Send message to specific client
     */
    public function sendToClient(RatchetConnectionInterface $client, $message): void
    {
        try {
            $json = is_string($message) ? $message : json_encode($message);
            $client->send($json);
            $this->stats['messages_sent']++;
        } catch (Exception $e) {
            $this->logger->error("Failed to send message to {$client->clientId}: " . $e->getMessage());
        }
    }
    
    /**
     * Handle ping message
     */
    private function handlePing(RatchetConnectionInterface $from): void
    {
        $from->lastPing = time();
        
        $this->sendToClient($from, [
            'type' => 'pong',
            'timestamp' => time(),
        ]);
    }
    
    /**
     * Get server metrics
     */
    public function getMetrics(): array
    {
        return [
            'total_connections' => count($this->clients),
            'active_channels' => count($this->channels),
            'uptime' => time() - $this->stats['started_at'],
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'messages_sent' => $this->stats['messages_sent'],
            'messages_received' => $this->stats['messages_received'],
            'connections_total' => $this->stats['connections'],
            'errors' => $this->stats['errors'] ?? 0,
        ];
    }

    /**
     * Handle CORS preflight requests
     */
    public function handleCORSPreflight(string $origin): bool
    {
        $allowedOrigins = $this->config['cors']['allowed_origins'] ?? ['*'];
        
        if (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins)) {
            $this->logger->debug("CORS preflight allowed for origin: {$origin}");
            return true;
        }
        
        $this->logger->warning("CORS preflight denied for origin: {$origin}");
        return false;
    }
    
    /**
     * Broadcast message to all connected clients
     */
    public function broadcast($message): void
    {
        foreach ($this->clients as $client) {
            $this->sendToClient($client, $message);
        }
        
        $this->logger->info('Broadcasted message to all clients');
    }
    
    /**
     * Get client count
     */
    public function getClientCount(): int
    {
        return count($this->clients);
    }
    
    /**
     * Register message callback
     */
    public function onMessageCallback(callable $callback): void
    {
        $this->callbacks['message'] = $callback;
    }
    
    /**
     * Register connect callback
     */
    public function onConnect(callable $callback): void
    {
        $this->callbacks['connect'] = $callback;
    }
    
    /**
     * Register disconnect callback
     */
    public function onDisconnect(callable $callback): void
    {
        $this->callbacks['disconnect'] = $callback;
    }
    
    /**
     * Add middleware
     */
    public function addMiddleware(callable $middleware): void
    {
        $this->middleware[] = $middleware;
    }
    
    /**
     * Add route
     */
    public function addRoute(string $path, callable $handler): void
    {
        $this->routes[$path] = $handler;
    }
    
    /**
     * Trigger event
     */
    public function triggerEvent(string $event, ...$args): void
    {
        if (isset($this->eventHandlers[$event])) {
            foreach ($this->eventHandlers[$event] as $handler) {
                try {
                    call_user_func($handler, ...$args);
                } catch (Exception $e) {
                    $this->logger->error("Error in event handler for '{$event}': " . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Add event handler
     */
    public function on(string $event, callable $handler): void
    {
        if (!isset($this->eventHandlers[$event])) {
            $this->eventHandlers[$event] = [];
        }
        
        $this->eventHandlers[$event][] = $handler;
    }
    
    /**
     * Remove event handler
     */
    public function off(string $event, ?callable $handler = null): void
    {
        if (!isset($this->eventHandlers[$event])) {
            return;
        }
        
        if ($handler === null) {
            unset($this->eventHandlers[$event]);
        } else {
            $this->eventHandlers[$event] = array_filter(
                $this->eventHandlers[$event],
                fn($h) => $h !== $handler
            );
        }
    }
    
    /**
     * Get server status
     */
    public function getStatus(): array
    {
        return [
            'running' => $this->running,
            'host' => $this->host,
            'port' => $this->port,
            'started_at' => $this->stats['started_at'],
            'clients' => $this->getClientCount(),
            'channels' => count($this->channels),
            'memory_usage' => memory_get_usage(true),
        ];
    }
    
    /**
     * Check if server is running
     */
    public function isRunning(): bool
    {
        return $this->running;
    }
    
    /**
     * Get all connected clients
     */
    public function getClients(): array
    {
        $clients = [];
        foreach ($this->clients as $client) {
            $clients[] = [
                'id' => $client->clientId ?? 'unknown',
                'remote_address' => $client->remoteAddress ?? 'unknown',
                'channels' => $client->channels ?? [],
                'connected_at' => $client->connectedAt ?? time(),
                'last_ping' => $client->lastPing ?? null,
            ];
        }
        
        return $clients;
    }
    
    /**
     * Get all channels
     */
    public function getChannels(): array
    {
        $channels = [];
        foreach ($this->channels as $name => $clients) {
            $channels[$name] = [
                'name' => $name,
                'member_count' => count($clients),
                'members' => $this->getChannelMembers($name),
            ];
        }
        
        return $channels;
    }
    
    /**
     * Disconnect a specific client
     */
    public function disconnectClient(string $clientId, ?string $reason = null): bool
    {
        $client = $this->findClientById($clientId);
        
        if ($client) {
            if ($reason) {
                $this->sendToClient($client, [
                    'type' => 'disconnect',
                    'reason' => $reason,
                    'timestamp' => time(),
                ]);
            }
            
            $client->close();
            return true;
        }
        
        return false;
    }
    
    /**
     * Cleanup resources
     */
    public function cleanup(): void
    {
        // Close all client connections
        foreach ($this->clients as $client) {
            try {
                $client->close();
            } catch (Exception $e) {
                $this->logger->error("Error closing client connection: " . $e->getMessage());
            }
        }
        
        // Clear collections
        $this->clients = new SplObjectStorage();
        $this->channels = [];
        $this->eventHandlers = [];
        $this->middleware = [];
        $this->routes = [];
        
        // Stop the event loop if running
        if ($this->loop && $this->loop->isRunning()) {
            $this->loop->stop();
        }
        
        $this->logger->info('WebSocket server cleanup completed');
    }
    
    /**
     * Destructor
     */
    public function __destruct()
    {
        if ($this->running) {
            $this->stop();
        }
        
        $this->cleanup();
    }
}