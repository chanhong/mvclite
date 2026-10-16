<?php

namespace MvcLite;
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



class CMenu
{
    public static function sb2s($sb, $separator)
    {
        $ret = "";
        if ($sb != null && $sb->getLength() > 0) {
            $ret = $sb->ToString();
            $ret = substr($ret, 0, strlen($ret) - strlen($separator));
        }
        return $ret;
    }

    public static function viewNameDir2Submnu($fa)
    {
        $sb = new StringBuilder();
        // In C#, foreach on NameValueCollection iterates keys
        foreach (array_keys($fa) as $f) {
            // check perm here before build the menu
            $mnu = self::viewDir2Nv4Mnu($f); // all menu link from the view folder
            if ($mnu != null && count($mnu) > 0) {
                $buff = "";
                /*
                [SMnu]:(jendo)
                [mnu]:(autocomplete=[http:\//localhost:83/ejvnetdev/?t=jendo&a=autocomplete],createselectpdf=[http:\//localhost:83/ejvnetdev/?t=jendo&a=createselectpdf],datepicker=[http:\//localhost:83/ejvnetdev/?t=jendo&a=datepicker],ldapUW=[http:\//localhost:83/ejvnetdev/?t=jendo&a=ldapUW],upload=[http:\//localhost:83/ejvnetdev/?t=jendo&a=upload]) 
                */
                // CMsg::_msg($mnu, "vfl"); // for each view folder
                $lnka = null;
                $sbx = new StringBuilder();
                foreach (array_keys($mnu) as $s) {
                    // CMsg::_msg($mnuType."-".$s, "m-i");
                    if ($s != null && strlen($s) > 0) {
                        $lnka = CUtil::mnu_s2a($mnu[$s], $s); // non-secure, inclusive
                        if (
                            CSecs::isPublicAccess4Mnu($lnka, $f) == true
                            || CSecs::isUsrHasAccess2Mnu($lnka, $f) == true
                        ) {
                            $sbx::Append(CUtil::a2Li($lnka));
                        }
                    }
                }
                $buff = $sbx->ToString();
                if ($buff != null && strlen($buff) > 0) {
                    $sb->Append($buff);
                }
            }
        }
        return $sb->ToString();
    }


    public static function mnu2Li($mnu)
    {
        $lnka = null;
        $sb = new StringBuilder();
        // CMsg::_msg($mnu, "mnu");
        // [mnu]: (front =[http:\//localhost:83/ejvnetdev/?t=front],admin=[http:\//localhost:83/ejvnetdev/?t=admin])
        foreach (array_keys($mnu) as $s) {
            if (CString::IsEmpty($s) == false) {
                $lnka = self::mnu_nv2a($mnu, $s);
                $sb->Append(self::a2Li($lnka));
            }
        }
        return $sb->ToString();
    }

    public static function Mnu2LiSec($mnu, $mnuType)
    {
        $lnka = null;
        $sb = new StringBuilder();
        // process each view file and link
        // CMsg::_msg($mnu, "mnu");
        // [mnu]:(jv=[http://localhost:83/ejvnetdev/?t=jv],jvadm=[http://localhost:83/ejvnetdev/?t=jvadm],jvapv=[http://localhost:83/ejvnetdev/?t=jvapv],jvtpl=[http://localhost:83/ejvnetdev/?t=jvtpl],jvinq=[http://localhost:83/ejvnetdev/?t=jvinq],jendo=[http://localhost:83/ejvnetdev/?t=jendo],jsgrid=[http://localhost:83/ejvnetdev/?t=jsgrid],ko=[http://localhost:83/ejvnetdev/?t=ko])
        foreach (array_keys($mnu) as $s) {
            if (CString::IsEmpty($s) == false && $s != $mnuType) // exclude the default controller in the submenu
            {
                $lnka = CUtil::mnu_s2a($mnu[$s], $s); // http:\//localhost:83/ejvnetdev/test, test
                // [lnka]:(0=[http://localhost:83/ejvnetdev/?t=jvadm],1=[],2=[],3=[JV Admin]) 
                // CMsg::_msg($lnka, "lnka");
                if (
                    CSecs::isPublicAccess4Mnu($lnka, $mnuType) == true
                    || CSecs::isUsrHasAccess2Mnu($lnka, $mnuType) == true
                ) {
                    $sb->Append(self::a2Li($lnka));
                }
                /*
                else
                {
                  CMsg::_msg($lnka, "lnka-else");
                  CMsg::_msg($mnuType, "mnuType");
                  // $sb->Append(self::a2Li($lnka)); // add public menu
                }
                */
            }
        }
        return $sb->ToString();
    }


    /**
     * Converts a NameValueCollection of paths to a menu collection.
     */
    public static function sName2Mnu(NameValueCollection $fa): NameValueCollection
    {
        $nvList = new NameValueCollection();
        //      CMsg::_msg($fa, "sName2Mnu");
        $name = "";
        foreach ($fa as $f) {
            $viewPath = CFiles::getRealViewPath($f);
            if (is_dir($viewPath)) {
                $name = $f;
                $nvList->add($name, tap("/" . $f));
            }
        }
        return $nvList;
    }

    /**
     * Converts a delimited string list to a menu collection.
     */
    public static function s2Mnu(string $iList, string $delimit = ','): NameValueCollection
    {
        $nvList = new NameValueCollection();
        $iArray = self::Str2a($delimit, $iList); // a list to make menu
        foreach ($iArray as $f) {
            $viewPath = CFiles::getRealViewPath($f);
            if (is_dir($viewPath)) {
                //          $nvList->add(Ucase($f), tap("/" . $f));
                $nvList->add($f, tap("/" . $f));
            }
        }
        return $nvList;
    }

    /**
     * Scans a directory and converts subdirectories into a menu collection.
     */
    public static function NOT_viewDir2Nv4Mnu(string $dPath = "."): NameValueCollection
    {
        $nvList = new NameValueCollection();
        $files = self::getViewNameArray($dPath);
        if ($files != null) {
            foreach ($files as $f) {
                //                          CMsg::_dprt($f, "f");
                //          $nvList->add(Ucase($f), tap("/" . $dPath . "/" . $f));
                $nvList->add($f, tap("/" . $dPath . "/" . $f));
            }
            //        {"Autocomplete",CUtil::tap("/jendo/autocomplete")}, 
        }
        return $nvList;
    }

    /**
     * Helper: Splits a string into an array, removing empty entries.
     */
    public static function Str2a(string $delimit, string $iList): array
    {
        if (empty($iList)) {
            return [];
        }
        return array_values(array_filter(CUtil::str2a($delimit, $iList), 'strlen'));
    }

    /**
     * Helper: Gets names of subdirectories within a given path.
     */
    public static function getViewNameArray(string $dPath): ?array
    {
        $realPath = CFiles::getRealViewPath($dPath);
        if (!is_dir($realPath)) {
            return null;
        }

        $items = scandir($realPath);
        $results = [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            // Logic typically filters for directories in this context
            if (is_dir($realPath . DIRECTORY_SEPARATOR . $item)) {
                $results[] = $item;
            }
        }
        return $results;
    }

    public static function getLiMenu($menu)
    {
        if (!is_array($menu) || empty($menu)) {
            return $menu;
        }

        $html = '';
        foreach ($menu as $item) {
            $html .= CHtml::TagWithHtml('li', CHtml::Alink($item));
        }

        return $html;
    }

    private static function buildTaskGroups($controller, array &$groups, array $visited = []): void
    {
        if (in_array($controller, $visited)) {
            return;
        }
        $visited[] = $controller;

        $taskDir = CSetting::get('apps.' . $controller);
        if (!is_array($taskDir)) {
            return;
        }

        foreach ($taskDir as $task => $value) {
            if (isset($groups[$task])) {
                continue;
            }

            [$group] = explode(',', $value);
            $group = $group ?: 'guest';
            $actions = [strtolower(CSetting::get('defview'))];
            $actions = array_merge(
                $actions,
                array_map('strtolower', CUtil::getViewNameArray($task, ['index']) ?? [])
            );

            $groups[$task] = [
                'group' => $group,
                'actions' => array_values(array_unique($actions)),
            ];

            if (is_array(CSetting::get('apps.' . $task))) {
                self::buildTaskGroups($task, $groups, $visited);
            }
        }
    }

    private static function resolveAppGroup(string $controller): array
    {
        $apps = CSetting::get('apps') ?? [];
        $own = $apps[$controller] ?? null;

        if (is_string($own) && is_array($apps[$own] ?? null)) {
            return $apps[$own];
        }
        if (is_array($own) && !empty($own)) {
            return $own;
        }
        foreach ($apps as $items) {
            if (is_array($items) && array_key_exists($controller, $items)) {
                return $items;
            }
        }
        return [];
    }

    public static function isAllowGrp($taskGroup = ''): bool
    {
        return empty($taskGroup)
            || $taskGroup == 'guest'
            || CSecs::IsUsrGrpComp($_SESSION['uinfo']['usrgroup'] ?? 'guest', $taskGroup, '>=');
    }

    // new menus with the new structure to be tested
    /**
     * Build all four menus (main, app, bot; sub is set later) into CSetting 'menus.*'.
     * Templates come from CCore::$_stg['menus'] (names and order only).
     * Group and title come from the apps.<key> lists (task => 'group,title').
     */
    public static function setMenu($selectedController): void
    {
        // 1. tg (permission/actions map), unchanged
        $taskGroups = [];
        $topGroups = array_map('trim', explode(',', CSetting::get('apps.list')));
        foreach ($topGroups as $group) {
            self::buildTaskGroups($group, $taskGroups);
        }
        CSetting::set('tg', $taskGroups);

        // 2. templates, app key, then lookup (the order matters)
        $tpl = CCore::$_stg['menus'] ?? [];
        $appKey = self::resolveAppKey($selectedController, $tpl);
        $lookup = self::buildLookup($topGroups, $appKey);

        // 3. main: shared home + common menu, built from config as before
        $homeMenu = CCore::$_cfg['mnuhome'] ?? [];
        $commonMenu = CCore::$_cfg['mnucommon'] ?? [];
        $topMenu = array_merge($homeMenu, $commonMenu);
        if (!empty($topMenu)) {
            CSetting::set('menus.main', $topMenu);
        }

        // 4. app menu, with the '=>' prefix item
        $app = self::buildMenu($tpl['app'][$appKey] ?? '', $selectedController, $lookup, true);
        if (!empty($app)) {
            $app = array_merge([['title' => '=>']], $app);
        }
        CSetting::set('menus.app', $app);

        // 5. bottom menu
        CSetting::set('menus.bot', self::buildMenu(
            $tpl['bot'][$appKey] ?? '',
            $selectedController,
            $lookup,
            false
        ));

        // menus.sub is set later via setSubMenu()
    }

    /** task => [group, title], merged from every apps.<key> list. First definition wins. */
    private static function buildLookup(array $topGroups): array
    {
        $lookup = [];
        foreach ($topGroups as $key) {
            foreach (CSetting::get('apps.' . $key) ?? [] as $task => $value) {
                if (isset($lookup[$task])) {
                    continue;
                }
                $parts = is_array($value) ? array_values($value) : explode(',', (string) $value);
                [$grp, $title] = array_pad($parts, 2, '');
                $lookup[$task] = [trim($grp) ?: 'guest', trim($title) ?: ucfirst($task)];
            }
        }
        return $lookup;
    }

    /** Which app key ('front','jv','learn') the selected controller belongs to. */
    private static function resolveAppKey(string $controller, array $tpl): string
    {
        $appKeys = array_keys($tpl['app'] ?? []);

        // 1. the controller is itself an app (front, jv, learn)
        if (in_array($controller, $appKeys, true)) {
            return $controller;
        }
        // 2. defined in a non-front app's own list (jvadm -> jv, lrnadmin -> learn)
        foreach ($appKeys as $key) {
            if ($key !== 'front' && isset((CSetting::get('apps.' . $key) ?? [])[$controller])) {
                return $key;
            }
        }
        // 3. everything else (fbsq, static, users, ...) lives under front
        return 'front';
    }


    private static function splitNames(string|array $names): array
    {
        $names = is_array($names) ? implode(',', $names) : $names;
        return array_values(array_filter(array_map('trim', explode(',', $names))));
    }

    private static function buildMenu(
        string|array $names,
        string $selected,
        array $lookup,
        bool $isApp,
        bool $allowUnknown = false
    ): array {
        $items = [];
        foreach (self::splitNames($names) as $task) {
            if ($task[0] === '_' || ($isApp && $task === $selected)) {
                continue;
            }

            $found = $lookup[$task] ?? null;
            if ($found === null) {
                if (!$allowUnknown) {
                    error_log("CMenu: '$task' is in a menu list but not in any apps.* list");
                    continue;
                }
                $found = ['guest', ucfirst($task)];
            }
            [$grp, $title] = $found;

            if (!self::isAllowGrp($grp)) {
                error_log("CMenu: skip '$task' (group '$grp' not allowed)");
                continue;
            }
            if ($isApp && !is_dir(CFiles::getRealViewPath($task))) {
                error_log("CMenu: skip '$task' (no view dir: " . CFiles::getRealViewPath($task) . ")");
                continue;
            }



            $items[$task] = [
                'title' => $title,
                'path' => '/' . $task . '/' . CSetting::get('defview'),
            ];
        }
        return $items;
    }

    /** Normalize the view's label => url into the common shape. */
    public static function setSubMenu(array $labelToUrl): void
    {
        $items = [];
        foreach ($labelToUrl as $label => $url) {
            if (!empty($url)) {
                $items[$label] = ['title' => $label, 'path' => $url];
            }
        }
        CSetting::set('menus.sub', $items);
    }

    public static function getMenu($menu = 'main')
    {
        $items = CSetting::get('menus.' . $menu);
        return empty($items) ? '' : self::getLiMenu($items);
    }    // new menus with the new structure to be tested
    // old menu save for now
    public static function old_setMenu($selectedController): void
    {
        $homeMenu = CCore::$_cfg['mnuhome'] ?? [];
        $commonMenu = CCore::$_cfg['mnucommon'] ?? [];
        $topMenu = array_merge($homeMenu, $commonMenu);
        if (!empty($topMenu)) {
            CSetting::set('menus.main', $topMenu);
        }

        $taskGroups = [];
        $topGroups = array_map('trim', explode(',', CSetting::get('apps.list')));
        foreach ($topGroups as $group) {
            self::buildTaskGroups($group, $taskGroups);
        }
        CSetting::set('tg', $taskGroups);

        $appGroup = self::resolveAppGroup($selectedController);
        $appMenu = [];
        foreach ($appGroup as $task => $value) {
            [$taskGroup, $title] = explode(',', $value);
            $taskGroup = $taskGroup ?: 'guest';
            $title = $title ?: ucfirst($task);

            $viewPath = CFiles::getRealViewPath($task);
            if (
                is_dir($viewPath)
                && substr($task, 0, 1) !== '_'
                && self::isAllowGrp($taskGroup)
            ) {
                $appMenu[$task] = [
                    'title' => $title,
                    'path' => '/' . $task . '/' . CSetting::get('defview'),
                ];
            }
        }

        if (!empty($appMenu)) {
            unset($appMenu[$selectedController]);
            if (!empty($appMenu)) {
                $appMenu = array_merge([['title' => '=>']], $appMenu);
                CSetting::set('menus.app', $appMenu);
            }
        }
    }

    public static function old_getMenu($menu = 'main')
    {
        $menuItems = CSetting::get('menus.' . $menu);
        if (empty($menuItems) || empty($menu)) {
            return '';
        }

        if ($menu === 'sub') {
            $html = '';
            foreach ($menuItems as $label => $url) {
                if (!empty($url)) {
                    $html .= CHtml::TagWithHtml('li', CHtml::ahref($url, $label));
                }
            }
            return $html;
        }

        return self::getLiMenu($menuItems);
    }
    // old menu save for now

    public static function getCommonMenu(): array
    {
        $homeMenu = CCore::$_cfg['mnuhome'] ?? [];
        $commonMenu = CCore::$_cfg['mnucommon'] ?? [];
        return array_merge($homeMenu, $commonMenu);
    }

    public static function getTopMenu()
    {
        $topMenuHtml = '';
        $menuHtml = '';
        $selectedController = CSetting::get('selctrl');
        $appGroup = CSetting::get('apps.' . $selectedController);
        $taskGroups = [];
        $appMenu = [];

        foreach ($appGroup ?? [] as $task => $value) {
            [$taskGroup, $title] = explode(',', $value);
            (empty($taskGroup)) ? $taskGroup = 'guest' : $taskGroup;
            (empty($title)) ? $title = ucfirst($task) : $title;
            $taskGroups[$task] = $taskGroup;

            $viewPath = CFiles::getRealViewPath($task);
            if (is_dir($viewPath) && (substr($task, 0, 1) <> '_')) {
                $defaultView = CSetting::get('defview');
                if (
                    empty($taskGroup) || $taskGroup == 'guest'
                    || CSecs::IsUsrGrpComp(
                        $_SESSION['uinfo']['usrgroup'] ?? 'guest',
                        $taskGroup,
                        '>='
                    )
                ) {
                    $appMenu[$task] = ['title' => $title, 'path' => "/$task/$defaultView"];
                }
            }
        }

        if (!empty($appMenu)) {
            CSetting::set('tg', $taskGroups);
            $appMenu = CUtil::rmArr1D($appMenu, $selectedController) ?? [];
            $appMenu = array_merge([['title' => '=>']], $appMenu) ?? [];
            $menuHtml = self::getLiMenu($appMenu);
        }

        return $topMenuHtml . $menuHtml;
    }

    public static function getSubMenu()
    {
        $loginOrLogout = [];
        $query = CUtil::qsValue() ?? [];
        $selectedView = CSetting::get('selctrl') ?: ($query['t'] ?? null);

        $viewPath = CFiles::getRealViewPath($selectedView);
        if (is_dir($viewPath) && substr($selectedView, 0, 1) !== '_') {
            $login = CSetting::get('login');
            $viewExtension = CSetting::get('viewext') ?: '.php';
            if (empty($_SESSION['uinfo']['usrgroup'])) {
                $viewFile = rtrim($viewPath, DIRECTORY_SEPARATOR)
                    . DIRECTORY_SEPARATOR . $login . $viewExtension;
                if (file_exists($viewFile)) {
                    $loginOrLogout['Login'] = CUtil::tap('/' . $selectedView . '/' . $login);
                }
            } else {
                $loginOrLogout['Logout'] = CUtil::tap(CSetting::get('urllogout'));
            }
        }

        $viewLinks = CUtil::viewDir2Nv4Mnu($selectedView);
        $submenuConfig = CSetting::get('sub.' . $selectedView);
        $renamedViews = [];
        foreach ($viewLinks ?? [] as $view => $url) {
            $taskGroup = '';
            $title = '';
            if (!empty($submenuConfig[$view])) {
                $parts = explode(',', $submenuConfig[$view], 2);
                $taskGroup = $parts[0] ?? '';
                $title = $parts[1] ?? '';
            }
            $taskGroup = $taskGroup !== '' ? $taskGroup : 'guest';
            $title = $title !== '' ? $title : ucfirst($view);
            if (!self::isAllowGrp($taskGroup)) {
                continue;
            }
            $renamedViews[$title] = $url;
        }

        $submenu = array_merge($loginOrLogout, $renamedViews);
        $html = '';
        foreach ($submenu as $label => $url) {
            $html .= CHtml::TagWithHtml('li', CHtml::ahref($url, $label));
        }
        return $html;
    }
}
