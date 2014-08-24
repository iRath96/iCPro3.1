<?php
  
  namespace iCPro;
  
  const ROOT = __DIR__;
  
  define('ROOT_DIR'  , __DIR__              );
  define('DATA_DIR'  , __DIR__ . '/data'    );
  define('ENUM_DIR'  , __DIR__ . '/enums'   );
  define('CLASS_DIR' , __DIR__ . '/classes' );
  
  \EngineLoader::LoadServerTemplate();
  
  require_once CLASS_DIR . '/Akwaya.php'; // Very important.
  
  require_once ENUM_DIR . '/Events.php';
  require_once ENUM_DIR . '/Errors.php';
  
  require_once CLASS_DIR . '/Servers/Server.php';
  require_once CLASS_DIR . '/Utils.php';
  require_once CLASS_DIR . '/UserLog.php';
  
  require_once 'config.php';
  
  # # # # # # # # # # # # # # # # #
  #  A little less conversation,  #
  # A little more action, please! # ~ Elvis
  # # # # # # # # # # # # # # # # #
  
  const CRASH_FLAG = 32768;
  
  \Debugger::AddStream(CRASH_FLAG, STDOUT);
  \Debugger::AddStream(CRASH_FLAG, fopen('CrashLog', 'a+'));
  \Debugger::AddStream(\DebugFlags::D_FINER,   STDOUT);
//\Debugger::AddStream(\DebugFlags::D_FINEST,  fsockopen('udp://alexrath.gotdns.org:9339/'));
  \Debugger::AddStream(\DebugFlags::D_FINEST,  fsockopen('udp://127.0.0.1:9339'));
  \Debugger::AddStream(\DebugFlags::D_SEVERE,  STDERR);
  \Debugger::AddStream(\DebugFlags::D_WARNING, fopen('DebugFile', 'a+'));
  
  \PacketAnalizer::LoadParser(XT_PACKET);
  \PacketAnalizer::LoadParser(XML_PACKET);
  
  $serverConfig = \iCPro\SettingsManager::GetServer(SERVER_ID);
  if($serverConfig === NULL) \Debugger::Debug(SEVERE, 'The Server <b>"' . SERVER_ID . '"</b> does <b>not</b> exist.');
  else {
    foreach($serverConfig as $key => $value) define('SERVER_' . strtoupper($key), $value);
    foreach(\iCPro\SettingsManager::GetServer(99) as $key => $value) define('LOGIN_'  . strtoupper($key), $value);
    
    if(SERVER_TYPE == ServerTypes::WORLD) {
      require_once CLASS_DIR . '/Users/WebMod.php';
      require_once CLASS_DIR . '/GameConfig.php';
      
      //var_dump(GameConfig::$d['items'][9258]);
      //die();
    }
    
    $serverClass = SERVER_TYPE == ServerTypes::LOGIN ? 'Login' : ((SERVER_TYPE == ServerTypes::REDEMPTION) ? 'Redemption' : 'World');
    require_once CLASS_DIR . '/Servers/' . $serverClass . '.Server.php';
    
    if(SERVER_TYPE == ServerTypes::WORLD)
      $func_addMsg  = sprintf(', http://%s:%d/', SERVER_IP, SERVER_PORT + 1);
    $func_message = 'The Server <b>%s</b> will be started on <b>%s:%d' . $func_addMsg . '</b> and <b>127.0.0.1:%d</b> now...';
    \Debugger::Debug(\DebugFlags::INFO, sprintf($func_message, SERVER_NAME, SERVER_IP, SERVER_PORT, SERVER_PORT));
    
    \iServer::Init();
    \iServer::AddSocket(SERVER_IP,   SERVER_PORT);
    if(SERVER_TYPE == ServerTypes::WORLD) \iServer::AddSocket(SERVER_IP,   SERVER_PORT + 1);
    \iServer::AddSocket('127.0.0.1', SERVER_PORT);
    \iServer::$escapeChar = chr(0);
    
    \Debugger::Debug(\DebugFlags::INFO, 'The Sockets have been <b>initialized</b>!');
    Utils::ConnectToSQL();
    
    echo chr(10);
    
    $serverClass = "\\iCPro\\Servers\\$serverClass";
    $SERVER = new $serverClass();
    $SERVER->internalInit();
    
    \EventListener::AddListener(\Events::NEW_CLIENT,          array($SERVER, 'handleClient'));
    \EventListener::AddListener(\Events::PACKET_RECEIVED,     array($SERVER, 'handlePacket'));
    \EventListener::AddListener(\Events::CLIENT_DISCONNECTED, array($SERVER, 'handleDisconnect'));
    \EventListener::AddListener(\Events::RAW_PACKET_RECV,     array($SERVER, 'handleRawPacket'));
    
    $mainLoop = function() use($SERVER) {
      while($SERVER->isRunning) {
        $SERVER->update();
        \iServer::Update();
        usleep(100000);
      }
    };
    
    register_shutdown_function(function() use($mainLoop, $errorHandler) {
      Akwaya::Notice('iCPro', "halting because of fatal error.");
    });
    
    $mainLoop();
  }

?>