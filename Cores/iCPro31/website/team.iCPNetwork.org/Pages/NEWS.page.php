<?php

  $func_current = count(scandir('News')) - 3;
  $func_entry = (integer) $_GET['entry'] ?: $func_current;
  echo urlencode(file_get_contents("News/{$func_entry}.htm"));
  echo "&index={$func_entry}&max={$func_current}&overlay=news&";

?>