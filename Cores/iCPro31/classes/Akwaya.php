<?php
  
  /* Akwaya *
    -======-
    
    From the saga of iCP.
    This class' job ranges from showing backlogs over catching
    exceptions to reporting players for cheating (or other kinds
    of suspicious behavior).
  
  */
  
  namespace iCPro;
  
  function akwaya_encode($mixed) {
    if(is_null($mixed)) return 'null';
    
    $attempt = @json_encode($mixed); // is_object($mixed) ? 'null' : @json_encode($mixed);
    if($attempt != 'null') return $attempt;
    
    $ret = gettype($mixed) . '/';
    if(is_object($mixed)) $ret .= get_class($mixed) . '/';
    if(is_object($mixed) && method_exists($mixed, '__toString')) $ret .= (string)$mixed; // can we stringify this object?
    return $ret;
  }
  
  class Akwaya {
    public static function Backtrace($offset = 2) {
      self::PrintBacktrace(debug_backtrace(), $offset);
    }
    
    public static function Notice($source, $message, $doBacktrace = true) {
      echo "\033[1;38;5;126m{$source}\033[0;38;5;138m {$message}\033[0m\n";
      if($doBacktrace) self::Backtrace(3);
    }
    
    public static function PrintBacktrace(array $backtrace, $offset = 1) {
      echo "\033[38;5;31mAkwaya::\033[38;5;116mPrintBacktrace\033[0m\n";
      
      foreach($backtrace as $i => $entry) {
        $important = $i < count($backtrace) - 2 && $i >= $offset;
        
        // colors!
        $coB = $important ?  15 : 246; // important ? white : light gray
        $coC = $important ? 248 : 239;
        
        $file = is_null($entry['file']) ? '' : getRelativePath(__DIR__, $entry['file']);
        $rawSource = "{$file}:{$entry['line']}";
        $source = "\033[38;5;" . ($important && $rawSource != ':' ? 112 : 101) . "m"; // colorA
        
        if($rawSource == ':') $rawSource = '(internal callback)';
        $source .= str_pad($rawSource, max(45, 5 + strlen($rawSource) - strlen($rawSource) % 5));
        
        $args = array();
        foreach($entry['args'] as $arg) $args[] = akwaya_encode($arg);
        
        $function = "{$entry['class']}{$entry['type']}{$entry['function']}";
        $object = is_null($entry['object']) ? '' : ' on ' . akwaya_encode($entry['object']);
        $object = ''; // not used for now.
        
        // available = 60 (iterative approach)
        // array(16, 40, 30, 5)
        // avg = 60 / 4 = 15
        // -> used = 3 * 15 + 5 = 50
        // avg = 70 / 4 = 17.5
        // -> used = 2 * 17.5 + 16 + 5 = 56
        // avg = 74 / 4 = 18.5
        // -> used = 2 * 18.5 + 16 + 5 = 58
        // avg = 76 / 4 = 19
        // -> used = 2 * 19 + 16 + 5 = 60
        
        $argMaxLen = 0;
        if(count($args) > 0) {
          $realSpace = $argSpace = 210 - strlen($source) - strlen($function) - strlen($object) - count($args) * 2; // for the commas!
          for($pass = 0; $pass < 16; ++$pass) { // Adjust max. argument length so that the line is filled as much as possible
            $argMaxLen = $argSpace / count($args);
            $used = 0; foreach($args as $arg) $used += min($argMaxLen, strlen($arg));
            $argSpace += $realSpace - $used; // Add the amount of space left to our space accumulator.
          } $argMaxLen = floor($argSpace / count($args));
        }
        
        $middle = floor($argMaxLen / 2) - 1;
        foreach($args as &$arg)
          if(strlen($arg) > $argMaxLen) $arg = substr($arg, 0, $middle) . '...' . substr($arg, strlen($arg) - $middle);
        
        $i = str_pad("{$i}", 2, ' ', STR_PAD_LEFT);
        echo "\033[38;5;{$coC}m{$i}  {$source}\033[38;5;{$coB}m {$function}(" . join(', ', $args) . ")\033[0m{$object}\n";
      } echo "\n";
    }
  }
  
  function getRelativePath($from, $to) { // Gordon @ http://stackoverflow.com/questions/2637945/getting-relative-path-from-absolute-path-in-php
    // some compatibility fixes for Windows paths
    $from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
    $to   = is_dir($to)   ? rtrim($to, '\/') . '/'   : $to;
    $from = str_replace('\\', '/', $from);
    $to   = str_replace('\\', '/', $to);

    $from     = explode('/', $from);
    $to       = explode('/', $to);
    $relPath  = $to;

    foreach($from as $depth => $dir) {
      // find first non-matching dir
      if($dir === $to[$depth]) array_shift($relPath); // ignore this directory
      else {
        // get number of remaining dirs to $from
        $remaining = count($from) - $depth;
        if($remaining > 1) {
          // add traversals up to first matching dir
          $padLength = (count($relPath) + $remaining - 1) * -1;
          $relPath = array_pad($relPath, $padLength, '..');
          break;
        } else $relPath[0] = './' . $relPath[0];
      }
    } return implode('/', $relPath);
  }
  
?>