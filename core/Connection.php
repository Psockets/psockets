<?php
class ConnectionState {
    const TLS_HANDSHAKE = 3;
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
    private $sslStack;
    private $isSecure;

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
        $this->sslStack = array(STREAM_CRYPTO_METHOD_TLS_SERVER, STREAM_CRYPTO_METHOD_SSLv3_SERVER, STREAM_CRYPTO_METHOD_SSLv23_SERVER, STREAM_CRYPTO_METHOD_SSLv2_SERVER);
        $this->isSecure = false;

        if ($this->isValid()) {
            stream_set_blocking($sock, 0);
            $this->ip = stream_socket_get_name($sock, true);
        }
    }

    public function __destruct() {
        $this->close();
    }

    public function hasWork() {
        return $this->lastRead < Connection::ACTIVITY_TRESHOLD || $this->lastWrite < Connection::ACTIVITY_TRESHOLD;
    }

    public function enableSSL() {
        $this->state = ConnectionState::TLS_HANDSHAKE;

        $result = stream_socket_enable_crypto($this->sock, true, reset($this->sslStack));

        if ($result === true) {
            $this->state = ConnectionState::OPENED;
            $this->isSecure = true;
            return true;
        } else if ($result === false) {
            array_shift($this->sslStack);
            if (empty($this->sslStack)) {
                $this->server->log->error('Unable to create secure socket');
                $this->close(true);
                return false;
            }
        }
    }

    public function isSecure() {
        return $this->isSecure;
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

        if ($this->state == ConnectionState::CLOSING) {
            $exception = new SocketClosingException("Socket is in the closing state");
            $promise->reject($exception);
            return $promise;
        }

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
        if ($this->state == ConnectionState::CLOSED || $this->state == ConnectionState::TLS_HANDSHAKE) return;

        if ($this->outboundBuffer) {
            $this->lastWrite = 0;
            $written = @fwrite($this->sock, $this->outboundBuffer->getChunk(Connection::WRITE_LENGTH));
            if ($written === false) {
                $exception = new SocketWriteException("Failed writing to socket. Connection ID: " . $this->id);
                $this->outboundBuffer->getPromise()->reject($exception);
                $this->outboundBuffer = $this->outboundBuffer->getNext();
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

    public function close($discardOutputBuffers = false) {
        if ($this->state == ConnectionState::CLOSED) return;

        $this->state = ConnectionState::CLOSING;

        if ($discardOutputBuffers) {
            while ($this->outboundBuffer) {
                $exception = new SocketClosedException("Socket was closed before sending all buffered data");
                $this->outboundBuffer->getPromise()->reject($exception);
                $this->outboundBuffer = $this->outboundBuffer->getNext();
            }

            fclose($this->sock);
            $this->state = ConnectionState::CLOSED;
            $this->server->onDisconnect($this);
        }
    }

    public function run() {
        $this->listen();
    }

    public function listen() {
        if ($this->state === ConnectionState::TLS_HANDSHAKE) {
            $this->enableSSL();
        }

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
class SocketClosingException extends RuntimeException {}
class SocketClosedException extends RuntimeException {}
