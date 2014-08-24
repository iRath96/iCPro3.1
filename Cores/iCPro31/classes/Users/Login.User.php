<?php
  
  namespace iCPro\Users;
  require_once 'User.php';
  
  final class Login extends User {
    public $friendsLoginKey, $confirmationHash;
  }

?>