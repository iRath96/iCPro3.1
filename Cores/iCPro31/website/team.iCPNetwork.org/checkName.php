<?php

  if(!function_exists('sendBack')) { function sendBack($func_value) { die($func_value); }}
  include '../../Classes/Utils.php';
  include '../../Network.php';
  
  $username = strtolower($_GET['username']);
  $mysql = mysql_connect(
    SettingsManager::GetSetting(Settings::MYSQL_HOSTNAME),
    SettingsManager::GetSetting(Settings::MYSQL_USERNAME),
    SettingsManager::GetSetting(Settings::MYSQL_PASSWORD)
  ) or sendBack('fail');
  mysql_select_db(SettingsManager::GetSetting(Settings::MYSQL_DATABASE)) or sendBack('fail');

  $func_res = mysql_query('SELECT playerName FROM Users') or sendBack('fail');
  while($func_line = mysql_fetch_array($func_res, MYSQL_ASSOC)) {
    if(strtolower($func_line['playerName']) == $username) { sendBack('true'); break; }
  } mysql_free_result($func_res);
  
  sendBack('false');

?>