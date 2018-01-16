<?php
class SimpleEcho extends Component {
    public static $PROTOCOL = "echo";

    public function onLoad($ip, $port, $host) {
        $this->server->log->debug("SimpleEcho component loaded on $ip:$port for host $host");
    }

    public function onMessage($con, $data, $dataType = 'text') {
        $con->send($data, ($dataType == 'binary' ? true : false));
    }
}
