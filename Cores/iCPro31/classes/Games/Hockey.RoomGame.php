<?php
  
  namespace iCPro\Games;
  require_once 'RoomGame.GameType.php';
  
  final class Hockey extends RoomGame {
    public $status = '0%0%0%0';
    
    public function handlePacket(&$user, $command, $packet) { switch($command) {
      case 'gz': $user->sendPacket('%xt%gz%802%' . $this->status . '%');
      case 'm':  $this->hitPuck($user, $packet);
      default:   \iCPro\Servers\dismiss_selector();
    }}
    
    public function hitPuck($user, $packet) {
      $this->status = join('%', array_map(function($v) { return (integer)$v; }, array_slice($packet, 2)));
      $this->sendPacket('%xt%zm%802%' . $user->id . '%' . $this->status . '%');
    }
  }
  
?>