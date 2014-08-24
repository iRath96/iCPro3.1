<?php
  
  namespace iCPro\Rooms;
  require_once 'Room.php';
  
  class Game extends Room {
    public $gameId;
    public function __construct($gameId) {
      $this->gameId = $gameId;
      if($gameId >= 900 && $gameId < 1000) $this->roomId = $gameId;
    }
    
    public function sendJoinPacket($player) {
      $player->sendPacket("%xt%jr%{$player->room}%{$this}%" . $this->serializePlayersFor($player)); // wtf?
      $player->sendPacket("%xt%jg%{$player->room}%{$this}%");
    }
    
    public function sendPacket($packet) {
      return false; // We're not sending public packets in game rooms.
    }
  }

?>