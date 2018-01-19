<?php
class ConnectionState {
    const OPENED = 1;
    const CLOSED = 0;
}

class Connection {
    private $server;
    private $wrapper;
    private $state = ConnectionState::OPENED;
    private $buffer = "";

    public static $ai_count = 0;
    public $id;
    public $ip = '';

    protected $sock;

    public function __construct($sock, $server) {
        $this->sock = $sock;
        $this->id = ++self::$ai_count;//TODO: make sure this does not overlap with other connection ids
        $this->server = $server;
        $this->wrapper = $server->getWrapper();

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
        fwrite($this->sock, $data);
        //TODO: Split these into small chunks that can be sent fast
        //Maybe even implement a job queue, also make this function async
    }

    public function close() {
        if ($this->state == ConnectionState::CLOSED) return;
        fclose($this->sock);
        $this->state = ConnectionState::CLOSED;

        $this->server->onDisconnect($this);
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
                $data = fread($this->sock, 8192);

                if (!empty($data)) {
                    $this->buffer .= $data;
                }
            } else if ($this->buffer != "" && $this->wrapper !== null) {
                $this->wrapper->onData($this, $this->buffer);
                $this->buffer = "";
            }
        }
    }
}
