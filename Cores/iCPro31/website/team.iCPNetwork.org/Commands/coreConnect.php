<?php

  include dirname(__DIR__) . '/../../Network.php';
  $func_server = SettingsManager::GetServer((integer) $_GET['serverID']);
  if(!$func_server) {
    noticeServerNotFound($func_server);
    outputServerList();
  } elseif($func_server['Type'] != ServerTypes::NORMAL && $func_server['Type'] != ServerTypes::MOD) {
    noticeServerWrongType($func_server);
    outputServerList();
  } else {
    $func_socket = @fsockopen($func_server['GIP'], $func_server['Gort'] + 1, $errno, $errstr, 5);
    if(!$func_socket) noticeServerUnreachable($func_server, $errno, $errstr);
    else {
      onSuccess();
      die();
    }
  } onFailure();
  
  function updateStatus($func_classString, $func_messageString) {
    ?><script type="text/javascript">
      isLoggedIn = false;
      updateStatus("<?= $func_classString ?>", "<?= $func_messageString ?>");
    </script><?php
  }
  
  function noticeServerUnreachable($func_server, $func_errorNumber, $func_errorString) {
    ?><strong>Strange... The Server you are looking for is unreachable :O</strong><br />
    Could not connect to <i><?= $func_server['GIP'] ?>:<?= $func_server['Gort'] + 1 ?></i><br />
    If you are a Freak - just like me - you might want to know what happend:<br />
    <br />
    <small><strong>Error Details:</strong><br />
    <u>ErrorCode:</u> <?= $func_errorNumber ?><br />
    <u>ErrorString:</u> <?= $func_errorString ?><br /></small><?php
  }
  
  function outputServerList() {
    echo "<ul>\n";
    foreach(SettingsManager::$servers as $func_serverID => $func_server) {
      if($func_server['Type'] != ServerTypes::NORMAL && $func_server['Type'] != ServerTypes::MOD) continue;
      ?><li><strong><?= $func_serverID ?>:</strong> <?= $func_server['Name'] ?></li><?php
    } echo "<ul />\n";
  }
  
  function noticeServerNotFound($func_server) {
    ?><strong>Strange... The Server you are looking for could not be found :O</strong><br />
    Don't blame me! I am just the Programmer, but I guess, I have something to help you :)<br />
    <br />
    <i>Here a list of the Servers:</i><br /><?php
  }
  
  function noticeServerWrongType($func_server) {
    ?><strong>Strange... The Server you are looking has the wrong Type!</strong> (<?= $func_server['Type']; ?>)<br />
    Don't blame me! I am just the Programmer, but I guess, I have something to help you :)<br />
    <br />
    <i>Here a list of Servers with an VALID TYPE:</i><br /><?php
  }
  
?>