<?php

  iServer::Init();
  final class iServer {
    const DEFAULT_QUEUE   = 10;
    const DEFAULT_BUFFER  = 4096;
    const DEFAULT_ESCCHAR = "\n";
    
    public static $bufferSize;
    public static $escapeChar;
    public static $escapeLen;
    public static $queue;
    
    public static $sockets;
    public static $clients;
    public static $additional;
    public static $index;
    
    public static function Init($func_bufferSize = self::DEFAULT_BUFFER, $func_escapeChar = self::DEFAULT_ESCCHAR) {
      self::$bufferSize = $func_bufferSize;
      self::$escapeChar = $func_escapeChar;
      self::$escapeLen  = strlen($func_escapeChar);
    }
    
    public static function SendGlobalPacket($func_packet, $func_append = true) {
      if($func_append) $func_packet .= self::$escapeChar;
      $func_length = strlen($func_packet);
      
      foreach(self::$clients as $func_clientID => $func_client)
      if(!self::$additional[$func_clientID]['isServer']) @socket_write($func_client, $func_packet, $func_length);
      
      return true;
    }
    
    public static function SendPacket($func_clientID, $func_packet, $func_append = true) {
      if($func_append) $func_packet .= self::$escapeChar;
      return @socket_write(self::$clients[$func_clientID], $func_packet, strlen($func_packet));
    }
    
    public static function AddSocket($func_ip) {
      if(!self::$bufferSize) return Debugger::Debug(DebugFlags::SEVERE, 'Trying to add a Socket without having iServer initialized yet!');
      
      $func_id = join(':', func_get_args());
      if(func_num_args() == 1) self::$sockets[$func_id] = $func_ip;
      else {
        $func_port = func_get_arg(1);
        $func_queue = func_num_args() > 2 ? func_get_arg(2) : self::DEFAULT_QUEUE;
        
        self::$sockets[$func_id] = socket_create(AF_INET, SOCK_STREAM, 0)        or Debugger::Debug(SEVERE, "Unable to create Socket"            . self::GetSocketError());
        socket_set_option(self::$sockets[$func_id], SOL_SOCKET, SO_REUSEADDR, 1) or Debugger::Debug(SEVERE, "Cannot reuse Address"               . self::GetSocketError());
        socket_bind(self::$sockets[$func_id], $func_ip, $func_port)              or Debugger::Debug(SEVERE, "Unable to bind Socket to $func_id " . self::GetSocketError());
        socket_listen(self::$sockets[$func_id], $func_queue)                     or Debugger::Debug(SEVERE, "Unable to listen on Socket"         . self::GetSocketError());
      }
      
      self::$clients[$func_id] = &self::$sockets[$func_id];
      self::$additional[$func_id] = array('isServer' => true);
      return each(self::$clients);
    }
    
    public static function RemoveSocket($func_id) {
      @socket_shutdown(self::$sockets[$func_id]);
      unset(self::$sockets[$func_id]);
      return true;
    }
    
    public static function RemoveClient($func_cID) {
      EventListener::FireEvent(Events::CLIENT_REMOVED, $func_cID);
      
      @socket_shutdown(self::$clients[$func_cID]);
      unset(self::$clients[$func_cID]);
      unset(self::$additional[$func_cID]);
      
      return true;
    }
    
    private static function GetSocketError() {
      return socket_strerror(socket_last_error()) . ' [' . socket_last_error() . ']';
    }
    
    public static function Update() {
      $func_read = self::$clients;
      $func_delete = array();
      
      if($func_read and @socket_select($func_read, $func_write, $func_except, 0))
      if($func_read) foreach($func_read as $func_key => $func_socket) {
        if(in_array($func_socket, self::$sockets)) self::HandleClient($func_key, socket_accept($func_socket));
        else {
          $func_data = @socket_read($func_socket, self::$bufferSize);
          if(!strlen($func_data)) $func_delete[] = $func_key;
          else self::HandlePacket($func_key, $func_data);
        }
      }
      
      return $func_delete ? self::HandleDisconnect($func_delete) : true;
    }

    private static function HandleClient($func_id, $func_client) {
      self::$clients[++self::$index]  = $func_client;
      self::$additional[self::$index] = array('isServer' => false, 'Buffer' => '');
      
      EventListener::FireEvent(Events::NEW_CLIENT, $func_id, self::$index, $func_client);
    }
    
    private static function HandlePacket($func_cID, $func_data) {
      EventListener::FireEvent(Events::RAW_PACKET_RECV, $func_cID, $func_data);
      
      $func_buffer  = &self::$additional[$func_cID]['Buffer'];
      $func_buffer .= $func_data;
      $func_packets = explode(self::$escapeChar, $func_buffer);
      $func_buffer  = array_pop($func_packets);
      
      foreach($func_packets as $func_packet) EventListener::FireEvent(Events::PACKET_RECEIVED, $func_cID, $func_packet);
    }
    
    private static function HandleDisconnect($func_cIDs) {
      foreach($func_cIDs as $func_cID) {
        EventListener::FireEvent(Events::CLIENT_DISCONNECTED, $func_cID);
        
        unset(self::$clients[$func_cID]);
        unset(self::$additional[$func_cID]);
      }
    }
    
    public static function &GetData($func_clientID) {
      return self::$additional[$func_clientID];
    }
  }

?>