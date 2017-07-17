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
    
    public static $loginUrl = '?login'; // Where to direct users to login
    
    static $LoggedIn;
    public $profile;
    public $Auth;
    public $Error;
    public $ut;
    public $h;
    public $db;
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
        self::$_userInfo = null;
        $this->ut = new Util;
        $this->h = new Helper;
        $this->db = new PdoLite;  
        $this->Auth = MVCAuth::getAuth('insert some random text here');
        $this->Error = MVCError::getError();
        
        $this->get = $_GET;
        $this->post = $_POST;
        if ($this->className === null) {
            $this->className = get_class($this);
        }
//        $this->_class_path = strtolower($this->className);
        $this->_class_path = $this->className;
    }

    public static function doRouter($args = false, $routes, $iClassName=self::class) {

        $args = Util::parseQs($routes, $iClassName);
        $className = $args['t'];
        $action = $args['a'];
        if (class_exists($className)) { 
            $ctl = new $className();
        }
        // if not router, make sure a valid action or view of controller
        if ($args['t'] <> strtolower($iClassName) 
            and class_exists($className)
            and (method_exists($ctl, $action) or $ctl->isAppView($action, $className))
        ) {
            $ctl->start($args);
        // if router has action or view show it (rare)    
        } elseif (!empty($action) and $ctl->isAppView($action, $className)) {
            self::doView($ctl, $action);
        // if controller "notfound" view exist show it
        } elseif ($ctl->isAppView("notfound", $className)) {
            self::doView($ctl, "notfound");  
        // in case QS=router show not found using base controller   
        } else {
            return $action;
        }
    }  

    public static function doView($ctl, $action) {
        if (empty($ctl->_view_data['title'] )) {
            $ctl->_view_data['title'] = $action;             
        }
        if (empty($ctl->_view_data['pagetitle'] )) {
            $ctl->add2HeaderArrays("pagetitle", $action);
        }
        $ctl->setViewData($ctl->_class_path);
        echo $ctl->appView($action, $ctl->layout); 
    } 
}

