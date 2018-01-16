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

    abstract public function init();
    abstract public function onConnect($con);
    abstract public function onDisconnect($con);
    abstract public function onData($con, $data);
    abstract public function onStop();
}
