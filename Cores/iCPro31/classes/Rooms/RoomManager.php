<?php

  namespace iCPro\Rooms;
  
  class RoomManager {
    public static $rooms = array();
    private static $roomIndex = 2000; // Start at 2000 for dynamic rooms.
    
    public static function RoomForId(integer $id) {
      return RoomManager::$rooms[$id];
    }
    
    public static function Register(\iCPro\Rooms\Room $room) {
      if(is_null($room->id)) $room->id = self::$roomIndex++; // Register a new room, don't care about the id
      else if(!is_null(self::$rooms[$room->id]))
        Debugger::Debug(DebugFlags::WARNING, sprintf('RoomManager::Register: Replacing an existing room (id=%d)', $room->id));
      self::$rooms[$room->id] = $room;
    }
    
    public static function Unregister(\iCPro\Rooms\Room $room) {
      unset(self::$rooms[$room->id]);
    }
  }
  
?>