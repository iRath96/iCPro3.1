<?php

  interface Events {
    const SERVER_READY        = 'onReady';
    const DEBUG_MESSAGE       = 'onDebug';
    const XML_ERROR           = 'onXMLError';
    const CONFIG_LOADED       = 'onConfigLoaded';
    const NEW_CLIENT          = 'onNewClient';
    const RAW_PACKET_RECV     = 'onRawPacket';
    const PACKET_RECEIVED     = 'onReceivedPacket';
    const PACKET_SENT         = 'onPacketSent';
    const CLIENT_DISCONNECTED = 'onDisconnectedClient';
    const CLIENT_REMOVED      = 'onClientRemoved';
  }
  
  final class EventListener {
    static public $events;
    static public function AddListener($func_event, $func_callback, $func_id = false) {
      return CoreUtils::AppendArray(static::$events[$func_event], $func_callback, $func_id);
    }
    
    static public function RemoveListener($func_event, $func_callback) {
      if((is_integer($func_callback) || is_string($func_callback)) and isset(static::$events[$func_event][$func_callback])) {
        unset(static::$events[$func_event][$func_callback]);
        return true;
      }
      
      $func_delete = array();
      foreach(static::$events[$func_event] as $func_index => $func_value) if($func_value == $func_callback) $func_delete[] = $func_index;
      foreach($func_delete as $func_index) unset(static::$events[$func_event][$func_index]);
      
      return false;
    }
    
    static public function FireEvent($func_event) {
      if(!static::$events[$func_event]) return false;
      foreach(static::$events[$func_event] as $func_callback) call_user_func_array($func_callback, func_get_args());
      return true;
    }
  }

?>