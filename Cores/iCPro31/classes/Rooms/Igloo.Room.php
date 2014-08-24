<?php
  
  namespace iCPro\Rooms;
  require_once 'Room.php';
  
  interface IglooAreas {
    const IGLOO    = 'igloo';
    const BACKYARD = 'backyard';
  }
  
  class Igloo extends Room {
    public $playerId, $area;
    public function __construct() {
      list($this->playerId, $this->area) = func_get_args();
    }
    
    public function sendJoinPacket(&$player) {
      $player->sendPacket("%xt%jp%{$player->room}%1967%{$player->id}%{$this->area}%");
      $player->sendPacket("%xt%jr%{$player->room}%1967%" . $this->serializePlayersFor($player));
      $this->sendPacket("%xt%ap%{$this}%{$player->string}%");
    }
  }

?>