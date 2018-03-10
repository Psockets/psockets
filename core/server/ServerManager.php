<?php
class ServerManager {
    private $servers;

    public function __construct() {
        $this->servers = array();
    }

    public function startServer($port, $wrapper, $wrapper_config, $ssl = array()) {
        if (!isset($this->servers[$port])) {
            $this->servers[$port] = new Server('0.0.0.0', $port, $ssl);
            $this->servers[$port]->loadWrapper($wrapper, $wrapper_config)->start();
        }
    }

    public function run() {
        stream_set_blocking(STDIN, 0);
        declare(ticks=1);

        pcntl_signal(SIGTERM, array($this, 'sigHandler'));
        pcntl_signal(SIGABRT, array($this, 'sigHandler'));
        pcntl_signal(SIGINT, array($this, 'sigHandler'));

        for(;;) {
            if (strpos('WIN', PHP_OS) === false){
                $line = trim(fgets(STDIN));
                if (!empty($line)) {
                    $this->parseCmd($line);
                }
            }

            $hasWork = 0;

            foreach ($this->servers as $server) {
                if ($server->isRunning()) {
                    $hasWork |= $server->loop();
                }
            }

            if (!$hasWork) {
                usleep(500);
            }
        }
    }

    public function sigHandler($signo, $siginfo) {
        switch($signo) {
        case SIGTERM:
        case SIGABRT:
        case SIGINT:
            foreach ($this->servers as $server) {
                $server->stop();
            }
            exit;
        }
    }

    private function parseCmd($cmd) {
        if ($cmd == 'quit') {
            foreach ($this->servers as $server) {
                $server->stop();
            }
            exit;
        }

        foreach ($this->servers as $server) {
            switch($cmd) {
            case 'uptime':
                $server->printUptime();
                break;
            case 'status':
                $server->printStatus();
                break;
            case 'stop':
                $server->stop();
                break;
            case 'start':
                $server->start();
                break;
            }
        }
    }
}
