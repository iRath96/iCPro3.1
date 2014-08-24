<?php
  
  namespace iCPro\Servers;
  require_once CLASS_DIR . '/Akwaya.php';
  class DismissedSelector {
    public $backtrace;
    public function __construct() { $this->backtrace = debug_backtrace(); }
    
    public static function Resolve($start, $packet) {
      $start = 'C: ' . $start;
      $dir = ROOT_DIR . '/wt/';
      $examples = array();
      
      if($dh = opendir($dir)) {
        while(($file = readdir($dh)) !== false) {
          if(strtolower(substr($file, -4)) != '.log') continue;
          foreach($lines = file($dir . $file) as $i => $line) if(substr($line, 0, strlen($start)) == $start)
            $examples[] = array( $file . ':' . ($i + 1), array_slice($lines, $i - 2, 8) );
        } closedir($dh);
      }
      
      if(count($examples) == 0) \iCPro\Akwaya::Notice("DismissedSelector::Resolve", "had bad luck.", false);
      else {
        \iCPro\Akwaya::Notice("DismissedSelector::Resolve", "yielded the following examples", false);
        
        shuffle($examples);
        foreach($examples as $i => $example) {
          if($i >= 10) {
            echo "(stop, 10 out of " . count($examples) . " examples shown)\n";
            break;
          }
          
          list($source, $lines) = $example;
          echo "\033[38;5;112m{$source}\033[0m\n";
          foreach($lines as $i => $line) {
            if(strlen($line) > 200) $line = substr($line, 0, 196) . ' ...';
            if($i != 2) echo "\033[38;5;246m";
            echo "  " . $line . "\033[0m";
          } echo "\n";
        }
      }
    }
  }
  
  function dismiss_selector() {
    return new DismissedSelector();
  }
  
?>