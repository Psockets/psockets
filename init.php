<?php
define('DS', DIRECTORY_SEPARATOR);
define('DIR_ROOT', dirname(__FILE__).DS);
define('DIR_CORE', DIR_ROOT . 'core' . DS);
define('DIR_CORE_SERVER', DIR_ROOT . 'core' . DS . 'server' . DS);
define('DIR_WRAPPERS', DIR_ROOT . 'wrappers' . DS);
define('DIR_LOG', DIR_ROOT . 'logs');
define('DEBUG_MODE', TRUE);

$error_level = E_ALL;

include 'config.php';
include 'autoload.php';
error_reporting($error_level);
date_default_timezone_set('UTC');
