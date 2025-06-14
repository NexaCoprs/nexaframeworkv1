<?php

namespace Ratchet;

interface MessageComponentInterface {
    function onOpen(ConnectionInterface $conn);
    function onClose(ConnectionInterface $conn);
    function onError(ConnectionInterface $conn, \Exception $e);
    function onMessage(ConnectionInterface $from, $msg);
}

interface ConnectionInterface {
    public function send($data);
    public function close();
}

namespace Ratchet\Server;

class WsServer {
    public function __construct($component) {}
    public function enableKeepAlive($loop, $interval) {}
}

class IoServer {
    public $socket;
    
    public static function factory($httpServer, $port, $host = '0.0.0.0') {
        return new self();
    }
    
    public function run() {}
}

namespace Ratchet\Http;

class HttpServer {
    public function __construct($wsServer) {}
}

namespace Nexa\WebSockets\Exceptions;

class WebSocketException extends \Exception {
    public function __construct($message = "", $code = 0, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}