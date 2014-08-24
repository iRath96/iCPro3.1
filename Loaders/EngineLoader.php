<?php
  
  interface Engines {
    const DEBUGGER         = 'Bases/Debugger.php';
    const PUBLISHER        = 'Bases/Publisher.php';
    const PACKET_ANALIZER  = 'Packets/PacketAnalizer.php';
    const TIMEOUT_MANAGER  = 'Optional/TimeoutManager.php';
    const SETTINGS_MANAGER = 'Optional/SettingsManager.php';
    const ISERVER          = 'Sockets/iServer.php';
    const ICLIENT          = 'Sockets/iClient.php';
    const SOCKS            = 'Sockets/SOCKS5.php';
    const MYSQL            = 'MySQL/MySQL.php';
    
    // depraced soon //
    const EVENT_LISTENER   = 'Bases/EventListener.php';
  }
  
  function __autoload($func_class) {
    EngineLoader::LoadClass($func_class);
  }
  
  final class EngineLoader {
    public static function LoadEngine($func_engine) {
      if(substr($func_engine, -4) != '.php') $func_engine = CoreUtils::RetrieveEnum('Engines', $func_engine);
      require_once 'Engines/' . $func_engine;
    }
    
    public static function LoadSocketTemplate($func_server) {
      REQUIRED_CLASSES; {
        BASE_CLASSES; {
          EngineLoader::LoadEngine(Engines::DEBUGGER);
          EngineLoader::LoadEngine(Engines::PUBLISHER);
        }
        
        SOCKET_CLASSES; {
          EngineLoader::LoadEngine(Engines::PACKET_ANALIZER);
          EngineLoader::LoadEngine($func_server ? Engines::ISERVER : Engines::ICLIENT);
        }
        
        MYSQL_CLASSES; {
          EngineLoader::LoadEngine(Engines::MYSQL);
        }
      }
    }
    
    public static function LoadClass($func_class) {
      switch($func_class) {
      //case 'Socks': return self::LoadEngine(Engines::SOCKS);
        case 'XTParser': case 'XMLParser': case 'JSONParser': case 'SerieParser': case 'YAMLParser': return PacketAnalizer::LoadParser($func_class);
        case 'MySQL': return self::LoadEngine(Engines::MYSQL);
        case 'iServer': return self::LoadEngine(Engines::ISERVER);
        case 'iClient': return self::LoadEngine(Engines::ICLIENT);
        case 'DebugFlags': case 'Debugger': case 'StreamProtocols': return self::LoadEngine(Engines::DEBUGGER);
        case 'PacketAnalizer': case 'PacketTypes': return self::LoadEngine(Engines::PACKET_ANALIZER);
        case 'TimeoutManager':  return self::LoadEngine(Engines::TIMEOUT_MANAGER);
        case 'SettingsManager': return self::LoadEngine(Engines::SETTINGS_MANAGER);
        case 'Publisher': return self::LoadEngine(Engines::PUBLISHER);
        
        // depraced soon //
        case 'EventListener': case 'Events': return self::LoadEngine(Engines::EVENT_LISTENER);
      }
      
      echo "!! WARNING !! Could not load {$func_class}!\n";
    }
    
    public static function LoadServerTemplate() { return self::LoadSocketTemplate(true);  }
    public static function LoadClientTemplate() { return self::LoadSocketTemplate(false); }
  }

?>