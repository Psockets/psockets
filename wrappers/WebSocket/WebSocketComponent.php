<?php
abstract class WebSocketComponent {
    public static $PROTOCOL = '';
    protected $server;
    protected $wrapper;

    public function __construct($server, $wrapper){
        $this->server = $server;
        $this->wrapper = $wrapper;
    }

    public function onLoad($ip, $port, $host) {}
    public function onConnect($con) {}
    public function onDisconnect($con) {}
    public function onStop() {}

    abstract public function onMessage($con, $data, $dataType);
}
