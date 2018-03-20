<?php
include 'init.php';

$server_manager = new ServerManager();

if (FORK_COUNT > 0) {
    $isParent = true;
    $children = array();

    for ($x = 0; $x < FORK_COUNT; $x++) {
        $pid = pcntl_fork();

        if ($pid == -1) {
            // TODO: Display some sort of error and exit
        } else if ($pid) {
            $children[] = $pid;
        } else {
            $isParent = false;
            break;
        }
    }

    if ($isParent) {
        while ($children) {
            $childPid = pcntl_wait($status);
            $index = array_search($childPid, $children);
            array_splice($children, $index, 1);
        }

        exit();
    }
}

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
