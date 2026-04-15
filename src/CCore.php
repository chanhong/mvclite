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

class CCore {

    public static $_Err;
    public static $_cfg;
    public static $_action;
    public static $_profile;

    public static $_usrInfo;

    public static $loginUrl = '?login'; // Where to direct users to login
    public $retUrl;

    static $LoggedIn;
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
    public $vendorFolder;
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
        
        CHelper::$_lineBreak = true;
        $this->ut = new CUtil;
        $this->h = new CHelper;
        $this->db = new PdoLite;  
        $this->Auth = CAuth::getAuth('MvcLiteSALT');
        $this->Error = CError::getError();
        
        $this->get = $_GET;
        $this->post = $_POST;
        if ($this->className === null) {
            $this->className = get_class($this);
        }
        $this->_class_path = strtolower((new \ReflectionClass($this))->getShortName()); // "ClassName" change get shortname to work in php 8.5

        (!empty($_REQUEST['r'])) ? $this->retUrl = $_REQUEST['r'] : $this->retUrl = "?";
    }


    protected static function shortClass(string $fqcn): string
    {
        return substr(strrchr($fqcn, '\\'), 1) ?: $fqcn; // same as ReflectionClass($this))->getShortName()
    }


    public static function debug($iVar, $iStr = "", $iFormat = "")
    {
        return CDebug::debug($iVar, $iStr, $iFormat); // show if _MVCDebug == true
    }

    public static function redirect2Url($ret2URL = null)
    {
        if (is_null($ret2URL))
            $ret2URL = $_SERVER['PHP_SELF'];
        header('Location: '.$ret2URL);
        exit;
    }

    public function getUser($usrname, $meTable) {
        $u=null;
        if (!empty($usrname)) {
            $where = "username ='$usrname'";
            $u = $this->db->dbRow($meTable, ['where'=>$where]);

        }
        return $u;
    }

    public function isAuthorized($validate, $where, $meTable) {

        if (!empty($validate) and !empty($where) and !empty($meTable)) {
            $this->Auth->logout();
            $userRow = $this->db->dbRow($meTable, ['where'=>$where]);
            if ($this->Auth->login($validate, $userRow)<>null) {
                return $userRow;
            }
        } 
    }

    public static function pln($iVar, $iStr = "", $iFormat = "br") {
    
        print CUtil::debug($iVar, $iStr, $iFormat);
    }     
}

