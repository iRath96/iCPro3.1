<?php
  
  namespace iCPro;
  
  final class wModUser {
    public $ip, $port;
    public $clientID;
    public $socket;
    
    public function __construct($func_id, $func_sock) { $this->clientID = $func_id; $this->socket = $func_sock; socket_getpeername($this->socket, $this->ip, $this->port); }
    public function __destruct() { @fclose($this->socket); }
    public function __toString() { return "<b>Apache</b> @ #{$this->clientID}:{$this->ip}"; }
    
    # # # # # # # # # # # # # # # # # # # PS: Run iCP as "nobody"?
    # And I have to check my EMails! :) # PS: How about disabling shell_exec?
    # # # # # # # # # # # # # # # # # # # PS: How about WebCLI?
    # abstract public function checkIP();
    
    public function handlePacket($func_packet) {
      global $SERVER;
      
      Debugger::Debug(DebugFlags::J_FINE, sprintf('<inv>%s</inv> sent <b>%s</b>...', $this, $func_packet));
      if(!($this->ip == LOCAL_IP || $this->ip == GLOBAL_IP || $this->ip == gethostbyname(TEAM_IP))) $this->sendPage('NOT_HOST');
      else {
        $func_user;
        
        $func_data = explode('%', $func_packet); array_shift($func_data); array_shift($func_data);
        $func_ip   = array_shift($func_data);
        $func_username   = strtolower(array_shift($func_data));
        $func_sessionKey = array_shift($func_data);
        foreach($SERVER->users as $func_u) if(strtolower($func_u->playerName) == $func_username) { $func_user = $func_u; break; }
        
        if(!$func_user)                                  $this->sendPage('NOT_FOUND');
        elseif(!$func_user->isMod)                       $this->sendPage('NOT_MOD');
        elseif($func_user->ip == $func_ip)               $this->sendPage('NOT_SAME');
        elseif(!$func_s = $func_user->retrieveSession()) $this->sendPage('NOT_SET');
        elseif($func_s != $func_sessionKey)              $this->sendPage('NOT_RIGHT');
        else $this->handleCommand($func_data, $func_user);
      }
      
      unset($SERVER->wmods[$this->clientID]);
      return iServer::RemoveClient($this->clientID);
    }
    
    public function handleCommand($func_data, $func_user) { $func_command = array_shift($func_data); global $SERVER; switch(strtoupper($func_command)) {
      case 'REHASH': return $SERVER->rehash() ? $this->sendPage('REHASHED') : $this->sendPage('NOT_REHASHED');
      case 'LOGIN':  return $this->sendPage('LOGIN_SUCCESSFUL', $func_user->playerName, SettingsManager::GetSetting(Settings::MODERATOR_TTL) - (time() - $func_user->modAge));
    }}
    
    public function sendPage($func_page) {
      return $this->sendPacket('%xt%pg%' . join('%', func_get_args()) . '%');
    }
    
    public function sendPacket($func_packet, $func_append = true) {
      global $SERVER;
      
      Debugger::Debug(DebugFlags::J_FINE, sprintf('<inv>%s</inv> will receive <b>%s</b>...', $this, $func_packet));
      return socket_write($this->socket, $func_append ? $func_packet . iServer::$escapeChar : $func_packet, strlen($func_packet) + ((integer) $func_append)) ||
       $SERVER->handleDisconnect($this->clientID);
    }
  }

?>