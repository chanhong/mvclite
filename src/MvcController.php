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

    public static function xdebug($iVar, $iStr = "", $iFormat = "") {
        $ret = Util::debug($iVar, $iStr, $iFormat);
        (!empty($_SESSION['debug'])) ? $_SESSION['debug'] .= $ret : $_SESSION['debug'] = $ret;
        return $ret;
    }

    function xdTrace() {
        return $this->ut->dTrace();
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
        print_r($url);
//        exit;
        $this->redirect2Url($url);
    }
    public static function qsValue() {
        return Util::qsValue();
    }


    public function setViewData4Header() {
        
        $this->_view_data['pagetitle'] = $this->pageTitle;
        $this->_view_data['meta'] = $this->meta;
        $this->_view_data['styleless'] = $this->styleless;
        $this->_view_data['stylesheets'] = $this->stylesheets;
        $this->_view_data['javascripts'] = $this->javascripts;
    }

    

    public static function xaliasLookup($app, $aliases) {
//        Util::debug($app,'app');       
        $luArr = array();
        foreach ($aliases as $key => $aliasArray) {
            $varry = array_values($aliasArray);
            if (in_array($app, $aliasArray)) { // false if not found
                $luArr['t'] = strtolower($key);
                $luArr['a'] = strtolower($app);
                break;
            }
        }
//        Util::debug($luArr,'alias');       
        return $luArr;
    }        


    public function not_use_usingApps($task, $className = "") { 
        (empty($className)) ? $className = $appName = $task : $appName = $task ;
        NsClassLoader::using($className, array("controller"));    
        return $className;
    }

    function not_use_usingAppModel($appName, $className) { 
        NsClassLoader::using($className, array("model"));    
        return $className;
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

    
    function captureBuffer($buff) {

        (!empty($this->_view_data)) ? $data = $this->_view_data : $data = ""; // in view $data['meta']
        ob_start();
        echo $buff;
        $contents = ob_get_contents();
        ob_end_clean();
        return trim($contents);
    }

    public function xisLayout($layout = "") {
        
        (empty($layout)) ? $oLayout = $this->layout : $oLayout = $layout;
        $layoutFile = DOCROOT . DS . $this->publicFolder . DS . 'layouts' . DS . $oLayout . '.' . $this->view_ext;
        (file_exists($layoutFile)) ? $ret = $layoutFile : $ret = "";
        return $ret;
    }

    public function isLayout($layout = "") {
        (empty($layout)) ? $oLayout = $this->layout : $oLayout = $layout;
        $layoutFile = DOCROOT . DS . $this->layoutsPath . DS . $oLayout . '.' . $this->view_ext;
//                    print_r($layoutFile);
        (file_exists($layoutFile)) ? $ret = $layoutFile : $ret = "";
        return $ret;
    }

    public function redirect2Url($ret2URL = null) {

        if (is_null($ret2URL))
            $ret2URL = $_SERVER['PHP_SELF'];

        $this->db->pln($ret2URL, 'ret2URL');
        $_SESSION['debug'] = "r: [$ret2URL]";
        header("Location: $ret2URL");
    }

    public function setViewData($class = "") {
        
//            echo "<br />class: ".$class;
        if (!empty($class))
            $class = strtolower($class);

//            echo "<br />class: ".$class;
        
        $this->setViewData4Header();
        $this->_view_data['header'] = $this->renderWidget('header', $class);
        $this->_view_data['top'] = $this->renderWidget('top', $class);
/*
        (empty($this->_view_data['menu']))
            ? $this->_view_data['menu'] = $this->hMenu() // use standard menu if not yet set
            : $this->_view_data['menu'] .= $this->hMenu(); // or prepend to standard menu
 * 
 */
        $this->_view_data['before_body'] = $this->renderWidget('before_body', $class);
// _body (content and body) can't be override by the class

        $this->_view_data['footer'] = $this->renderWidget('footer', $class);
        $this->_view_data['after_footer'] = $this->renderWidget('after_footer', $class);

        $this->_view_data['loadjs'] = $this->renderWidget('loadjs', $class);
    }

    public function xrenderWidget($view, $class = "") {

        if (count(explode(DS, $view)) == 1) // if not the full path then append widgets folder
            $view = $this->widgetFolder . DS . $view;  // if only view name is used then add widgets folder to view 

        $fileName = $view . '.' . $this->view_ext;

        $cvFile = DOCROOT . DS . $this->appsFolder . DS . $class . DS . $fileName;
        (!empty($class) and ( file_exists($cvFile))) 
        ? $vFile = $cvFile // class widgets override widgets from the layouts folder
        : $vFile = DOCROOT . DS . $this->layoutsFolder . DS . $fileName;

        (file_exists($vFile)) ? $return = $this->captureContent($vFile) : $return = "";
        return $return;
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

    public function xisAppView($view, $class = "") {
        
        if (count(explode(DS, $view)) == 1) // if not the full path then append views folder
            $view = $this->viewFolder . DS . $view;  // if only view name is used then add views folder to view 

        $fileName = $view . '.' . $this->view_ext;
        (empty($class)) 
            ? $viewClass = $this->_class_path 
            : $viewClass = $class; // if not the full path then use class
        $fview = $viewClass . DS . $fileName;
        $vFile = strtolower(DOCROOT . DS . $this->appsFolder . DS . $fview);
        (file_exists($vFile)) ? $ret = $vFile : $ret = "";
         $this->ut->debug($ret,'ret: '.__METHOD__);      
        return $ret;
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

    public function renderAppView($view) {
        $buff = "";
        $vFile = $this->isAppView($view);
        if (!empty($vFile)) {
            $buff = $this->captureContent($vFile);
        } elseif (!empty($view)) {
            $buff = $this->captureBuffer($view);
        } else {
//            $this->ut->debug($vFile, __METHOD__.':vFile','p');
            $buff = $this->notFound($view);
        }
        return $buff;
    }

    function _notFound($page="Page") {
        if (!is_string($page)) {
    //        print $this->ut->debug(__METHOD__);
            $page = "Unknown: ".print_r($page,true);
        }
        return '<p /><div align="center"><h2>['.$page. '] is not found!</h2></div>';
    } 

    function appView($view, $iLayout = "") {
        $buff = "";
        $vFile = $this->isLayout($iLayout);
        if (!empty($vFile)) {
            $this->setViewData4Header();
            $this->_view_data['content'] = $this->renderAppView($view);
            $buff = $this->captureContent($vFile);
        } else {
            $buff = $this->notFound($view);
        }
        return $buff;
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

    public function doAction($args = false, $iClassName=self::class) {

        $ret = "";
        $app = strtolower($args['t']);
        $action = strtolower($args['a']);
        $ctl = new $iClassName;  
//        echo Util::debug($args, __METHOD__."  ". $iClassName);                

        // if view or method of the same controller show it.
        if ($app == strtolower($iClassName)) {
            // do action
            if  (Util::methodNotParent($iClassName, $action) == true
                    and (method_exists($ctl, $action))) {
                $ret = $ctl->$action($args);
//                echo "here:".__METHOD__."  ". $ret;
            // do view
            } elseif (!empty($action) and $ctl->isAppView($action, $app)) {
                self::doView($ctl, $action);   
            }
            // doRouter handle this no need any more but just in case
            else {
                self::doView($ctl, "notfound");  
            }
        }
        return $ret;
    }
}
