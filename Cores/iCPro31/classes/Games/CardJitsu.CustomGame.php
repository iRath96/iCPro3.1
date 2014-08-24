<?php
  
  namespace iCPro\Games;
  require_once 'CustomGame.GameType.php';
  
  GameManager::AddGame('CardJitsu');
  final class CardJitsu extends CustomGame {
    public static $waiting = array();
    
    public static function HandleUserLeft(&$func_user) {
      
    }
    
    public static function IsGame($func_gameId) {
      return $func_gameId == 92 || $func_gameId == 998 || $func_gameId == 951;
    }
    
    public static function HandlePacket(&$func_user, $func_command, $func_packet) { switch($func_command) {
      case 'jmm': self::AddWaiting($func_user, $func_packet); break;
      default: return \iCPro\Servers\dismiss_selector();
    }}
    
    public static function AddWaiting(&$func_user, $func_packet) {
      $func_user->sendPacket("%xt%jmm%0%{$func_user->playerName}%");
      
      self::$waiting[] = $func_user;
      if(count(self::$waiting) == 2) {
        list($func_a, $func_b) = self::$waiting;
        
        $func_a->sendPacket("%xt%tmm%0%-1%{$func_a->playerName}%{$func_b->playerName}%");
        $func_b->sendPacket("%xt%tmm%0%-1%{$func_a->playerName}%{$func_b->playerName}%");
        
        $func_a->sendPacket("%xt%scard%0%998%26775%2%");
        $func_b->sendPacket("%xt%scard%0%998%26775%2%");
        
        self::$waiting = array();
      }
    }
    
    public static function HandleJoinWaddle(&$func_user, $func_packet) {
      $func_waddleId = 177031;
      return $func_user->sendPacket("%xt%jx%{$func_waddleId}%{$func_packet[1]}%");
    }
    
    public static function GetWaddle(&$func_user, $func_packet) {
      
    }
    
    public static function JoinWaddle(&$func_user, $func_packet) {
      
    }
    
    public static function LeaveWaddle(&$func_user) {
      
    }
     
    public static function FlushWaddle($func_roomID, $func_waddleID) {
      
    }
    
    public static function JoinGame(&$func_user, $func_packet) {
      
    }
    
    public static function SendMove(&$func_user, $func_packet) {
      
    }
  }
  
?>