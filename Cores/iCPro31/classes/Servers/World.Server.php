<?php
  
  namespace iCPro\Servers;
  require_once 'Server.php';
  require_once 'DismissedSelector.php';
  
  require_once CLASS_DIR . '/TimeoutManager.php';
  require_once CLASS_DIR . '/Users/World.User.php';
  
//require_once CLASS_DIR . '/Games/GameManager.php';
//require_once CLASS_DIR . '/Games/SinglePlayer.GameType.php';
  require_once CLASS_DIR . '/Games/FindFour.TableGame.php';
//require_once CLASS_DIR . '/Games/Mancala.TwoPlayers.php';
//require_once CLASS_DIR . '/Games/TreasureHunt.TwoPlayers.php';
//require_once CLASS_DIR . '/Games/CardJitsu.CustomGame.php';
//require_once CLASS_DIR . '/Games/SledRace.CustomGame.php';
  require_once CLASS_DIR . '/Games/Hockey.RoomGame.php';
  
  require_once CLASS_DIR . '/Rooms/RoomManager.php';
  require_once CLASS_DIR . '/Rooms/Generic.Room.php';
  require_once CLASS_DIR . '/Rooms/Game.Room.php';
  require_once CLASS_DIR . '/Rooms/Igloo.Room.php';
  
  interface PlayerLevels {
    const ADMIN  = 4;
    const MOD    = 3;
    const TEST   = 2;
    const MEMBER = 1;
    const NEWBIE = 0;
  }

  final class World extends Server {
    const USER_CLASS = '\\iCPro\\Users\\World';
    
    private $userString;
    private $clientSocket;
    
    public function __toString() { return "<Servers\\World:" . count($this->clients) . " clients>"; }
    
    private function getIglooString() {
      if(!$this->data['OpenIgloos']) return '';
      
      $ret = '';
      foreach($this->data['OpenIgloos'] as $playerId => $playerName) $ret .= "{$playerId}|{$playerName}%";
      return $ret;
    }
    
    public function getCrumbProperty($crumb, $id, $property) {
      if(!isset($this->data[$crumb][$id][$property])) return false;
      return $this->data[$crumb][$id][$property];
    }
    
    public function updateUserProperty($playerId, $key, $value) {
      if(!isset($this->users[$playerId])) return false;
      $this->users[$playerId][$key] = $value;
      return \MySQL::Update('users', array( $key => $value ), array( 'id' => $playerId ));
    }
    
    public function updateIglooProperty($playerId, $key, $value) {
      if(!isset($this->users[$playerId])) return false;
      return \MySQL::Update('igloos', array( $key => $value ), array( 'playerId' => $playerId));
    }
    
    public function getPlayerBi($playerId) {
      $u = $this->users[(integer)$playerId];
      if(!$u) return false;
      return $this->generateSWID($u['name'], $u['id']) . '%' . $u['id'] . '%' . $u['name'];
    }
    
    public function getPlayer($playerId, $opt = false) {
      $u = $this->users[(integer)$playerId];
      if(!$u) return false;
      
      // 227660186|Alexrath|0|7|1848|0|3032|4029|5440|0|0|9106|0|0|1|0|0|0|{"spriteScale":100,"spriteSpeed":100,"ignoresBlockLayer":false,"invisible":false,"floating":false}|307644103|0||142|0|
      
      // player-id|username|is-localized-name|color|head|face|neck|body|hand|feet|flag|photo|x|y|frame|is-member|total-mem-days|template|json|puffle-id|puffle-type|puffle-subtype|puffle-head|puffle-state
      
      $glow = '';
      if($this->alias[$u['id']]) $glow = ''; // $this->alias[$u['id']]->glow; // TODO: What is this?
      
      $ret[] = $u['id'];
      $ret[] = $opt['isMascot'] ? $opt['mascotName'] : $u['name'];
      $ret[] = 1; // Is this name approved?
      $ret[] = $opt['isMascot'] ? $opt['mascotDressing'] : $u['dressing'];
      $ret[] = $opt['x'] ?: 0;
      $ret[] = $opt['y'] ?: 0;
      $ret[] = $opt['frame'] ?: 0;
      $ret[] = 1; // Is member?
      $ret[] = ($a = $this->getPlayerLevel($u) * 180 + 30) > 30 ? $a : ''; // member-days
      $ret[] = 0; // Avatar template or something
      $ret[] = '{"spriteScale":100,"spriteSpeed":100,"ignoresBlockLayer":false,"invisible":false,"floating":false}';
      
      // Puffle data
      
      $puffle = \iCPro\Users\World::getWalkingPuffle((integer)$playerId);
      if(is_null($puffle)) $ret[] = '||||';
      else {
        $puffleState = ''; // TODO: Aha?
        
        $ret[] = $puffle['id'];
        $ret[] = $puffle['type'];
        $ret[] = $puffle['subType'] == 0 ? '' : $puffle['subType'];
        $ret[] = $puffle['hat'];
        $ret[] = $puffleState;
      }
      
      // iCP-data.
      $ret[] = $u['isBot'] ? ((integer)(boolean)($u['flags'] & 2)) + 1 : 0;
      $ret[] = floor((time() - $u['age']) / 86400); //... Make this go faster perhaps? To make Membership Counter show a little more... ...//
      $ret[] = $u['mood'];
      $ret[] = $glow;
      
      return join('|', $ret);
    }
    
    public function getPlayerStamps($id) {
      $u = $this->users[(integer)$id];
      if(!$u) return false;
      return $u['stamps'];
    }
    
    public function getPlayerLevel($u) {
      $p = (integer)$u['id'];
      $m = $this->alias[$p] ? $this->alias[$p]->maxBadge : PlayerLevels::ADMIN;
      
      $f = (integer)$u['flags'];
      if($f & 1) return min($m, PlayerLevels::ADMIN);
      if($f & 2) return min($m, PlayerLevels::MOD);
      if($f & 4) return min($m, PlayerLevels::TEST);
      
      $jTime = $u['age'];//[TODO]: Use PlayTime for this
      if(time() - $jTime > 604800) return min($m, PlayerLevels::MEMBER);
      return PlayerLevels::NEWBIE;
    }
    
    public function internalInit() {
      \iCPro\TimeoutManager::AddTimeout(\iCPro\Settings::SERVER_IDLE_TIME, array($this, 'serverIdleTimeout'));
      \iCPro\TimeoutManager::AddTimeout(\iCPro\Settings::USER_IDLE_CHECK,  array($this, 'userIdleTimeout'));
      $this->serverIdleTimeout();
    }
    
    public function internalUpdate() {
      \iCPro\TimeoutManager::QueueTimeouts();
    }
    
    public function handleDisconnect($event, $clientId) {
      parent::handleDisconnect($event, $clientId);
      $this->serverIdleTimeout();
    }
    
    public function serverIdleTimeout() {
      return $this->sendPacketToLogin('%xt%%#su%' . join('%', array(SERVER_ID, SERVER_NAME, SERVER_GIP, SERVER_GORT, count($this->alias))) . '%' . $this->getOnlineUserString() . '%');
    }
    
    public function userIdleTimeout() {
      foreach($this->clients as $u)
        if($u->lastAction + \iCPro\SettingsManager::GetSetting(\iCPro\Settings::USER_IDLE_TIME) < time())
          $u->kick('SERVER|IDLE', true);
    }
    
    private function sendPacketToLogin($p, $append = true) {
      $p = $append ? $p . \iServer::$escapeChar : $p;
      if($this->clientSocket && fwrite($this->clientSocket, $p)) return;
      
      \Debugger::Debug(\DebugFlags::FINER, sprintf('Reconnecting to Login Server at <b>%s:%d</b>', LOGIN_IP, LOGIN_PORT));
      $this->clientSocket = @fsockopen(LOGIN_IP, LOGIN_PORT);
      $this->clientSocket && fwrite($this->clientSocket, $p);
      
      $this->clientSocket && stream_set_blocking($this->clientSocket, false);
    }
    
    private function retrievePacketFromLogin() {
      return strstr(@fread($this->clientSocket, 1024), \iServer::$escapeChar, true);
    }
    
    private function getOnlineUserString() {
      $ret = '|';
      foreach($this->clients as $client) if($client instanceof \iCPro\Users\User && $client->id) $ret .= $client->id . '|';
      return $ret;
    }
    
    public function getPasswordHash(&$u) {
      if($u->isBot) return $u->password;
      
      // If there is no login key, return something random (which cannot be guessed)
      $lkey = \MySQL::GetData("SELECT loginKey FROM users WHERE id = {$u->id} AND lastLogin > " . (time() - 120));
      if(count($lkey) == 0) return md5(\iCPro\Utils::GenerateRandomKey('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ[](){}/\\\'"', 16));
      
      $u->loginKey = $lkey[0]['loginKey'];
      return \iCPro\Utils::SwapMD5(md5($u->loginKey . $u->randomKey)) . $u->loginKey;
    }
    
    public function acceptLogin(&$u) {
      $u->sendPacket('%xt%l%-1%');
      $u->noticeBuddies('bon');
      
      $this->alias[$u->id] = &$u;
      $this->serverIdleTimeout();
    }
    
    public function swidsToIds($swids) {
      return join('|', array_map(function($swid) {
        return @hexdec(substr($swid, -10));
      }, explode('|', $swids)));
    }
    
    public function getNinjaRanks($id) {
      $user = $this->users[(integer)$id];
      if(!$user) return false;
      return join('%', array_map(function($level) {
        if($level == 0) return '';
        return floor((integer)($level) / 100);
      }, array(
        $user['ninjaLevel'],
        $user['ninjaFireLevel'],
        $user['ninjaWaterLevel'],
        $user['ninjaSnowLevel']
      )));
    }
    
    public function handleXTPacket(&$u, $p) {
      if(!$u instanceof \iCPro\Users\User) return false; // $u = new self::USER_CLASS(-1, -1, -1);
      $u->lastAction = time();
      
      $original = $p;
      
      $zone = $p[0];
      list($nav, $c) = explode('#', $h = $p[1]);
      array_splice($p, 0, 2);
      
      if(!$u->isLoggedIn && $h != 'bo#lgn') return var_dump("Packet dropped because isLoggedIn=false"); // TODO: Error report.
      if(is_null($u->room) && $h != 'j#js' && $h != 'bo#lgn') return; // TODO: Error report. No other packets are allowed right now.
      
      $result = dismiss_selector();
      
      try {
        switch($zone) {
          case 's': $result = $this->handleSystemPacket($u, $nav, $c, $p); break;
          case 'z': $result = is_null($u->game) ? dismiss_selector() : $u->game->handlePacket($u, $nav, $p); break;
        }
        
        if($result instanceof DismissedSelector) {
          $start = "%xt%{$zone}%{$h}%";
          
          \iCPro\Akwaya::Notice('iCPNormal::handleXTPacket', "unrecognized selector '{$start}...'", false);
          \iCPro\Akwaya::PrintBacktrace($result->backtrace);
          
          return; // TODO: Re-enable.
          DismissedSelector::Resolve($start, join('%', $original));
        }
      } catch(\iCPro\Users\UserException $e) {
        // Will clean-up on __destruct
      }
    }
    
    private function handleSystemPacket(&$u, $nav, $c, $p) { switch($nav) {
      case 'a':   return $this->handleTablePacket        ($u, $c, $p);
      case 'b':   return $this->handleBuddyPacket        ($u, $c, $p);
      case 'e':   return $this->handleSurveyPacket       ($u, $c, $p);
      case 'f':   return $this->handleEPFPacket          ($u, $c, $p);
      case 'g':   return $this->handleIglooPacket        ($u, $c, $p);
      case 'i':   return $this->handleItemPacket         ($u, $c, $p);
      case 'j':   return $this->handleNavigationPacket   ($u, $c, $p);
      case 'l':   return $this->handleMailPacket         ($u, $c, $p);
      case 'm':   return $this->handleMessagePacket      ($u, $c, $p);
      case 'n':   return $this->handleIgnorePacket       ($u, $c, $p);
      case 'o':   return $this->handleModerationPacket   ($u, $c, $p);
      case 'p':   return $this->handlePetPacket          ($u, $c, $p);
      case 'r':   return $this->handleRoomPacket         ($u, $c, $p);
      case 's':   return $this->handleSettingPacket      ($u, $c, $p);
      case 't':   return $this->handleToyPacket          ($u, $c, $p);
      case 'u':   return $this->handlePlayerPacket       ($u, $c, $p);
      case 'w':   return $this->handleWaddlePacket       ($u, $c, $p);
      
      case 'ni':  return $this->handleNinjaPacket        ($u, $c, $p);
      
      // TODO:
      case 'cd':  return $this->handleCardPacket         ($u, $c, $p);
      case 'pt':  return $this->handleTransformPacket    ($u, $c, $p); // "Player Transformation"
      case 'gb':  return $this->handleGhostBusterPacket  ($u, $c, $p); // Ha, what?
      case 'tic': return $this->handleTicketPacket       ($u, $c, $p); // "Player Ticket"
      case 'ba':  return $this->handleCookieBakeryPacket ($u, $c, $p);
      
      // TODO level 2:
      case 'nx':  return $this->handleExperiencePacket   ($u, $c, $p); // "New User Experience"
      case 'bi':  return $this->handleBiPacket           ($u, $c, $p);
      case 'st':  return $this->handleStampPacket        ($u, $c, $p);
      case 'rpq': return $this->handleQuestPacket        ($u, $c, $p);
      
      case 'iCP': return $this->handleCostumPacket       ($u, $c, $p);
      case 'bo':  return $this->handleBotPacket          ($u, $c, $p);
      
      default:    return dismiss_selector();
    }}
    
    private function handleTablePacket(&$u, $c, $p) { switch($c) {
      case 'gt': return $u->getTables($p);
      case 'jt': $table = $u->room->tables[(integer)$p[1]]; return is_null($table) ? dismiss_selector() : $u->joinGame($table);
      case 'lt': return $u->leaveGame();
      
      default:   return dismiss_selector();
    }}
    
    private function handleBuddyPacket(&$u, $c, $p) { switch($c) {
      case 'gb': return $u->sendPacket   ("%xt%gb%-1%{$u->getPlayerBuddies()}");
      case 'br': return $u->requestBuddy ((integer)$p[1]);
      case 'ba': return $u->acceptBuddy  ((integer)$p[1]);
      case 'rb': return $u->removeBuddy  ((integer)$p[1]);
      case 'bf': return $u->findBuddy    ((integer)$p[1]);
      
      default:   return dismiss_selector();
    }}
    
    private function handleSurveyPacket(&$u, $c, $p) { switch($c) {
      case 'spl': return $u->votePenguinAwards($p[1]); // TODO: Actually, this is called "poll"
      case 'sig': return $u->signIglooContest();
      case 'dc':  return $u->handleDonateCoins((int) $p[1], (int) $p[2]);
      
      default:    return dismiss_selector();
    }}
    
    private function handleEPFPacket(&$u, $c, $p) { switch($c) {
      case 'epfga': return $u->sendPacket            ("%xt%epfga%-1%{$u->isEPF_A}%");
      case 'epfsa': return $this->updateUserProperty ($u->id, 'flags', $p[1] ? $u->flags | 8 : $u->flags & ~8);
      case 'epfgf': return $u->sendPacket            ("%xt%epfgf%-1%{$u->currentOP}%");
      case 'epfsf': return $this->updateUserProperty ($u->id, 'currentOP', (integer)$p[1]);
      case 'epfgr': return $u->sendPacket            ("%xt%epfgr%-1%{$u->medalsTotal}%{$u->medalsUnused}%");
      case 'epfgm': return $u->sendPacket            ("%xt%epfgm%-1%1%Alex has a cat named Sarcasm.|" . time() . "|17%");
    //case 'epfai': return var_dump($p);
      
      default:      return dismiss_selector();
    }}
    
    private function handleIglooPacket(&$u, $c, $p) { switch($c) {
      case 'im':   return; // Starting to edit igloo
      case 'ur':   return $u->updateIglooFurniture ($p);
      case 'gm':   return $u->getIglooDetails      ((integer)$p[1]);
      case 'gf':   return $u->sendPacket           ("%xt%gf%{$u->room}%{$u->iglooInventory}"); // Not used anymore
      case 'ag':   return $u->updateIglooFloor     ((integer)$p[1]);
      case 'au':   return $u->updateIglooType      ((integer)$p[1]);
      case 'af':   return $u->addFurniture         ((integer)$p[1]);
      case 'um':   return $u->updateIglooMusic     ((integer)$p[1]);
      case 'or':   return $u->openIgloo            ();
      case 'cr':   return $u->closeIgloo           ();
      case 'gr':   return $u->sendPacket           ("%xt%gr%{$u->room}%{$this->getIglooString()}");
      case 'go':   return $u->sendPacket           ("%xt%go%{$u->room}%%"); // TODO: Get owned igloos
      case 'pio':  return $u->sendPacket           ("%xt%pio%{$u->room}%" . (isset($SERVER->data['OpenIgloos'][(integer)$p[1]])?1:0) . "%");
      case 'ggd':  return $u->sendPacket           ("%xt%ggd%{$u->room}%%"); // TODO: Investigate.
      case 'uic':  return $u->updateIglooLayout    ((integer)$p[1], array_slice($p, 2));
      case 'aloc': return $u->updateIglooLocation  ((integer)$p[1]);
      case 'gii':  return $u->sendPacket           ("%xt%gii%{$u->room}%{$u->iglooInventory}%");
      case 'cli':  return $u->sendPacket           ('%xt%cli%' . $u->room . '%' . $u->id . '%200%{"canLike":false,"periodicity":"ScheduleDaily","nextLike_msecs":21017986}%');
      case 'gili': return $u->sendPacket           ('%xt%gili%' . $u->room . '%' . $u->id . '%200%{"likedby":{"counts":{"count":1,"maxCount":1,"accumCount":1},"IDs":[{"id":"{412e87f8-cd70-4909-be05-7a1e73e5a18a}","time":1397994523081,"count":1}]}}%'); // TODO: Investigate
      case 'gail': return $u->sendAllIglooLayouts  ((integer)$p[1]);
      case 'al':   return $u->addIglooLayout       ($p[1]);
      case 'uiss': return $u->updateIglooSlots     ((integer)$p[1], $p[2]);
      
      default:   return dismiss_selector();
    }}
    
    private function handleItemPacket(&$u, $c, $p) { switch($c) {
      case 'gi': return $u->sendPacket ("%xt%gi%-1%{$u->inventory}");
      case 'ai': return $u->addItem    ((integer)$p[1]);
      
      default:   return dismiss_selector();
    }}
    
    private function handleNavigationPacket(&$u, $c, $p) { switch($c) {
      case 'js': return $u->joinServer();
      
      case 'jp': $u->joinIgloo((integer)$p[1], (string)$p[2]); break;
      case 'jr': if($p[1] < 900) { $u->joinRoom((integer)$p[1], (integer)$p[2], (integer)$p[3]); break; };
      case 'jg': $u->joinGameRoom((integer)$p[1]); break;
      
      case 'crl': /* The client loaded the room. Great. Why would we care? */; break;
      case 'grs': $u->sendPacket("%xt%grs%-1%{$u->room->friendlyId}%" . $u->room->serializePlayersFor($u));
      
      default:   return dismiss_selector();
    }}
    
    private function handleMailPacket(&$u, $c, $p) { switch($c) {
      case 'mst': return $u->startMailEngine ();
      case 'ms':  return $u->sendMail        ((integer)$p[1], (integer)$p[2], "");
      case 'mg':  return $u->sendPacket      ("%xt%mg%2%{$u->getPlayerMail()}");
      case 'mc':  return $u->checkMail       ();
      
      default:    return dismiss_selector();
    }}
    
    private function handleIgnorePacket(&$u, $c, $p) { switch($c) {
      case 'gn': return $u->sendPacket   ("%xt%gn%-1%{$u->getPlayerIgnores()}");
      case 'an': return $u->addIgnore    ((integer)$p[1]);
      case 'rn': return $u->removeIgnore ((integer)$p[1]);
      
      default:   return dismiss_selector();
    }}
    
    private function handleModerationPacket(&$u, $c, $p) { // TODO: Verify this.
      if(isset($this->alias[$p[1] = (integer)$p[1]]) && ($u->isMod || $u->kick())) switch($c) {
      
      case 'm': return $this->alias[$p[1]]->mute ($u->name, $u->isAdmin);
      case 'k': return $this->alias[$p[1]]->kick ($u->name, $u->isAdmin);
      case 'b': return $this->alias[$p[1]]->ban  ($u->name, $u->isAdmin);
      case 'initban': return; // TODO: Implement this?
      
      default:  return dismiss_selector();
    }}
    
    private function handlePetPacket(&$u, $c, $p) { switch($c) {
      case 'pgu': return $u->sendPacket       ("%xt%pgu%{$u->room}%" . \iCPro\Users\World::getPuffles($u->id)); // TODO: Shouldn't this be in Server?
      case 'pw':  return $u->sendWalkPuffle   ((integer)$p[1], (integer)$p[2]);
      // TODO: Investigate '2', maybe count? (below)
      case 'pg':  return $u->sendPacket       ("%xt%pg%{$u->room}%2%" . \iCPro\Users\World::getPuffles((integer)$p[1], $p[2]));
      case 'pn':  return $u->adoptPuffle      ((integer)$p[1], $p[2], (integer)$p[3]);
      case 'ps':  return $u->sendRoomPacket   ("%xt%ps%{$u->room}%" . ((integer)$p[1]) . "%" . ((integer)$p[2]) . "%");
      case 'pm':  return $u->sendPuffleMove   ((integer)$p[1], (integer)$p[2], (integer)$p[3]);
      case 'pb':  return $u->sendPuffleAction ('pb', 10, (integer)$p[1]);
      case 'pr':  return $u->sendPuffleAction ('pr',  5, (integer)$p[1]);
      case 'pp':  return $u->sendPuffleAction ('pp',  5, (integer)$p[1]);
      case 'pt':  return $u->sendPuffleAction ('pt', 20, (integer)$p[1]);
      case 'ir':  return $u->sendRoomPacket   ("%xt%ir%{$u->room}%{$u->getPuffleString($p[1])}%{$p[2]}%{$p[3]}%");
      case 'ip':  return $u->sendRoomPacket   ("%xt%ip%{$u->room}%{$u->getPuffleString($p[1])}%{$p[2]}%{$p[3]}%");
      case 'if':  return $u->sendRoomPacket   ("%xt%if%{$u->room}%{$u->getPuffleString($p[1])}%{$p[2]}%{$p[3]}%");
      case 'pir': return $u->sendRoomPacket   ("%xt%pir%{$u->room}%{$p[1]}%{$p[2]}%{$p[3]}%");
      case 'pip': return $u->sendRoomPacket   ("%xt%pip%{$u->room}%{$p[1]}%{$p[2]}%{$p[3]}%");
      case 'pgpi': return $u->sendPacket("%xt%pgpi%19%27|1%142|1%8|1%2|1%37|1%29|1%1|1%79|3%3|11%"); // TODO: Analyze
      case 'pgmps': return; // TODO: Nothing sent back, really?
      case 'puffledig': return $u->sendPuffleDig(false);
      case 'puffleswap': return $u->sendPuffleSwap((integer)$p[1], (string)$p[2]);
      case 'puffletrick': return $u->sendPuffleTrick((integer)$p[1]);
      case 'pufflewalkswap': return $u->swapWalkingPuffle((integer)$p[1]);
      case 'checkpufflename': return $u->sendPacket("%xt%checkpufflename%{$u->room}%{$p[1]}%1%"); // Yar har! Allow all names!
      case 'puffledigoncommand': return $u->sendPuffleDig(true);
      
      default:    return dismiss_selector();
    }}
    
    private function handleExperiencePacket(&$u, $c, $p) { switch($p) {
      case 'pcos': return; // TODO: Player card opened.
      case 'bimp': return; // TODO: Tracking?
      case 'binx': return; // TODO: Tracking?
      case 'mcs':  return; // TODO: Set saved map category.
      default: return dismiss_selector();
    }}
    
    private function handleRoomPacket(&$u, $c, $p) { switch($c) {
      case 'cdu': return $u->digForCoins();
      case 'dc':  return $u->donateCoins((integer)$p[1], (integer)$p[2]);
      
      default: return dismiss_selector();
    }}
    
    private function handleSettingPacket(&$u, $c, $p) { switch($c) {
      case 'upc': case 'uph': case 'upf': case 'upn': case 'upb': case 'upa': case 'upe': case 'upl': case 'upp':
        return $u->updateLayer($c, (integer)$p[1]);
      default: return dismiss_selector();
    }}
    
    private function handleToyPacket(&$u, $c, $p) { switch($c) {
      case 'at': return $u->sendRoomPacket("%xt%at%{$u->room}%{$u->id}%"); // TODO: Status management? Investigate!
      case 'rt': return $u->sendRoomPacket("%xt%rt%{$u->room}%{$u->id}%");
      default:   return dismiss_selector();
    }}
    
    private function handlePlayerPacket(&$u, $c, $p) { switch($c) {
      case 'gp':  return $u->sendPacket     ("%xt%gp%{$u->room}%{$this->getPlayer($p[1])}%");
      case 'glr': return $u->getRevision    ();
      case 'sp':  return $u->sendPlayerMove ((integer)$p[1], (integer)$p[2]);
      case 'tp':  return $u->sendPlayerMove ((integer)$p[1], (integer)$p[2], true); // Teleport
      case 'sb':  return $u->sendRoomPacket ("%xt%sb%{$u->room}%{$u->id}%{$p[1]}%{$p[2]}%");
      case 'se':  return $u->sendRoomPacket ("%xt%se%{$u->room}%{$u->id}%{$p[1]}%");
      case 't':   return; // TODO: The client timed out!
      case 'h':   return $u->sendPacket     ("%xt%h%{$u->room}%");
      case 'sa':  return $u->sendAction     ((integer)$p[1]);
      case 'sf':  return $u->sendFrame      ((integer)$p[1]);
      case 'ss':  return $u->sendRoomPacket ("%xt%ss%{$u->room}%{$u->id}%{$p[1]}%");
      case 'sl':  return $u->sendRoomPacket ("%xt%sl%{$u->room}%{$u->id}%{$p[1]}%");
      case 'sq':  return $u->sendRoomPacket ("%xt%sq%{$u->room}%{$u->id}%{$p[1]}%");
      case 'sg':  return $u->sendRoomPacket ("%xt%sg%{$u->room}%{$u->id}%{$p[1]}%");
      case 'sj':  return $u->sendRoomPacket ("%xt%sj%{$u->room}%{$u->id}%{$p[1]}%");
      case 'sma': return $u->sendRoomPacket ("%xt%sma%{$u->room}%{$u->id}%{$p[1]}%");
      
      case 'pbi':  return $u->sendPacket("%xt%pbi%-1%{$this->getPlayerBi($p[1])}%");
      case 'pbsu': return $u->sendPacket("%xt%pbsu%-1%{$u->name}%");
      
      case 'pbsms': return $u->sendPacket("%xt%pbsms%-1%"); // pbsm-start
      case 'pbsm':  return $u->sendPacket("%xt%pbsm%-1%{$this->swidsToIds($p[1])}%");
      case 'pbsmf': return $u->sendPacket("%xt%pbsmf%-1%"); // pbsm-finish
      
      case 'gabcms': return $u->sendPacket('%xt%gabcms%-1%{"FurnitureCatalogueLocationTest":{"variant":0},"PreActivatedPlay":{"num_seconds_play":604800,"num_seconds_grace":172800,"variant":1},"MapTest":{"MapSettingId":0,"variant":0},"PuffleOopsMemberVsFree":{"variant":2},"BOGO":{"pageId":0},"HelloRockhopperTest":{"YarrColourID":5},"MembershipUpsell":{"free":"Limited Play","membership":"Unlimited Play"},"JustForTest":{"Last":"Smith","First":"John"},"NewPlayerLoginRoom":{"roomId":100,"variant":1},"FurnitureCatalogTest":{"catalogID":1,"variant":1},"XDayTrialOffers":{"TrialMembershipSettingID":2,"variant":2},"PenguinStyleTest":{"catalogID":"1"},"ClientConfigTest":{"icon":"http://www.clubpenguin.com/sites/default/files/EN0130-PuffleParty-Homepage-Billboard-Main-1361908642-1362026304.jpg","ShowLevelAccessPopup":true,"ClassName":"MemberGameLevel2","BonusPoints":500},"EndGameScreenTest":{"variant":0},"DinosaurTransformTest":{"variant":0},"QuestTest":{"startRoom":100,"isControl":false,"variant":1},"TeenBeachItems":{"CatalogID":"0"}}%');
      
      default:    return dismiss_selector();
    }}
    
    private function handleWaddlePacket(&$u, $c, $p) { switch($c) {
      case 'jx':  return $u->joinWaddle($p); // \iCPro\Games\GameManager::HandleJoinWaddle($u, $p);
      default:    return dismiss_selector();
    }}
    
    private function handleNinjaPacket(&$u, $c, $p) { switch($c) {
      case 'gnl': return $u->sendPacket("%xt%gnl%-1%" . floor($u->ninjaLevel      / 100) . "%" . ($u->ninjaLevel      % 100) . "%10%");
      case 'gfl': return $u->sendPacket("%xt%gfl%-1%" . floor($u->ninjaFireLevel  / 100) . "%" . ($u->ninjaFireLevel  % 100) . "%5%");
      case 'gwl': return $u->sendPacket("%xt%gwl%-1%" . floor($u->ninjaWaterLevel / 100) . "%" . ($u->ninjaWaterLevel % 100) . "%5%");
      case 'gsl': return $u->sendPacket("%xt%gsl%-1%" . floor($u->ninjaSnowLevel  / 100) . "%" . ($u->ninjaSnowLevel  % 100) . "%24%");
      case 'gnr': return $u->sendPacket("%xt%gnr%-1%{$p[1]}%{$this->getNinjaRanks((integer)$p[1])}%");
      default:    return dismiss_selector();
    }}
    
    private function handleMessagePacket(&$u, $c, $p) { switch($c) {
      case 'sm':   return $u->sendMessage  ($p[2]);
      case 'r':    return $u->reportPlayer ((integer)$p[1], (integer)$p[2], $p[3]);
      case 'pcam': return $u->sendMessage  (base64_decode($p[1])); // Requires Jamie's mod.
      
      default:     return dismiss_selector();
    }}
    
    private function handleStampPacket(&$u, $c, $p) { switch($c) {
      case 'sse': return $u->sendStampEarned((integer)$p[1]);
      default:    return dismiss_selector();
    }}
    
    private function handleBiPacket(&$u, $c, $p) { switch($c) {
      case 'ack': /* Acknowledgement */; return;
      default:    return dismiss_selector();
    }}
    
    private function handleQuestPacket(&$u, $c, $p) { switch($c) {
      case 'rpqd':  return $u->sendPuffleTaskCookie();
      case 'rpqtc': return $u->sendPuffleTaskCompleted((integer)$p[1]);
      case 'rpqcc': return $u->sendPuffleCoinCollected((integer)$p[1]);
      case 'rpqic': return $u->sendPuffleItemCollected((integer)$p[1]);
      case 'rpqbc': return $u->sendPuffleBonusCollected();
      default:      return dismiss_selector();
    }}
    
    private function handleCostumPacket(&$u, $c, $p) { switch($c) {
      case 'umo':  return $u->updateMood($p[1]);
      case 'scmt': return $u->sendComment((integer)$p[0], $p[1]);
      case 'glog': return $u->sendPacket('%xt%glog%' . UserLog::GetLog($u->id) . '%');
      default:     return dismiss_selector();
    }}
    
    private function handleBotPacket(&$u, $c, $p) { switch($c) {
      case 'lgn': {
        $u->isBot = true;
        $this->users[$u->id]['isBot'] = true;
        return $u->login($p[0], $p[1]);
      };
      default: return dismiss_selector();
    }}
  }
  
?>