<?php

  uses ;{
    $realUsername  = $func_data[4];
    $remainingTime = $func_data[5];
  };
  
?>
<strong>You have been logged in <u>successfully</u></strong><br />
Thank you for logging in, <?= $realUsername ?>! You can now do Moderator Actions.<br />
Don't forget that your Session will expire in <?= $remainingTime; ?> Seconds!<br />
<br />
Anyway, if you have any further Problems feel free to contact Lofhy or Alex.

<?php updateStatus('ui-state-highlight', '<strong>Login Successful:</strong> You are now logged in!"); isLoggedIn = true; moderatorTimer = ' . $remainingTime . ';//'); ?>