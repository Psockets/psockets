<?php
include 'init.php';

$server_manager = new ServerManager();

foreach ($server_config as $port => $config) {
    $keys = array_keys($config);
    $wrapper = array_shift($keys);
    $ssl = !empty($config['ssl']) ? $config['ssl'] : null;
    if ($ssl === null) {
        $ssl = array();
    }


    if ($wrapper !== null) {
        $wrapper_config = !empty($config[$wrapper]) ? $config[$wrapper] : array();

        $server_manager->startServer($port, $wrapper, $wrapper_config, $ssl);
    }
}

$server_manager->run();
