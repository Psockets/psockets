<?php
abstract class Component {
    public static $PROTOCOL = '';
    protected $server;

    public function __construct($server){
        $this->server = $server;
    }

    public function onLoad($ip, $port, $host) {}
    public function onConnect($con) {}
    public function onDisconnect($con) {}
    public function onStop() {}

    abstract public function onMessage($con, $data, $dataType);
}
