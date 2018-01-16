<?php
class WebChat extends Component {
    public static $PROTOCOL = "webchat";

    private $clients = array();

    public function onLoad($ip, $port, $host) {
        $this->server->log->debug("WebChat component loaded on $ip:$port for host $host");
    }

    public function onConnect($con) {
        $this->clients[] = $con;
    }

    public function onMessage($con, $data, $dataType = 'text') {
        foreach ($this->clients as $client) {
            if ($client == $con) continue;
            $client->send($data, ($dataType == 'binary' ? true : false));
        }
    }
}
