<?php
  
  namespace iCPro\Games;
  
  require_once 'GameType.php';
  abstract class TwoPlayers extends GameType {
    public static $games = array();
    public static $users = array();
    
    public static $currentTurnOffset = 3;
    public static function HandlePacket(&$func_user, $func_command, $func_packet) { switch($func_command) {
      case 'gz': return static::GetGame($func_user, $func_packet);
      case 'jz': return static::JoinGame($func_user, $func_packet);
      case 'lz': return static::CloseGame($func_user, $func_packet);
      case 'zm': return static::SendMove($func_user, $func_packet);
      
      default: return \iCPro\Servers\dismiss_selector();
    }}
    
    public static function HandleUserLeft(&$func_user) {
      foreach(static::$games as $func_gameId => $func_data) static::CloseGame($func_user, array($func_gameId));
      return true;
    }
    
    public static function CloseGame(&$func_user, $func_packet) {
      echo "CloseGame\n";
      
      $func_gameId = (integer) $func_packet[0];
      
      $func_playerId;
      foreach(static::$users[$func_gameId] as $func_playerId => $func_realId) if($func_realId == $func_user->id) break;
      if($func_realId != $func_user->id || $func_playerId > 1) return false;
      
      static::ClearGame($func_gameId);
      return static::SendTablePacket($func_gameId, "%xt%cz%-1%{$func_user->getPlayerName()}%");
    }
    
    public static function ClearGame($func_gameId) {
      static::$games[$func_gameId] = array();
      static::$users[$func_gameId] = array();
    }
    
    public static function GetGame(&$func_user, $func_packet) {
      static::CheckGame(static::$games[$func_packet[0] = (integer) $func_packet[0]]);
      $func_user->sendPacket('%xt%gz%-1%' . join('%', static::$games[$func_packet[0]]) . '%');
    }
    
    public static function JoinGame(&$func_user, $func_packet) {
      $func_gameId = (integer) $func_packet[0];
      static::$users[$func_gameId][] = $func_user->id;
      
      $func_playerId = count(static::$users[$func_gameId]) - 1;
      $func_user->sendPacket("%xt%jz%-1%{$func_playerId}%");
      
      $func_game = &static::$games[$func_gameId];
      static::CheckGame($func_game);
      
      if($func_playerId < 2) {
        $func_game[$func_playerId] = $func_user->getPlayerName();
        static::SendTablePacket($func_gameId, "%xt%uz%-1%{$func_playerId}%{$func_user->getPlayerName()}%");
      }
      
      if($func_playerId == 1) {
        $func_game[static::$currentTurnOffset] = 0;
        static::SendTablePacket($func_gameId, static::GetJoinPacket($func_gameId, $func_playerId, $func_user));
      }
      
      return true;
    }
    
    public static function GetJoinPacket($func_gameId, $func_playerId, $func_user) {
      return "%xt%sz%{$func_gameId}%0%";
    }
    
    public static function SendMove(&$func_user, $func_packet) {
      $func_gameId = (integer) $func_packet[0];
      
      if(static::$users[$func_gameId][0] == $func_user->id)     $func_playerId = 0;
      elseif(static::$users[$func_gameId][1] == $func_user->id) $func_playerId = 1;
      else return false;
      
      if(static::$games[$func_gameId][static::$currentTurnOffset] != $func_playerId) return false;
      static::$games[$func_gameId][static::$currentTurnOffset] = 1 - static::$games[$func_gameId][static::$currentTurnOffset];
      
      return static::HandleMove($func_user, $func_packet, $func_gameId, $func_playerId);
    }
    
    //[TODO]: Not always compatible - fix that!
    //abstract public static function HandleMove(&$func_user, $func_packet, $func_gameId, $func_playerId);
    //abstract public static function CheckGame(&$func_game);
    //abstract public static function CheckGameOver($func_gameId);
  }

?>