<?php
  
  namespace iCPro\Rooms;
  abstract class Room {
    public $id = null, $players = array();
    public $game = null, $tables = array();
    public function __toString() { return "{$this->id}"; } // For backward-compatibility with "%xt%{$player->room}%..."
    public function __get($name) {
      if($name == 'friendlyId') return static::getFriendlyId();
      if($name == 'friendlyName') return static::getFriendlyName();
      \iCPro\Akwaya::Notice('Room::__get', "cannot resolve \${$name}!");
      return null;
    }
    
    public function getFriendlyName() { return "Room<{$this->id}>"; }
    public function getFriendlyId() { return $id; }
    
    public function bindGame(\iCPro\Games\RoomGame $game) {
      $game->room = $this;
      $this->game = $game;
    }
    
    public function bindTable(\iCPro\Games\TableGame $table) {
      $table->room = $this;
      $this->tables[$table->id] = $table;
    }
    
    public function isFull() {
      return count($this->players) >= \iCPro\SettingsManager::GetSetting(\iCPro\Settings::ROOM_LIMIT);
    }
    
    public function addPlayer(\iCPro\Users\User &$player) {
      if($player->room) $player->room->removePlayer($player);
      
      $this->players[$player->id] = $player;
      $player->game = $this->game;
      
      $this->sendJoinPacket($player);
    }
    
    public function removePlayer(\iCPro\Users\User &$player) {
      unset($this->players[$player->id]);
      
      $player->game = null;
      $this->sendLeavePacket($player);
      
      if(count($this->players) == 0 && static::canUnload()) $this->unload();
    }
    
    public function sendPacket($packet, $appendNUL = true) {
      foreach($this->players as $player) $player->sendPacket($packet, $appendNUL);
    }
    
    public function sendModPacket($packet, $appendNUL = true) {
      foreach($this->players as $player) if($player->isMod) $player->sendPacket($packet, $appendNUL);
    }
    
    public function sendJoinPacket(\iCPro\Users\User &$player) { $this->sendPacket("%xt%ap%{$this}%{$player->string}%"); }
    public function sendLeavePacket(\iCPro\Users\User &$player) { $this->sendPacket("%xt%rp%-1%{$player->id}%"); }
    
    public function serializePlayersFor(\iCPro\Users\User &$requestor) {
      $ret = '';
      foreach($this->players as $player) {
        if($player->isHidden && !$requestor->isMod) continue;
        //if($player->isHidden) $hstr = str_replace($user->playerName, "{$user->playerName} // Hidden", $user->string);
        $ret .= $player->string . '%'; // TODO: ($user->isHidden ? $hiddenString : $user->string) . '%';
      } return $ret;
    }
    
    protected function canUnload() {
      return true;
    }
    
    protected function unload() {
      RoomManager::Unregister($this);
    }
    
    // Static stuff.
    
    public static function Cache(\iCPro\Rooms\Room $room, array $args) {
      $hash = join('#', array_merge(array( get_class($room) ), $args)); // See ::Load
      self::$rooms[$hash] = $room;
    }
    
    private static $rooms = array();
    public static function Load() { // Attention! Makes heavy use of late static binding and reflection!
      $hash = join('#', array_merge(array( get_called_class() ), func_get_args())); // Arguments may not cotain "#". Arguments may contain nuts.
      if(!is_null(self::$rooms[$hash])) return self::$rooms[$hash];
      
      $reflection = new \ReflectionClass(get_called_class());
      $room = $reflection->newInstanceArgs(func_get_args());
      
      self::Cache($room, func_get_args());
      RoomManager::Register($room);
      
      return $room;
    }
  }

?>