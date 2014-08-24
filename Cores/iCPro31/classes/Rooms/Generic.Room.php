<?php

  namespace iCPro\Rooms;
  require_once 'Room.php';
  
  class Generic extends Room {
    public static $ref = array(); // Reference to preloaded rooms by names.
    
    public $genericId;
    public $key, $name, $displayName, $musicId, $isMember, $path, $maxUsers, $jumpEnabled, $jumpDisabled, $requiredItem, $shortName;
    public function __construct($roomId) {
      $this->genericId = $roomId;
      $crumb = GameConfig::$d[$roomId];
      
      if(is_null($crumb)) {
        var_dump("TODO: This should be a Debugger-statement, also: There was a bug.");
      } else {
        $this->key = $crumb->room_key;
        $this->name = $crumb->name;
        $this->displayName = $crumb->display_name;
        $this->musicId = $crumb->music_id;
        $this->isMember = $crumb->isMember != 0;
        $this->path = $crumb->path;
        $this->maxUsers = $crumb->max_users;
        $this->jumpEnabled = $crumb->jump_enabled;
        $this->jumpDisabled = $crumb->jump_disabled;
        $this->requiredItem = $crumb->required_item;
        $this->shortName = $crumb->short_name;
      }
    }
    
    public function sendJoinPacket(&$player) {
      $player->sendPacket("%xt%jr%{$player->room}%{$this->genericId}%" . $this->serializePlayersFor($player));
      $this->sendPacket("%xt%ap%{$this}%{$player->string}%");
    }
    
    protected function canUnload() {
      return is_null($name); // If this wasn't preloaded, you can unload it.
    }
    
    public function getFriendlyName() {
      return is_null($name) ? "Generic:{$this}" : $this->name;
    }
    
    public function getFriendlyId() {
      return $genericId;
    }
  }
  
  // TODO: Read "rooms.xml" here
  
  /*
  function reference_generic($name, $roomId, $realId) {
    $room = new Generic($roomId); // TODO: What about deallocating rooms?
    $room->id = $realId;
    $room->name = $name;
    
    Room::Cache($room, array( $roomId ));
    RoomManager::Register($room);
    
    return Generic::$ref[$name] = $room;
  }
  
  foreach(\iCPro\GameConfig::$d['rooms'] as $room) reference_generic($room->room_id);
  
  $stadium = reference_generic('stadium', 802, 802); // TODO: What was the smartfox-id?
  $stadium->bindGame(new \iCPro\Games\Hockey());
  
  $lodge = reference_generic('lodge', 220, 15); // TODO: What was this?
  for($i = 205; $i <= 207; ++$i) $lodge->bindTable(new \iCPro\Games\FindFour($i, 2));
  
  $attic = reference_generic('attic', 221, );
  for($i = 200; $i <= 204; ++$i) $lodge->bindTable(new \iCPro\Games\FindFour($i, 2));
  */
  
?>