<?php
  
  namespace iCPro;
  
  define('ROOT_DIR', __DIR__);
  define('CLASS_DIR', __DIR__ . '/classes');
  
  require_once CLASS_DIR . '/Servers/DismissedSelector.php';
  Servers\DismissedSelector::Resolve($argv[1], $argv[1]);
  
?>