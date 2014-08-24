<?php
  
  namespace iCPro;
  
  final class TimeoutManager {
    private static $timeouts;
    
    public static function AddTimeout($func_timeout, $func_function) { self::$timeouts[$func_timeout] = array('Elapsed' => time(), 'Function' => $func_function); }
    public static function RemoveTimeout($func_timeout)              { unset(self::$timeouts[$func_timeout]);                                                     }
        
    public static function QueueTimeouts() {
      foreach(self::$timeouts as $func_name => $func_) self::QueueTimeout($func_name);
    }
    
    public static function QueueTimeout($func_name) {
      if(@($func_timeout = self::$timeouts[$func_name]))
       if(time() - $func_timeout['Elapsed'] >= SettingsManager::GetSetting($func_name))
        call_user_func($func_timeout['Function'], self::$timeouts[$func_name]['Elapsed'] = time());
       else;
      else return false;
    }
  }

?>