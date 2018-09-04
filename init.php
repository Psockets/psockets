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
  $OS = ["name" => strtoupper(substr(PHP_OS, 0, 3)), 
        "release" => php_uname("r"), 
        "version_info" => php_uname("v")];
  
  if ('WIN' == $OS["name"]) {
    //NOTE: since Windows 2000/NT env. variable %NUMBER_OF_PROCESSORS% holds number of ALL cores
    $cores = trim(fgets(popen('echo %NUMBER_OF_PROCESSORS%', 'rb')));
    $OS["name"] .= (($OS["release"] < 6) ? " XP " : " Vista/7/8/10 ") . $OS["release"];
  } else if ('LIN' == $OS["name"]) { //tested on Ubuntu x64 14.04, 16.04, 18.04 and Slack x64 14.1
    $OS["name"] .= " {$OS["release"]} " . substr($OS["version_info"], 0, stripos($OS["version_info"], " "));
    if (is_file('/proc/cpuinfo')) {
      preg_match_all('/^processor/m', file_get_contents('/proc/cpuinfo'), $matches);
      $cores = count($matches[0]);
    } else if ($cores = trim(fgets(popen('lscpu -a -e | wc -l', 'rb'))) - 1) { //minus first line
      ; //add more logic here if needed
    } else if ($cores = trim(fgets(popen('nproc --all', 'rb')))) { //all, it's possible some of OFFLINE cpus to become ONLINE
      ; //add more logic here if needed
    } else if ($cores = trim(fgets(popen('getconf _NPROCESSORS_ONLN', 'rb')))) {
      ; //add more logic here if needed
    } else if ($cores = trim(fgets(popen('grep -c ^processor /proc/cpuinfo', 'rb')))) {
      ; //add more logic here if needed
    } else if (preg_match('/hw.ncpu: (\d+)/', stream_get_contents(popen('sysctl -a', 'rb')), $matches) > 0) { //TODO: fix sysctl permissions
      $cores = intval($matches[1][0]);
    } else { echo "Unknown Linux-based operating system, setting the fork limit to 1 ... "; }
  } else { echo "Unknown operating system, setting the fork limit to 1 ... "; }
  
  //enable only for testing
  //return "{$OS["name"]} | {$cores} cpu cores";
  
  return $cores;
}

define('FORK_COUNT', cpu_cores());

$error_level = E_ALL;

include 'config.php';
include 'autoload.php';
error_reporting($error_level);
date_default_timezone_set('UTC');
