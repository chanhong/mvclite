<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * HTML Helper
 *
 * @author chanhong
 */
namespace MvcLite;

class Helper {
    static $_lineBreak;
    public $ut;
    var $lineBreak;
    
    public function __construct() {
        (self::$_lineBreak == true) ?$this->lineBreak = "\n" : $this->lineBreak = "";
        $this->ut = new Util;
    }

    function urlKeyPair($iVar, $kArray) {
        $iVar = $this->ut->clean($iVar, "txt");
        $ret = "";
        $i = 0;
        $iArray = explode("/", ltrim($iVar, "/")); // take off 1st / before explode
        $cArray = array_combine($kArray, array_pad($iArray, count($kArray), ''));
        foreach ($cArray as $key => $value) {
            ($i == 0) ? $prefix = "?" : $prefix = "&";
//            ($i == 0) ? $prefix = "" : $prefix = "&";
            if (!empty($value))
                $ret .= "$prefix$key=$value";
            $i++;
        }
        return $ret;
    }

    function path2URL($iVar, $kArray) {
        $ret = "?";
        switch (substr($iVar, 0, 1)) {
            case "/": // /users/add/1
                $ret = $this->urlKeyPair($iVar, $kArray);
//                $ret = "?p=$iVar";
                break;
            case "?": // ?page1
                $ret = $iVar;
                break;
        }
        return $ret;
    }

    function tap($iVar) {
        $kArray = array('t', 'a', 'p1', 'p2'); // task, action, parm1, parm2
//        $kArray = array('t', 'p1', 'p2'); // task, action, parm1, parm2
        return $this->path2URL($iVar, $kArray);
    }

    function tai($iVar) { // no need for this since tap should take care in all case
        $kArray = array('t', 'a', 'id'); // task, action, id
        return $this->path2URL($iVar, $kArray);
    }

    function alink($iVar) {
        if (empty($iVar) or (!is_array($iVar)))
            return;
        $confirm = $imgortext = $buff = "";
        foreach ($iVar as $key => $value) {
            if (!empty($value)) {
                switch ($key) {
                    case "confirm":
                        $confirm = $this->jsConfirm('Y');
                        break;
                    case "path":
                        $href = $this->href($value);
                        break;
                    case "title":
                        $imgortext = $text = ucfirst($value);
                        break;
                    case "img":
                        if (!empty($value))
                            $imgortext = $this->href($value);
                        break;
                    default:
                        $buff .= "$key='$value'";
                }
            }
        }
        return '<a ' . $href . $confirm . $buff . '>' . $imgortext . '</a>';
    }

    function jsConfirm($Yes = "") {
        (!empty($Yes)) ? $ret = " onclick=\"return confirm('Are you sure?');\"" : $ret = "";
        return $ret;
    }

    function img($iVar) {
        return '<img class="icon" src="' . $iVar . '">';
    }

    function href($iVar) {
        return 'href="' . $this->ut->selfURL() . "/" . $this->tap($iVar) . '"';
    }

    function tag($iTag, $iTitle) {
        $cTag = strtolower($iTag);
        return "<$cTag>$iTitle</$cTag>";
    }

    function select($iVarArray, $selVar, $selName) {
        $buff = "";
        foreach ($iVarArray as $one) {
            ($one == $selVar) ? $selected = " selected" : $selected = "";
            $buff .= "<option$selected>" . htmlentities($one) . "</option>";
        }
        return '<select id ="' . $selName . '" name ="' . $selName . '">' . $buff . '</select>&nbsp;';
    }

    function filterByForm($mescript, $iVarArray, $iSelVar, $iSelName) { // modified
        if (!empty($iVarArray)) {
            return $this->lineBreak.'<FORM class="filterBy" method="post" action="' . $mescript . '">Filter by:&nbsp;&nbsp;'
                    . $this->select($iVarArray, $iSelVar, $iSelName)
                    . '<input type="submit" value="Go"></FORM>';
        }
    }

    function jsSrc($file) {
        return $this->lineBreak.'<script type="text/javascript" src="' . $file . '"></script>';
    }

    function favicon() {
        return $this->lineBreak.'<link href="favicon.ico" rel="icon" type="image/x-icon">';
    }

    function css($file = "screen.css", $iTitle = "", $media = "all") { // all or screen
        (!empty($iTitle)) ? $title = " title='$iTitle'" : $title = '';
        return $this->lineBreak.'<link rel="stylesheet" type="text/css" href="' . $file . '"' . $title . ' media="' . $media . '" />';
    }
    // not use since it is not cross browser compatible yet
    function less($file = "screen.less", $iTitle = "", $media = "all") {
        (!empty($iTitle)) ? $title = " title='$iTitle'" : $title = '';
        return $this->lineBreak.'<link rel="stylesheet/less" type="text/css" href="' . $file . '"' . $title . ' media="' . $media . '" />';
    }

    function submit($value) {
        return '<input type="submit" name="submit" value="' . $value . '">';
    }

    function marquee($str) {
        (!empty($str)) ? $ret = $this->lineBreak.'<marquee behavior="scroll" direction="left">' . $str . '</marquee>' : $ret = "";
        return $ret;
    }

    function bold($str, $color = "darkgreen") {
        (!empty($color)) ? $color = ' color=' . $color : $color = ' color=darkgreen';
        return '<b><i><font size=+1' . $color . '>' . $str . '</font></i></b>';
    }

    function meta($charset) {
//        return '<meta http-equiv="Content-Type" content="text/html; charset=' . $charset . '" />';
        return '<meta http-equiv="X-UA-Compatible" content="text/html; IE=edge; charset=' . $charset . '" />';
    }

    function classOddOrEven($url, $lineCount, $className) {
        return "<tr" . $this->classTROddOrEven($lineCount, $className) . ">" . $url . "</tr>";
    }

    function classTROddOrEven($lineCount, $className) {
        $mod = $lineCount % 2;
        ($mod == 0) ? $detclass = " class='" . $className . "Even'" : $detclass = " class='" . $className . "Odd'";
        return $detclass;
    }

}

?>
