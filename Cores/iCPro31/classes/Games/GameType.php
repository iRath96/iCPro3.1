<?php
  
  namespace iCPro\Games;
  
  abstract class GameType {
    /* TODO: Check if these are required.
    public static $games = array();
    public static $users = array();
    */
    
  //abstract public static function IsGame($func_gameID);
  //abstract public static function HandleUserLeft(&$func_user);
    
    /* TODO: Check if these are required. */
  /*public static function SendGamePacket($func_gameID, $func_packet, $func_append = true) {
      foreach(static::$users[$func_gameID] as $func_userID) GameManager::SendToPlayer($func_userID, $func_packet, $func_append);
      return true;
    }
    
    public static function SendTablePacket($func_tableID, $func_packet, $func_append = true) {
      global $SERVER;
      foreach($SERVER->data['Tables'][$func_tableID] as $func_user) $func_user->sendPacket($func_packet, $func_append);
      return true;
    }/**/
  }

?>