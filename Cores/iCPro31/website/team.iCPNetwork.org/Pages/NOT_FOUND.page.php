<strong><i><?= $_GET['username'] ?></i> is not an online iCP Moderator</strong><br />
You have to be online in iCP to use the Moderation Panel.<br />
If you are online, check for spelling mistakes in the Username Field.<br />
<small>(Notice: The Username is <u>case insensitive</u>)</small>

<?php updateStatus('ui-state-error', '<strong>Login Failed:</strong> You aren\'t online >.<'); ?>