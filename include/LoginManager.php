<?php

require('Session.php');
require_once('define.php');

define('GET_USER_INFO', 'SELECT * FROM ' . USERS_TABLE . ' WHERE username = \':1\'');
define('ADD_USER', 'INSERT INTO ' . USERS_TABLE . '
    (username, password, date, access) VALUES (\':1\', \':2\', :3, :4)');

class LoginManager {

    private $db;

    function LoginManager($db) {
        $this->db = $db;
    }

    function login($username, $password, $autologin) {
        // check that username and password are not empty
        if (trim($username) == "" || trim($password) == "") {
            return "Wrong login or password!";
        }

        $this->db->connect();

        $query = Database::buildQuery(GET_USER_INFO, array($username), $this->db);
        $re = $this->db->query($query);
        if (!$re) {
            return "Database error or user not found!";
        }

        $user = Database::fetchArray($re);
        if (!$user) {
            return "Database error or user not found!";
        }

        if ($user['password'] === sha1($password)) {
            session_begin($user['user_id'], $user['username'], $user['access'], $autologin);
            return true;
        } else {
            return "Wrong login or password!";
        }
    }

    function register($username, $password) {
        // check that username and password are not empty
        if (trim($username) == "" || trim($password) == "") {
            return "Username and password required!";
        }

        $this->db->connect();

        $query = Database::buildQuery(GET_USER_INFO, array($username), $this->db);
        $re = $this->db->query($query);
        if ($re) {
            $user = @Database::fetchArray($re);
            if ($user) {
                if ($username == $user['username']) {
                    return "User already exists!";
                }
            }
        }

        $query = Database::buildQuery(ADD_USER, array($username, sha1($password), time(), 0), $this->db);
        //echo $query . '<br>';
        if ($this->db->query($query)) {
            session_begin(mysql_insert_id(), $username, 0, true);
            //$this->login($username, $password, true);
            return "Registration successful - logged in";
        } else {
            //echo mysql_error() . '<br>';
            return "Database error while saving user!";
        }
    }

}

?>
