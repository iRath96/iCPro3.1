<?php
  
  namespace iCPro\Games;
  require_once 'TableGame.GameType.php';
  
  final class FindFour extends TableGame {
    public $grid; // TODO: Make this an array. Please!
    
    public function statusString() { return $this->grid; }
    
    public function reset() {
      parent::reset();
      $this->grid = substr(str_repeat(',0', 7 * 6), 1);
    }
    
    public function processMove(\iCPro\Users\User &$user, $packet) {
      parent::processMove($user, $packet);
      
      $x = (integer)$packet[1];
      $y = (integer)$packet[2];
      
      if($x < 0 || $x > 6) throw new InvalidMoveException($user, $this, 'x-coordinate was out of bounds.');
      if($y < 0 || $y > 5) throw new InvalidMoveException($user, $this, 'y-coordinate was out of bounds.');
      if($this->grid{$x * 12 + $y * 2} != '0') throw new InvalidMoveException($user, $this, 'A chip has been placed there already.');
      if($y != 5 && $this->grid{$x * 12 + ($y + 1) * 2} == '0') throw new InvalidMoveException($user, $this, 'You cannot place a chip in the air.');
      
      $this->grid{$x * 12 + $y * 2} = $this->turn + 1;
      
      $this->checkGameOver($user, $x, $y);
      $this->sendPacket("%xt%zm%{$this->id}%{$this->turn}%{$x}%{$y}%");
      
      $this->turn = ($this->turn + 1) % $this->requiredPlayerCount;
    }
    
    private function checkGameOver($winner, $x, $y) {
      $a = $this->checkLine($x, $y, 0,  1) + $this->checkLine($x, $y,  0, -1) - 1;
      $b = $this->checkLine($x, $y, 1,  0) + $this->checkLine($x, $y, -1,  0) - 1;
      $c = $this->checkLine($x, $y, 1,  1) + $this->checkLine($x, $y, -1, -1) - 1;
      $d = $this->checkLine($x, $y, 1, -1) + $this->checkLine($x, $y, -1,  1) - 1;
      
      if($a < 4 && $b < 4 && $c < 4 && $d < 4) return false;
      foreach($this->players as $player) {
        $player->addCoins($player == $winner ? 10 : 5);
        $player->sendPacket("%xt%zo%-1%{$player->coins}%");
      }
      
      $this->sendPacket('The Game has ended and the Programmer didnt know the right Packet for this. Sorry - Much Apologies - Alex');
      $this->gameOver();
    }
    
    private function checkLine($x, $y, $vX, $vY) {
      $count = 0;
      $init = $this->grid{$x * 12 + $y * 2};
      while(!($x < 0 || $x > 6 || $y < 0 || $y > 5)) {
        if($this->grid{$x * 12 + $y * 2} != $init) return $count;
        ++$count;
        $x += $vX;
        $y += $vY;
      } return $count;
    }
  }
  
?>