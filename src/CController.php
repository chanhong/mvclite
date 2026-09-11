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
    protected $styleless = [];
    //    public $viewPath;
//    public $layoutsPath;


    public function __construct()
    {
        parent::__construct();
        $this->layout = $this->cfg->get('info.layout'); //set default template file
        $this->_appFolder = $this->cfg->get('folder.app');
        $this->_viewFolder = $this->cfg->get('folder.view');
        $this->_widgetFolder = $this->cfg->get('folder.widget');
        $this->vendorFolder = $this->cfg->get('folder.vendor');
        $this->publicFolder = $this->cfg->get('folder.public');
        $this->_layoutFolder = $this->cfg->get('folder.layout');

        $this->cfg->path['view'] = $this->_appFolder . DS . $this->_viewFolder;
        $this->cfg->path['layout'] = $this->_appFolder . DS . $this->_layoutFolder;
        $this->stg->cur['qs'] = CUtil::qsValue(); // current query string
        $this->stg->qs = CUtil::qsValue(); // current query string
//        print CDebug::debug($this->cfg->_cfg['db']);

        $conn = $this->db->dbConnect($this->cfg->get('db.dsn'), $this->cfg->get('db.username'), $this->cfg->get('db.password'));
    }

    public function selfVsStatic($clsName = self::class, $who = "")
    {
        pln($clsName, 'who: [$who]'); // depend on caller $clsName or default to self
// old static to object self (always CController) static::class (whoever call such as Authors)
        pln(self::class, 'self'); // always MvcLite\CController
        pln(static::class, 'static');             // MvcLite\Authors when Authors calls it
    }

    function winUser()
    {
        return $this->ut->winUser();
    }

    function className($className)
    {

        return strtolower(get_class($className));
    }

    function Add2SessVar($iVar, $msg) // self:: in controller class/sub is fine, outsie will trigger depreciated warning
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

        (!empty($this->_view_data)) ? $pageData = $this->_view_data : $pageData = "";
        ob_start();
        try {
            include $fspec;
        } catch (\Throwable $e) {
            ob_end_clean();
            pln($e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine(), 'captureContent-FATAL');
            return '';
        }
        $contents = ob_get_contents();
        ob_end_clean();
        return trim($contents);
    }
    function captureContent_notry($fspec)
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
        //        permDbg($vFile, 'widget'); // work
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

        // DI: was self::doAction(... self::class) — self::class always resolves to CController,
//     static::class resolves to the actual child class at runtime (e.g. Authors, Front, etc.)

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

    public function doU404_4_bug($args, $shortNameRte)
    {
        $p404 = CConfig::$_cfg['routes']['page404'];
        $buff = "";
        $ctl = CUtil::getClass($shortNameRte);
        $vFile = $ctl->isLayout($ctl->layout);
        self::$_action = $p404;  // <-- temp critical fix        for the doBodyNoLayout() ignore the 'content' but don't need with the fix in doBodyNoLayout()
        $ctl->_view_data['content'] = $ctl->renderAppView($p404); // render content before the layout
        if (!empty($vFile) and !empty($ctl->_view_data['content'])) {
            //            $ctl->setViewData4Header();
            ob_start();
            include $vFile; // layout
            $buff .= ob_get_contents(); // render content with layout
//            $buff .= $ctl->_view_data['content']; // must do this for custom 404 to show, WHY??
            ob_end_clean();
            print trim($buff);
        }
    }

    // WORK-unified 404, bad t= else fail, use internal 404
    public function U404($args, $shortNameRte) // router controller and router shortname
    {
        // redirect Warning: Cannot modify header information - headers already sent by (output started at Y:\_needed\mvclite_work\apps\Lib\mvclite\src\CUtil.php:1110) in Y:\_needed\mvclite_work\apps\Lib\mvclite\src\CCore.php on line 195
        $p404 = CConfig::$_cfg['routes']['page404'];
        $task = $args['t'];
        $action = $args['a'];
        $ctl = CUtil::getClass($shortNameRte);

//        pln(['HERE headers_sent' => headers_sent(), 't' => $task, 'a' => $action], 'U404-entry');


        if ($ctl->isAppView($p404, $shortNameRte)) {
            CUtil::debug("Custom 404: $task-$action");
            //            $this->redirect2Url("?" . $p404); // WORK, bad t, good a and router page404 exist
// render in place, keep the original URL and debug trail , WHY??? didn't work for some reason  
            self::doView($ctl, $p404);
            //            self::doU404_4_bug($args,$shortNameRte);
            echo "here after doview";
        } else {
            CUtil::debug("Internal 404: $task-$action");
            //                    gI404("$className-$action"); // internal 404
            $this->i404("$task-$action"); // internal 404
        }
    }

    public function isNotTG($args, $shortNameRte) // always MvcLite\Router
    {
        $notTnTg = false;
        $task = $args['t'];
        $action = $args['a'];
        $tg = $this->stg->get('tg') ?? [];
        $tgpass = $this->stg->get('tgExemptControllers') ?? [];
        $entry[$task] = $tg[strtolower($task)] ?? null;
        //        pln($entry[$task], 'entry=t');
//                                pln($tg,"isNotTG:tg t:$task");
//                                pln($tgpass,"isNotTG:tgpass t:$task");

        $uinfo = $_SESSION["uinfo"] ?? [];
        $ugrp = $uinfo['usrgroup'] ?? 'guest';
        $tgrp = $entry[$task]['group'] ?? null;
        if (in_array(strtolower($task), $tgpass, true)) {
            $notTnTg = false;
        } else {
            $notTnTg = empty($entry[$task])
                || !CSecs::IsUsrGrpComp($ugrp, $tgrp, ">=")
                || !in_array(strtolower($action), $entry[$task]['actions'] ?? [], true);
        }

        return $notTnTg;
    }

    // bef DI   public static function doRouter($routes, $iClassName = self::class) // always MvcLite\Router
    public function doRouter($routes, $iClassName = self::class) // always MvcLite\Router
    {
        //                pln($this->stg->get('tg'), "tg");

        $shortNameRte = strtolower((new \ReflectionClass($iClassName))->getShortName()); // "ClassName" change get shortname to work in php 8.5
        $args = CUtil::parseQs($routes, $shortNameRte);
        CUtil::captureLastUrl($args);   // moved here — single, reliable call per request        
        //        pln($args, 'doRouter-args-for-logout');
        //        print "cn: $iClassName sn: $shortName rt: " . print_r($routes, true) . ", args: " . print_r($args, true); // already got 404?? redirect?
        $task = $args['t'];
        $tCtl = CUtil::getClass($task);

        // safe current action/view to be render by doBodyContent()
        self::$_action = $action = $args['a'];
        $rCtl = CUtil::getClass($iClassName);

        // WORK 08/23: tg (built fresh every request by setMenu(), gated by usrgroup, sourced from the
        // master task directory - not $selctrl) is the single source of truth for t=/a= access.
        // Router itself (t=router) is exempt - it's not a task.
        if (self::isNotTG($args, $shortNameRte) == true)
            self::U404($args, $shortNameRte); // 404

        //        print (print_r($this->stg->get('tg'), true));
        switch (true) {
            // WORK, good t= & a=
            case (strtolower($args['t']) <> strtolower($shortNameRte)
            and class_exists($task)):
                // if not router, make sure a valid action or view of a controller
//                $ctl = CUtil::getClass($task);
                if (
                    !empty($tCtl)
                    and (method_exists($tCtl, $action) or $tCtl->isAppView($action, $task))
                ) {
                    CUtil::debug("rt: $task-$action");
                    //                    pln("rt: $task-$action");
                    $tCtl->start($args); // WORK good t & good a
                }
                // WORK, router? good t= but bad a=, MUST redirect multiple place to avoid mofified header warning
                else {
                    self::U404($args, $shortNameRte); // WORK-unified 404, catch bad a=
                }

                break;
            case (!empty($action) // WORK, use router to process view if no controller
            and $task <> strtolower($shortNameRte) // t <> router, use router to see view
            and (!class_exists($task) // no controller
            and $rCtl->isAppView($action, $task) // good action
            )):
                //                pln("use route: $task-$action");
                $rCtl->_class_path = $task; // use t as view for _class_path
                self::doView($rCtl, $action); // use route to view action            
                break;
            // WORK, router? BAD t=,  good action, to stop reditrect loop
            case (!empty($action)
            and $rCtl->isAppView($action, $shortNameRte)
            and ($task == strtolower($shortNameRte))):
                pln("BAD route: $task-$action");
                self::doView($rCtl, $action); // use route, need this to avoid loop and show 404            
                break;
            // WORK-unified 404, bad t= and/ or a=, else fail, use internal 404
            default:
                self::U404($args, $shortNameRte); // WORK-unified 404, catch bad a=
        }
    }

    // bef DI   public static function doRouter($routes, $iClassName = self::class) // always MvcLite\Router

    public static function doView($ctl, $action) // more flexible: router process view file and action
    {
        //        pln($ctl);
        if (empty($ctl->_view_data['title'])) {
            $ctl->_view_data['title'] = $action;
        }
        if (empty($ctl->_view_data['pagetitle'])) {
            $ctl->add2HeaderArrays("pagetitle", $action);
        }
        $ctl->setViewData($ctl->_class_path);   // can be override in doRouter to use t as view _class_path

        $buff = "";
        // render content before the layout
        $vFile = $ctl->isLayout($ctl->layout);
        //        pln("doView:vFile-check: l:$vFile v:$ctl->_class_path a:$action");

        $ctl->_view_data['content'] = $ctl->renderAppView($action);
        if (!empty($vFile) and !empty($ctl->_view_data['content'])) {
            //            pln("doView_path: $ctl-$action");
            $ctl->setViewData4Header();
            // render content with layout
            $buff = $ctl->captureContent($vFile);
            //            pln($buff, "doView:buff-check");
        }
        echo $buff;
    }


    public function doBodyNoLayout_not_good()
    {
        echo "doBodyNoLayout:";
        // content from the current action/view
        return $this->renderAppView(self::$_action);
    }

    public function doBodyNoLayout()
    {
        // if content has already been rendered (e.g. by doView()/doU404()), use it as-is
        if (!empty($this->_view_data['content'])) {
            return $this->_view_data['content'];
        }

        echo "doBodyNoLayout:";
        // fallback: content from the current action/view
        return $this->renderAppView(self::$_action);
    }

    public function i404($page = "i404")
    {
        $i404 =
            '<div style="height:auto; min-height:100%; "><div style="text-align: center; width:800px; margin-left: -400px; position:absolute; top: 30%; left:50%;">'
            . '<h1 style="margin:0; font-size:150px; line-height:150px; font-weight:bold;">404</h1>'
            . '<h2 style="margin-top:20px;font-size: 30px;">Not Found - [' . $page . ']</h2>'
            . '<p>The resource requested could not be found on this server! or create your custom 404.php</p></div></div>';

        print '<!DOCTYPE html><html style="height:100%"><head></head>'
            . '<title>404 Not Found</title><style>@media (prefers-color-scheme:dark){body{background-color:#000!important}}</style></head>'
            . '<body style="color: #444; margin:0;font: normal 14px/20px Arial, Helvetica, sans-serif; height:100%; background-color: #fff;">'
            . $i404
            . '</body></html>';
    }

}
