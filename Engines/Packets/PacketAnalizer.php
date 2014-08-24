<?php

  interface PacketTypes {
    const XT_PACKET    = 'XTParser';
    const XML_PACKET   = 'XMLParser';
    const JSON_PACKET  = 'JSONParser';
    const YAML_PACKET  = 'YAMLParser';
    const SERIE_PACKET = 'SerieParser';
  }

  final class PacketAnalizer {
    public static function loadParser($func_name) {
      if(substr($func_name, -7) ==  '_PACKET') $func_name = CoreUtils::RetrieveEnum('PacketTypes', $func_name);
      include $func_name . '.php';
    }
    
    public static function LoadXTParser()    { return self::loadParser(PacketTypes::XT_PACKET);    }
    public static function LoadXMLParser()   { return self::loadParser(PacketTypes::XML_PACKET);   }
    public static function LoadJSONParser()  { return self::loadParser(PacketTypes::JSON_PACKET);  }
    public static function LoadYAMLParser()  { return self::loadParser(PacketTypes::YAML_PACKET);  }
    public static function LoadSerieParser() { return self::loadParser(PacketTypes::SERIE_PACKET); }
    
    public static function GetPacketType($func_packet) {
      if(function_exists('is_xt_packet'))    if(is_xt_packet($func_packet))    return PacketTypes::XT_PACKET;
      if(function_exists('is_xml_packet'))   if(is_xml_packet($func_packet))   return PacketTypes::XML_PACKET;
      if(function_exists('is_json_packet'))  if(is_json_packet($func_packet))  return PacketTypes::JSON_PACKET;
      if(function_exists('is_yaml_packet'))  if(is_yaml_packet($func_packet))  return PacketTypes::YAML_PACKET;
      if(function_exists('is_serie_packet')) if(is_serie_packet($func_packet)) return PacketTypes::SERIE_PACKET;
      
      return false;
    }
    
    public static function Decode(&$func_packet) {
      if(function_exists('is_xt_packet'))    if($func_data = is_xt_packet($func_packet))    { $func_packet = $func_data; return PacketTypes::XT_PACKET;    }
      if(function_exists('is_xml_packet'))   if($func_data = is_xml_packet($func_packet))   { $func_packet = $func_data; return PacketTypes::XML_PACKET;   }
      if(function_exists('is_json_packet'))  if($func_data = is_json_packet($func_packet))  { $func_packet = $func_data; return PacketTypes::JSON_PACKET;  }
      if(function_exists('is_yaml_packet'))  if($func_data = is_yaml_packet($func_packet))  { $func_packet = $func_data; return PacketTypes::YAML_PACKET;  }
      if(function_exists('is_serie_packet')) if($func_data = is_serie_packet($func_packet)) { $func_packet = $func_data; return PacketTypes::SERIE_PACKET; }
      
      return $func_packet;
    }
    
    public static function Encode(&$func_data, $func_type) { switch($func_type) {
      case XT_PACKET:       return $func_data = (function_exists('xt_encode') ?    xt_encode($func_data) : false);
      case XML_PACKET:     return $func_data = (function_exists('xml_encode') ?   xml_encode($func_data) : false);
      case JSON_PACKET:   return $func_data = (function_exists('json_encode') ?  json_encode($func_data) : false);
      case YAML_PACKET:   return $func_data = (function_exists('yaml_encode') ?  yaml_encode($func_data) : false);
      case SERIE_PACKET: return $func_data = (function_exists('serie_encode') ? serie_encode($func_data) : false);
      default:          return $func_data = (                                                              false);
    }}
  }
  
  function packet_encode($func_data, $func_type) { return PacketAnalizer::Encode($func_data, $func_type); }
  function packet_decode($func_data)             { return PacketAnalizer::Decode($func_data);             }

?>