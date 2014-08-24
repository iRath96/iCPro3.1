<?php
  
  namespace iCPro;
  
  final class Utils {
    public static function PickRandom($func_array)     { return $func_array[rand(0, count($func_array) - 1)]; }
    public static function PickRealRandom($func_array) { return self::PickRandom(array_value($func_array));   }
    
    public static function SwapMD5($func_md5) { return substr($func_md5, 16) . substr($func_md5, 0, 16); }
    
    public static function GenerateRandomKey($func_chars, $func_length) {
      $func_chars = str_split($func_chars);
      for($func_retVal = '', $func_i = 0; $func_i < $func_length; ++$func_i) $func_retVal .= self::PickRandom($func_chars);
      return $func_retVal;
    }
    
    public static function ConnectToSQL() {
      $func_hostname = SettingsManager::GetSetting(Settings::MYSQL_HOSTNAME);
      $func_username = SettingsManager::GetSetting(Settings::MYSQL_USERNAME);
      $func_password = SettingsManager::GetSetting(Settings::MYSQL_PASSWORD);
      $func_database = SettingsManager::GetSetting(Settings::MYSQL_DATABASE);
      
      \Debugger::Debug(\DebugFlags::J_INFO, sprintf(
        'Connecting MySQL to <b>%s</b> with Username %s using PWD: %s',
        $func_hostname,
        $func_username,
        $func_password ? 'Yes' : 'No'
      ));
      \MySQL::Connect($func_hostname, $func_username, $func_password);
      
      \Debugger::Debug(\DebugFlags::J_INFO, sprintf('Selecting Database <b>%s</b>', $func_database));
      return \MySQL::SelectDb($func_database);
    }
    
    public static function CheckString($func_haystack, $func_minLength, $func_maxLength, $func_needle) {
      if(strlen($func_needle) < $func_minLength || strlen($func_needle) > $func_maxLength) return false;
      return !((boolean) str_replace(str_split($func_haystack), '', $func_needle));
    }
    
    public static function ConvertMemorySize($func_bytes) {
      $func_sizes = array('b' => 0.125, 'B' => 1, 'KiB' => 1024, 'MiB' => 1048576, 'GiB' => 1073741824, 'TiB' => 1099511627776);
      foreach($func_sizes as $func_size => $func_divisor) if($func_bytes < next($func_sizes)) break;
      return ($func_bytes / $func_divisor) . " {$func_size}";
    }
  }

?>