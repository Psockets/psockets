<?php
class ConnectionState {
    const OPENED  = 2;
    const CLOSING = 1;
    const CLOSED  = 0;
}

class Connection {
    const READ_LENGTH = 8192;
    const WRITE_LENGTH = 8192;
    const BUF_LENGTH = 8192;
    const ACTIVITY_TRESHOLD = 3;

    private $server;
    private $wrapper;
    private $state;
    private $inboundBuffer;
    private $outboundBuffer;
    private $lastOutboundBuffer;
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
        $this->outboundBuffer = NULL;
        $this->lastOutboundBuffer = NULL;
        $this->lastRead = 0;
        $this->lastWrite = 0;

        if ($this->isValid()) {
            stream_set_blocking($sock, 0);
            $this->ip = stream_socket_get_name($sock, true);
        }
    }

    public function hasWork() {
        return $this->lastRead < Connection::ACTIVITY_TRESHOLD || $this->lastWrite < Connection::ACTIVITY_TRESHOLD;
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
            $data->setPromise($promise);

            if ($this->outboundBuffer) {
                $this->lastOutboundBuffer->setNext($data);
            } else {
                $this->outboundBuffer = $data;
            }

            $this->lastOutboundBuffer = $data;
        } else {
            $buf = new InMemoryStream($data);
            $buf->setPromise($promise);

            if ($this->outboundBuffer) {
                $this->lastOutboundBuffer->setNext($buf);
            } else {
                $this->outboundBuffer = $buf;
            }

            $this->lastOutboundBuffer = $buf;
        }

        return $promise;
    }

    public function flush() {
        if ($this->state == ConnectionState::CLOSED) return;

        if ($this->outboundBuffer) {
            $this->lastWrite = 0;
            $written = @fwrite($this->sock, $this->outboundBuffer->getChunk(Connection::WRITE_LENGTH));
            if ($written === false) {
                $this->outboundBuffer = $this->outboundBuffer->getNext();
                $exception = new SocketWriteException("Failed writing to socket. Connection ID: " . $this->id);
                $this->outboundBuffer->getPromise()->reject($exception);
                throw $exception;
            } else if ($written > 0) {
                $this->outboundBuffer->advanceBy($written);
            }

            if ($this->outboundBuffer->eof()) {
                $promise = $this->outboundBuffer->getPromise();
                $this->outboundBuffer = $this->outboundBuffer->getNext();
                $promise->resolve();
            }
        } else {
            $this->lastOutboundBuffer = NULL;

            if ($this->state == ConnectionState::CLOSING) {
                fclose($this->sock);
                $this->state = ConnectionState::CLOSED;
                $this->server->onDisconnect($this);
            } else {
                $this->lastWrite++;
            }
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
            $this->wrapper->onDisconnect($this);
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
            } else {
                $this->lastRead++;
            }
            
            if ($this->inboundBuffer != "") {
                if (strlen($this->inboundBuffer >= Connection::BUF_LENGTH)) {
                    $this->wrapper->onData($this, substr($this->inboundBuffer, 0, Connection::BUF_LENGTH));
                    $this->inboundBuffer = substr($this->inboundBuffer, Connection::BUF_LENGTH);
                } else if ($this->lastRead == Connection::ACTIVITY_TRESHOLD) {
                    $this->wrapper->onData($this, $this->inboundBuffer);
                    $this->inboundBuffer = "";
                }
            }
        }
    }
}

class SocketWriteException extends RuntimeException {}
