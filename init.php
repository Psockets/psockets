<?php
define('DS', DIRECTORY_SEPARATOR);
define('DIR_ROOT', dirname(__FILE__) . DS);
define('DIR_APP', DIR_ROOT . 'app' . DS);
define('DIR_CORE', DIR_ROOT . 'core' . DS);
define('DIR_CORE_SERVER', DIR_ROOT . 'core' . DS . 'server' . DS);
define('DIR_WRAPPERS', DIR_ROOT . 'wrappers' . DS);
define('DIR_LOG', DIR_ROOT . 'logs');
define('DEBUG_MODE', TRUE);

//limit the number of forked processes to the number of available cpu cores
function cpu_cores() {
  $cores = 1;
  if (is_file('/proc/cpuinfo')) {
    $cpuinfo = file_get_contents('/proc/cpuinfo');
    preg_match_all('/^processor/m', $cpuinfo, $matches);
    $cores = count($matches[0]);
  } else if ('WIN' == strtoupper(substr(PHP_OS, 0, 3))) {
    $process = popen('wmic cpu get NumberOfLogicalProcessors', 'rb');
    if (false !== $process) {
      fgets($process);
      $cores = intval(fgets($process));
      pclose($process);
    }
  } else {
    $process = popen('sysctl -a', 'rb');
    if (false !== $process) {
      $output = stream_get_contents($process);
      preg_match('/hw.ncpu: (\d+)/', $output, $matches);
      if ($matches) { $cores = intval($matches[1][0]); }
      pclose($process);
    }
  }
  
  return $cores;
}

define('FORK_COUNT', cpu_cores());

$error_level = E_ALL;

include 'config.php';
include 'autoload.php';
error_reporting($error_level);
date_default_timezone_set('UTC');
