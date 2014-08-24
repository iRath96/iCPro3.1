<?php

  abstract class iClient extends Publisher {
    const DEFAULT_BUFFER  = 4096;
    const DEFAULT_ESCCHAR = "\n";
    
    public $bufferSize;
    public $escapeChar;
    
    public $buffer;
    public $socket;
    
    public $useProxy;
    
    public function connect($func_ip, $func_port, $func_escapeChar = self::DEFAULT_ESCCHAR, $func_bufferSize = self::DEFAULT_BUFFER) {
      $this->bufferSize = $func_bufferSize;
      $this->escapeChar = $func_escapeChar;
      $this->socket = $this->useProxy ? @fproxopen($func_ip, $func_port, $eNo, $eStr, 5) : @fsockopen($func_ip, $func_port, $eNo, $eStr, 5);
    }
    
    public function disconnect() {
      fclose($this->socket);
    }
    
    public function update() {
      if(!$this->socket) return;
      if($func_packet = fread($this->socket, $this->bufferSize)) {
        $this->buffer .= $func_packet;
        
        $func_i = 0;
        $func_data = explode($this->escapeChar, $this->buffer);
        
        for($func_j = count($func_data) - 1; $func_i < $func_j; ++$func_i) $this->handlePacket($func_data[$func_i]);
        $this->buffer = $func_data[$func_i];
        
        return true;
      } else return false;
    }
    
    public function sendPacket($func_packet, $func_appendLineBreak = true) {
      if($func_appendLineBreak) $func_packet .= $this->escapeChar;
      return fwrite($this->socket, $func_packet);
    }
    
    abstract public function handlePacket($func_packet);
  }

?>