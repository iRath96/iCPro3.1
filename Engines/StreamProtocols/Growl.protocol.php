<?php

  /*
    Growl Client:
      Made by ClickOnTyler (http://clickontyler.com/php-growl/),
      Rewritten by Alexander Rath to give further Possibilities and
      another Program Design matching the Coding Standards of iFox.
      
      Changes made:
        - Now using "Enums" (Interface Constants) GrowlPriorities and GrowlDefaults
        - Now supporting a smart Constructor
        - Now supporting static Context
        - Now supporting Streams
  */

  interface GrowlPriorities {
    const LOW       = -2;
    const MODERATE  = -1;
    const NORMAL    = 0;
    const HIGH      = 1;
    const EMERGENCY = 2;
  }

  interface GrowlDefaults {
    const IP = '127.0.0.1';
    const PORT = 9887;
    const APP_NAME = 'PHP Growl';
  }

  final class GrowlClient {
    public $appName;
    public $hostname;
    public $password;
    public $notifications = array();
    
    public function __construct() { call_user_func_array(array($this, 'init'), func_get_args()); }
    public function init($func_hostname = GrowlDefaults::IP) {
      $func_password = func_num_args() > 1 ? (string) func_get_arg(1) : '';
      $func_appName  = func_num_args() > 2 ? func_get_arg(2) : GrowlDefaults::APP_NAME;
      
      $this->appName  = utf8_encode($func_appName);
      $this->hostname = strpos($func_hostname, ':') ? $func_hostname : $func_hostname . ':' . GrowlDefaults::PORT;
      $this->password = $func_password;
    }
    
    public function addNotification($func_name, $func_enabled = true) {
      $this->notifications[] = array('name' => utf8_encode($func_name), 'enabled' => $func_enabled);
      return each($this->notifications);
    }
    
    public function register() {
      $func_packet      = '';
      $func_defaults    = '';
      $func_numDefaults = 0;
      
      foreach($this->notifications as $func_index => $func_notification) {
        $func_packet .= pack('n', strlen($func_notification['name'])) . $func_notification['name'];
        if($func_notification['enabled']) {
          $func_defaults .= pack('c', $func_index);
          ++$func_numDefaults;
        }
      }

      // pack(Protocol version, type, app name, number of notifications to register)
      $func_packet  = pack('c2nc2', 1, 0, strlen($this->appName), count($this->notifications), $func_numDefaults) . $this->appName . $func_packet . $func_defaults;
      $func_packet .= pack('H32', md5($func_packet . $this->password));

      return $this->sendPacket($func_packet);
    }
    
    public function notify($func_name, $func_title, $func_message, $func_priority = 0, $func_sticky = false) {
      $func_name     = utf8_encode($func_name);
      $func_title    = utf8_encode($func_title);
      $func_message  = utf8_encode($func_message);
      $func_priority = (integer) $func_priority;

      $func_flags = ($func_priority & 7) * 2;
      if($func_priority < 0) $func_flags |= 8;
      if($func_sticky) $func_flags |= 256;

      // pack(protocol version, type, priority/sticky flags, notification name length, title length, message length. app name length)
      $func_packet  = pack('c2n5', 1, 1, $func_flags, strlen($func_name), strlen($func_title), strlen($func_message), strlen($this->appName));
      $func_packet .= $func_name . $func_title . $func_message . $this->appName;
      $func_packet .= pack('H32', md5($func_packet . $this->password));

      return $this->sendPacket($func_packet);
    }

    private function sendPacket($func_packet) {
      if(function_exists('socket_create') && function_exists('socket_sendto')){
        $func_socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        return socket_sendto($func_socket, $func_packet, strlen($func_packet), 0x100, strstr($this->hostname, ':', true), substr(strstr($this->hostname, ':'), 1));
      } elseif(function_exists('fsockopen')) {
        $func_socket = fsockopen('udp://' . $this->hostname);
        fwrite($func_socket, $func_packet);
        return fclose($func_socket);
      }

      return false;
    }
  }
  
  final class Growl {
    public static $growlClient;
    public static function __callStatic($func_name, $func_args) {
      if(!self::$growlClient) self::$growlClient = new GrowlClient();
      return call_user_func_array(array(self::$growlClient, strtolower($func_name{0}) . substr($func_name, 1)), $func_args);
    }
  }
  
  final class GrowlStream {
    private $growlClient;
    public function stream_open($func_path, $func_mode, $func_options, &$func_openedPath) {
      if($func_mode != 'r') return print('You can\'t create a GrowlStream with Write Permissions') && false;
      
      $func_url = parse_url($func_path);
      $func_host = $func_url['host'] ?: GrowlDefaults::IP;
      $func_port = $func_url['port'] ?: GrowlDefaults::PORT;
      $func_pass = $func_url['pass'] ?: $func_url['user'] ?: '';
      $func_name = substr($func_url['path'], 1) ?: GrowlDefaults::APP_NAME;
      
      $this->growlClient = new GrowlClient($func_host . ':' . $func_port, $func_pass);
      $this->growlClient->addNotification('GrowlStream');
      $this->growlClient->register();
      
      return true;
    }

    public function stream_read($func_count) {
      return 'You can\'t read a GrowlStream';
    }

    public function stream_write($func_data) {
      list($func_title, $func_message) = explode(chr(0), $func_data);
      $this->growlClient->notify('GrowlStream', $func_title, $func_message);
      return strlen($func_data);
    }

    public function stream_tell() {
      return 0;
    }

    public function stream_eof() {
      return true;
    }

    public function stream_seek($func_offset, $func_whence) {
      return false;
    }
  }

  stream_wrapper_register('growl', 'GrowlStream') or die('Failed to register Growl Protocol');
  
  /*
  $growl = new GrowlClient('localhost', 'password');
  $growl->addNotification('Test');
  $growl->register();
  $growl->notify('Test', 'Header', 'Message);
  */
  
?>