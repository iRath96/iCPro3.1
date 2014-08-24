<?php
  
  final class SettingsManager {
    public static $settings;
    public static $servers;
    
    public static function MakeSetting($func_s) { return $func_s == strtoupper($func_s) ? CoreUtils::RetrieveEnum('Settings', $func_s) : $func_s; }
    
    public static function AddSetting($func_setting, $func_data) { return self::$settings[self::MakeSetting($func_setting)] = $func_data; }
    public static function GetSetting($func_setting)             { return self::$settings[self::MakeSetting($func_setting)];              }
    
    public static function AddServer($func_ID, $func_data) { return self::$servers[$func_ID] = $func_data; }
    public static function GetServer($func_ID)             { return self::$servers[$func_ID];              }
  }

?>