<?php

  date_default_timezone_set(@date_default_timezone_get());

  require_once 'EventListener.php';
  interface DebugFlags {
    const D_NONE    = 0;   const J_NONE    = 0;  const NONE    = 0;
    const D_SEVERE  = 1;   const J_SEVERE  = 1;  const SEVERE  = 1;
    const D_WARNING = 3;   const J_WARNING = 2;  const WARNING = 2;
    const D_INFO    = 7;   const J_INFO    = 4;  const INFO    = 4;
    const D_CONFIG  = 15;  const J_CONFIG  = 8;  const CONFIG  = 8;
    const D_FINE    = 31;  const J_FINE    = 16; const FINE    = 16;
    const D_FINER   = 63;  const J_FINER   = 32; const FINER   = 32;
    const D_FINEST  = 127; const J_FINEST  = 64; const FINEST  = 64;
  }

  interface StreamProtocols {
    const GROWL = '../StreamProtocols/Growl.protocol.php';
  }

  final class Debugger {
    const DEFAULT_TIMEFORMAT = 'd.m.Y H:i:s.u';
    const DEFAULT_MAINFORMAT = "%s - [ %s ] > %s\n";
    
    public static $logStreams = array();
    public static $timeFormat = self::DEFAULT_TIMEFORMAT;
    public static $mainFormat = self::DEFAULT_MAINFORMAT;
    
    public static function SetTimeFormat($func_format) { static::$timeFormat = $func_format; }
    public static function SetMainFormat($func_format) { static::$mainFormat = $func_format; }
    
    public static function GetTimeFormat() { return static::$timeFormat; }
    public static function GetMainFormat() { return static::$mainFormat; }
    
    public static function AddStream($func_level, $func_stream, $func_id = false) {
      static::CheckLevel($func_level);
      return CoreUtils::AppendArray(static::$logStreams, array($func_level, $func_stream, false), $func_id);
    }
    
    public static function RemoveStream($func_id) {
      if(static::$logStreams[$func_id]) unset(static::$logStreams[$func_id]);
      else static::$logStreams = array_filter(static::$logStreams, function($func_stream) use($func_id) { return $func_stream[1] != $func_id; });
    }
    
    public static function AddFormating($func_stream, $func_formating) {
      $func_element = &static::$logStreams[$func_stream];
      if($func_element) return $func_element[2] = $func_formating;
      else foreach(static::$logStreams as &$func_logStream) if($func_logStream[1] == $func_stream) $func_logStream[2] = $func_formating;
      return false;
    }
    
    public static function RemoveFormating($func_stream) {
      static::AddFormating($func_stream, false);
    }
    
    public static function Debug($func_level, $func_message, $func_reset = true) {
      static::CheckLevel($func_level);
      EventListener::FireEvent(Events::DEBUG_MESSAGE, $func_level, $func_message);
      
      $func_dMessage;
      foreach(static::$logStreams as $func_logStream) if($func_logStream[0] & $func_level) {
        if($func_logStream[2]) {
          $func_callback = $func_msg = $func_logStream[2];
          $func_fMessage = call_user_func_array($func_callback, func_get_args());
        } elseif(!$func_dMessage) $func_dMessage = static::DefaultFormating($func_level, $func_message, $func_reset);
        fwrite($func_logStream[1], $func_logStream[2] ? $func_fMessage : $func_dMessage);
      }
      
      return true;
    }
    
    public static function DefaultFormating($func_level, $func_message, $func_reset = true) {
      if($func_reset) $func_message .= '<reset>';
      
      $func_message = static::HTML2UNIX($func_message);
      $func_message = sprintf(static::$mainFormat, date(str_replace('u', substr(microtime(), 2, 3), static::$timeFormat)), static::LevelToString($func_level), $func_message);
      
      return $func_message;
    }
    
    public static function HTML2UNIX($func_string, $func_k = true) {
      $func_eMain = array('reset'  => 0, 'b'  =>  1, 'key'  =>  2, 'u'  =>  4, 'blink'  =>  5, 'inv'  =>  7, 'inverse'  =>  7);
      $func_uMain = array('/reset' => 0, '/b' => 22, '/key' => 22, '/u' => 24, '/blink' => 25, '/inv' => 27, '/inverse' => 27);
      $func_color = array('!black' => 30, '!red' => 31, '!green' => 32, '!yellow' => 33, '!purple' => 34, '!pink' => 35, '!lightblue' => 36, '!white' => 37, '!normal' => 39);
      
      $func_k = $func_k && IS_UNIX;
      $func_end = 0;
      $func_retVal = '';
      for($func_i = 0, $func_j = strlen($func_string); $func_i < $func_j; ++$func_i) if($func_string{$func_i} == '<')
      for($func_start = $func_i + 1; $func_i < $func_j; ++$func_i) if($func_string{$func_i} == '>') {
        $func_retVal .= substr($func_string, $func_end, $func_start - $func_end - 1);
        $func_element = substr($func_string, $func_start, ($func_end = $func_i + 1) - $func_start - 1);
        $func_element{0} == '?' ? $func_element{0} = $func_add = '!' : $func_add = false;
        $func_elemNew = $func_element{0} == '!' ? $func_color[$func_element] : ($func_element{0} == '/' ? $func_uMain[$func_element] : $func_eMain[$func_element]); if($func_k)
        $func_retVal .= $func_elemNew === NULL ? "<{$func_element}>" : ($func_add ? "\033[" . ($func_elemNew + 10) . "m" : "\033[{$func_elemNew}m");
        break;
      }
      
      return html_entity_decode(str_replace('<br />', PHP_EOL, $func_retVal));
    }
    
    public static function LevelToString($func_level) {
      $func_flags = array();
      
      try {
        $func_ref = new ReflectionClass('DebugFlags');
        $func_con = $func_ref->getConstants();
        foreach($func_con as $func_name => $func_c)
         if(substr($func_name, 0, 2) == 'D_' && $func_level == $func_c) return $func_name;
         elseif($func_level & $func_c && $func_name{1} != '_') $func_flags[] = $func_name;
      } catch(ReflectionException $e) {
        echo "!! WARNING !! The DebugFlags were not loaded, therefore Debugger::LevelToString({$func_level}) is senseless!\n";
      }
      
      if(!is_numeric($func_level)) return $func_level;
      
      if(!$func_flags) $func_flags = array('UNKNOWN');
      return join(' | ', $func_flags);
    }
    
    public static function CheckLevel(&$func_level) {
      if(!is_numeric($func_level)) $func_level = CoreUtils::RetrieveEnum('DebugFlags', $func_level);
      return $func_level;
    }
    
    public static function LoadStreamProtocol($func_streamProtocol) {
      require_once $func_streamProtocol;
    }
  }
  
?>