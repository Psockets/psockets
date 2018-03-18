<?php
abstract class Wrapper {
    protected $config;
    protected $server;
    protected $log;

    public function __construct($config, $server) {
        $this->config = $config;
        $this->server = $server;
        $this->log = $this->server->log;
    }

    public function setTimeout($callback, $delay) { // Delay is in milliseconds
        $timer = new Timer($callback, $delay, TimerType::TIMEOUT);
        $this->server->setTimer($timer);
        return $timer->id;
    }

    public function setInterval($callback, $delay) { // Delay is in milliseconds
        $timer = new Timer($callback, $delay, TimerType::INTERVAL);
        $this->server->setTimer($timer);
        return $timer->id;
    }

    public function cancelTimeout($id) {
        $this->server->removeTimer($id);
    }

    public function cancelInterval($id) {
        $this->server->removeTimer($id);
    }

    abstract public function init();
    abstract public function onConnect($con);
    abstract public function onDisconnect($con);
    abstract public function onData($con, $data);
    abstract public function onStop();
}
