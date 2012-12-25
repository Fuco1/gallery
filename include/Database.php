<?php

require(INCLUDE_HOME . "Image.php");
/*

  Data structures

  +-----------+
  | IMAGE     |
  +-----------+
  | image_id  |
  | name      |
  | author_id |
  | date      |
  | views     |
  +-----------+

  +--------+
  | TAG    |
  +--------+
  | tag_id |
  | name   |
  +--------+

  +------------+
  | IMAGE_TAGS |
  +------------+
  | image_id   |
  | tag_id     |
  +------------+

 */

class Database {

    private $db = null;
    private $host, $user, $password, $dbname;

    function Database($host, $user, $password, $dbname) {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->dbname = $dbname;
    }

    function connect() {
        if (!$this->db) {
            $this->db = mysql_connect($this->host, $this->user, $this->password);
            if (!$this->db)
                die("Could not connect: " . mysql_error());

            $db_selected = mysql_select_db($this->dbname, $this->db);
            if (!$db_selected) {
                $this->db = null;
                die("Could not connect: " . mysql_error());
            }
        }
    }

    function close() {
        return mysql_close($this->db);
    }

    function query($query) {
        if (!$this->db) {
            $this->connect();
        }

        return mysql_query($query);
    }

    static function fetchArray($resultSet) {
        return mysql_fetch_assoc($resultSet);
    }

    /* static function fetchObject($resultSet) {
      return mysql_fetch_object($resultSet);
      } */

    static function fetchObject($resultSet, $className) {
        return mysql_fetch_object($resultSet, $className);
    }

    function getDb() {
        return $this->db;
    }

    static function buildQuery($query, $values, $db_handle) {
        foreach ($values as &$value) {
            $value = mysql_real_escape_string($value, $db_handle->getDb());
        }

        return str_replace(
                array(":1", ":2", ":3", ":4", ":5", ":6", ":7", ":8", ":9", ":10",
                    ":11", ":12", ":13", ":14", ":15", ":16", ":17", ":18", ":19", ":20"),
                $values, $query);
    }

}

?>
