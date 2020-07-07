<?php
namespace MvcLite;

class MvcController extends MvcCore {


    public function __construct() {
        
        parent::__construct();
        /*
        $this->layout = 'default'; //set default template file
        $this->publicFolder = 'public';
        $this->layoutsFolder = $this->publicFolder . DS . 'layouts';
        $this->appsFolder = 'apps';
        $this->viewFolder = 'views';
        $this->widgetFolder = 'widgets';
*/
        $this->layout = 'default'; //set default template file
        $this->_appFolder = 'apps';
        $this->_viewFolder = 'views';
        $this->_widgetFolder = 'widgets';
        $this->publicFolder = 'public';
        $this->viewPath = $this->_appFolder . DS . $this->_viewFolder;
        $this->_layoutFolder = 'layouts';
        $this->layoutsPath = $this->_appFolder . DS . $this->_layoutFolder;
        
        $conn = $this->db->dbConnect(MVCCore::$_cfg['db']['dsn'],MVCCore::$_cfg['db']['username'],MVCCore::$_cfg['db']['password']);        
        
    }
    function winUser() {
        return $this->ut->winUser();
    }

    function className($className) {

        return strtolower(get_class($className));
    }

    function alertMsg($iStr, $color = "red") {

        if (!empty($iStr))
            $iStr = "<center>" . $this->h->bold($iStr, $color) . "</center>";
        return $iStr;
    }

    function feedback($fb = "feedback", $color = "") {

        $feedback = $this->ut->getSafeVar($_SESSION, $fb, "raw");
        if (!empty($feedback))
            $feedback = $this->alertMsg($feedback, $color);
        return $feedback;
    }

    public function requireUser($rUrl = "") {

        if (!$this->Auth->loggedIn())
            $this->sendToLoginPage($rUrl);
    }

    public function requireAdmin($rUrl = "") {
        
        if (!$this->Auth->loggedIn() || !$this->isLevel("admin"))
            $this->sendToLoginPage($rUrl);
    }

    public function isLevel($type) {

        return (MVCAuth::$_profile['level'] === $type);
    }

    public function sendToLoginPage($rUrl = "") {
        
        $url = self::$loginUrl;
//        $full_url = urlencode($rUrl); // must do this or missing & qs
        $full_url = $rUrl; // must do this or missing & qs
        if (strpos($full_url, 'logout') === false) {
            $url .= '&r=' . $full_url;
        }

        $this->redirect2Url($url);
    }

    public static function xqsValue() {
        return Util::qsValue();
    }


    public function setViewData4Header() {
        
        $this->_view_data['pagetitle'] = $this->pageTitle;
        $this->_view_data['meta'] = $this->meta;
        $this->_view_data['styleless'] = $this->styleless;
        $this->_view_data['stylesheets'] = $this->stylesheets;
        $this->_view_data['javascripts'] = $this->javascripts;
    }

    function captureContent($fspec) {
        
        if (!file_exists($fspec))
            return;

        (!empty($this->_view_data)) ? $data = $this->_view_data : $data = ""; // in view $data['meta']
        ob_start();
        include $fspec;
        $contents = ob_get_contents();
        ob_end_clean();
        return trim($contents);
    }

    // does not seem to do anything useful
    function captureBuffer($buff) {

        (!empty($this->_view_data)) ? $data = $this->_view_data : $data = ""; // in view $data['meta']
        ob_start();
        echo $buff;
        $contents = ob_get_contents();
        ob_end_clean();
        return trim($contents);
    }

    public function isLayout($layout = "") {

        (empty($layout)) ? $oLayout = $this->layout : $oLayout = $layout;
        $layoutFile = DOCROOT . DS . $this->layoutsPath . DS . $oLayout . '.' . $this->view_ext;
//                    print_r($layoutFile);
        (file_exists($layoutFile)) ? $ret = $layoutFile : $ret = "";
        return $ret;
    }

    public function setViewData($class) {
        
        if (empty($class)) 
            return;

        $class = strtolower($class);

//            echo "<br />class: ".$class;       
        $this->setViewData4Header();
        $this->_view_data['header'] = $this->renderWidget('header', $class);
        $this->_view_data['top'] = $this->renderWidget('top', $class);
        $this->_view_data['before_body'] = $this->renderWidget('before_body', $class);
// _body (content and body) can't be override by the class
        $this->_view_data['footer'] = $this->renderWidget('footer', $class);
        $this->_view_data['after_footer'] = $this->renderWidget('after_footer', $class);
        $this->_view_data['loadjs'] = $this->renderWidget('loadjs', $class);
    }

    public function renderWidget($view, $class = "") {
        /*
        if (count(explode(DS, $view)) == 1) // if not the full path then append widgets folder
            $view = 'widgets' . DS . $view;  // if only view name is used then add widgets folder to view 
*/
        $fileName = $view . '.' . $this->view_ext;
        // class widgets override widgets from the layouts folder
        $cvFile = DOCROOT . DS . $this->viewPath .DS . $class .DS.$this->_widgetFolder. DS . $fileName;
        (!empty($class) and ( file_exists($cvFile))) 
        ? $vFile = $cvFile 
        : $vFile = DOCROOT . DS . $this->layoutsPath .DS .$this->_widgetFolder . DS . $fileName;
//        permDbg($vFile, 'widget');
        (file_exists($vFile)) ? $return = $this->captureContent($vFile) : $return = "";
        return $return;
    }

    public function isAppView($view, $class = "") {

/*        
        if (count(explode(DS, $view)) == 1) // if not the full path then append views folder
            $view = 'views' . DS . $view;  // if only view name is used then add views folder to view 
*/
        $fileName = $view . '.' . $this->view_ext;
        // if not the full path then use class
        (empty($class)) ?
                        $viewClass = $this->_class_path : $viewClass = $class; 
        $fview = $viewClass . DS . $fileName;
        $vFile = strtolower(DOCROOT . DS . $this->viewPath . DS. $fview);
        (file_exists($vFile)) ? $ret = $vFile : $ret = "";
        return $ret;
    }

    public function ccrenderAppView($view) {

        if ($vFile = $this->isAppView($view)) {
            return $this->captureContent($vFile);
        } 
    }

    public function renderAppView($view) {

        $buff = "";
        $vFile = $this->isAppView($view);
        if (!empty($vFile)) {
            $buff = $this->captureContent($vFile);
        } elseif (!empty($view)) {
            $buff = $this->captureBuffer($view);
        } 
        return $buff;
    }
    function _notFound($page="Page") {

        if (!is_string($page)) {
    //        print $this->ut->debug(__METHOD__);
            $page = "Unknown: ".print_r($page,true);
        }
        return '<p /><div align="center"><h1>Internal: '.$page. ' is not found!</h1>
        <p />create views\router\notfound.php will avoid this internal page</div>';
    } 

    function add2HeaderArrays($iType = "css", $iStr = "") {
        switch (strtolower($iType)) {
            case "js":
                array_push($this->javascripts, $iStr); // inject css
                break;
            default:
            case "css":
                array_push($this->stylesheets, $iStr); // inject css
                break;
            case "less":
                array_push($this->cssLess, $iStr); // inject less
                break;
            case "meta":
                array_push($this->meta, $iStr); // set meta
                break;
            case "pagetitle":
                array_push($this->pageTitle, $iStr); // set title
                break;
        }
    }

    function getAjax($iType, $format = "") {
        
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

    public static function isMyAction($iClassName, $action) {

        if  (!empty($iClassName) and !empty($action)) {
            return Util::methodNotParent($iClassName, $action);
        }
    }

    public function doAction($args = false, $iClassName=self::class) {

        $ret = "";
        $app = strtolower($args['t']);
        $action = strtolower($args['a']);
        $ctl = new $iClassName;  

        // if view or method of the same controller show it.
        if ($app == strtolower($iClassName)) {
            // do action
            if ((self::isMyAction($iClassName, $action) == true) and method_exists($ctl, $action)) {
//            if  (Util::methodNotParent($iClassName, $action) == true and (method_exists($ctl, $action))) {
                $ret = $ctl->$action($args);
            // do view
            } elseif (!empty($action) and $ctl->isAppView($action, $app)) {
                self::doView($ctl, $action);   
            }
        }
        return $ret;
    }

    public static function isRoutable($className, $routerClassName) {

        if  (!empty($routerClassName) 
            and !empty($className) 
            and self::isController($className)
            and strtolower($className) <> strtolower($routerClassName)
            and class_exists($className))
        {
            return true;
        }
    }

    public static function isController($className) {

        if  (!empty($className) 
            and !empty(MVCCore::$_cfg['controllers'])
            and array_search(strtolower($className), array_map('strtolower', MVCCore::$_cfg['controllers']))
        ) {
            return true;
        }
    }

    public static function doRouter($routes, $iClassName=self::class) {
 
        $args = Util::parseQs($routes, $iClassName);
        $className = $args['t'];
        // safe current action/view to be render by doBodyContent()
        self::$_action = $action = $args['a'];
        $rCtl = Util::getClass($iClassName);
//        if (strtolower($args['t']) <> strtolower($iClassName) and class_exists($className)
//        if (self::isRoutable($className, $iClassName)) {
            // if not router, make sure a valid action or view of a controller
            $ctl = Util::getClass($className);
            if (!empty($ctl)
                and (method_exists($ctl, $action) or $ctl->isAppView($action, $className))) {
                $ctl->start($args);
            } else {
                // good controller but bad action
                self::redirect2Url("?".MVCCore::$_cfg['page404']);
            }
            /*
        // if router has action or view show it (rare)    
        } elseif (!empty($action) 
            and $rCtl->isAppView($action, $iClassName) 
            and ($className == strtolower($iClassName))) 
        {
            self::doView($rCtl, $action);
        // if not a valid controller and router page404 exist
        } elseif ($rCtl->isAppView(MVCCore::$_cfg['page404'], $iClassName)) {
            self::redirect2Url("?".MVCCore::$_cfg['page404']);
        } else {
            // all else fail, use internal notfound
            echo $rCtl->_notFound($action);            
        }
        */
    }  

    public static function doView($ctl, $action) {

        if (empty($ctl->_view_data['title'] )) {
            $ctl->_view_data['title'] = $action;             
        }
        if (empty($ctl->_view_data['pagetitle'] )) {
            $ctl->add2HeaderArrays("pagetitle", $action);
        }
        $ctl->setViewData($ctl->_class_path);
        echo $ctl->appView($ctl, $action, $ctl->layout); 
    } 

    function appView($ctl, $view, $iLayout = "") {
        
        $buff = "";
        // render content before the layout
        $vFile = $ctl->isLayout($iLayout);
        $ctl->_view_data['content'] = $ctl->renderAppView($view);
        if (!empty($vFile) and !empty($ctl->_view_data['content'])) {
            $ctl->setViewData4Header();
            // render content with layout
            return $ctl->captureContent($vFile);
        } 
    }

    public function doBodyNoLayout() {

        // content from the current action/view
        return $this->renderAppView(self::$_action);
    }

    public function doBody() {

        $youare = $dmsg = $alertMsg = $feedback = $buff = "";

        $dmsg = $this->ut->getSafeVar($_SESSION, "debug");
        (!empty($dmsg)) ? $dmsg = "<center>" . $dmsg . "</center>" : $dmsg = "";

        $feedback = $this->feedback("feedback", "DarkGreen");
        $alertMsg = $this->feedback("alert", "IndianRed");

        //print_r($this->Auth->loggedIn());
        //($this->Auth->loggedIn()) ? $youare = $this->alertMsg("You are login as ".$this->profile['username'], "green") : $youare = "";

        $buff .=  $youare . $dmsg . $alertMsg . $feedback;
        $buff .=  $this->Error;
        $buff .=  $this->doBodyNoLayout();
        $_SESSION["debug"] = $_SESSION["feedback"] = $_SESSION["alert"] = "";
        echo $buff; 
    }   
    
}
