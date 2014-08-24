<?php

  define('IS_UNIX', CoreUtils::IsUnix()); //... IsUnix actually just checks if Windoof is *NOT* being used ...//
  class CoreUtils {
    public static function PickRandom($func_array)     { return $func_array[rand(0, count($func_array) - 1)]; }
    public static function PickRealRandom($func_array) { return self::PickRandom(array_value($func_array));   }
    
    public static function GenerateRandomKey($func_chars, $func_length) {
      $func_chars = str_split($func_chars);
      for($func_retVal = '', $func_i = 0; $func_i < $func_length; ++$func_i) $func_retVal .= self::PickRandom($func_chars);
      return $func_retVal;
    }
    
    public static function CheckString($func_haystack, $func_minLength, $func_maxLength, $func_needle) {
      if(strlen($func_needle) < $func_minLength || strlen($func_needle) > $func_maxLength) return false;
      return !((boolean) str_replace(str_split($func_haystack), '', $func_needle));
    }
    
    public static function RetrieveEnum($func_interface, $func_constant) {
      try {
        $func_ref = new ReflectionClass($func_interface);
        $func_con = $func_ref->getConstants();
      } catch(ReflectionException $e) {
        echo "!! WARNING !! Trying to access an undefined Interface ({$func_interface})!\n";
      }
      
      return $func_con[$func_constant];
    }
    
    public static function AppendArray(&$func_array, $func_value, $func_id = false) {
      if($func_id) $func_array[$func_id] = $func_value;
      else $func_array[] = $func_value;
      
      return each($func_array);
    }
    
    public static function IsUnix() {
      return strpos(strtolower(PHP_OS), 'win') === false || strpos(strtolower(PHP_OS), 'dar') !== false;
    }
    
    public static function str2hex($func_str) {
      return rtrim(str_replace('X', 'x', strtoupper(call_user_func_array('sprintf', array(str_repeat('0x%02x ', strlen($str))) + array_map('ord', str_split(' ' . $str))))));
    }
  }

?>