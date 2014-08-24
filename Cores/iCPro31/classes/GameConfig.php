<?php
  
  namespace iCPro;
  final class GameConfig {
    const DEFAULT_CACHE_TTL = 86400; // One day.
    
    public static $d = array();
    public static function Load(array $resources, $cacheTTL = self::DEFAULT_CACHE_TTL) {
      $locals = array();
      
      foreach($resources as $name => $filename) {
        \Debugger::Debug(\DebugFlags::J_FINE, sprintf('Loading GameConfig for <b>%s</b>', htmlentities($name)));
        
        $locals[$name] = array($filename, $local = DATA_DIR . '/game-config/' . $filename . '.json');
        if(file_exists($local) && filemtime($local) > time() - $cacheTTL) continue; // We have this file and it hasn't expired.
        
        $global = 'http://media1.clubpenguin.com/play/en/web_service/game_configs/' . $filename . '.json';
        $content = @file_get_contents($global);
        
        if(!$content) { // TODO: Maybe display file-age in the debugging-line below.
          if(file_exists($local)) \Debugger::Debug(\DebugFlags::J_INFO, sprintf('Cannot load <b>%s</b>, using local file.', htmlentities($global)));
          else {
            \Debugger::Debug(\DebugFlags::J_WARNING, sprintf('Cannot load <b>%s</b>, no local version available.', htmlentities($global)));
            unset($locals[$name]);
          } continue;
        }
        
        $parsed = @json_decode($content);
        if(is_null($parsed)) {
          if(file_exists($local)) \Debugger::Debug(\DebugFlags::J_WARNING, sprintf('Could not parse <b>%s</b>, using local file.', htmlentities($global)));
          else {
            \Debugger::Debug(\DebugFlags::J_WARNING, sprintf('Cannot parse <b>%s</b>, no local version available.', htmlentities($global)));
            unset($locals[$name]);
          } continue;
        }
        
        // Thought: Maybe match this against our local version to ensure it's valid.
        
        file_put_contents($local, $content);
        \Debugger::Debug(\DebugFlags::J_FINE, sprintf('- Loaded <b>%s</b> from media1.clubpenguin.com', htmlentities($name)));
        
        self::Set($filename, $name, $parsed);
        unset($locals[$name]);
      }
      
      foreach($locals as $name => $filenames) {
        list($filename, $local) = $filenames;
        \Debugger::Debug(\DebugFlags::J_FINE, sprintf('- Loaded <b>%s</b> from local file', htmlentities($name)));
        
        $content = file_get_contents($local);
        self::Set($filename, $name, json_decode($content));
      }
    }
    
    private static function Set($filename, $name, $parsed) {
      switch($filename) {
        case 'paper_items': $value = self::IndexBy($parsed, 'paper_item_id'); break;
        case 'igloo_locations': $value = self::IndexBy($parsed, 'igloo_location_id'); break;
        case 'igloos': $value = self::IndexBy($parsed, 'igloo_id'); break;
        case 'igloo_floors': $value = self::IndexBy($parsed, 'igloo_floor_id'); break;
        case 'furniture_items': {
          $value = self::IndexBy($parsed, 'furniture_item_id');
          foreach($value as $entry) {
            $entry->is_member = $entry->is_member_only == '1';
            $entry->is_redeemable = $entry->is_redeemable == '1';
            $entry->max_quantity = (integer)$entry->max_quantity;
            
            unset($entry->is_member_only);
          }
        }; break;
        default: $value = $parsed;
      } self::$d[$name] = $value;
    }
    
    private static function IndexBy($array, $path) {
      $value = array();
      foreach($array as $entry) $value[(integer)$entry->$path] = $entry;
      return $value;
    }
  }
  
  GameConfig::Load(array(
    'rooms'           => 'rooms',
    'games'           => 'games',
    'stamps'          => 'stamps',
    'items'           => 'paper_items',
    'frames'          => 'penguin_action_frames',
    'iglooItems'      => 'furniture_items',
    'iglooBuildings'  => 'igloos',
    'iglooLocations'  => 'igloo_locations',
    'iglooMusic'      => 'igloo_music_tracks',
    'iglooFloors'     => 'igloo_floors',
    'puffles'         => 'puffles',
    'puffleItems'     => 'puffle_items',
    'postcards'       => 'postcards'
  ));
  
?>