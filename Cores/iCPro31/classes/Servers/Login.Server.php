<?php
  
  namespace iCPro\Servers;
  require_once 'Server.php';
  require_once CLASS_DIR . '/Users/Login.User.php';
  
  final class Login extends Server {
    const USER_CLASS = '\\iCPro\\Users\\Login';
    
    private $servers = array();
    
    public function internalUpdate() {} // Clean up Users and LoginKeys by the first element of the Array (Which is the Timestamp of Creation)In cl
    public function internalInit()   {}
    
    protected function rehash() { // Don't load crumbs here.
      return $this->rehashMySQL(true);
    }
    
    public function getPasswordHash(&$func_user) {
      return \iCPro\Utils::SwapMD5(
        md5(\iCPro\Utils::SwapMD5($func_user->password) . $func_user->randomKey . 'a1ebe00441f5aecb185d0ec178ca2305Y(02.>\'H}t":E1_root')
      );
    }
    
    public function acceptLogin(&$func_user) {
      $func_user->loginKey = md5(\iCPro\Utils::GenerateRandomKey('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ[](){}/\\\'"', 16));
      
      \MySQL::Insert('ips', array(
        'usingIP' => $func_user->ip,
        'usingPlayerId' => $func_user->id,
        'usingTimestamp' => time()
      ));
      
      \MySQL::Update('users', array(
        'loginKey' => $func_user->loginKey,
        'lastLogin' => time()
      ), array(
        'id' => $func_user->id
      ));
      
      $func_user->confirmationHash = 'e512521e351c5dd4c3389e20667fc7c9f5f85cbf'; // ... or this
      $func_user->friendsLoginKey = '072dc08de9198920fc0c157ecd343ca7'; // OR ANY OF THIS.
      $func_email = 'a***n@google.com';
      
      // %playerId|swid|username|loginKey|?|languageApproved|languageRejected%confirmationHash%friendsLoginKey%world-populations%emailAddress%remaining_hours|trialMax|max_grace_hours%
      $func_user->sendPacket("%xt%l%-1%{$func_user->id}|{$func_user->swid}|{$func_user->name}|{$func_user->loginKey}|1|1|0|false|false|" . time() . rand(100, 999) . "%{$func_user->confirmationHash}%{$func_user->friendsLoginKey}%{$this->getServerString()}%{$func_email}%");
    }
    
    private function getServerString() {
      return '3826,1|3827,1|3824,1|3825,1|3830,1|3831,1|3828,1|3829,1|3834,1|3835,1|3832,1|3833,1|3836,1|3811,1|3810,1|3809,1|3808,1|3815,1|3814,1|3813,1|3812,1|3819,1|3817,1|3816,1|3823,1|3822,1|3821,1|3820,1|3314,1|3315,1|3312,1|3313,1|3800,1|3801,1|3802,1|3803,1|3804,1|3805,1|3806,1|3807,1|3303,1|3302,1|3301,1|3300,1|3311,1|3310,1|3309,1|3308,1|3307,1|3306,1|3304,1|3760,1|3201,1|3751,1|3200,1|3750,1|3203,1|3749,1|3202,1|3748,1|3205,1|3747,1|3204,1|3746,1|3207,1|3745,1|3206,1|3744,1|3209,1|3759,1|3208,1|3758,1|3211,1|3757,1|3210,1|3756,1|3213,1|3755,1|3212,1|3754,1|3215,1|3753,1|3214,1|3752,1|100,1|3734,1|3735,1|3728,1|3729,1|3730,1|3731,1|3740,1|3741,1|3742,1|3743,1|3736,1|3737,1|3738,1|3739,1|3717,1|3716,1|3719,1|3718,1|3713,1|3712,1|3715,1|3714,1|3725,1|3724,1|3727,1|3726,1|3721,1|3720,1|3723,1|3722,1|3165,1|3707,1|3164,1|3706,1|3167,1|3705,1|3166,1|3704,1|3161,1|3711,1|3160,1|3710,1|3163,1|3709,1|3162,1|3708,1|3157,1|3699,1|3156,1|3698,1|3159,1|3697,1|3158,1|3696,1|3153,1|3703,1|3152,1|3702,1|3155,1|3701,1|3154,1|3700,1|3690,1|3148,1|3691,1|3149,1|3688,1|3150,1|3689,1|3151,1|3694,1|3144,1|3695,1|3145,1|3692,1|3146,1|3693,1|3147,1|3682,1|3140,1|3683,1|3141,1|3680,1|3142,1|3681,1|3143,1|3686,1|3136,1|3687,1|3137,1|3684,1|3138,1|3685,1|3139,1|3673,1|3672,1|3675,1|3674,1|3677,1|3676,1|3679,1|3678,1|3665,1|3664,1|3667,1|3666,1|3669,1|3668,1|3671,1|3670,1|3656,1|3182,1|3657,1|3183,1|3658,1|3180,1|3181,1|3660,1|3178,1|3661,1|3179,1|3662,1|3176,1|3663,1|3177,1|3174,1|3175,1|3172,1|3651,1|3173,1|3652,1|3170,1|3653,1|3171,1|3654,1|3168,1|3655,1|3169,1|3101,1|3100,1|3103,1|3102,1|3131,1|3130,1|3129,1|3128,1|3135,1|3134,1|3133,1|3132,1|3123,1|3122,1|3121,1|3120,1|3127,1|3126,1|3125,1|3124,1|3114,1|3115,1|3112,1|3113,1|3118,1|3119,1|3116,1|3117,1|3106,1|3107,1|3104,1|3105,1|3110,1|3111,1|3108,4|3109,1|3520,1|3550,1|3551,1|3553,1|3552,1|3555,1|3554,1|3501,1|3502,1|3504,1|3505,1|3508,1|3509,1|3512,1|3513,1|3514,1|3515,1|3516,1|3517,1|3518,1|3519,1|3406,1|3407,1|3404,1|3405,1|3402,1|3403,1|3400,1|3401,1|3411,1|3410,1|3409,1|3408,1|';
      
      $func_retVal = '';
      foreach($this->servers as $func_serverID => &$func_server)
       if($func_server['LastResponse'] + SettingsManager::GetSetting(Settings::SERVER_IDLE_TIME) < time()) unset($func_server);
       else $func_retVal .= "{$func_serverID}|{$func_server['Name']}|{$func_server['IP']}|{$func_server['Port']}%";
      return $func_retVal;
    }
    
    private function getServers(&$func_user) {
      $func_buddies = explode('%', $func_user->getPlayerBuddies());
      $func_serverLoads = $func_serverBuddies = '';
      foreach($this->servers as $func_serverID => $func_server) {
        foreach($func_buddies as $func_buddy) if(strpos($func_server['Users'], '|' . strstr($func_buddy, '|', true) . '|') !== false) {
          $func_serverBuddies .= $func_serverID . '|';
          break;
        }
        $func_load = (($func_load = floor($func_server['Load'] / SettingsManager::GetSetting(Settings::SERVER_LOAD_DIVISOR)) + 1) > 6) ? 6 : $func_load;
        $func_serverLoads .= "{$func_serverID},{$func_load}|";
      }
      
      return "{$func_serverBuddies}%{$func_serverLoads}";
    }
    
    private function sendUpdatePackets(&$func_user) {
      $func_user->sendErrorBox('max', 'Sorry, your Client is outdated. Please get the newest one at http://download.iCPNetwork.org/', 'Damnit', 'iDate');
      return \Debugger::Debug(DebugFlags::J_FINE, 'A User tried Connecting <b>with an Outdated Client</b>...');
    }
    
    public function handleXTPacket(&$func_user, $func_packet) {
      list($func_navigation, $func_command) = explode('#', $func_packet[1]);
      array_shift($func_packet); array_shift($func_packet);
      
      /*
      if($func_command == 'vds') {
        $func_keys = array('Name' => 0, 'IP' => 1, 'Port' => 2, 'Load' => 3, 'LastResponse' => 4);
        
        $func_packet;
        foreach($this->servers as $func_serverID => $func_server) $func_packet .= $func_serverID . ':' . join('|', array_intersect_key($func_server, $func_keys)) . '%';
        return $func_user->sendPacket($func_packet);
      } elseif($func_command == 'gv')
       if($func_packet[1] < SERVER_VERSION) return $this->sendUpdatePackets($func_user);
       else return $this->sendLoginPackets($func_user);
      elseif($func_command == 'su') {
        $LastResponse = time();
        
        list($ID, $Name, $IP, $Port, $Load, $Users) = $func_packet;
        return $this->servers[$ID] = compact('Name', 'IP', 'Port', 'Load', 'Users', 'LastResponse');
      } elseif($func_command == 'gl') return $func_user->sendPacket($this->loginkeys[$func_packet[0]][1]);*/
    }
  }
  
?>