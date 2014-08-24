<?php
  
  namespace iCPro;
  
  //include_once 'Enums/IndividualSettings.php';
  interface ServerTypes {
    const REDEMPTION = 'redemptionServer';
    const STEALTH    = 'stealthServer';
    const WORLD      = 'worldServer';
    const LOGIN      = 'loginServer';
    const OPEN       = 'openServer';
    const TEST       = 'testServer';
    const MOD        = 'modServer';
    const DEV        = 'devServer';
  }
  
  interface Settings {
    const MYSQL_HOSTNAME = 'mysqlHostname';
    const MYSQL_USERNAME = 'mysqlUsername';
    const MYSQL_PASSWORD = 'mysqlPassword';
    const MYSQL_DATABASE = 'mysqlDatabase';
    
    const ITEM_INI      = 'itemINI';
    const FLOOR_INI     = 'floorINI';
    const IGLOO_INI     = 'iglooINI';
    const CENSOR_INI    = 'censorINI';
    const FURNITURE_INI = 'furnitureINI';
    
    const MONEYMAKER_BAN = 'moneymakerBan';
    const DIGHACK_BAN    = 'dighackBan';
    const MODSERVER_MSG  = 'moderatorserverMessage';
    const SERVERDOWN_MSG = 'serverdownMessage';
    
    const LOGIN_KEY_LIFE_TIME = 'loginKeyLifeTime';
    const SERVER_LOAD_DIVISOR = 'serverLoadDivisor';
    const SERVER_IDLE_TIME    = 'serverIdleTime';
    const USER_IDLE_TIME      = 'userIdleTime';
    const USER_IDLE_CHECK     = 'userIdleCheck';
    const ROOM_LIMIT          = 'roomLimit';
    const DIG_TTL             = 'digHackTimeToLife';
    const KICK_LIMIT          = 'moderatorKickLimit';
    const MUTE_LIMIT          = 'moderatorMuteLimit';
    const MOVE_LIMIT          = 'moderatorMoveLimit';
    
    const PUFFLE_MINLEN = 'puffleMinLength';
    const PUFFLE_MAXLEN = 'puffleMaxLength';
    const PUFFLE_CHARS  = 'puffleChars';
    
    const PLAYER_MINLEN = 'playerMinLength';
    const PLAYER_MAXLEN = 'playerMaxLength';
    const PLAYER_CHARS  = 'playerChars';
    
    const PASSWORD_MINLEN = 'passwordMinLength';
    const PASSWORD_MAXLEN = 'passwordMaxLength';
    
    const REHASH_PERIOD = 'rehashingPeriod';
    const MODERATOR_TTL = 'modSessionLifeTime';
    
    const EMAIL_MINLEN = 'emailMinLength';
    const EMAIL_MAXLEN = 'emailMaxLength';
    const EMAIL_CHARS  = 'emailChars';
    
    const MAX_MOOD_LENGTH = 'maximalMoodLength';
  }
  
  final class SettingsManager {
    public static $settings;
    public static $servers;
    
    public static function AddSetting($func_setting, $func_data) { return self::$settings[$func_setting] = $func_data; }
    public static function GetSetting($func_setting)             { return self::$settings[$func_setting];              }
    
    public static function AddServer($func_ID, $func_data) { return self::$servers[$func_ID] = $func_data; }
    public static function GetServer($func_ID)             { return self::$servers[$func_ID];              }
  }

?>