&html=<?php

  $func_pages = 'news,user';
  
  $func_pages = array_flip(explode(',', $func_pages));
  if($func_pages[$_GET['request']] !== NULL) include 'Pages/' . strtoupper($_GET['request']) . '.page.php';
  else {

?>
The Page you are looking for does not exist :O&overlay=blank&
<?php

  }
  
?>