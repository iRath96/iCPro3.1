<?php
  
  namespace iCPro\Users;
  abstract class User {
    public $server, $clientId;
    public $ip, $port, $socket;
    public $confirmationHash, $randomKey, $loginKey;
    public $lastAction;
    
    // CP properties
    public $id, $name, $password, $age, $coins;
    public $ban, $look, $inventory, $flags;
    public $x, $y, $frame, $stamps, $string;
    public $buddies, $ignores, $mail, $dressing;
    public $buddyRequests; // TODO: Deserialize strings when loading from database!
    
    // Igloo properties
    public $iglooInventory, $activeIglooId;
    
    // Management properties
    public $email, $registrationIP, $lastLogin;
    
    // iCP properties
    public $mood;
    
    // Flags
    const IGNORED_BY_CLIENT = 0;
    public $isConnected = false, $isLoggedIn = false;
    public $isHidden, $isTryout, $isAdmin, $isMod, $isTester, $isBot, $isMuted;
    public $isEPF_A, $isEPF_F, $isAgent = self::IGNORED_BY_CLIENT, $isGuide = self::IGNORED_BY_CLIENT;
    
    // Management
    protected $currentRoom, $currentGame;
    
    # # # # # # # # #
    # Magic methods #
    # # # # # # # # #
    
    public function __construct($clientId, $socket, $server) {
      $this->clientId = $clientId;
      $this->isConnected = true;
      
      $this->socket = $socket;
      $this->server = $server;
      
      $this->lastAction = time();
      socket_getpeername($this->socket, $this->ip, $this->port);
    }
    
    public function __destruct() {
      \iServer::RemoveClient($this->clientId);
    }
    
    public function __get($name) {
      if($name == 'room') return $this->currentRoom;
      if($name == 'game') return $this->currentGame;
      
      if($name == 'swid') return $this->server->generateSWID($this->name, $this->id);
      if(substr($name, 0, 5) == 'igloo') {
        list($items, $floors, $buildings, $locations) = explode('%', $this->iglooInventory);
        switch($name) {
          case 'iglooItems': return $items;
          case 'iglooFloors': return $floors;
          case 'iglooBuildings': return $buildings;
          case 'iglooLocations': return $locations;
        }
      }
      
      \iCPro\Akwaya::Notice('User::__get', "cannot resolve \${$name}!");
      
      return null;
    }
    
    public function __set($name, $value) {
      if($name == 'room') return $this->joinCommon($value);
      if($name == 'game') return is_null($value) ? $this->leaveGame() : $this->joinGame($value);
      if(substr($name, 0, 5) == 'igloo') {
        list($items, $floors, $buildings, $locations) = explode('%', $this->iglooInventory);
        
        $matched = true;
        switch($name) {
          case 'iglooItems': $items = $value; break;
          case 'iglooFloors': $floors = $value; break;
          case 'iglooBuildings': $buildings = $value; break;
          case 'iglooLocations': $locations = $value; break;
          default: $matched = false;
        }
        
        if($matched) {
          $this->iglooInventory = join('%', array( $items, $floors, $buildings, $locations ));
          return;
        }
      }
      
      \iCPro\Akwaya::Notice('User::__set', "cannot resolve \${$name}!");
    }
    
    public function __toString() {
      return "<b>{$this->name} [{$this->id}]</b>";
    }
    
    # # # # # # # # # # # # # # # # #
    # Core Packet Sending Functions #
    # # # # # # # # # # # # # # # # #
    
    public function sendPacket($packet, $append = true) {
      \Debugger::Debug(\DebugFlags::J_FINE, sprintf('<inv>%s</inv> &lt;-- <b>%s</b>', $this, htmlentities($packet)));
      return socket_write($this->socket, $append ? $packet . \iServer::$escapeChar : $packet, strlen($packet) + $append);
    }
    
    public function sendRoomPacket($packet, $append = true) {
      if($this->isHidden) return $this->room->sendModPacket($packet, $append);
      return $this->room->sendPacket($packet, $append);
    }
    
    # # # # # # # # # # # # # # #
    # Packet Sending Functions  #
    # # # # # # # # # # # # # # #
    
    public function sendError() {
      return $this->sendPacket('%xt%e%-1%' . join('%', func_get_args()) . '%');
    }
    
    public function sendPolicyFile() {
      return $this->sendPacket('<cross-domain-policy><allow-access-from domain="*" to-ports="' . SERVER_PORT . '" /></cross-domain-policy>');
    }
    
    public function sendVersionCheck($version) {
      return $version == 153 ?
               $this->sendPacket('<msg t="sys"><body action="apiOK" r="0"></body></msg>'):
               $this->sendPacket('<msg t="sys"><body action="apiKO" r="0"></body></msg>');
    }
    
    public function sendRandomKey() {
      if(!$this->randomKey) $this->randomKey = \iCPro\Utils::GenerateRandomKey('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ[](){}/\\\'"', 16);
      return $this->sendPacket('<msg t="sys"><body action="rndK" r="-1"><k>' . $this->randomKey . '</k></body></msg>');
    }
    
    public function sendRemainingBanTime() {
      if(($banHours = ceil(($this->ban - time()) / 3600)) === 1) return $this->sendError(CPErrors::BANNED_AN_HOUR);
      /* Space for rent! Contact Alex for information ;) */ else return $this->sendError(CPErrors::BANNED_DURATION, $banHours);
    }
    
    public function loadPlugin($pluginID, $pluginURL) {
      return $this->sendPacket('%xt%pl%-1%' . $pluginID . '%' . $pluginURL . '%');
    }
    
    # # # # # # # # # #
    # Login Functions #
    # # # # # # # # # #
    
    public function checkIP() {
      $ipbans = \MySQL::Select('ipbans', "'{$this->ip}' LIKE ip AND flag = 1");
      if(count($ipbans) > 0) { // TODO: Priority for ip-bans!
        \Debugger::Debug(\DebugFlags::WARNING, sprintf('<inv>%s</inv> will be disconnected (<b>ip-banned</b>) ', $this));
        \iServer::RemoveClient($this->clientId);
        return true;
      } return false; /* Hmmm... Somehow I have the slighly feeling I should put a Weird-looking Smiley here... >X-3 */
    }
    
    public function login($uName, $pWord) {
      $split = explode('|', $uName);
      if(count($split) > 1) { // (uses confirmation hash): 'unknown|swid|username|loginKey|... unknown ...'
        \Debugger::Debug(\DebugFlags::FINE, sprintf('<inv>%s</inv> uses the confirmation-hash protocol.', $this));
        list(, $swid, $uName, $loginKey) = $split;
        list($pWord, $confHash) = explode('#', $pWord);
      }
      
      \Debugger::Debug(\DebugFlags::J_FINE, sprintf('<inv>Client #%d</inv> tries to log in as <b>%s</b> (%s)', $this->clientId, $uName, $pWord));
      
      $user = $this->server->getUser(strtolower($uName));                                                           # # # # # # # # # # #
      if($user == NULL || !$this->loadProperties($user))  return $this->sendError(\iCPro\Errors::NAME_NOT_FOUND);  # User does not exist #
      if($this->server->getPasswordHash($this) != $pWord) return $this->sendError(\iCPro\Errors::PASSWORD_WRONG);  # Password is wrong   #
                                                                                                                   # # # # # # # # # # # #
      if($this->ban === 1)        return $this->sendError(CPErrors::BANNED_FOREVER);                               # Banned forever      #
      elseif($this->ban > time()) return $this->sendRemainingBanTime();                                            # Banned for %d hours #
                                                                                                                    # # # # # # # # # # #
      $this->isLoggedIn = true;
      return (SERVER_TYPE == \iCPro\ServerTypes::LOGIN || ($this->convertFlags() ^ $this->refineData())) ^ $this->server->acceptLogin($this);
    }
    
    private function convertFlags() {
      // TODO: $this->Inventory .= $this->Redemptions;
      
      $this->isTryout = $this->name == 'Tryout';
      $this->isAdmin  = $this->flags &  1 ? 1 : 0;
      $this->isMod    = $this->flags &  2 ? 1 : 0;
      $this->isTester = $this->flags &  4 ? 1 : 0;
      $this->isEPF_A  = $this->flags &  8 ? 1 : 0;
      $this->isEPF_F  = $this->flags & 16 ? 1 : 0;
    }
    
    private function refineData() {
      $this->age = floor((time() - $this->age) / 86400);
      $this->refreshPlayerString();
    }
    
    private function loadProperties($object) {
      if(!is_array($object)) return false;
      foreach($object as $key => $value) $this->$key = $value;
      return true;
    }
  }

?>