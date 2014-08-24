<?php

  if(!isset($_GET["method"]) and isset($argv[3])) {
    $_GET = array();
    $_GET["one"]    = $argv[1];
    $_GET["method"] = $argv[2];
    $_GET["two"]    = $argv[3];
  }

  $one    = $_GET["one"];
  $two    = $_GET["two"];
  $method = $_GET["method"];

?>
<html>
  <head>
    <title>Alexsche BigInt</title>
  </head>
  <body>
    <font face="Tahoma">
      <small>
        <b><?php echo $one; ?></b> <?php echo $method; ?> <br>
        <b><?php echo $two; ?></b> ist <br>
        <b>
        <?php

          $func_calcStep = 13;
          //$func_calcStep = 18; //On x64 CPU's

          function multiplikation($func_one, $func_two) {
            global $func_calcStep;
            $func_step = $func_calcStep;
            $func_calcStep = floor($func_calcStep / 2);
            
            //STRREV
            $func_one = strrev($func_one);
            
            //SPLIT
            $func_one = str_split($func_one, $func_calcStep);
            
            //LENGTH
            $func_len = count($func_one);
            
            //RESULT
            $func_end = array();
            
            //MAINLOOP
            for($func_i = 0; $func_i < $func_len; $func_i++) {
              $func_A = strrev($func_one[$func_i]);
              
              $func_end[$func_i] = $func_A * $func_two;
            }
            
            transform($func_end);
            
            $func_calcStep = $func_step;
            
            return strrev(join("", $func_end));
          }
          
          function addition($func_one, $func_two) {
            global $func_calcStep;
            
            //STRREV
            $func_one = strrev($func_one);
            $func_two = strrev($func_two);
            
            //SPLIT
            $func_one = str_split($func_one, $func_calcStep);
            $func_two = str_split($func_two, $func_calcStep);
            
            //LENGTH
            $func_len = (count($func_one) > count($func_two)) ? count($func_one) : count($func_two);
            
            //RESULT
            $func_end = array();
            
            //MAINLOOP
            for($func_i = 0; $func_i < $func_len; $func_i++) {
              $func_A = (isset($func_one[$func_i])) ? strrev($func_one[$func_i]) : 0;
              $func_B = (isset($func_two[$func_i])) ? strrev($func_two[$func_i]) : 0;
              
              $func_end[$func_i] = $func_A + $func_B;
            }
            
            transform($func_end);
            
            return strrev(join("", $func_end));
          }
          
          function transform(&$func_array) {
            global $func_calcStep;
            
            $func_len = count($func_array);
            
            for($func_i = 0; $func_i < $func_len; $func_i++) {
              $func_number = $func_array[$func_i];
              
              if($func_number > pow(10, $func_calcStep)) {
                $func_toM = substr($func_number, 0, -$func_calcStep);
                $func_array[$func_i] = substr($func_number, -$func_calcStep);
                
                if(!isset($func_array[$func_i + 1])) $func_array[$func_i + 1] = 0;
                $func_array[$func_i + 1] += $func_toM;
              }
              
              $func_array[$func_i] = strrev($func_array[$func_i]);
            }
          }
          
          function isBigger($func_one, $func_two) {
            global $func_calcStep;
            
            $func_false = 0;
            $func_true  = 2;
            
            $func_one = strrev($func_one);
            $func_two = strrev($func_two);
            
            $func_one = str_split($func_one, $func_calcStep);
            $func_two = str_split($func_two, $func_calcStep);
            
            $func_len = (count($func_one) > count($func_two)) ? count($func_one) : count($func_two);
            if(count($func_one) > count($func_two)) return $func_true;
            if(count($func_one) < count($func_two)) return $func_false;
            
            for($func_i = $func_len; $func_i > 0; $func_i--) {
              $func_A = strrev($func_one[$func_i - 1]);
              $func_B = strrev($func_two[$func_i - 1]);
              
              if($func_A > $func_B) return $func_true;
              if($func_A < $func_B) return $func_false;
            }
            
            return 1;
          }
          
          function divide($func_one, $func_two) {
            $func_multis     = array();
            $func_multis[0]  = 0;
            $func_multis[1]  = $func_two;
            $func_multis[2]  = addition($func_two, $func_two);
            $func_multis[3]  = addition($func_multis[2], $func_two);
            $func_multis[4]  = addition($func_multis[3], $func_two);
            $func_multis[5]  = addition($func_multis[4], $func_two);
            $func_multis[6]  = addition($func_multis[5], $func_two);
            $func_multis[7]  = addition($func_multis[6], $func_two);
            $func_multis[8]  = addition($func_multis[7], $func_two);
            $func_multis[9]  = addition($func_multis[8], $func_two);
            $func_multis[10] = $func_two . "0";
            
            $func_index      = 0;
            $func_number     = 0;
            
            while(isBigger($func_one, $func_number) == 2) {
              $func_index++;
              $func_number = addition($func_number, $func_two);
            }
            
            return $func_index;
          }
          
          if($method == "plus") {
            $time   = microtime(true);
            $result = addition($one, $two);
            $time   = microtime(true) - $time;
          } else if($method == "minus") {
            echo "<b>Warnung!</b></b> Subtraktion ist noch in der Beta Phase!<br>";
            echo "Es können ggf. Falsche Ergebnisse ausgegeben werden!<br><br><b>";
            $time   = microtime(true);
            $result = divide($one, $two);
            $time   = microtime(true) - $time;
          } else if($method == "durch") {
            echo "<b>Warnung!</b></b> Division ist noch in der Beta Phase!<br>";
            echo "Es wird ein aufgerundetes Ergebniss ausgegeben!<br><br><b>";
            $time   = microtime(true);
            $result = divide($one, $two);
            $time   = microtime(true) - $time;
          } else {
            $time   = microtime(true);
            $result = multiplikation($one, $two);
            $time   = microtime(true) - $time;
          }
          
          echo $result . "<br>";
          
        ?>
        </b><br>
        <small><b>Berechnungszeit:</b> <?php echo $time; ?> Sekunden</small><br>
        <small>Diese Multiplikation einer <?php echo strlen($one); ?>-stelligen Zahl mit einer <?php echo strlen($one); ?>-stelligen Zahl<br>
        ergab eine <?php echo strlen($result); ?>-stellige Zahl.</small><br><br>
        <form action="" method="GET">
          <input type="text" name="one" id="one">
            <select name="method" id="method" size="1">
              <option value="plus">plus</option>
              <option value="mal">mal</option>
              <option value="minus">minus</option>
              <option value="durch">durch</option>
            </select>
          <input type="text" name="two" id="two">
          <input type="submit" value="ist?">
        </form>
      </small>
    </font>
  </body>
</html>