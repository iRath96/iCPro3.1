<?php
  
  namespace iCPro\Games;
  
  require_once 'TwoPlayers.GameType.php';
  
  GameManager::AddGame('Mancala');
  final class Mancala extends TwoPlayers {
    public static function IsGame($func_gameID) {
      return $func_gameID > 99 && $func_gameID < 105; //Tables are 100, 101, 102, 103, 104
    }
    
    public static function CheckGame(&$func_game) {
      if(count($func_game) < 3) $func_game = array('', '', '4,4,4,4,4,4,0,4,4,4,4,4,4,0');
    }
    
    public static function HandleMove(&$func_user, $func_packet, $func_gameID, $func_playerID) {
      $func_f       = $func_fOriginal = (integer) $func_packet[1];// + $func_playerID * 7;
      $func_fields  = explode(',', static::$games[$func_gameID][2]);
      $func_fAmount = $func_fields[$func_f];
      
      $func_fields[$func_f] = 0;
      for($func_i = 0; $func_i < $func_fAmount; ++$func_i) ++$func_fields[($func_f = ($func_f + 1) % 14) == 13 - $func_playerID * 7 ? $func_f = ($func_f + 1) % 14 : $func_f];
      
      /* This space is for rent, too! :) */ $func_type = 'd';
      if($func_f == 6 + $func_playerID * 7) $func_type = 'f';
      if($func_fields[$func_f] == 1 && (($func_playerID && $func_f > 6 && $func_f != 13) || (!$func_playerID && $func_f < 6))) $func_type = 'q';
      
      switch($func_type) {
        case 'q': {
          $func_oField = 12 - $func_f;
          $func_fields[6 + $func_playerID * 7] += $func_fields[$func_f] + $func_fields[$func_oField];
          $func_fields[$func_f] = $func_fields[$func_oField] = 0;
        } break;
        case 'f': static::$games[$func_gameID][3] = 1 - static::$games[$func_gameID][3]; break;
      }
      
      OUTPUT_MANCALA_FOR_DEBUG; {
        echo "\n\n\033[1m";
        echo "\t"  . strrev(join(',', array_intersect_key($func_fields, array_flip(range(7, 13))))) . "\n";
        echo "\t " .        join(',', array_intersect_key($func_fields, array_flip(range(0,  6))))  . "\n";
        echo "\033[0m\n\n";
      }
      
      static::$games[$func_gameID][2] = join(',', $func_fields);
      static::CheckGameOver($func_gameID);
      return static::SendTablePacket($func_gameID, "%xt%zm%{$func_gameID}%{$func_playerID}%{$func_fOriginal}%{$func_type}%");
    }
    
    public static function CheckGameOver($func_gameID) {
      global $SERVER;
      
      $func_board = explode(',', static::$games[$func_gameID][2]);
      $func_a = join(',', array_intersect_key($func_board, array_flip(range(0, 5))));
      $func_b = join(',', array_intersect_key($func_board, array_flip(range(7, 12))));
      
      var_dump($func_a, $func_b);
      if($func_a != '0,0,0,0,0,0' && $func_b != '0,0,0,0,0,0') return;
      
      $func_p1Won = $func_board[6] > $func_board[13];
      $func_p1Won ? ($func_p1Coins = 50) . ($func_p2Coins = 25):
                    ($func_p2Coins = 50) . ($func_p1Coins = 25);
                    
      $func_a = static::$users[$func_gameID][0];
      $func_b = static::$users[$func_gameID][1];
      
      if($SERVER->alias[$func_a]) $SERVER->alias[$func_a]->addCoins($func_p1Coins, $func_gameID);
      if($SERVER->alias[$func_b]) $SERVER->alias[$func_b]->addCoins($func_p2Coins, $func_gameID);
      
      static::ClearGame($func_gameID);
      return static::SendTablePacket($func_gameID, 'The Game has ended and the Programmer didnt know the right Packet for this. Sorry - Much Apologies - Alex');
    }
  }
  
?>