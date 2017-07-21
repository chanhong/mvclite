<?php

namespace MvcLite;

class MvcAuth {

    const DOMAIN = "MVCLite";
    const ONEMONTH = 2592000;
       
    private static $me;
    private static $salt;
    private $loggedIn;
    private $nid;
    private $profile;
    // array('nid','username'...)
    public $expiryDate;
    public $ut;

// to be removed    
    public $id;
    public $username;
    public $user;
    
    
    public function __construct() {
        
        $this->ut = new Util;
        
        $this->nid = null;
        $this->loggedIn = false;
//        $this->expiryDate = mktime(0, 0, 0, 6, 2, 2037);
        $this->expiryDate = time() + self::ONEMONTH;

        // to be removed
        $this->id = null;
        $this->username = null;
        $this->user = null;
    }

    public static function getAuth($salt="") {
        
        if (is_null(self::$me)) {
            if (empty($salt)) {
                srand(time());
                $salt = md5(rand() . microtime());
            }
            self::$salt = $salt;
            self::$me = new MVCAuth();
            self::$me->init();
        }
        return self::$me;
    }

    public function init() {

        $this->setInitCookie();
        $this->loggedIn = $this->attemptCookieLogin();
    }

    public static function debug($iVar, $iStr = "", $iFormat = "") {
        
        return Util::debug($iVar, $iStr, $iFormat);
    }

     /* 
     * utilize debug default to br
     * @param $ivar $istr $iformat  
     * @return string 
     */ 
    public static function pln($iVar, $iStr = "", $iFormat = "br") {
    
        print Util::debug($iVar, $iStr, $iFormat);
    }
    
    public function winUser() {

        $winId = "";
        $eadUserName = $_SERVER['LOGON_USER']; // get Windows User ID without domain name
        if (!empty($eadUserName)) {
            $winUser = explode("\\", $eadUserName);
            array_shift($winUser);
            list($winId) = $winUser;
        }
        return $winId; // get Windows User ID without domain name
    }

    public function login($validate, $user) {
        
        $status = false;
        if (empty($validate) or empty($user)) return $status;
        
        // if login via form or win login
        if (!empty($user['username'])) {
//                $this->pln($user);
            if (!empty($user['password']) and $user['password'] == $this->md5Hash($validate)) {
//                if (!empty($user['password']) and $user['password'] == $this->md5Hash($validate, $user['confirm_hash'])) {
                $status = true;
            } elseif (!empty($user['winuser']) and $user['winuser'] == $validate ) {
                $status = true;
            }
        }
        
        if ($status = true) {
            $this->profile = $user;
            $this->loggedIn = $status;
            $this->generateBCCookies();
            return $status;
        }
    }

    public function logout() {
        
        $this->loggedIn = false;
        session_unset();         
        $this->clearCookies();
    }

    public function loggedIn() {
        
        return $this->loggedIn;
    }


    public static function generateStrongPassword($length = 9, $add_dashes = false, $available_sets = 'luds') {
        
        $sets = array();
        if (strpos($available_sets, 'l') !== false)
            $sets[] = 'abcdefghjkmnpqrstuvwxyz';
        if (strpos($available_sets, 'u') !== false)
            $sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        if (strpos($available_sets, 'd') !== false)
            $sets[] = '23456789';
        if (strpos($available_sets, 's') !== false)
            $sets[] = '!@#$%&*?';

        $all = '';
        $password = '';
        foreach ($sets as $set) {
            $password .= $set[array_rand(str_split($set))];
            $all .= $set;
        }

        $all = str_split($all);
        for ($i = 0; $i < $length - count($sets); $i++)
            $password .= $all[array_rand($all)];

        $password = str_shuffle($password);

        if (!$add_dashes)
            return $password;

        $dash_len = floor(sqrt($length));
        $dash_str = '';
        while (strlen($password) > $dash_len) {
            $dash_str .= substr($password, 0, $dash_len) . '-';
            $password = substr($password, $dash_len);
        }
        $dash_str .= $password;
        return $dash_str;
    }

    private function setInitCookie() {

        if (!isset($_COOKIE['A'])) {
            srand(time());
            $a = md5(rand() . microtime());
            setcookie('A', $a, $this->expiryDate, '/', self::DOMAIN);
        }
    }

    private function generateBCCookies() {
        
        $c = '';
        $c .= 'n=' . base64_encode($this->profile['nid']) . '&';
        $c .= 'l=' . str_rot13($this->profile['username']) . '&';
        $c = base64_encode($c);
        $c = str_rot13($c);

        $sig = md5($c . $this->expiryDate . self::$salt);
        $b = "x={$this->expiryDate}&s=$sig";
        $b = base64_encode($b);
        $b = str_rot13($b);

        setcookie('B', $b, $this->expiryDate, '/', self::DOMAIN);
        setcookie('C', $c, $this->expiryDate, '/', self::DOMAIN);
        // domain to null to match md5hash()
        setcookie('id_hash', $this->md5Hash($this->profile['username']), $this->expiryDate, '/', '', 0);         
    }

    private function clearCookies() {

        setcookie('B', '', time() - 3600, '/', self::DOMAIN);
        setcookie('C', '', time() - 3600, '/', self::DOMAIN);
    }

    public function md5HashNoSalt($hash, $p1 = "", $p2 = "") {

        return md5($hash . self::$salt . strtolower($p1) . strtolower($p2));
    }

    public function md5Hash($hash, $salt="", $p1 = "", $p2 = "") {

        if (empty($hash)) return;
        // if no salt passed, use Auth static salt
        if (empty($salt)) {
            $salt = self::$salt;
        } 
        return md5($hash . $salt . strtolower($p1) . strtolower($p2));
    }

    public static function newNid() {

        srand(time());
        return md5(rand() . microtime());
    }

    private function attemptCookieLogin() {

        if (!isset($_COOKIE['A']) || !isset($_COOKIE['B']) || !isset($_COOKIE['C']))
            return false;

        $ccookie = base64_decode(str_rot13($_COOKIE['C']));
        if ($ccookie === false)
            return false;

        $c = array();
        parse_str($ccookie, $c);
        if (!isset($c['n']) || !isset($c['l']))
            return false;

        $bcookie = base64_decode(str_rot13($_COOKIE['B']));
        if ($bcookie === false)
            return false;

        $b = array();
        parse_str($bcookie, $b);
        if (!isset($b['s']) || !isset($b['x']))
            return false;

        if ($b['x'] < time())
            return false;

        $computed_sig = md5(str_rot13(base64_encode($ccookie)) . $b['x'] . self::$salt);
        if ($computed_sig != $b['s'])
            return false;

        $nid = base64_decode($c['n']);
        if ($nid === false)
            return false;

        // handle this outside of Auth
        /*
        // We SELECT * so we can load the full user record into the user DBObject later
        $row = $this->db->dbRow("users", ['where'=>"nid ='$nid'"]);
        
        if ($row === false)
            return false;

        $this->id = $row['id'];
        $this->nid = $row['nid'];
        $this->username = $row['username'];
        $this->user = new User();
        $this->user->id = $this->id;
        $this->user->load($row);
        return true;
         * 
         */
    }
// from custom, check it out
    public function requireUser($rUrl = "") {
        if (!$this->loggedIn())
            $this->sendToLoginPage($rUrl);
    }

    public function requireAdmin($rUrl = "") {
        if (!$this->loggedIn() || !$this->isAdmin())
            $this->sendToLoginPage($rUrl);
    }

    public function isAdmin() {
        return ($this->user->level === 'admin');
    }

    public function changeCurrentUsername($new_username) {
//        $db = Database::getDatabase();
       
        srand(time());
        $this->user->nid = Auth::newNid();
        $this->nid = $this->user->nid;
        $this->user->username = $new_username;
        $this->username = $this->user->username;
        $this->user->update();
        $this->generateBCCookies();
    }

    public function impersonateUser($id_or_username) {
        if (ctype_digit($id_or_username))
            $u = new User($id_or_username);
        else {
            $u = new User();
            $u->select($id_or_username, 'username');
        }

        if (!$u->ok())
            return false;

        $this->id = $u->id;
        $this->nid = $u->nid;
        $this->username = $u->username;
        $this->user = $u;
        $this->generateBCCookies();

        return true;
    }
    public function changeCurrentPassword($new_password) {
        
        srand(time());
        $this->user->nid = self::newNid();
        $this->user->password = self::hashedPassword($new_password);
        $this->user->update();
        $this->nid = $this->user->nid;
        $this->generateBCCookies();
    }

    public static function changeUsername($id_or_username, $new_username) {
        if (ctype_digit($id_or_username))
            $u = new User($id_or_username);
        else {
            $u = new User();
            $u->select($id_or_username, 'username');
        }

        if ($u->ok()) {
            $u->username = $new_username;
            $u->update();
        }
    }

    public static function changePassword($id_or_username, $new_password) {
        if (ctype_digit($id_or_username))
            $u = new User($id_or_username);
        else {
            $u = new User();
            $u->select($id_or_username, 'username');
        }

        if ($u->ok()) {
            $u->nid = self::newNid();
            $u->password = self::hashedPassword($new_password);
            $u->update();
        }
    }    
}
