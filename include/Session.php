<?php

/* * ****************************************************************************
  Author: 3DM@rk
  December 2009
  for dasnet.cz
  (session.php)
 * **************************************************************************** */

/**
 * Starts a browser session.
 *
 * @param $user_id - ID of the user that has successfully logged in
 * @param $username - username of the user that has successfully logged in
 * @param $privs - user privileges (int)
 * @param $autologin - 0: temporary login, 1: permanently logged in
 *
 */
function session_begin($user_id, $username, $privs, $autologin) {
    global $db;
    //set some variables
    $now = time();
    $user_id = intval($user_id);
    $browser = substr(htmlspecialchars((string) $_SERVER['HTTP_USER_AGENT']), 0, 255);
    $ip = substr(htmlspecialchars($_SERVER['REMOTE_ADDR']), 0, 40);

    $session_id = gen_session_id($browser, $ip);

    //delete any old sessions of this user, if he requested autologin
    if ($autologin) {
        $sql = 'DELETE FROM ' . SESSIONS_TABLE . ' WHERE userId = ' . $user_id;
        @mysql_query($sql);
    }

    //create the database query
    $sql = sprintf('INSERT INTO ' . SESSIONS_TABLE . ' VALUES (\'%s\', \'%s\', %d, %d, %d, %d, \'%s\', \'%s\', %d)',
                    mysql_real_escape_string($session_id), //sessionId
                    mysql_real_escape_string($username), //username
                    $user_id, //userId
                    $privs, //userPrivs
                    $now, //start
                    $now, //lastActive
                    mysql_real_escape_string($browser), //browser
                    mysql_real_escape_string($ip), //ip
                    $autologin        //autologin
    );

    //save the session into the database
    mysql_query($sql, $db->getDb());

    //put a cookie with the session ID to the client's computer
    if ($autologin) {
        setcookie("gallery_ssid", $session_id, $now + SESSION_LIFETIME, APP_ROOT, DOMAIN);
    } else {
        setcookie("gallery_ssid", $session_id, 0, APP_ROOT, DOMAIN);
    }
}

/**
 * Generates a unique session ID.
 *
 * @param $key1
 * @param $key2
 *
 * @return - string 32 hex characters (the unique ID)
 */
function gen_session_id($key1, $key2) {
    $prefix = substr(md5($key1 . $key2 . microtime(true)), 0, 19);

    $id = uniqid($prefix);

    return substr($id, 0, 32);
}

/**
 * Tries to resume an existing session.
 *
 * @return -	false if the session has expired or doesn't exist
 * 		  -	array with the session data on success
 */
function session_resume() {
    global $db;
    //if there's no cookie saved, no session has been started
    if (!isset($_COOKIE['gallery_ssid'])) {
        return false;
    }

    //verify the session ID in the database
    $session_id = mysql_real_escape_string($_COOKIE['gallery_ssid'], $db->getDb());

    $sql = 'SELECT * FROM ' . SESSIONS_TABLE . " WHERE sessionId = '$session_id'";

    $result = mysql_query($sql, $db->getDb());

    //wrong session ID
    if (!$result || @mysql_num_rows($result) < 1) {
        return false;
    }
    //existing session
    else {
        $session_data = mysql_fetch_array($result);

        foreach ($session_data as $key => $value) {
            $session_data[$key] = stripslashes($value);
        }

        //check if the session hasn't expired yet and if the IP and browser matches
        if ((time() > $session_data['lastActive'] + SESSION_LIFETIME && !$session_data['autologin'])
                || htmlspecialchars($_SERVER['REMOTE_ADDR']) !== $session_data['ip']
                || htmlspecialchars((string) $_SERVER['HTTP_USER_AGENT']) !== $session_data['browser']
        ) {
            return false;
        } else {
            session_update($session_id);
            return $session_data;
        }
    }
}

/**
 * Deletes old sessions from the database.
 *
 */
function clean_sessions() {
    global $db;
    $now = time();

    $sql = 'DELETE FROM ' . SESSIONS_TABLE . " WHERE autologin = 0 AND lastActive+900 < $now";

    @mysql_query($sql, $db->getDb());
}

/**
 * Updates a session.
 *
 * @param $ssid - ID of the session to update
 */
function session_update($ssid) {
    global $db;
    $now = time();

    $sql = 'UPDATE ' . SESSIONS_TABLE . " SET lastActive = $now WHERE sessionId = '$ssid'";

    @mysql_query($sql, $db->getDb());
}

/**
 * Destroys a session.
 *
 * @param $ssid - ID of the session to destroy
 */
function session_kill($ssid) {
    global $db;
    $now = time();

    //unset the cookie
    setcookie("gallery_ssid", "", $now - 3600, APP_ROOT, DOMAIN);

    //delete the session from database
    $sql = 'DELETE FROM ' . SESSIONS_TABLE . " WHERE sessionId = '$ssid'";

    @mysql_query($sql, $db->getDb());
}

?>