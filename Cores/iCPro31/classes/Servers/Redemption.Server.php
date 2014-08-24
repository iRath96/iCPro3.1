<?php
  
  namespace iCPro\Servers;
  require_once 'Server.php';
  
  final class Redemption extends Server {
    const USER_CLASS = '\\iCPro\\Users\\Redemption';
    
    public function getCrumbProperty($func_crumb, $func_id, $func_property) {
      if(!isset($this->data[$func_crumb][$func_id][$func_property])) return false;
      return $this->data[$func_crumb][$func_id][$func_property];
    }
    
    public function updateUserProperty($func_playerId, $func_key, $func_value) {
      if(!isset($this->data['Users'][$func_playerId])) return false;
      
      $this->data['Users'][$func_playerId][$func_key] = $func_value;
      return MySQL::Query("UPDATE `Users` SET `{$func_key}` = '{$func_value}' WHERE `playerId` = {$func_playerId}");
    }
        
    public function internalInit() {}
    public function internalUpdate() {}
    
    public function getPasswordHash(&$func_user) {
      return 'ThisServerDoesntCareAboutPasswords';
    }
    
    public function acceptLogin(&$func_user) {
      $func_user->sendPacket('%xt%l%-1%');
      $this->alias[$func_user->playerId] = &$func_user;
    }
    
    public function handleXTPacket(&$func_user, $func_packet) {
      $func_zone = $func_packet[0];
      list($func_navigation, $func_command) = explode('#', $func_packet[1]);
      array_shift($func_packet);
      array_shift($func_packet);
      
      return $this->handleRedemptionPacket(&$func_user, $func_navigation, $func_packet);
    }
        
    private function handleRedemptionPacket(&$func_user, $func_command, $func_packet) { switch($func_command) {
      case 'rjs':   return $func_user->joinRedemptionServer ((integer) $func_packet[1], $func_packet[2], $func_packet[3]);
      case 'rsc':   return $func_user->sendRedemptionCode   ($func_packet[1]);
      case 'rsp':   return $func_user->sendRedemptionPuffle ($func_packet[1], (integer) $func_packet[2]);
      case 'rscrt': return $func_user->sendRedemptionCart   ($func_packet[1], $func_packet[2], (integer) $func_packet[3]);
      
      default:      return var_dump($func_command);
    }}
  }
  
?>