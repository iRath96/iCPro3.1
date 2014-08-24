<?php
  
  # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
  # Just a Wrapper-Class to match the OOP-Standard of iFox. #
  # And to add a few useful Functions like MySQL::Insert(). #
  # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
  
  final class MySQL {
    const ASSOC = MYSQL_ASSOC;
    
    public static function Connect($host, $user, $pass) { return mysql_connect($host, $user, $pass); }
    public static function SelectDb($database)          { return mysql_select_db($database);         }
    public static function FetchArray(&$res, $type)     { return mysql_fetch_array($res, $type);     }
    public static function FreeResult(&$res)            { return mysql_free_result($res);            }
    public static function Error()                      { return mysql_error();                      }
    
    public static function &Query($query) {
      echo 'mysql: ' . $query . "\n";
      $result = mysql_query($query);
      if(!$result) echo "(failed): " . mysql_error() . "\n";
      return $result;
    }
    
    public static function GetData($statement) {
      $ret = array();
      $res = self::Query($statement);
      while($line = self::FetchArray($res, MySQL::ASSOC)) $ret[] = $line;
      MySQL::FreeResult($res);
      return $ret;
    }
    
    public static function Select($table, $where = array()) {
      $query = 'SELECT * FROM ' . $table;
      return MySQL::GetData($query . MySQL::TransformWhere($where));
    }
    
    public static function Insert($table, $data) {
      $values = $keys = array();
      foreach($data as $key => $value) {
        $keys[] = (string)$key;
        $values[] = MySQL::SafeValue($value);
      }
      
      return MySQL::Query('INSERT INTO ' . $table . ' (' . join(', ', $keys) . ') VALUES (' . join(', ', $values) . ')');
    }
    
    public static function Update($table, $data, $where = array()) {
      $sets = array();
      foreach($data as $key => $value)
        $sets[] = ((string)$key) . ' = ' . MySQL::SafeValue($value);
      return MySQL::Query('UPDATE ' . $table . ' SET ' . join(', ', $sets) . MySQL::TransformWhere($where));
    }
    
    public static function SafeValue($value) {
      if(is_numeric($value)) return (integer)$value;
      /* Apple is AWESOME */ else return '"' . ((string)MySQL::SafeString($value)) . '"';
    }
    
    public static function SafeString($string) { return $string; } //... Work on this a little ...//
    
    public static function TransformWhere($where) {
      if(is_string($where)) return ' WHERE ' . $where;
      if(is_array($where))
        if(count($where) > 0) {
          $rules = array();
          foreach($where as $key => $value) $rules[] = $key . ' = ' . MySQL::SafeValue($value);
          return ' WHERE ' . join(' AND ', $rules);
        } else return '';
      
      var_dump("Jack: 'Excuse me?' | Could not transform 'where'", $where);
      return '';
    }
  }

?>