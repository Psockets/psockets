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
    private $lastRead;
    private $lastWrite;

    public static $ai_count = 0;
    public $id;
    public $ip = '';

    protected $sock;

    public function __construct($sock, $server) {
        $this->sock = $sock;
        $this->id = (int)$sock;
        $this->server = $server;
        $this->wrapper = $server->getWrapper();
        $this->state = ConnectionState::OPENED;
        $this->inboundBuffer = "";
        $this->outboundBuffer = array();
        $this->lastRead = 0;
        $this->lastWrite = 0;

        if ($this->isValid()) {
            stream_set_blocking($sock, 0);
            $this->ip = stream_socket_get_name($sock, true);
        }
    }

    public function hasWork() {
        return $this->lastRead < 3 || $this->lastWrite < 3;
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

        $promise = new Promise();

        if ($data instanceof DataStream) {
            if ($data instanceof HttpResponse) {
                $data->generateResponse();
            }

            $data->setPromise($promise);
            $this->outboundBuffer[] = $data;
        } else {
            $buf = new InMemoryStream($data);
            $buf->setPromise($promise);
            $this->outboundBuffer[] = $buf;
        }

        return $promise;
    }

    public function flush() {
        if ($this->state == ConnectionState::CLOSED) return;

        $buf = reset($this->outboundBuffer);

        if ($buf && !$buf->eof()) {
            $this->lastWrite = 0;
            $chunk = $buf->getChunk(Connection::WRITE_LENGTH);
            $written = @fwrite($this->sock, $chunk, Connection::WRITE_LENGTH);
            //$written = @fwrite($this->sock, $buf->getChunk(Connection::WRITE_LENGTH), Connection::WRITE_LENGTH);
            if ($written === false) {
                $exception = new SocketWriteException("Failed writing to socket. Connection ID: " . $this->id);
                $buf->getPromise()->reject($exception);
                throw $exception;
            } else if ($written > 0) {
                $buf->advanceBy($written);
            }

            if ($buf->eof()) {
                array_shift($this->outboundBuffer);
                $buf->getPromise()->resolve();
            }
        } else {
            $this->lastWrite++;
        }
        
        if ($this->state == ConnectionState::CLOSING && count($this->outboundBuffer) === 0) {
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

            if (stream_select($read, $write, $except, 0, 0)) {
                $this->lastRead = 0;
                $data = fread($this->sock, Connection::READ_LENGTH);

                if (!empty($data)) {
                    $this->inboundBuffer .= $data;
                }
            } else if ($this->inboundBuffer != "" && $this->wrapper !== null) {
                $this->lastRead++;
                $this->wrapper->onData($this, $this->inboundBuffer);
                $this->inboundBuffer = "";
            }
        }
    }
}

class SocketWriteException extends RuntimeException {}
