<?php

  include '../../Network.php';
  include '../../../../Engines/MySQL/MySQL.php';
  
  MySQL::Connect(
    SettingsManager::GetSetting(Settings::MYSQL_HOSTNAME),
    SettingsManager::GetSetting(Settings::MYSQL_USERNAME),
    SettingsManager::GetSetting(Settings::MYSQL_PASSWORD)
  );
  MySQL::Select(SettingsManager::GetSetting(Settings::MYSQL_DATABASE));
  
  if(!MySQL::GetData("SELECT * FROM `IPBans` WHERE '{$this->ip}' LIKE `IP` AND `Flag` = 1") && ($data = MySQL::GetData("SELECT * FROM `IPBans` WHERE '{$this->ip}' LIKE `IP`"))) {
    die('You are IPBanend');
  }
    
?>