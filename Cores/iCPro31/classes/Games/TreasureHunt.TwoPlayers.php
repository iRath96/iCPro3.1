<?php
  
  namespace iCPro\Games;
  
  require_once 'TwoPlayers.GameType.php';
  
  GameManager::AddGame('TreasureHunt');
  final class TreasureHunt extends TwoPlayers {
    public static $currentTurnOffset = TreasureGame::TURN_OFFSET;
    
    public static $map;
    public static $coinAmount;
    public static $gemAmount;
    public static $gemLocations;
    
    public static function IsGame($func_gameID) {
      return $func_gameID > 299 && $func_gameID < 308; //Tables are 300, 301, 302, 303, ..., 305, 306, 307
    }
    
    public static function CheckGame(&$func_game) {
      if(count($func_game) < 3) {
        self::RandomizeMap();
        $func_game = array(
          TreasureGame::USER_ONE          => '',
          TreasureGame::USER_TWO          => '',
          TreasureGame::MAP_WIDTH         => 10,
          TreasureGame::MAP_HEIGHT        => 10,
          TreasureGame::COIN_AMOUNT       => self::$coinAmount,
          TreasureGame::GEM_AMOUNT        => self::$gemAmount,
          TreasureGame::TURN_AMOUNT       => 12,
          TreasureGame::GEM_VALUE         => 25,
          TreasureGame::COIN_VALUE        => 1,
          TreasureGame::GEM_LOCATIONS     => substr(self::$gemLocations, 0, -1),
          TreasureGame::TREASURE_MAP      => self::$map,
          TreasureGame::GEMS_FOUND        => 0,
          TreasureGame::COINS_FOUND       => 0,
          TreasureGame::RARE_GEM_FOUND    => 'false',
          TreasureGame::RECORD_NAMES      => '',
          TreasureGame::RECORD_DIRECTIONS => '',
          TreasureGame::RECORD_NUMBERS    => ''
        );
      }
    }
    
    public static function GetJoinPacket($func_gameID, $func_playerID, $func_user) {
      return '%xt%sz%-1%' . join('%', array_intersect_key(static::$games[$func_gameID], range(TreasureGame::USER_ONE, TreasureGame::TREASURE_MAP))) . '%';
    }
    
    public static function HandleMove(&$func_user, $func_packet, $func_gameID, $func_playerID) {
      $func_data      = (integer) substr($func_packet[3], -1);
      $func_direction = $func_playerID ? 'down' : 'right';
      $func_game      = &static::$games[$func_gameID];
      
      if($func_game[TreasureGame::RECORD_NAMES])      $func_game[TreasureGame::RECORD_NAMES]      .= ',';
      if($func_game[TreasureGame::RECORD_DIRECTIONS]) $func_game[TreasureGame::RECORD_DIRECTIONS] .= ',';
      if($func_game[TreasureGame::RECORD_NUMBERS])    $func_game[TreasureGame::RECORD_NUMBERS]    .= ',';
      
      $func_game[TreasureGame::RECORD_NAMES]      .= $func_a = $func_direction . 'button' . $func_data . '_mc';
      $func_game[TreasureGame::RECORD_DIRECTIONS] .= $func_b = $func_direction;
      $func_game[TreasureGame::RECORD_NUMBERS]    .= $func_c = $func_data;
      
      if($func_playerID) {
        $func_x = substr($func_game[TreasureGame::RECORD_NUMBERS], -1);
        $func_y = $func_data;
        
        switch($func_game[TreasureGame::TREASURE_MAP]{$func_x * 10 + $func_y}) {
          case TreasureMap::GEM:
          case TreasureMap::GEM_PIECE: $func_game[TreasureGame::GEMS_FOUND]    += 0.25;   break;
          case TreasureMap::RARE_GEM:  $func_game[TreasureGame::RARE_GEM_FOUND] = 'true'; break;
          case TreasureMap::COIN:    ++$func_game[TreasureGame::COINS_FOUND];             break;
        }
      }
      
      static::CheckGameOver($func_gameID);
      return static::SendTablePacket($func_gameID, "%xt%zm%{$func_gameID}%{$func_a}%{$func_b}%{$func_c}%");
    }
    
    public static function CheckGameOver($func_gameID) {
      if(strlen(static::$games[$func_gameID][TreasureGame::RECORD_NUMBERS]) == 23) {
        //static::SendGamePacket($func_gameID, "%xt%zo%{$func_gameID}%129316%2%15%65%"); //... [TODO] Improve this! ...//
        static::SendTablePacket($func_gameID, 'The Game has ended and the Programmer didnt know the right Packet for this. Sorry - Much Apologies - Alex');
      } else return false;
    }
    
    public static function RandomizeMap() {
      srand(time());
      
      self::$map =
      self::$gemLocations = '';
      self::$coinAmount   =
      self::$gemAmount    = 0;
      $func_originalMap   = array();
      for($func_y = 0; $func_y < 10; ++$func_y) for($func_x = 0; $func_x < 10; ++$func_x) {
        if($func_originalMap[$func_x][$func_y] == TreasureMap::GEM_PIECE) continue;
        if(rand(0, 26) == 13 && $func_x < 9 && $func_y < 9) {
          self::$gemLocations .= "{$func_x},{$func_y},";
          ++self::$gemAmount;
          
          $func_originalMap[$func_x][$func_y] = rand(0, 10) == 1 ? TreasureMap::RARE_GEM : TreasureMap::GEM;
          $func_originalMap[$func_x][$func_y + 1] =
          $func_originalMap[$func_x + 1][$func_y] =
          $func_originalMap[$func_x + 1][$func_y + 1] = TreasureMap::GEM_PIECE;
        } elseif(rand(0, 2) == 1) {
          ++self::$coinAmount;
          $func_originalMap[$func_x][$func_y] = TreasureMap::COIN;
        } else $func_originalMap[$func_x][$func_y] = TreasureMap::NONE;
      }
      
      foreach($func_originalMap as $func_originalRow) foreach($func_originalRow as $func_originalEntry) self::$map .= $func_originalEntry . ',';
      self::$map = substr(self::$map, 0, -1);
    }
  }
  
  interface TreasureGame { //... Actually +1, but GameID doesn't exist in the Main Array ...//
    const GAME_ID           = -1;
    const USER_ONE          = 0;
    const USER_TWO          = 1;
    const MAP_WIDTH         = 2;
    const MAP_HEIGHT        = 3;
    const COIN_AMOUNT       = 4;
    const GEM_AMOUNT        = 5;
    const TURN_AMOUNT       = 6;
    const GEM_VALUE         = 7;
    const COIN_VALUE        = 8;
    const GEM_LOCATIONS     = 9;
    const TREASURE_MAP      = 10;
    const GEMS_FOUND        = 11;
    const COINS_FOUND       = 12;
    const RARE_GEM_FOUND    = 13;
    const RECORD_NAMES      = 14;
    const RECORD_DIRECTIONS = 15;
    const RECORD_NUMBERS    = 16;
    const TURN_OFFSET       = 17;
  }
  
  interface TreasureMap {
    const NONE      = 0;
    const COIN      = 1;
    const GEM       = 2;
    const GEM_PIECE = 3;
    const RARE_GEM  = 4;
  }
  
?>