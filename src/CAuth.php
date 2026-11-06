<?php

namespace MvcLite;

use MvcLite\Interfaces\ISessionStore;

class CAuth
{

    const DOMAIN = "MVCLite";
    const ONEMONTH = 2592000;

    //    public static $_usrInfo;

    private static $me;
    private static $salt;
    private $loggedIn;
    private $nid;
    private $profile;
    // array('nid','username'...)
    public $expiryDate;
    public $ut;
    private ISessionStore $session;

    // to be removed    
    public $id;
    public $username;
    public $user;
    public $needsRehash = false;

    public function __construct(?CUtil $ut = null, ?ISessionStore $session = null)
    {

        $this->ut = $ut ?? new CUtil();
        $this->session = $session ?? new PhpSessionStore();
        $this->nid = null;
        $this->loggedIn = false;
        $this->expiryDate = time() + self::ONEMONTH;

        // to be removed
        $this->id = null;
        $this->username = null;
        $this->user = null;
    }

    // Get Singleton object
    public static function getAuth($salt = "", ?CUtil $ut = null, ?ISessionStore $session = null)
    {

        if (is_null(self::$me) || (!empty($salt) && self::$salt !== $salt)) {
            if (empty($salt)) {
                srand(time());
                $salt = md5(rand() . microtime());
            }
            self::$salt = $salt;
            self::$me = new CAuth($ut, $session);
            self::$me->init();
        } elseif ($ut !== null) {
            self::$me->ut = $ut;
        }
        if ($session !== null) {
            self::$me->session = $session;
        }
        return self::$me;
    }

    public function init()
    {
        $this->clearCookies();
        $profile = $this->session->get('uinfo');
        $username = is_array($profile) ? ($profile['username'] ?? $profile['usrname'] ?? null) : null;
        if (!is_string($username) || $username === '') {
            $this->loggedIn = false;
            return;
        }

        $profile['username'] = $username;
        $this->profile = $profile;
        $this->user = (object) $profile;
        $this->id = $profile['id'] ?? null;
        $this->username = $username;
        $this->nid = $profile['nid'] ?? null;
        $this->loggedIn = true;
    }

    /* 
     * utilize debug default to br
     * @param $ivar $istr $iformat  
     * @return string 
     */
    public static function pln($iVar, $iStr = "", $iFormat = "br")
    {

        print CUtil::debug($iVar, $iStr, $iFormat);
    }

    public function winUser()
    {

        $winId = "";
        // get Windows User ID without domain name
        if (!empty($_SERVER['LOGON_USER'])) {
            $winUser = explode("\\", $_SERVER['LOGON_USER']);
            array_shift($winUser);
            list($winId) = $winUser;
        }
        return $winId; // get Windows User ID without domain name
    }

    public function login($validate, $user)
    {

        $status = false;
        $needsRehash = false;

        if (!empty($user['username']) and !empty($validate)) {
            if (!empty($user['password'])) {
                // MD5 hash is always 32 chars; bcrypt is 60
                if (strlen($user['password']) === 32) {
                    // Old MD5 password
//                    if ($user['password'] == $this->md5Hash($validate, $user['nid'])) {  // <-- FIX: add $user['nid']
                    if (hash_equals((string)$user['password'], $this->md5Hash($validate, $user['nid']))) {                    
                        $status = true;
                        $needsRehash = true;
                    }
                } else {
                    // New bcrypt hash
                    if (password_verify($validate, $user['password'])) {
                        $status = true;
                    }
                }
            } elseif (!empty($user['winuser']) and $user['winuser'] == $validate) {
                $status = true;
            }

            if ($status === true) {
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_regenerate_id(true);
                }
                $this->profile = $user;
                $this->loggedIn = $status;
                $this->needsRehash = $needsRehash;
                $this->user = (object) $user;
                $this->id = $user['id'] ?? null;
                $this->username = $user['username'];
                $this->nid = $user['nid'] ?? null;
                $this->persistProfileToSession();
            }
        }
        return $status;
    }
    public function old_md5_login($validate, $user) // old_md5_login
    {

        $status = false;
        // if login via form or win login
        if (!empty($user['username']) and !empty($validate)) {
//                        if (!empty($user['password']) and $user['password'] == $this->md5Hash($validate)) {
// hashpassword??, migrated away from md5
            if (!empty($user['password']) and password_verify($validate, $user['password'])) {
                $status = true;
            } elseif (!empty($user['winuser']) and $user['winuser'] == $validate) {
                $status = true;
            }
            //            if ($status = true) {
// gemini change            
            if ($status === true) {
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_regenerate_id(true);
                }
                $this->profile = $user;
                $this->loggedIn = $status;
                $this->user = (object) $user;
                $this->id = $user['id'] ?? null;
                $this->username = $user['username'];
                $this->nid = $user['nid'] ?? null;
                $this->persistProfileToSession();
            }
        }
        return $status;
    }

    public function logout()
    {

        $this->loggedIn = false;
        foreach (['uinfo', 'loggedin', 'usrname', 'usrgroup', 'usrentity', 'uinfotxt'] as $key) {
            $this->session->remove($key);
        }
        $this->profile = null;
        $this->user = null;
        $this->loggedIn = false;
        $this->clearCookies();
        CUtil::Cookie_Usr();
    }

    public function loggedIn()
    {

        return $this->loggedIn;
    }


    public static function generateStrongPassword($length = 9, $add_dashes = false, $available_sets = 'luds')
    {

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

    private function persistProfileToSession(): void
    {
        if (!is_array($this->profile) || empty($this->profile['username'])) {
            return;
        }

        $this->session->set('uinfo', $this->profile);
        $this->session->set('loggedin', $this->profile['username']);
    }

    private function clearCookies()
    {
        $options = [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ];
        foreach (['A', 'B', 'C', 'id_hash'] as $cookieName) {
            if (isset($_COOKIE[$cookieName])) {
                setcookie($cookieName, '', $options);
                unset($_COOKIE[$cookieName]);
            }
        }
    }

    public function md5HashNoSalt($hash, $p1 = "", $p2 = "")
    {

        return md5((string) $hash . (self::$salt ?? '') . (string) $p1 . (string) $p2);
    }

    public function md5Hash($hash, $salt = "", $p1 = "", $p2 = "")
    {

        if (empty($hash))
            return null;

        $projectSalt = self::$salt ?? '';
        return md5((string) $hash . $projectSalt . (string) $salt . (string) $p1 . (string) $p2);
    }
    // gemini change??
    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function newNid()
    {

        srand(time());
        return md5(rand() . microtime());
    }

    // from custom, check it out
    public function requireUser($rUrl = "")
    {
        if (!$this->loggedIn())
            $this->sendToLoginPage($rUrl);
    }

    public function requireAdmin($rUrl = "")
    {
        if (!$this->loggedIn() || !$this->isAdmin())
            $this->sendToLoginPage($rUrl);
    }

    public function isAdmin()
    {
        return is_object($this->user) && (($this->user->level ?? null) === 'admin');
    }

    public function changeCurrentUsername($new_username)
    {
        (new MUser('users'))->changeCurrentUsername($new_username);
        $this->syncProfileFromSession();
    }

    public function impersonateUser($id_or_username)
    {
        if (!is_scalar($id_or_username) || (string) $id_or_username === '') {
            return false;
        }

        $identity = (string) $id_or_username;
        $model = new MUser('users');
        $profile = ctype_digit($identity)
            ? $model->findById($identity)
            : $model->findByUsername($identity);
        if ($profile === null) {
            return false;
        }

        $this->profile = $profile;
        $this->user = (object) $profile;
        $this->id = $profile['id'] ?? null;
        $this->nid = $profile['nid'] ?? null;
        $this->username = $profile['username'] ?? null;
        $this->loggedIn = true;
        $this->persistProfileToSession();

        return true;
    }

    public function changeCurrentPassword($new_password)
    {
        (new MUser('users'))->changeCurrentPassword($new_password);
        $this->syncProfileFromSession();
    }

    public static function changeUsername($id_or_username, $new_username)
    {
        return MUser::changeUsername($id_or_username, $new_username);
    }

    public static function changePassword($id_or_username, $new_password)
    {
        return MUser::changePassword($id_or_username, $new_password);
    }

    private function syncProfileFromSession(): void
    {
        $profile = $this->session->get('uinfo');
        if (!is_array($profile)) {
            return;
        }

        $this->profile = $profile;
        $this->user = (object) $profile;
        $this->id = $profile['id'] ?? null;
        $this->nid = $profile['nid'] ?? null;
        $this->username = $profile['username'] ?? null;
    }


}
