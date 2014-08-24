<?php
  
  namespace iCPro\Games;
  require_once 'GameType.php';
  
  final class InvalidMoveException extends \iCPro\Users\UserException {
    public $user, $game, $message;
    public function __construct() {
      list($this->user, $this->game, $this->message) = func_get_args();
    }
    
    public function cleanUp() {
      var_dump($this->user, $this->game);
      var_dump("InvalidMoveException: {$this->message}");
    }
  }
  
  abstract class TableGame extends GameType {
    public $requiredPlayerCount = 0;
    public $room, $players = array(), $spectators = array();
    public $id = 0, $turn = 0;
    public $isActive;
    
    public function __toString() { return $id; }
    public function __get($name) {
      if($name == 'participants') return array_merge($this->players, $this->spectators);
      return null;
    }
    
    abstract function statusString();
    
    protected function processMove(\iCPro\Users\User &$user, $packet) {
      if(!$this->isActive) throw new InvalidMoveException($user, $this, 'The game has not been started yet.');
      if(in_array($user, $this->spectators)) throw new InvalidMoveException($user, $this, 'Spectators cannot make moves.');
      if($user != $this->players[$this->turn]) throw new InvalidMoveException($user, $this, 'It was not her turn.');
      
      // Override this and call 'parent::processMove($user, $packet);' at the beginning of your method.
    }
    
    public function __construct($id, $requiredPlayerCount = 0) {
      $this->id = $id;
      $this->requiredPlayerCount = $requiredPlayerCount;
      
      static::reset();
    }
    
    public function reset() {
      /*foreach($this->participants as $participant) $participant->leaveGame(false); // TODO: Is this elegant?
      
      $this->players = array();
      $this->spectators = array();*/
      
      $this->turn = 0;
      $this->isActive = false;
      
      // Override this and call 'parent::reset();' at the beginning of your method.
    }
    
    public function addPlayer(\iCPro\Users\User &$player) {
      $this->spectators[$player->id] = $player;
      $player->sendPacket("%xt%jt%{$this->id}%{$this->id}%" . count($this->participants) . "%"); // '$this->id' is incorrect, but works.
      $this->room->sendPacket("%xt%ut%{$this->room}%{$this->id}%" . count($this->participants) . "%");
    }
    
    public function removePlayer(\iCPro\Users\User &$player) {
      if(in_array($player, $this->players)) $this->quitGame($player);
      else unset($this->spectators[$player->id]);
      
      $this->room->sendPacket("%xt%ut%{$this->room}%{$this->id}%" . count($this->participants) . "%");
    }
    
    // TODO: How does the client know whose turn it is?
    public function handleJoin(\iCPro\Users\User &$player) {
      $player->sendPacket("%xt%jz%-1%" . count($this->players) . "%");
      
      if(count($this->players) < $this->requiredPlayerCount) {
        $this->sendPacket("%xt%uz%-1%" . count($this->players) . "%{$player->name}%");
        
        unset($this->spectators[$player->id]);
        $this->players[] = $player;
        
        if(count($this->players) == $this->requiredPlayerCount) $this->startGame();
      }
    }
    
    public function handleLeave(\iCPro\Users\User &$player) {
      $this->removePlayer($player);
    }
    
    public function handlePacket(\iCPro\Users\User &$user, $command, $packet) { switch($command) {
      case 'gz': return $this->sendStatus($user);
      case 'jz': return $this->handleJoin($user);
      case 'lz': return $this->handleLeave($user);
      case 'zm': return $this->processMove($user, $packet);
      
      default: return \iCPro\Servers\dismiss_selector();
    }}
    
    public function sendStatus(\iCPro\Users\User &$user) {
      $args = array();
      for($i = 0; $i < $this->requiredPlayerCount; ++$i) $args[] = is_null($this->players[$i]) ? '' : $this->players[$i]->name;
      $args[] = $this->statusString();
      
      $user->sendPacket('%xt%gz%-1%' . join('%', $args) . '%');
    }
    
    public function sendPacket($packet) {
      foreach($this->participants as $user) $user->sendPacket($packet);
    }
    
    public function startGame() {
      $this->sendPacket("%xt%sz%{$this->id}%{$this->turn}%"); // Is '$this->turn' correct?
      $this->isActive = true;
    }
    
    public function quitGame(\iCPro\Users\User &$user) {
      $this->sendPacket("%xt%cz%-1%{$user->name}%");
      $this->gameOver();
    }
    
    public function gameOver() { // TODO: This is a mess.
      foreach($this->players as $player) $this->spectators[$player->id] = $player;
      $this->players = array();
      $this->reset();
    }
  }

?>