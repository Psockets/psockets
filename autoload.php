<?php
function dir_search_recursive($dir, $needle) {
    $dh = opendir($dir);
    while (false !== ($entry = readdir($dh))) {
        if (in_array($entry, array('.', '..'))) continue;

        $filename = $dir . $entry;
        if ($entry == $needle) {
            require_once $filename;
            break;
        } else if (is_dir($filename)) {
            dir_search_recursive($filename . DS, $needle);
        }
    }
    closedir($dh);
}

function system_autoload($className) {
    $parts = explode('\\', $className);
    $className = array_pop($parts);
    $sub_dir = implode(DS, $parts) . DS;

    $incl_dirs = array(
        'core' => DIR_CORE,
        'core_server' => DIR_CORE_SERVER,
        'wrappers' => DIR_WRAPPERS
    );

    foreach ($incl_dirs as $key => $dir) {
        if ($key == 'wrappers') {
            dir_search_recursive($dir, $className . '.php');
        } else {
            $filename = $dir . $className . '.php';
            if (file_exists($filename)) {
                require_once $filename;
                return;
            }

            if (!empty($sub_dir)) {
                $filename = $dir . $sub_dir . $className . '.php';
                if (file_exists($filename)) {
                    require_once $filename;
                    return;
                }
            }
        }
    }
}

spl_autoload_register('system_autoload', true, true);
