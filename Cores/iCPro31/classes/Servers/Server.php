<?php
  
  namespace iCPro\Servers;
  
  // TODO: How many coins in Mancala?
  
  require_once CLASS_DIR . '/Utils.php';
  require_once CLASS_DIR . '/SettingsManager.php';

  $swid_prefixes = array(
    'A' => 'ANOTHERO-NEBI-TEST-HEDU-ST',
    'B' => 'BILLIEJE-ANIS-NOTM-YLOV-ER',
    'C' => 'CANTTOUC-HDIS-HAMM-ERTI-ME',
    'D' => 'DASISTDI-EPER-FEKT-EWEL-LE',
    'E' => 'EASTBOUN-DNDO-WNAL-ONGW-AY',
    'F' => 'FAIRYTAL-EGON-EBAD-AVEN-UE',
    'G' => 'GIVENUPI-MSIC-KOFF-EELI-NG',
    'H' => 'HITMEBAB-EONE-MORE-TIME-OH',
    'I' => 'ICHLEBEW-EILD-UMAT-EMBI-ST',
    'J' => 'JUSTDANC-EGOT-TABE-OKAY-MH',
    'K' => 'KNOCKING-ONHE-AVEN-SDOO-RS',
    'L' => 'LOVEITSJ-USTA-KISS-AWAY-RS',
    'M' => 'MYWAYBUT-HATL-ERSV-ERSI-ON',
    'N' => 'NERVERGO-NNAG-IVEY-OUUP-RA',
    'O' => 'ONEDAYBA-BEWE-LLBE-OLDM-HH',
    'P' => 'PASTTHEB-LACK-SIRE-NSSI-NG',
    'Q' => 'QUESTION-SLIK-EACA-NCER-LP',
    'R' => 'READALLA-BOUT-ITPA-RTTH-RE',
    'S' => 'STAYINGA-LIVE-HAHA-HAHA-HA',
    'T' => 'THISLOVE-HAST-AKEN-ITST-OL',
    'U' => 'UNURHAND-NENT-ERTA-INME-NT',
    'V' => 'VIVALAVI-DAIS-AGRE-ATSO-NG',
    'W' => 'WHOCANIT-BEKN-OCKI-NGAT-MY',
    'X' => 'XITSSUCH-AWON-DERF-ULLI-FE',
    'Y' => 'YOUKNOWY-OULI-KEIT-VESP-ER',
    'Z' => 'ZEROGRAV-ITYH-AGEM-EIST-ER',
    ' ' => 'USERNAME-ISNO-TSUP-PORT-ED'
  );

  abstract class Server {
    public $isRunning = true;
    public $lastRehash;
    
    public $users = array();
    public $clients = array();
    
    public function generateSWID($playerName, $id) {
      global $swid_prefixes;
      
      $swid = $swid_prefixes[strtoupper($playerName{0})];
      if(is_null($swid)) $swid = $swid_prefixes[' '];
      $swid .= str_pad(dechex($id), 10, '0', STR_PAD_LEFT); // Will break for ids higher than 1099511627775
      
      var_dump("SWID for {$playerName} is {$swid}");
      
      return $swid;
    }
    
    abstract public function getPasswordHash(&$user);
    abstract public function acceptLogin(&$user);
    abstract public function internalUpdate();
    abstract public function internalInit();
    
    public function __construct() {
      \Debugger::Debug(\DebugFlags::INFO, '<b>Loading Data...</b>');
      $this->rehash();
    }
    
    protected function rehash() {
      if($this->lastRehash + \iCPro\SettingsManager::GetSetting(\iCPro\Settings::REHASH_PERIOD) > time()) return false;
      // TODO: Cause GameConfig to refresh here!
      return $this->rehashMySQL(true);
    }
    
    protected function rehashMySQL($automatic = false) {
      if($this->lastRehash + \iCPro\SettingsManager::GetSetting(\iCPro\Settings::REHASH_PERIOD) > time()) return false;
      if($automatic) \Debugger::Debug(\DebugFlags::INFO, '<b>Refreshing MySQL</b> for general iCPServer::refresh');
      else           \Debugger::Debug(\DebugFlags::INFO, '<b>Refreshing MySQL</b> for User Database');
      
      $users = \MySQL::Select('users');
      $this->users = array();
      
      foreach($users as $line) {
        $this->users[(integer)$line['id']] = $line;
      //$this->users[strtolower($line['login'])] = &$this->users[$line['id']];
      }
    }
    
    public function __destruct() {
      echo 'The $SERVER has been deallocated. Thus Meaning is that we\'ll all die. Have a nice day ~PHP' . chr(10);
    }
    
    # # # # # # # # # #
    # Main Functions  #
    # # # # # # # # # #
    
    public function update() {
      $this->internalUpdate();
    }
    
    # # # # # # # # # # #
    # Handler Functions #
    # # # # # # # # # # #
    
    public function handleClient($event, $serverSocket, $clientId, $userSocket) {
      $userClass = static::USER_CLASS;
      $client = substr($serverSocket, -strlen(SERVER_PORT)) == SERVER_PORT ?
        $this->clients[$clientId] = new $userClass($clientId, $userSocket, $this) :
        $this->clients[$clientId] = new \iCPro\Users\WebMod($clientId, $userSocket, $serverSocket);
      
      ((substr($serverSocket, -strlen(SERVER_PORT)) == SERVER_PORT) && $client->checkIP())
   || \Debugger::Debug(\DebugFlags::FINE, sprintf('<inv>%s</inv> connected on <b>%s</b>', $client, $serverSocket));
      
      return true;
    }
    
    public function handlePacket($event, $clientId, $packet) {
      $packet{strlen($packet) - 2} == chr(1) ? $isXT = true :
        \Debugger::Debug(\DebugFlags::FINE, sprintf('<inv>%s</inv> --&gt; <b>%s</b>', $this->clients[$clientId], htmlentities($packet)));
      
      switch(\PacketAnalizer::Decode($packet)) {
        case \PacketTypes::XT_PACKET:  return $this->handleXTPacket($this->clients[$clientId],  $packet);
        case \PacketTypes::XML_PACKET: return $this->handleXMLPacket($this->clients[$clientId], $packet);
      }
      
      return \Debugger::Debug(SEVERE, 'Could <b>not</b> parse the Packet!');
    }
    
    public function handleRawPacket($args) {
      list(, $clientId, $packet) = $args;
      if(isset($this->clients[$clientId])) $this->clients[$clientId]->handlePacket($packet);
    }
    
  //protected function handleXTPacket(&$user, $packet) { \Debugger::Debug(\DebugFlags::WARNING, 'An XT-Packet was passed <u>to the <b>wrong</b> Server Type!</u>'); }
    protected function handleXMLPacket(&$user, $packet) {
      $command = each($packet);
      $command = $command[0];
      
      switch($command) {
        case 'policy-file-request': return $user->sendPolicyFile();
        case 'msg': /* Spacerent */ return $this->handleMSGPacket($user, $packet['msg'][0]['body'][0]);
      }
      
      return \Debugger::Debug(\DebugFlags::INFO, sprintf('<inv>%s</inv> sent an unknown XML Command: <b>%s</b>', $user, $command));
    }
    
    protected function handleMSGPacket(&$user, $packet) {
      $command = $packet['<Attributes />']['action'];
      //return $user->sendErrorBox('max', 'Sorry, iCPro3 is current down due to Adobe\'s f**king Incompetence.', 'Screw them!', 'They are so freaking dumb');
      
      switch($command) {
        case 'verChk': return $user->sendVersionCheck($packet['ver'][0]['<Attributes />']['v']);
        case 'rndK':   return $user->sendRandomKey();
        case 'login': {
          $user->isBot = false;
          $this->users[$user->id]['isBot'] = false;
          return $user->login(trim($packet['login'][0]['nick'][0]['<CharacterData />']), $packet['login'][0]['pword'][0]['<CharacterData />']);
        }
      }
      
      return \Debugger::Debug(\DebugFlags::INFO, sprintf('<inv>%s</inv> sent an unknown XML/MSG Command: <b>%s</b>', $user, $command));
    }
    
    public function handleDisconnect($event, $clientId) { // TODO: Make this look nicer! Also: $this->clients[$clientId] => $user
      if(!$this->clients[$clientId] && !$this->clients[$clientId]) return false;
      
      \Debugger::Debug(\DebugFlags::FINE, sprintf('<inv>%s</inv> disconnected', $this->clients[$clientId], $serverSocket));
      if(isset($this->clients[$clientId])) { $this->clients[$clientId] = NULL; unset($this->clients[$clientId]); return true; }
      $this->clients[$clientId]->isConnected = false;
      
      if($this->clients[$clientId]->room) {
        \EventListener::FireEvent(iCPEvents::USER_LEFT, $this->clients[$clientId]);
        $this->clients[$clientId]->room->removePlayer($this->clients[$clientId]);
      } if($this->clients[$clientId]->buddies) $this->clients[$clientId]->noticeBuddies('bof');
      
      $this->alias[$this->clients[$clientId]->id] = NULL;
      $this->clients[$clientId] = NULL;
      
      unset($this->alias[$this->clients[$clientId]->id]);
      unset($this->clients[$clientId]);
      
      if(SERVER_TYPE == \iCPro\ServerTypes::WORLD && $this->clients[$clientId]->name) $this->serverIdleTimeout();
    }
    
    public function getUser($name) {
      $this->rehashMySQL(true);
      foreach($this->users as $user) if(strtolower($user['name']) == $name) return $user;
      return NULL;
    }
    
    public function getFailCount($user) {}
  }

?>