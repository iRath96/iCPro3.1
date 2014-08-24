<?php
    
  namespace iCPro\Games;
  require_once 'GameType.php';
  
  GameManager::AddGame('SinglePlayer');
  
  // TODO: This OOP is fucked up.
  class SinglePlayer extends GameType {
    public static $games = array();
    
    public static function HandleUserLeft(&$func_user) {}
    public static function IsGame($func_gameID) {
      return $func_gameID >= 900 && $func_gameID < 1000; //Game Rooms from 900
    }
    
    public static function HandlePacket(&$func_user, $func_command, $func_packet) { switch($func_command) {
      case 'zo': return $func_user->addCoins(round(static::ConvertCoins((integer) $func_packet[1], (integer) $func_packet[0])), (integer) $func_packet[0]);
      default:   return \iCPro\Servers\dismiss_selector();
    }}
    
    public static function ConvertCoins($func_amount, $func_gameID) { switch($func_gameID) {
      case 901: return $func_amount / 10; //Bean Counters - Divide by 10
      case 999: return $func_amount * 10; //Sled Race - Multiply by 10
      default:  return $func_amount;
    }}
    
    public static function HandleJoinWaddle(&$func_user, $func_packet) {
      return $func_user->sendPacket("%xt%jx%{$func_packet[0]}%{$func_packet[1]}%");
    }
  }
  
?>