<?php
  
  namespace iCPro\Games;
  require_once 'CustomGame.GameType.php';
  
  GameManager::AddGame('SledRace');
  final class SledRace extends CustomGame {
    public static $index = 0;
    public static $games = array();
    public static $waddles = array(103 => array('', ''), 102 => array('', ''), 101 => array('', '', ''), 100 => array('', '', '', ''));
    public static $users = array();
    public static $links = array();
    
    public static function HandleUserLeft(&$user) {
      return self::LeaveWaddle($user);
    }
    
    public static function IsGame($gameId) {
      return $gameId == 230 || $gameId == 999;
    }
    
    public static function HandlePacket(&$user, $command, $packet) { switch($command) {
      case 'gw': return static::GetWaddle($user, $packet);
      case 'jw': return static::JoinWaddle($user, $packet);
      case 'lw': return static::LeaveWaddle($user, $packet);
      case 'zm': return static::SendMove($user, $packet);
      case 'jz': return static::JoinGame($user, $packet);
      case 'zo': return Game::HandlePacket($user, $command, $packet);
      
      default: return \iCPro\Servers\dismiss_selector();
    }}
    
    public static function GetWaddle(&$user, $packet) {
      $response = ''; array_shift($packet);
      foreach(self::$waddles as $waddleId => $waddle) $response .= $waddleId . '|' . join(',', $waddle) . '%';
      return $user->sendPacket("%xt%gw%{$user->currentRoom}%{$response}");
    }
    
    public static function JoinWaddle(&$user, $packet) {
      self::LeaveWaddle($user);
      
      $waddleId = (integer) $packet[1];
      $playerId = count(self::$users[$waddleId]);
      
      self::$users[$waddleId][$playerId]   = &$user;
      self::$waddles[$waddleId][$playerId] = $user->name;
      
      if($playerId == count(self::$waddles[$waddleId]) - 1) self::FlushWaddle($user->room, $waddleId);
      $user->sendPacket("%xt%jw%{$user->currentRoom}%{$playerId}%");
      
      return $user->sendRoomPacket("%xt%uw%-1%{$waddleId}%{$playerId}%{$user->getPlayerName()}%");
    }
    
    public static function LeaveWaddle(&$user) {
      foreach(self::$users as $wId => $waddle) foreach($waddle as $uId => $wUser) if($wUser == $user) {
        $user->sendRoomPacket("%xt%uw%-1%{$wId}%{$uId}%%");
        
        self::$waddles[$wId][$uId] = '';
        unset(self::$users[$wId][$uId]);
      }
      
      $arr = &self::$games[self::$links[$user->id]];
      foreach($arr ?: array() as $uId => $u) if($user == $u) unset($arr[$uId]);
    }
     
    public static function FlushWaddle($roomId, $waddleId) {
      self::$index = (self::$index + 1) % 16384;
      $uCount = count(self::$users[$waddleId]);
      
      self::$games[self::$index] = self::$users[$waddleId];
      
      foreach(self::$waddles[$waddleId] as &$f) $f = '';
      foreach(self::$users[$waddleId] as $user) {
        self::$links[$user->id] = self::$index;
        $user->sendPacket("%xt%sw%{$user->currentRoom}%999%" . self::$index . "%{$uCount}%");
      }
      
      self::$users[$waddleId] = array();
    }
    
    public static function JoinGame(&$user, $packet) {
      $playerStrings = array();
      foreach(self::$games[self::$links[$user->id]] ?: array() as $u) {
        $sled = 15007;
        
        list($color) = explode('|', $u->dressing);
        $playerStrings[] = "{$u->getPlayerName()}|{$color}|{$sled}|{$u->name}";
      }
      
      return $user->sendPacket('%xt%uz%-1%' . count($playerStrings) . '%' . join('%', $playerStrings) . '%');
    }
    
    public static function SendMove(&$user, $packet) {
      foreach(self::$games[self::$links[$user->id]] ?: array() as $u) $u->sendPacket('%xt%zm%' . join('%', $packet) . '%');
    }
  }
  
?>