<?php
  
  namespace iCPro\Games;
  require_once 'GameType.php';
  
  abstract class RoomGame extends GameType {
    public $room;
    
    public function addPlayer(\iCPro\Users\User &$user) {}
    public function removePlayer(\iCPro\Users\User &$user) {}
    
    public function sendPacket($packet) { $this->room->sendPacket($packet); }
  }

?>