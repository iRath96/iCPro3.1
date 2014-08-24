<?php
  
  namespace iCPro\Users;
  require_once 'User.php';
  
  const REDEMPTION_CODE_MINLEN = 9;
  const REDEMPTION_CODE_MAXLEN = 16;
  const REDEMPTION_CODE_CHARS  = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  
  final class Redemption extends User {
    public $playerRedemptions;
    
    public function login($func_uName, $func_pWord) {
      global $SERVER;
      Debugger::Debug(DebugFlags::J_FINE, sprintf('<inv>%s</inv> tries to log in as <b>%s</b> (%s)...', $this, $func_uName, $func_pWord));
      
      $func_user = $SERVER->getUser(strtolower($func_uName));                                                      # # # # # # # # # # #
      if($func_user == NULL || !$this->appendBy($func_user)) return $this->sendError(CPErrors::NAME_NOT_FOUND);   # User does not Exist #
                                                                                                                  # # # # # # # # # # # #
      if($this->playerBan === 1)        return $this->sendError(CPErrors::BANNED_FOREVER);                        # Banned forever      #
      elseif($this->playerBan > time()) return $this->sendRemainingBanTime();                                     # Banned for %d hours #
                                                                                                                   # # # # # # # # # # #
      return $SERVER->acceptLogin($this);
    }
    
    public function joinRedemptionServer($func_playerId, $func_loginKey, $func_localization) {
      return $this->sendPacket('%xt%rjs%-1%%1,2,4,6,7,8,9%0%'); //Workify on thiz a little?
    }
    
    public function sendRedemptionCode($func_code) {
      if(!is_array($func_code = $this->getCode($func_code))) return $this->sendError($func_code);
      return $this->processRedemption($func_code);
    }
    
    public function processRedemption($func_code) { global $SERVER; switch($func_code['redemptionType']) {
      case 'CARD': break; /* Work on this a little */
      case 'DS':
      case 'BLANKET': {
        $func_codeData = explode('%', $func_code['redemptionData']);
        $func_items = explode(',', $func_codeData[0]);
        $func_addedItems = 0;
        
        if($func_items)
        foreach($func_items as $func_item) $func_addedItems += (integer) $this->addRedemptionItem((integer) $func_item);
        $this->playerCoins += @((integer) $func_codeData[1]);
        
        /*
          When there were no added Items, this means, the Code was already used (when permanent or a similar one),
          Then there won't be ANY UPDATE to MySQL (No Coins either) because every Code(-type) should only be unlocked once
          per Account.
          
          If a similar one was already used, this Code won't be deleted either.
          Cuz there was no update to the MySQL :P (Which means, it wasn't really used ;))
        */
        if(!$func_addedItems) return $this->sendError(CPErrors::R_CODE_GROUP_REDEEMED);
        
        $SERVER->updateUserProperty($this->playerId, 'playerRedemptions', $this->playerRedemptions);
        $SERVER->updateUserProperty($this->playerId, 'playerCoins', $this->playerCoins);

        if($func_code['redemptionUses'] == -1);
        else $this->setRedemptionUses($func_code['redemptionCode'], $func_code['redemptionUses'] - 1);
      }
    } return $this->sendPacket("%xt%rsc%-1%{$func_code['redemptionType']}%{$func_code['redemptionData']}%"); }
    
    private function addRedemptionItem($func_itemID) {
      if($func_itemID < 10000) return false; //... No Redemption Item ...//
      
      if(strpos("%{$this->playerRedemptions}%", "%{$func_itemID}%") !== false) return false;
      $this->playerRedemptions .= $func_itemID . '%';
      
      return true;
    }
    
    public function sendRedemptionCart($func_code, $func_items, $func_someUnknownParameter) {
      if(!is_array($func_code = $this->getCode($func_code))) return $this->sendError($func_code);
      global $SERVER;
      
      $func_data  = explode('%', $func_code['redemptionData']);
      $func_coins = $func_code['redemptionCoins'];
      $func_items = explode(',', $func_itemString = $func_items);
      $func_addedItems = 0;
      
      if($func_items)
      foreach($func_items as $func_item) $func_addedItems += (integer) $this->addRedemptionItem((integer) $func_item);
      $this->playerCoins += (integer) $func_coins;
      
      if($func_addedItems > $func_data[0]) return $this->sendError(CPErrors::R_CONNECTION_LOST);
      $this->sendPacket("%xt%rscrt%-1%{$func_itemString}%{$func_coins}%%");
      $SERVER->updateUserProperty($this->playerId, 'playerRedemptions', $this->playerRedemptions);
      $SERVER->updateUserProperty($this->playerId, 'playerCoins', $this->playerCoins);
      
      if($func_code['redemptionUses'] == -1) return;
      else return $this->setRedemptionUses($func_code['redemptionCode'], $func_code['redemptionUses'] - 1);
    }
    
    public function sendRedemptionPuffle($func_puffleName, $func_puffleID) {
      $func_puffleValid = true ? 1 : 0;
      $this->sendPacket("%xt%rsp%-1%{$func_puffleValid}%");
    }
    
    private function getCode($func_code) {
      if(strlen($func_code) < REDEMPTION_CODE_MINLEN)                   return CPErrors::R_LONG_CODE;
      if(strlen($func_code) > REDEMPTION_CODE_MAXLEN)                   return CPErrors::R_SHORT_CODE;
      if(str_replace(str_split(REDEMPTION_CODE_CHARS), '', $func_code)) return CPErrors::R_CONNECTION_LOST;
      
      $func_res = MySQL::GetData("SELECT * FROM `Redemptions` WHERE `redemptionCode` = \"{$func_code}\" LIMIT 1");
      if($func_res[0]['redemptionUses'] == 0) return CPErrors::R_REDEEMED_CODE;
      return $func_res[0] ? $func_res[0] : CPErrors::R_UNKNOWN_CODE;
    }
    
    private function setRedemptionUses($func_code, $func_uses) {
      return MySQL::Query("UPDATE `Redemptions` SET `redemptionUses` = $func_uses WHERE `redemptionCode` = \"{$func_code}\" LIMIT 1");
    }
  }

?>