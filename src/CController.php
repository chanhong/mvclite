<?php
namespace MvcLite;
// need to clean up static related call or variables.

class CController extends Ccore
{

    public $layout;
    public $_appFolder;
    public $_viewFolder;
    public $_widgetFolder;
    public $vendorFolder;
    public $publicFolder;
    public $_layoutFolder;
    protected $styleless    = [];    
//    public $viewPath;
//    public $layoutsPath;


    public function __construct()
    {
        parent::__construct();
        $this->layout = $this->cfg->get('info.layout'); //set default template file
        $this->_appFolder = $this->cfg->get('folder.app');
        $this->_viewFolder = $this->cfg->get('folder.view');
        $this->_widgetFolder =$this->cfg->get('folder.widget');
        $this->vendorFolder = $this->cfg->get('folder.vendor');
        $this->publicFolder = $this->cfg->get('folder.public');
        $this->_layoutFolder = $this->cfg->get('folder.layout');
        
        $this->cfg->path['view'] = $this->_appFolder . DS . $this->_viewFolder;
        $this->cfg->path['layout'] = $this->_appFolder . DS . $this->_layoutFolder;
        $this->stg->cur['qs'] = CUtil::qsValue(); // current query string
        $this->stg->qs = CUtil::qsValue(); // current query string
//        print CDebug::debug($this->cfg->_cfg['db']);

        $conn = $this->db->dbConnect( $this->cfg->get('db.dsn'),  $this->cfg->get('db.username'),  $this->cfg->get('db.password'));
    }

    function winUser()
    {
        return $this->ut->winUser();
    }

    function className($className)
    {

        return strtolower(get_class($className));
    }

    function Add2SessVar($iVar, $msg)
    {
        if ($iVar != "" && $msg != "") {
            if ($_SESSION[$iVar] != null || $_SESSION[$iVar] != "") {
                $_SESSION[$iVar] .= " " . $msg;
            } else {
                $_SESSION[$iVar] = $msg;
            }
        }
    }

    function alertMsg($iStr, $color = "red")
    {

        if (!empty($iStr))
            $iStr = "<center>" . $this->h->bold($iStr, $color) . "</center>";
        return $iStr;
    }

    function feedback($fb = "feedback", $color = "")
    {

        $feedback = $this->ut->getSafeVar($_SESSION, $fb, "raw");
        if (!empty($feedback))
            $feedback = $this->alertMsg($feedback, $color);
        return $feedback;
    }


    public function requireUser($rUrl = "")
    {

        if (!$this->Auth->loggedIn())
            $this->sendToLoginPage($rUrl);
    }

    public function requireAdmin($rUrl = "")
    {

        if (!$this->Auth->loggedIn() || !$this->isLevel("admin"))
            $this->sendToLoginPage($rUrl);
    }

    public function isLevel($type)
    {

        //    return (CAuth::$_profile['level'] === $type);

        return (CSetting::$_profile['level'] === $type);
    }

    public function sendToLoginPage($rUrl = "")
    {

        $url = self::$loginUrl;
        //        $full_url = urlencode($rUrl); // must do this or missing & qs
        $full_url = $rUrl; // must do this or missing & qs
        if (strpos($full_url, 'logout') === false) {
            $url .= '&r=' . $full_url;
        }

        $this->redirect2Url($url);
    }

    public static function xqsValue()
    {
        return CUtil::qsValue();
    }


    public function setViewData4Header()
    {

        $this->_view_data['pagetitle'] = $this->pageTitle;
        $this->_view_data['meta'] = $this->meta;
        $this->_view_data['styleless'] = $this->styleless;
        $this->_view_data['stylesheets'] = $this->stylesheets;
        $this->_view_data['javascripts'] = $this->javascripts;
        //        permDbg($this->_view_data, 'vd');        

    }

    function captureContent($fspec)
    {

        if (!file_exists($fspec))
            return;

        (!empty($this->_view_data)) ? $pageData = $this->_view_data : $pageData = ""; // in view $pageData['meta']
        ob_start();
        include $fspec;
        $contents = ob_get_contents();
        ob_end_clean();
        return trim($contents);
    }

    // does not seem to do anything useful
    function captureBuffer($buff)
    {

        (!empty($this->_view_data)) ? $pageData = $this->_view_data : $pageData = ""; // in view $pageData['meta']
        ob_start();
        echo $buff;
        $contents = ob_get_contents();
        ob_end_clean();
        return trim($contents);
    }

    public function isLayout($layout = "")
    {

        (empty($layout)) ? $oLayout = $this->layout : $oLayout = $layout;
//        $layoutFile = DOCROOT . DS . $this->layoutsPath . DS . $oLayout . '.' . $this->view_ext;
        $layoutFile = DOCROOT . DS . $this->cfg->path['layout'] . DS . $oLayout . '.' . $this->view_ext;
        //                    print_r($layoutFile);
        (file_exists($layoutFile)) ? $ret = $layoutFile : $ret = "";
        return $ret;
    }

    public static function is404()
    {
        // this is not object, DI?? 

        $ret = "";
        $vFile = DOCROOT . DS . CConfig::$_cfg['path']['view'] . DS . CConfig::$_cfg['routes']['404'] . CConfig::$_cfg['info']['viewext'];
        print_r($vFile);
        (file_exists($vFile)) ? $ret = $vFile : $ret = "";
        return $ret;
    }

    public function setViewData($class)
    {

        if (empty($class))
            return;

        $class = strtolower($class);

        //            echo "<br />class: ".$class;       
        $this->setViewData4Header();
        $this->_view_data['top'] = $this->renderWidget('top', $class);
        $this->_view_data['header_bef'] = $this->renderWidget('header_bef', $class);
        $this->_view_data['header_aft'] = $this->renderWidget('header_aft', $class);
        $this->_view_data['body_bef'] = $this->renderWidget('body_bef', $class);
        $this->_view_data['body_lft'] = $this->renderWidget('body_lft', $class);
        // _body (content and body) can't be override by the class
        $this->_view_data['footer_bef'] = $this->renderWidget('footer_bef', $class);
        $this->_view_data['footer_aft'] = $this->renderWidget('footer_aft', $class);
        $this->_view_data['loadjs_bef'] = $this->renderWidget('loadjs_bef', $class);
        $this->_view_data['loadjs_aft'] = $this->renderWidget('loadjs_aft', $class);
    }

    public function renderWidget($view, $class = "")
    {

        $fileName = $view . '.' . $this->view_ext;
        // class widgets override widgets from the layouts folder
        $cvFile = DOCROOT . DS . $this->cfg->path['view'] . DS . $class . DS . $this->_widgetFolder . DS . $fileName;
        (!empty($class) and (file_exists($cvFile)))
            ? $vFile = $cvFile
//            : $vFile = DOCROOT . DS . $this->layoutsPath . DS . $this->_widgetFolder . DS . $fileName;
            : $vFile = DOCROOT . DS . $this->cfg->path['layout'] . DS . $this->_widgetFolder . DS . $fileName;
        permDbg($vFile, 'widget');
        (file_exists($vFile)) ? $return = $this->captureContent($vFile) : $return = "";
        return $return;
    }

    public function isAppView($view, $class = "")
    {

        $fileName = $view . '.' . $this->view_ext;
        // if not the full path then use class
        (empty($class))
            ? $viewClass = $this->_class_path : $viewClass = $class;
        $fview = $viewClass . DS . $fileName;
        $vFile = strtolower(DOCROOT . DS . $this->cfg->path['view'] . DS . $fview);
        (file_exists($vFile)) ? $ret = $vFile : $ret = "";
        return $ret;
    }



    public function renderAppView($view)
    {

        $buff = "";
        $vFile = $this->isAppView($view);
        if (!empty($vFile)) {
            $buff = $this->captureContent($vFile);
        } elseif (!empty($view)) {
            $buff = $this->captureBuffer($view);
        }
        return $buff;
    }

    function add2HeaderArrays($iType = "css", $iStr = "")
    {
        switch (strtolower($iType)) {
            case "js":
                array_push($this->javascripts, $iStr); // inject css
                break;
            default:
            case "css":
                array_push($this->stylesheets, $iStr); // inject css
                break;
            case "less":
                array_push($this->styleless, $iStr); // inject less
                break;
            case "meta":
                array_push($this->meta, $iStr); // set meta
                break;
            case "pagetitle":
                array_push($this->pageTitle, $iStr); // set title
                break;
        }
    }

    function getAjax($iType, $format = "")
    {

        $term = $this->ut->getSafeVar($_GET, 'term');
        $aType = strtolower($iType);

        if ($aType == "ldapemail" or $aType == "ldapname") {
            $retArray = $this->ut->getLdapByType(substr($aType, 4), $term);
        } else {
            switch ($aType) {
                case "email":
                    $sql = 'select distinct full_email as mail, full_email as value, full_email as id, person as cn, box_num as mailstop from recipient where full_email like "' . $term . '%" order by full_email desc';
                    break;
                case "budget":
                    $sql = 'select distinct budget as value, budget as id, budget from budget where budget is not null and budget like "' . $term . '%" order by budget asc';
                    break;
            }
            $retArray = $this->rows2Array($sql, "array"); // [] turn to nested array even for single row for json_encode to work, bug?
        }
        if (strtolower($format) == "json")
            $retArray = json_encode($retArray);
        return $retArray;
    }

    public static function isMyAction($iClassName, $action)
    {

        if (!empty($iClassName) and !empty($action)) {
            return CUtil::methodNotParent($iClassName, $action);
        }
    }

    #        $shortName = strtolower(self::shortClass($iClassName)); // "front"
    public function doAction($args = false, $iClassName = self::class)
    {

        $ret = "";
        $app = strtolower($args['t']);
        $action = strtolower($args['a']);
        //        $shortName = strtolower(self::shortClass($iClassName)); // for comparison only
        $shortName = strtolower((new \ReflectionClass($iClassName))->getShortName()); // "ClassName" change get shortname to work in php 8.5

        $ctl = new $iClassName;                           // use FQN to instantiate

        if ($app == $shortName) {  // compare using short name
            if ((self::isMyAction($iClassName, $action) == true) and method_exists($ctl, $action)) {
                $ret = $ctl->$action($args);
            } elseif (!empty($action) and $ctl->isAppView($action, $app)) {
                self::doView($ctl, $action);
            }

        }
        return $ret;
    }

    public static function isRoutable($className, $routerClassName)
    {
        $ret = false;
        if (
            !empty($routerClassName)
            and !empty($className)
            and self::isController($className)
            and strtolower($className) <> strtolower($routerClassName)
            and class_exists($className)
        ) {
            $ret = true;
        }
        return $ret;
    }

    public static function isController($className)
    {

//            pln($this->cfg->get('cfg'));
        if (
            !empty($className)
            and !empty(CConfig::$_cfg['controllers'])
            and array_search(strtolower($className), array_map('strtolower', CConfig::$_cfg['controllers']))
        ) {
            return true;
        }
    }

    public static function doRouter($routes, $iClassName = self::class) // always MvcLite\Router
    {
        $shortName = strtolower((new \ReflectionClass($iClassName))->getShortName()); // "ClassName" change get shortname to work in php 8.5
        $args = CUtil::parseQs($routes, $shortName);
        //        print "cn: $iClassName sn: $shortName rt: " . print_r($routes, true) . ", args: " . print_r($args, true); // already got 404?? redirect?
        $className = $args['t'];
        // safe current action/view to be render by doBodyContent()
        self::$_action = $action = $args['a'];
        $rCtl = CUtil::getClass($iClassName);
        switch ($args) {
            // WORK, good t= & a=
            case (strtolower($args['t']) <> strtolower($shortName)
            and class_exists($className)):
                // if not router, make sure a valid action or view of a controller
                $ctl = CUtil::getClass($className);
                if (
                    !empty($ctl)
                    and (method_exists($ctl, $action) or $ctl->isAppView($action, $className))
                ) {
                    CUtil::debug("rt: $className-$action");
                    $ctl->start($args); // WORK good t & good a
                } 
                // WORK, router? good t= but bad a=, MUST redirect multiple place to avoid mofified header warning
                else {
//                    print " good t= bad a= cn: $className a: $action" . ", args: " . print_r($args, true);
                    CUtil::debug("Custom 404: $className-$action");
// $this->cfg->get('routes'); // DI??
                    self::redirect2Url("?" . CConfig::$_cfg['routes']['page404']);
                }
                break;
            // WORK, router? BAD t=,  good action, reditrect?   
            case (!empty($action)
            and $rCtl->isAppView($action, $shortName)
            and ($className == strtolower($shortName))):
                CUtil::debug("BAD route: $className-$action");
                self::doView($rCtl, $action); // use route, need this to avoid loop and show 404            
                break;
            // WORK-unified 404, bad t= and/ or a=, else fail, use internal 404
            default:
                if ($rCtl->isAppView(CConfig::$_cfg['routes']['page404'], $shortName)) {
                    CUtil::debug("Custom 404: $className-$action");
                    self::redirect2Url("?" . CConfig::$_cfg['routes']['page404']); // WORK, bad t, good a and router page404 exist
                } else {
                    CUtil::debug("Internal 404: $className-$action");
                    gI404("$className-$action"); // internal 404
                }
        }
    }

    public static function doView($ctl, $action)
    {

        if (empty($ctl->_view_data['title'])) {
            $ctl->_view_data['title'] = $action;
        }
        if (empty($ctl->_view_data['pagetitle'])) {
            $ctl->add2HeaderArrays("pagetitle", $action);
        }
        $ctl->setViewData($ctl->_class_path);

        $buff = "";
        // render content before the layout
        $vFile = $ctl->isLayout($ctl->layout);
        $ctl->_view_data['content'] = $ctl->renderAppView($action);
        if (!empty($vFile) and !empty($ctl->_view_data['content'])) {
            $ctl->setViewData4Header();
            // render content with layout
            $buff = $ctl->captureContent($vFile);
            //            echo $buff;
        }
        echo $buff;
    }

    public function doBodyNoLayout()
    {

        // content from the current action/view
        return $this->renderAppView(self::$_action);
    }



}
