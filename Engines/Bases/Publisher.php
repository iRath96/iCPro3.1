<?php

  abstract class Publisher { //... BaseClass to give observers the ability to subscribe to events ...//
    public $listeners = array();
    
    public function addListener($evt, $callback, $id = false) {
      if(is_array($evt)) {
        foreach($evt as $theEvent) $this->addListener($theEvent, $callback, $id);
        return true;
      }
      
      is_array(@$this->listeners[$evt]) || $this->listeners[$evt] = array();
      if($id === false) $this->listeners[$evt][] = $callback;
      else $this->listeners[$evt][$id] = $callback;
      return $id === false ? end($this->listeners) : $id; //... Sure its end? ...//
    }
    
    public function removeListener($evt, $callback) {
      if(!is_array(@$this->listeners[$evt])) return false;
      $rem = false;
      $use = is_callable($callback) ? 'value' : 'key';
      foreach($this->listeners[$evt] as $key => $value) if($$use === $callback) { $rem = array($key, $value); unset($this->listeners[$evt][$key]); break; }
      return $rem;
    }
    
    public function dispatchEvent($evt) {
      $args = func_get_args();
      $args[0] = array(
        'sender' => $this,
        'event' => $args[0]
      ); foreach(@$this->listeners[$evt] ?: array() as $listener) call_user_func_array($listener, $args);
    }
  }
  
?>