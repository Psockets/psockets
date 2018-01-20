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

    public function __construct($server) {
        $this->id = ++self::$ai_count;//TODO: make sure this does not overlap with other connection ids
        $this->server = $server;
        $this->wrapper = $server->getWrapper();

        if ($this->isValid()) {
            $this->ip = $this->server->getConIp($this->id);
        }
    }

    public function enableSSL() {
        if (!$this->server->enableTlsCon($this->id)) {
            $this->close();
        }
    }

    public function isValid() {
        return $this->server->isValidConnection($this->id);
    }

    public function getState() {
        return $this->state;
    }

    public function send($data) {
        if ($this->state == ConnectionState::CLOSED) return;
        $this->server->writeCon($this->id, $data);
        //TODO: Split these into small chunks that can be sent fast
        //Maybe even implement a job queue, also make this function async
    }

    public function close() {
        if ($this->state == ConnectionState::CLOSED) return;

        $this->server->closeCon($this->id);

        $this->state = ConnectionState::CLOSED;

        $this->server->onDisconnect($this);
    }

    public function listen() {
        if ($this->state !== ConnectionState::OPENED) return;

        $data = $this->server->readCon($this->id);

        if ($data === FALSE) {
            $this->server->closeCon($this->id);
            if ($this->wrapper !== null) {
                $this->wrapper->onDisconnect($this);
            }
        } else if ($data === NULL) {
            if ($this->buffer != "" && $this->wrapper !== null) {
                $this->wrapper->onData($this, $this->buffer);
                $this->buffer = "";
            }
        } else if ($data !== '') {
            $this->buffer .= $data;
        }
    }
}
