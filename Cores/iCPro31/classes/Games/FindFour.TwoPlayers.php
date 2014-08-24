<?php
  
  namespace iCPro\Games;
  
  require_once 'TwoPlayers.GameType.php';
  
  GameManager::AddGame('FindFour');
  final class FindFour extends TwoPlayers {
    public static function IsGame($func_gameID) {
      return $func_gameID > 199 && $func_gameID < 208; //Tables are 200, 201, 202, 203, ..., 205, 206, 207
    }
    
    public static function CheckGame(&$func_game) {
      if(count($func_game) < 3) $func_game = array('', '', '0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0');
    }
    
    public static function HandleMove(&$func_user, $func_packet, $func_gameID, $func_playerId) {
      $func_x = (integer) $func_packet[1];
      $func_y = (integer) $func_packet[2];
      static::$games[$func_gameID][2]{$func_x * 12 + $func_y * 2} = $func_playerId + 1;
      
      static::CheckGameOver($func_x, $func_y, $func_gameID);
      return static::SendTablePacket($func_gameID, "%xt%zm%{$func_gameID}%{$func_playerId}%{$func_x}%{$func_y}%");
    }
    
    public static function CheckGameOver($func_x, $func_y, $func_gameID) {
      $func_a = static::CheckLine($func_x, $func_y, 0,  1, $func_gameID) + static::CheckLine($func_x, $func_y,  0, -1, $func_gameID) - 1;
      $func_b = static::CheckLine($func_x, $func_y, 1,  0, $func_gameID) + static::CheckLine($func_x, $func_y, -1,  0, $func_gameID) - 1;
      $func_c = static::CheckLine($func_x, $func_y, 1,  1, $func_gameID) + static::CheckLine($func_x, $func_y, -1, -1, $func_gameID) - 1;
      $func_d = static::CheckLine($func_x, $func_y, 1, -1, $func_gameID) + static::CheckLine($func_x, $func_y, -1,  1, $func_gameID) - 1;
      
      if($func_a < 4 && $func_b < 4 && $func_c < 4 && $func_d < 4) return false;
      global $SERVER;
      
      $func_p1Won = static::$games[$func_gameID][2]{$func_x * 12 + $func_y * 2} == 1;
      $func_p1Won ? ($func_p1Coins = 10) . ($func_p2Coins = 5):
                    ($func_p2Coins = 10) . ($func_p1Coins = 5);
                    
      $func_a = static::$users[$func_gameID][0];
      $func_b = static::$users[$func_gameID][1];
      
      if($SERVER->alias[$func_a]) $SERVER->alias[$func_a]->addCoins($func_p1Coins, $func_gameID);
      if($SERVER->alias[$func_b]) $SERVER->alias[$func_b]->addCoins($func_p2Coins, $func_gameID);
      
      static::ClearGame($func_gameID);
      return static::SendTablePacket($func_gameID, 'The Game has ended and the Programmer didnt know the right Packet for this. Sorry - Much Apologies - Alex');
    }
    
    public static function CheckLine($func_x, $func_y, $func_vX, $func_vY, $func_gameID) {
      $func_count = 0;
      $func_board = static::$games[$func_gameID][2];
      $func_init = $func_board{$func_x * 12 + $func_y * 2};
      while(!($func_x < 0 || $func_x > 7 || $func_y < 0 || $func_y > 6)) {
        if($func_board{$func_x * 12 + $func_y * 2} != $func_init) return $func_count;
        ++$func_count;
        $func_x += $func_vX;
        $func_y += $func_vY;
      }
      
      return $func_count;
    }
  }
  
?>