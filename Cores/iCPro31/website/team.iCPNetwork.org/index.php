<style>
  body {
    font-family: Tahoma, Arial;
    font-size:   .7em;
  }
</style>
<title>iCanHazTheWorstHTMLandCSSofTheWorld!</title>
Welcome!<br />
You are connected to the Apache HTTPd on tcp://alexrath.gotdns.org:80/<br />
What do you want?<br />
<input id="xD" type="radio" onClick="javascript:alert('You fail :P');" />French Fries<br />
<input id="xD" type="radio" checked />ChickenMcNuggets<br />
<input id="xD" type="radio" onClick="javascript:alert('I ran out of Uranium, sry.');" />Uranium<br />
<br /><small>btw, why thiz is the worst HTML and CSS?<br />
No HTML Tags, <small>No HEAD Tags, <small>No BODY Tages, <small>TITLE Tag just randomly placed, <small>STYLE Tag doesnt take any Details, <small>Radio Boxes made bad and <small>no ending SMALL Tags<br />
Dont think this is my default Page Creation |-(<br />
I am just to lazy to create something with barely ANYBODY cares about
<?php

  $growl = new Growl('127.0.0.1', 'passwort');
  $growl->addNotification('Visitor');
  $growl->register();
  $isCP = $inCP ? 'Logging into iCP!' : '';
  $growl->notify('Visitor', 'Somebody connected...', '...to my HTTPd! ' . ($inRegister ? 'Trying to register!' : $isCP) . chr(10) . 'IP: ' . $_SERVER['REMOTE_ADDR']);//, 2, true);

    class Growl
    {
        const GROWL_PRIORITY_LOW = -2;
        const GROWL_PRIORITY_MODERATE = -1;
        const GROWL_PRIORITY_NORMAL = 0;
        const GROWL_PRIORITY_HIGH = 1;
        const GROWL_PRIORITY_EMERGENCY = 2;

        private $appName;
        private $address;
        private $notifications;
        private $password;
        private $port;

        public function __construct($address, $password = '', $app_name = 'PHP Growl')
        {
            $this->appName       = utf8_encode($app_name);
            $this->address       = $address;
            $this->notifications = array();
            $this->password      = $password;
            $this->port          = 9887;
        }

        public function addNotification($name, $enabled = true)
        {
            $this->notifications[] = array('name' => utf8_encode($name), 'enabled' => $enabled);
        }

        public function register()
        {
            $data         = '';
            $defaults     = '';
            $num_defaults = 0;

            for($i = 0; $i < count($this->notifications); $i++)
            {
                $data .= pack('n', strlen($this->notifications[$i]['name'])) . $this->notifications[$i]['name'];
                if($this->notifications[$i]['enabled'])
                {
                    $defaults .= pack('c', $i);
                    $num_defaults++;
                }
            }

            // pack(Protocol version, type, app name, number of notifications to register)
            $data  = pack('c2nc2', 1, 0, strlen($this->appName), count($this->notifications), $num_defaults) . $this->appName . $data . $defaults;
            $data .= pack('H32', md5($data . $this->password));

            return $this->send($data);
        }

        public function notify($name, $title, $message, $priority = 0, $sticky = false)
        {
            $name     = utf8_encode($name);
            $title    = utf8_encode($title);
            $message  = utf8_encode($message);
            $priority = intval($priority);

            $flags = ($priority & 7) * 2;
            if($priority < 0) $flags |= 8;
            if($sticky) $flags |= 256;

            // pack(protocol version, type, priority/sticky flags, notification name length, title length, message length. app name length)
            $data = pack('c2n5', 1, 1, $flags, strlen($name), strlen($title), strlen($message), strlen($this->appName));
            $data .= $name . $title . $message . $this->appName;
            $data .= pack('H32', md5($data . $this->password));

            return $this->send($data);
        }

        private function send($data)
        {
            if(function_exists('socket_create') && function_exists('socket_sendto'))
            {
                $sck = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
                socket_sendto($sck, $data, strlen($data), 0x100, $this->address, $this->port);
                return true;
            }
            elseif(function_exists('fsockopen'))
            {
                $fp = fsockopen('udp://' . $this->address, $this->port);
                fwrite($fp, $data);
                fclose($fp);
                return true;
            }

            return false;
        }
    }
    
?>