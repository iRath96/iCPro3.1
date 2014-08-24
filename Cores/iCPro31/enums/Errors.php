<?php
  
  namespace iCPro;
  interface Errors {
    const NO_CONNECTION             = 0;
    const CONNECTION_LOST           = 1;
    const TIME_OUT                  = 2;
    const MULTI_CONNECTIONS         = 3;
    const DISCONNECT                = 4;
    const KICK                      = 5;
    const NAME_NOT_FOUND            = 100;
    const PASSWORD_WRONG            = 101;
    const SERVER_FULL               = 103;
    const PASSWORD_REQUIRED         = 130;
    const PASSWORD_SHORT            = 131;
    const PASSWORD_LONG             = 132;
    const NAME_REQUIRED             = 140;
    const NAME_SHORT                = 141;
    const NAME_LONG                 = 142;
    const LOGIN_FLOODING            = 150;
    const PLAYER_IN_ROOM            = 200;
    const ROOM_FULL                 = 210;
    const GAME_FULL                 = 211;
    const ROOM_CAPACITY_RULE        = 212;
    const ITEM_IN_HOUSE             = 400;
    const NOT_ENOUGH_COINS          = 401;
    const ITEM_NOT_EXIST            = 402;
    const NAME_NOT_ALLOWED          = 441;
    const PUFFLE_LIMIT_M            = 440;
    const PUFFLE_LIMIT_NM           = 442;
    const BAN_DURATION              = 601;
    const BAN_AN_HOUR               = 602;
    const BAN_FOREVER               = 603;
    const AUTO_BAN                  = 610;
    const GAME_CHEAT                = 800;
    const ACCOUNT_NOT_ACTIVATE      = 900;
    const BUDDY_LIMIT               = 901;
    const NO_PLAY_TIME              = 910;
    const OUT_PLAY_TIME             = 911;
    const GROUNDED                  = 913;
    const PLAY_TIME_OVER            = 914;
    const SYSTEM_REBOOT             = 990;
    const NOT_MEMBER                = 999;
    const NO_DB_CONNECTION          = 1000;
    const TIME_WARNING              = 10001;
    const TIMEOUT                   = 10002;
    const PASSWORD_SAVE_PROMT       = 10003;
    const SOCKET_LOST_CONNECTION    = 10004;
    const LOAD_ERROR                = 10005;
    const MAX_IGLOO_FURNITURE_ERROR = 10006;
    const MULTIPLE_CONNECTIONS      = 10007;
    const CONNECTION_TIMEOUT        = 10008;
    const PUFFLE_INVALID            = -1; //... Error Code unknown ...//
    const MYSQL_ERROR               = -1; //... Error Code unknown ...//
    
    const R_CONNECTION_LOST     = 1;
    const R_ALREADY_HAVE_ITEM   = 2;
    const R_SERVER_FULL         = 103;
    const R_UNKNOWN_BOOK        = 710;
    const R_REDEEMED_BOOK       = 711;
    const R_WRONG_BOOK_ANSWER   = 712;
    const R_BOOK_FLOOD          = 713;
    const R_UNKNOWN_CODE        = 720;
    const R_REDEEMED_CODE       = 721;
    const R_CODE_FLOOD          = 722;
    const R_UNKNOWN_CATALOG     = 723;
    const R_NO_EXCLUSE_REDEEMS  = 724;
    const R_CODE_GROUP_REDEEMED = 725;
    const R_LONG_CODE           = 1702;
    const R_SHORT_CODE          = 1703;
  }
  
?>