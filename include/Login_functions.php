<?php

/******************************************************************************
    Author: 3DM@rk
    December 2009
    for dasnet.cz
    (login_functions.php)
******************************************************************************/

require('Session.php');
require_once('define.php');

class LoginManager {
    private $db;

    function setDb($db) {
        $this->db = $db;
    }

    /**
     * Tries to login the user, using the provided login form data
     *
     * @param $username - directly from the form
     * @param $password - directly from the form
     * @param $autologin - boolean, true if autologin was requested
     *
     * @return 	- boolean true, if the login was successful
     *       	- string Error message, if the login failed
     */
    function login_eval($username, $password, $autologin) {
        $this->db->connect();

        // check that username and password are not empty
        if(trim($username) == "" || trim($password) == "") {
            return "Musíte zadat jméno i heslo.";
        }

        $username = mysqli_real_escape_string(trim($username), $this->db->getDb());
        $autologin = $autologin === true ? 1 : 0;

        if(strlen($username) > 255 || strlen($password) > 255) {
            return "Chybné přihlašovací údaje.";
        }

        //get user data from the database
        $user_data = $this->get_user_data($username);

        //user doesn't exist
        if($user_data === false) {
            return "Chybné přihlašovací údaje.";
        }

        //now check the password
        $pass_hash = stripslashes($user_data['user_password']);
        $user_id = $user_data['user_id'];

        //wrong password
        if(!$this->phpbb_check_hash($password, $pass_hash)) {
            return "Chybné přihlašovací údaje.";
        }
        //correct password
        else {
            $privs = $this->get_user_privs($user_id);

            //check that the user has sufficient privileges
            //if($privs > 0) {
            clean_sessions();
            session_begin($user_id, $username, $privs, $autologin);
            return true;
        //            }
        //            else {
        //                return "Nemáte dostatečná oprávnění.";
        //            }
        }
    }

    //-----------------------------------------------------------

    /**
     * Retrieves password hash and user ID from the database, based on the provided username
     *
     * @param $username
     *
     * @return 	- boolean false, if the user was not found
     *       	- fetched array on success
     */
    function get_user_data($username) {

        $sql = 'SELECT user_id, user_password FROM '.PHPBB_DATABASE.'.'.PHPBB_PREFIX.'users WHERE username = \''.$username.'\'';

        $result = mysqli_query($sql, $this->db->getDb());

        if(!$result || @mysqli_num_rows($result) < 1) {
            echo mysqli_error($this->db->getDb());
            return false;
        }
        else {
            $row = mysqli_fetch_array($result);

            return $row;
        }

    }

    //----------------------------------------------------------

    /**
     * Check for correct password
     *
     * @param string $password The password in plain text
     * @param string $hash The stored password hash
     *
     * @return bool Returns true if the password is correct, false if not.
     */
    function phpbb_check_hash($password, $hash) {
        $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        if (strlen($hash) == 34) {
            return ($this->_hash_crypt_private($password, $hash, $itoa64) === $hash) ? true : false;
        }

        return (md5($password) === $hash) ? true : false;
    }

    //---------------------------------------------------------------

    /**
     * Encode hash
     */
    function _hash_encode64($input, $count, &$itoa64) {
        $output = '';
        $i = 0;

        do {
            $value = ord($input[$i++]);
            $output .= $itoa64[$value & 0x3f];

            if ($i < $count) {
                $value |= ord($input[$i]) << 8;
            }

            $output .= $itoa64[($value >> 6) & 0x3f];

            if ($i++ >= $count) {
                break;
            }

            if ($i < $count) {
                $value |= ord($input[$i]) << 16;
            }

            $output .= $itoa64[($value >> 12) & 0x3f];

            if ($i++ >= $count) {
                break;
            }

            $output .= $itoa64[($value >> 18) & 0x3f];
        }
        while ($i < $count);

        return $output;
    }

    //----------------------------------------------------------

    /**
     * The crypt function/replacement
     */
    function _hash_crypt_private($password, $setting, &$itoa64) {
        $output = '*';

        // Check for correct hash
        if (substr($setting, 0, 3) != '$H$') {
            return $output;
        }

        $count_log2 = strpos($itoa64, $setting[3]);

        if ($count_log2 < 7 || $count_log2 > 30) {
            return $output;
        }

        $count = 1 << $count_log2;
        $salt = substr($setting, 4, 8);

        if (strlen($salt) != 8) {
            return $output;
        }

        /**
         * We're kind of forced to use MD5 here since it's the only
         * cryptographic primitive available in all versions of PHP
         * currently in use.  To implement our own low-level crypto
         * in PHP would result in much worse performance and
         * consequently in lower iteration counts and hashes that are
         * quicker to crack (by non-PHP code).
         */
        if (PHP_VERSION >= 5) {
            $hash = md5($salt . $password, true);
            do {
                $hash = md5($hash . $password, true);
            }
            while (--$count);
        }
        else {
            $hash = pack('H*', md5($salt . $password));
            do {
                $hash = pack('H*', md5($hash . $password));
            }
            while (--$count);
        }

        $output = substr($setting, 0, 12);
        $output .= $this->_hash_encode64($hash, 16, $itoa64);

        return $output;
    }

    //----------------------------------------------------------

    /**
     * Get user privileges.
     *
     * @param int $user_id
     *
     * @return int - User privileges, 0 means no access
     */
    function get_user_privs($user_id) {

        $sql = 'SELECT group_id FROM '.PHPBB_DATABASE.'.'.PHPBB_PREFIX.'user_group WHERE user_id = '.$user_id.' AND user_pending = 0
    AND group_id IN ('.ADMIN_GROUP.')';

        $result = mysqli_query($sql, $this->db->getDb());

        if(!$result || @mysqli_num_rows($result) < 1) {
            return 0;
        }
        else {
            return 1;
        }

    }

//---------------------------------------------------------
}
?>
