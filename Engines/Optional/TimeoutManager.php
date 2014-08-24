<?php
  
  /*
  
    Attention!
     This Class is not the best Solution,
     as there are Functions working in the same
     Way as other Functions, just with a few
     minor Changes.
     
     Any Changes applied should be checked if
     other Functions require the same Change.
  
  */
  
  final class TimeoutManager {
    public static $timeouts;
    public static $intervals;
    
    public static function AddTimeout($func_function, $func_timeout, $func_args = array(), $func_id = false) {
      return CoreUtils::AppendArray(self::$timeouts, array($func_timeout + microtime(true), $func_function, $func_args), $func_id);
    }
    
    public static function RemoveTimeout($func_id) { //... Linked to RemoveInterval ...//
      if(isset(self::$timeouts[$func_id])) unset(self::$timeouts[$func_id]);
      elseif(is_callable($func_id)) foreach(self::$timeouts as &$func_timeout) if($func_timeout[1] == $func_id) unset($func_timeout);
      else return false;
      return true;
    }
    
    public static function AddInterval($func_function, $func_timeout, $func_args = array(), $func_id = false) {
      return CoreUtils::AppendArray(self::$intervals, array($func_timeout + microtime(true), $func_timeout, $func_function, $func_args), $func_id);
    }
    
    public static function RemoveInterval($func_id) { //... Linked to RemoveTimeout ...//
      if(isset(self::$intervals[$func_id])) unset(self::$intervals[$func_id]);
      elseif(is_callable($func_id)) foreach(self::$intervals as &$func_interval) if($func_interval[2] == $func_id) unset($func_interval);
      else return false;
      return true;
    }
    
    public static function Update() {
      foreach(self::$timeouts  as $func_id => $func_timeout)  if($func_timeout[0]  <= microtime(true)) self::QueueTimeout($func_id);
      foreach(self::$intervals as $func_id => $func_interval) if($func_interval[0] <= microtime(true)) self::QueueInterval($func_id);
    }
    
    public static function QueueTimeout($func_id) {
      $func_timeout = self::$timeouts[$func_id];
      call_user_func_array($func_timeout[1], $func_timeout[2]);
      unset(self::$timeouts[$func_id]);
    }
    
    public static function QueueInterval($func_id) {
      $func_interval = &self::$intervals[$func_id];
      $func_interval[0] = microtime(true) + $func_interval[1];
      call_user_func_array($func_interval[2], $func_interval[3]);
    }
  }

?>