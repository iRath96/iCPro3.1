<?php

  interface LogEvents {
    const NEW_COMMENT = 0;
    const NOT_NEWBIE  = 1;
    const NEW_PRIVMSG = 2;
    const MUTE_PLAYER = 3;
    const UNMUTE_PLAYER = 4;
  }

  final class UserLog {
    public static function AddToLog($func_uID, $func_type, $func_additional = array()) {
      global $SERVER;
      $func_data = $func_type . join('||', $func_additional);
      
      if(isset($SERVER->alias[$func_uID])) $SERVER->alias[$func_uID]->sendPacket("%xt%nlog%{$func_data}%");
      
      $func_file = fopen(self::GetLogPath($func_uID), 'a+');
      fwrite($func_file, $func_data . chr(10));
      fclose($func_file);
    }
    
    public static function GetLog($func_uID) {
      return @file_get_contents(self::GetLogPath($func_uID));
    }
    
    public static function ClearLog($func_uID) {
      unlink(self::GetLogPath($func_uID));
    }
    
    public static function GetLogPath($func_uID) {
      return ROOT . "/ULogs/{$func_uID}.txt";
    }
  }

?>