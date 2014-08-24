<?php

  function onSuccess() {
    global $func_socket;
    
    fwrite($func_socket, "%xt%{$_SERVER['REMOTE_ADDR']}%{$_GET['username']}%{$_GET['sessionID']}%login%");
    $func_packet = fread($func_socket, 1024);
    $func_data = explode('%', $func_packet);
    switch($func_data[2]) {
      case 'pg':
       include dirname(__DIR__) . '/Pages/' . $func_data[3] . '.page.php';
       break;
    }
  }
  
  function onFailure() {
    updateStatus('ui-state-error', '<strong>Login Failed:</strong> Look underneath for Error Details');
  }

  require_once 'coreConnect.php';

?>