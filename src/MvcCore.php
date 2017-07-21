<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of class
 *
 * @author chanhong
 */
namespace MvcLite;
use PdoLite\PdoLite;

defined('_MVCLite') or die('Direct Access to this location is not allowed.');

class MvcCore {

    public static $_Err;
    public static $_cfg;
    public static $_userInfo;
    public static $_action;
    
    public static $loginUrl = '?login'; // Where to direct users to login
    public $retUrl;

    static $LoggedIn;
    public $profile;
    public $Auth;
    public $Error;
    public $ut;
    public $h;
    public $db;
    public $meTable;

    protected $_view_data = array(
    );    
    public $model;
    public $controller;
    public $widget;

    protected $get;
    protected $post;
    public $_request;
    public $view_ext = 'php';
    public $layout;
    public $appsFolder;
    public $viewFolder;
    public $widgetFolder;
    public $publicFolder;
    public $layoutsFolder;
    protected $_class_path;
    protected $pageTitle = array(
    );
    protected $meta = array(
    );
    protected $arr = array(
    );
    protected $javascripts = array(
    );
    protected $stylesheets = array(
    );
    protected $styleless = array(
    );
    public $className;
    
    public function __construct() {
        
        Helper::$_lineBreak = true;
        $this->ut = new Util;
        $this->h = new Helper;
        $this->db = new PdoLite;  
//        $this->Auth = MVCAuth::getAuth('insert some random text here');
        $this->Auth = MVCAuth::getAuth('MvcLiteSALT');
        $this->Error = MVCError::getError();
        
        $this->get = $_GET;
        $this->post = $_POST;
        if ($this->className === null) {
            $this->className = get_class($this);
        }
//        $this->_class_path = strtolower($this->className);
        $this->_class_path = $this->className;
        (!empty($_REQUEST['r'])) ? $this->retUrl = $_REQUEST['r'] : $this->retUrl = "?";        
    }

    public static function redirect2Url($ret2URL = null) {

        if (is_null($ret2URL))
            $ret2URL = $_SERVER['PHP_SELF'];

//        self::pln($ret2URL, 'ret2URL');
        $_SESSION['debug'] = "r: [$ret2URL]";
        header("Location: $ret2URL");
    }

    public function isAuthorized($validate, $where, $meTable) {

        if (!empty($validate) and !empty($where) and !empty($meTable)) {
            $this->Auth->logout();
            $userRow = $this->db->dbRow($meTable, ['where'=>$where]);
            if ($this->Auth->login($validate, $userRow)) {
                $_SESSION['userinfo'] = $userRow; 
                // mask out sensitive user info
                unset($_SESSION['userinfo']['nid']);
                unset($_SESSION['userinfo']['password']);
                unset($_SESSION['userinfo']['confirm_hash']);                               
                return $userRow;
            }
        } 
    }
       
    public static function pln($iVar, $iStr = "", $iFormat = "br") {
    
        print Util::debug($iVar, $iStr, $iFormat);
    }     
}

