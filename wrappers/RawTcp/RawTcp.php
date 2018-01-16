<?php
class RawTcp extends Wrapper {
    public function init() {}

    public function onConnect($con) {
        $con->send("Hello\n");
    }

    public function onDisconnect($con) {
        $this->log->debug("Client disconnected " . $con->ip);
    }

    public function onData($con, $data) {
        $con->send($data);
    }

    public function onStop() {}
}
