<?php

  function setProxy($func_ip = NULL, $func_port = NULL) {
    static $proxyIP, $proxyPort;
    if($func_ip != NULL && $func_port != NULL) {
      $proxyIP = $func_ip;
      $proxyPort = $func_port;
    }
    return "tcp://{$proxyIP}:{$proxyPort}/";
  }
  
  function fproxopen($func_ip, $func_port, &$func_eN = NULL, &$func_eS = NULL, $func_timeout = false) {
    $func_socket = fsockopen(setProxy(), -1, $func_errNo, $func_errStr, $func_timeout !== false ? $func_timeout : ini_get('default_socket_timeout'));
    $func_eN = $func_errNo;
    $func_eS = $func_errStr;
    if(!$func_socket) return false;
    
    fwrite($func_socket, pack('C3', 0x05, 0x01, 0x00));
    
    $func_response = @unpack('Cversion/Cmethod', fread($func_socket, 4096));
    if($func_response['version'] != 0x05 || $func_response['method'] != 0x00) return false;
    
    if(ip2long($func_ip) == -1) fwrite($func_socket, pack('C5', 0x05, 0x01, 0x00, 0x03, strlen($func_ip)) . $func_ip . pack('n', $func_port));
    else fwrite($func_socket, pack('C4Nn', 0x05, 0x01, 0x00, 0x01, ip2long(gethostbyname($func_ip)), $func_port));
    
    $func_response = unpack('Cversion/Cresult/Creg/Ctype/Lip/Sport', fread($func_socket, 4096));
    if($func_response['version'] != 0x05 || $func_response['result'] != 0x00) return false;
    
    return $func_socket;
  }

?>