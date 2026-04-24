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
      if ($sb != null && $sb->getLength() > 0)
      {
        $ret = $sb->ToString();
        $ret = substr($ret, 0, strlen($ret) - strlen($separator));
      }
      return $ret;
    }

    public static function viewNameDir2Submnu($fa)
    {
      $sb = new StringBuilder();
      // In C#, foreach on NameValueCollection iterates keys
      foreach (array_keys($fa) as $f)
      {
        // check perm here before build the menu
        $mnu = self::viewDir2Nv4Mnu($f); // all menu link from the view folder
        if ($mnu != null && count($mnu) > 0)
        {
          $buff = "";
          /*
          [SMnu]:(jendo)
          [mnu]:(autocomplete=[http:\//localhost:83/ejvnetdev/?t=jendo&a=autocomplete],createselectpdf=[http:\//localhost:83/ejvnetdev/?t=jendo&a=createselectpdf],datepicker=[http:\//localhost:83/ejvnetdev/?t=jendo&a=datepicker],ldapUW=[http:\//localhost:83/ejvnetdev/?t=jendo&a=ldapUW],upload=[http:\//localhost:83/ejvnetdev/?t=jendo&a=upload]) 
          */
          // CMsg::_pdmsg($mnu, "vfl"); // for each view folder
          $lnka = null;
          $sbx = new StringBuilder();
          foreach (array_keys($mnu) as $s)
          {
            // CMsg::_pdmsg($mnuType."-".$s, "m-i");
            if ($s != null && strlen($s) > 0)
            {
              $lnka = CUtil::mnu_s2a($mnu[$s], $s); // non-secure, inclusive
              if (
                CSecs::isPublicAccess4Mnu($lnka, $f) == true
                || CSecs::isUsrHasAccess2Mnu($lnka, $f) == true
              )
              {
                $sbx::Append(CUtil::a2Li($lnka));
              }
            }
          }
          $buff = $sbx->ToString();
          if ($buff != null && strlen($buff) > 0)
          {
            $sb->Append($buff);
          }
        }
      }
      return $sb->ToString();
    }

    public static function setActiveCtrl($qsa)
    {
      // CMsg::_pdmsg($qsa, "setActiveCtrl");
      $fa = null;
      $selectctrl = "";
      if ($qsa != null && count($qsa) > 0)
      {
        $fa = CUtil::getCfg("apps");
        $mnu_apps = self::sName2Mnu($fa);
        // CMsg::_pdmsg($fa, "setActiveCtrl");
        if ($mnu_apps != null && isset($mnu_apps[$qsa[0]]) && strlen($mnu_apps[$qsa[0]]) > 0)
        {
          _ctx_c::$Application["selctrl"] = $qsa[0]; // set selected active controller
          // CCore::$_cfg["mnu" . $qsa[0]] = [];
        }
        $selectctrl = self::getAppTxt("selctrl");
        if ($selectctrl == "")
        {
          _ctx_c::$Application["selctrl"] = self::getAppTxt("defctrl"); // use default controller
        }
        // CMsg::_pdmsg(self::getAppTxt("selctrl"), "aselC");
        _ctx_c::$Application["urllogin"] = "/" . CCore::getSelectedViewSet() . "/" . self::getAppTxt("login"); // set login url based on selected controller
      }
      CCore::SetMenuTop(); // set default for top menu
    }

    public static function mnu2Li($mnu)
    {
      $lnka = null;
      $sb = new StringBuilder();
      // CMsg::_pdmsg($mnu, "mnu");
      // [mnu]: (front =[http:\//localhost:83/ejvnetdev/?t=front],admin=[http:\//localhost:83/ejvnetdev/?t=admin])
      foreach (array_keys($mnu) as $s)
      {
        if (CString::IsEmpty($s) == false)
        {
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
      // CMsg::_pdmsg($mnu, "mnu");
      // [mnu]:(jv=[http://localhost:83/ejvnetdev/?t=jv],jvadm=[http://localhost:83/ejvnetdev/?t=jvadm],jvapv=[http://localhost:83/ejvnetdev/?t=jvapv],jvtpl=[http://localhost:83/ejvnetdev/?t=jvtpl],jvinq=[http://localhost:83/ejvnetdev/?t=jvinq],jendo=[http://localhost:83/ejvnetdev/?t=jendo],jsgrid=[http://localhost:83/ejvnetdev/?t=jsgrid],ko=[http://localhost:83/ejvnetdev/?t=ko])
      foreach (array_keys($mnu) as $s)
      {
        if (CString::IsEmpty($s) == false && $s != $mnuType) // exclude the default controller in the submenu
        {
          $lnka = CUtil::mnu_s2a($mnu[$s], $s); // http:\//localhost:83/ejvnetdev/test, test
                                     // [lnka]:(0=[http://localhost:83/ejvnetdev/?t=jvadm],1=[],2=[],3=[JV Admin]) 
                                     // CMsg::_pdmsg($lnka, "lnka");
          if (
            CSecs::isPublicAccess4Mnu($lnka, $mnuType) == true
            || CSecs::isUsrHasAccess2Mnu($lnka, $mnuType) == true
          )
          {
            $sb->Append(self::a2Li($lnka));
          }
          /*
          else
          {
            CMsg::_pdmsg($lnka, "lnka-else");
            CMsg::_pdmsg($mnuType, "mnuType");
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
      //      CMsg::_pdmsg($fa, "sName2Mnu");
      $name = "";
      foreach ($fa as $f)
      {
        $viewPath = CFiles::getRealViewPath($f);
        if (is_dir($viewPath))
        {
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
      foreach ($iArray as $f)
      {
        $viewPath = CFiles::getRealViewPath($f);
        if (is_dir($viewPath))
        {
          //          $nvList->add(Ucase($f), tap("/" . $f));
          $nvList->add($f, tap("/" . $f));
        }
      }
      return $nvList;
    }

    /**
     * Scans a directory and converts subdirectories into a menu collection.
     */
    public static function viewDir2Nv4Mnu(string $dPath = "."): NameValueCollection
    {
      $nvList = new NameValueCollection();
      $files = self::getViewNameArray($dPath);
      if ($files != null)
      {
        foreach ($files as $f)
        {
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
        return array_values(array_filter(explode($delimit, $iList), 'strlen'));
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
}

