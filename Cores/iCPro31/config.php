<?php
  
  namespace iCPro;
  
  require_once CLASS_DIR . '/SettingsManager.php';
  const SERVER_VERSION = '20100725';
  
  /* Predefined Lines - Do not change! */
  if(!$argv) $argv = array();
  define('IS_SANDBOX', array_search('-sandbox', $argv));
  define('GLOBAL_IP',  IS_SANDBOX ? '127.0.0.1' : 'media.icpnetwork.org');//... You have to change 'alexrath.gotdns.org' to your DynDNS/GlobalIP   ...//
  define('LOCAL_IP',   IS_SANDBOX ? '127.0.0.1' : '25.164.204.40');             //... You have to change '192.168.178.35 to' your IntranetIP/LocalIP     ...//
  define('TEAM_IP',    IS_SANDBOX ? '127.0.0.1' : 'alexrath.gotdns.org'); //... You have to change 'Not used in my case' to the IP of your ModHost ...//
  define('SERVER_ID',  (integer) (is_numeric($argv[2]) ? $argv[2] : $argv[3]));
  define('BASE_DIR',   __DIR__ . '/');
  /* Predefined Lines end here. Have fun messing up the Config! */
  
  //SettingsManager::AddSetting(IndividualSettings::EPF_FIELD_OPS, 2);
  
  SettingsManager::AddSetting(Settings::MYSQL_HOSTNAME, '127.0.0.1');
  SettingsManager::AddSetting(Settings::MYSQL_USERNAME, 'root');
  SettingsManager::AddSetting(Settings::MYSQL_PASSWORD, '');
  SettingsManager::AddSetting(Settings::MYSQL_DATABASE, 'iCP');
  
  /* DEPRECATED
    SettingsManager::AddSetting(Settings::ITEM_INI,      'Data/Items.ini');
    SettingsManager::AddSetting(Settings::FLOOR_INI,     'Data/Floors.ini');
    SettingsManager::AddSetting(Settings::IGLOO_INI,     'Data/Igloos.ini');
    SettingsManager::AddSetting(Settings::CENSOR_INI,    'Data/Censorings.ini');
    SettingsManager::AddSetting(Settings::FURNITURE_INI, 'Data/Furniture.ini');
  */

  SettingsManager::AddSetting(Settings::MONEYMAKER_BAN, 'Haha. Banned. Loser. Haha. You failed :P');
  SettingsManager::AddSetting(Settings::DIGHACK_BAN,    'Yea, I was to lazy to enter usefull Ban informations');
  SettingsManager::AddSetting(Settings::MODSERVER_MSG,  'ASDF :)');
  SettingsManager::AddSetting(Settings::SERVERDOWN_MSG, 'The Server is down! Down! DOWN! D! O! DOUBLE-U! N!');
  
  SettingsManager::AddSetting(Settings::LOGIN_KEY_LIFE_TIME, 300);
  SettingsManager::AddSetting(Settings::SERVER_IDLE_TIME,    10);
  SettingsManager::AddSetting(Settings::USER_IDLE_TIME,      600);
  SettingsManager::AddSetting(Settings::USER_IDLE_CHECK,     100);
  SettingsManager::AddSetting(Settings::SERVER_LOAD_DIVISOR, 50); //... 250 Users per Server (LOAD_DIVISOR times 5 Bars) ...//
  SettingsManager::AddSetting(Settings::ROOM_LIMIT,          75); //... 75, but TestValue = 2 ...//
  
  SettingsManager::AddSetting(Settings::PUFFLE_MINLEN, 1); //... Find out the real Value? ...//
  SettingsManager::AddSetting(Settings::PUFFLE_MAXLEN, 12);
  SettingsManager::AddSetting(Settings::PUFFLE_CHARS,  'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!?(){}[]<>.:-_ ');
  
  SettingsManager::AddSetting(Settings::PLAYER_MINLEN, 3);
  SettingsManager::AddSetting(Settings::PLAYER_MAXLEN, 12);
  SettingsManager::AddSetting(Settings::PLAYER_CHARS,  'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789._-:*/\\!?(){}[]<>.:-_ ');
  
  SettingsManager::AddSetting(Settings::PASSWORD_MINLEN, 6);
  SettingsManager::AddSetting(Settings::PASSWORD_MAXLEN, 32);

  SettingsManager::AddSetting(Settings::EMAIL_MINLEN, 6); //1@3.56
  SettingsManager::AddSetting(Settings::EMAIL_MAXLEN, 128);
  SettingsManager::AddSetting(Settings::EMAIL_CHARS,  'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789._-@');
  
  SettingsManager::AddSetting(Settings::REHASH_PERIOD, 30);
  SettingsManager::AddSetting(Settings::MODERATOR_TTL, 540); //... 9 Minutes should be okay :) ...//
  
  SettingsManager::AddSetting(Settings::DIG_TTL,    2);
  SettingsManager::AddSetting(Settings::KICK_LIMIT, 7);
  SettingsManager::AddSetting(Settings::MUTE_LIMIT, 35);
  SettingsManager::AddSetting(Settings::MOVE_LIMIT, 100);
  
  SettingsManager::AddSetting(Settings::MAX_MOOD_LENGTH, 64);
  
  SettingsManager::AddServer(99, array(
    'Name' => 'iLogin',
    'Type' => ServerTypes::LOGIN,
    'IP'   => LOCAL_IP,
    'GIP'  => GLOBAL_IP,
    'Port' => 3724, // Notice: Had to be changed to 3724 for the new CP.
    'Gort' => 3724
  ));
  
  SettingsManager::AddServer(100, array(
    'Name' => 'iSnow Leopard',
    'Type' => ServerTypes::WORLD,
    'IP'   => LOCAL_IP,
    'GIP'  => GLOBAL_IP,
    'Port' => 9875,
    'Gort' => 9875
  ));
  
  SettingsManager::AddServer(101, array(
    'Name' => 'iBreeze [Mods only]',
    'Type' => ServerTypes::MOD,
    'IP'   => LOCAL_IP,
    'GIP'  => GLOBAL_IP,
    'Port' => 3274,
    'Gort' => 3274
  ));
  
  SettingsManager::AddServer(102, array(
    'Name' => 'iLove Mac [Devs only]',
    'Type' => ServerTypes::DEV,
    'IP'   => '127.0.0.1', //Localhost - only I can connect :P
    'GIP'  => '127.0.0.1', //Olachlsot - noyl I acn ocnncet P:
    'Port' => 9339,
    'Gort' => 9339
  ));
  
  SettingsManager::AddServer(200, array(
    'Name' => 'iCracker',
    'Type' => ServerTypes::REDEMPTION,
    'IP'   => LOCAL_IP,
    'GIP'  => GLOBAL_IP,
    'Port' => 6113,
    'Gort' => 6113
  ));
  
  SettingsManager::AddServer(201, array(
    'Name' => '!!WTF|FTW!!',
    'Type' => ServerTypes::REDEMPTION,
    'IP'   => LOCAL_IP,
    'GIP'  => GLOBAL_IP,
    'Port' => 6114,
    'Gort' => 6114
  ));
  
?>