<?php

  $a = array("<b>Admin Comments:</b><!-- CMT2 -->\n\n", "<b>Moderator Comments:</b><!-- CMT1 -->\n\n", "<b>Human Comments:</b><!-- CMT0 -->\n\n");

  $func_uID = $_GET['user'];
  if(is_numeric($func_uID)) echo (@str_replace($a, '', file_get_contents("Comments/Users/{$func_uID}.txt")) ?: 'Be the first to comment on this User!') . '&overlay=user&';
  else {
  
?>
You have to open a PlayerCard before using this Feature.&overlay=blank&
<?php

  }
  
?>