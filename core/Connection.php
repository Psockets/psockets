<?php
class ConnectionState {
    const OPENED  = 2;
    const CLOSING = 1;
    const CLOSED  = 0;
}

class Connection {
    const READ_LENGTH = 8192;
    const WRITE_LENGTH = 8192;

    private $server;
    private $wrapper;
    private $state;
    private $inboundBuffer;
    private $outboundBuffer;

    public static $ai_count = 0;
    public $id;
    public $ip = '';

    protected $sock;

    public function __construct($sock, $server) {
        $this->sock = $sock;
        $this->id = ++self::$ai_count;//TODO: make sure this does not overlap with other connection ids
        $this->server = $server;
        $this->wrapper = $server->getWrapper();
        $this->state = ConnectionState::OPENED;
        $this->inboundBuffer = "";
        $this->outboundBuffer = "";

        if ($this->isValid()) {
            stream_set_blocking($sock, 0);
            $this->ip = stream_socket_get_name($sock, true);
        }
    }

    public function enableSSL() {
        stream_set_blocking($this->sock, true);
        if (!stream_socket_enable_crypto($this->sock, true, STREAM_CRYPTO_METHOD_TLS_SERVER)) {
            if (!stream_socket_enable_crypto($this->sock, true, STREAM_CRYPTO_METHOD_SSLv3_SERVER)) {
                if (!stream_socket_enable_crypto($this->sock, true, STREAM_CRYPTO_METHOD_SSLv23_SERVER)) {
                    if (!stream_socket_enable_crypto($this->sock, true, STREAM_CRYPTO_METHOD_SSLv2_SERVER)) {
                        $this->close();
                        return false;
                    }
                }
            }
        }
        stream_set_blocking($this->sock, false);
        return true;
    }

    public function isValid() {
        return is_resource($this->sock);
    }

    public function getResource() {
        return $this->sock;
    }

    public function send($data) {
        if ($this->state == ConnectionState::CLOSED) return;
        $this->outboundBuffer .= $data;
    }

    public function flush() {
        if ($this->state == ConnectionState::CLOSED) return;

        if (strlen($this->outboundBuffer) > 0) {
            $written = fwrite($this->sock, $this->outboundBuffer, Connection::WRITE_LENGTH);
            if ($written === false) {
                throw new SocketWriteException("Failed writing to socket. Connection ID: " . $this->id);
            } else if ($written > 0) {
                $this->outboundBuffer = substr($this->outboundBuffer, $written);
            }
        }
        
        if ($this->state == ConnectionState::CLOSING) {
            fclose($this->sock);
            $this->state = ConnectionState::CLOSED;
            $this->server->onDisconnect($this);
        }
    }

    public function close() {
        if ($this->state == ConnectionState::CLOSED) return;

        $this->state = ConnectionState::CLOSING;
    }

    public function run() {
        $this->listen();
    }

    public function listen() {
        if ($this->state !== ConnectionState::OPENED) return;

        if (feof($this->sock)) {
            $this->close();
            if ($this->wrapper !== null) {
                $this->wrapper->onDisconnect($this);
            }
        }

        if (is_resource($this->sock)) {
            $read = array($this->sock);
            $write = $except = null;

            if (stream_select($read, $write, $except, 0, 10)) {
                $data = fread($this->sock, Connection::READ_LENGTH);

                if (!empty($data)) {
                    $this->inboundBuffer .= $data;
                }
            } else if ($this->inboundBuffer != "" && $this->wrapper !== null) {
                $this->wrapper->onData($this, $this->inboundBuffer);
                $this->inboundBuffer = "";
            }
        }
    }
}

class SocketWriteException extends RuntimeException {}
