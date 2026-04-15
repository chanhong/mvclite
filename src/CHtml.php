<?php

namespace MvcLite;
use Exception;

class CHtml
{
    // HttpContext.Current does not have a direct 1:1 equivalent in PHP. 
    // We maintain the static property for structural parity.
    public static $_ctx_c = null;
    public $Title;

    public static function FrmBeg($postUrl = "", $cls = "")
    {
        return sprintf("<form method=\"post\" action=\"%s\" class=\"%s\">", htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8'), htmlspecialchars($cls, ENT_QUOTES, 'UTF-8'));
    }

    public static function FrmEnd($retUrl = "")
    {
        return sprintf("<input name=\"rurl\" type = \"hidden\" value=\"%s\"></form>", htmlspecialchars($retUrl, ENT_QUOTES, 'UTF-8'));
    }

    public static function Tag($iTag, $iTitle, $iTip = "")
    {
        $cTag = strtolower($iTag);
        $stip = (CString::IsEmpty($iTip) == false)
            ? sprintf(" title=\"%s\"", htmlspecialchars($iTip, ENT_QUOTES, 'UTF-8'))
            : "";
        return sprintf("<%s %s>%s</%s>", $cTag, $stip, htmlspecialchars($iTitle, ENT_QUOTES, 'UTF-8'), $cTag);
    }

    public static function Img($iVar)
    {
        return "<img class=\"icon\" src=\"" . htmlspecialchars($iVar, ENT_QUOTES, 'UTF-8') . "\">";
    }

    public static function JsxSrc($file)
    {
        return "<script type=\"text/babel\" src=\"" . htmlspecialchars($file, ENT_QUOTES, 'UTF-8') . "\"></script>";
    }

    public static function JsSrc($file)
    {
        return "<script type=\"text/javascript\" src=\"" . $file . "\"></script>";
    }

    public static function favicon()
    {
        return "<link href=\"favicon.ico\" rel=\"icon\" type=\"image/x-icon\">";
    }

    public static function Css($file = "css/screen.css", $iTitle = "JV", $media = "all")
    {
        // all or screen
        $title = (!CString::IsEmpty($iTitle)) ? " title=\"" . htmlspecialchars($iTitle, ENT_QUOTES, 'UTF-8') . "\"" : "";
        return "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . htmlspecialchars($file, ENT_QUOTES, 'UTF-8') . "\"" . $title . " media=\"" . htmlspecialchars($media, ENT_QUOTES, 'UTF-8') . "\" />";
    }

    public static function Href($iVar)
    {
        return "href=\"" . CUtil::selfUrl() . "/" . CUtil::tap($iVar) . '"';
    }

    public static function JsConfirm($Yes = "")
    {
        return (CString::IsEmpty($Yes) == false) ? " onclick=\"return confirm('Are you sure?');\"" : "";
    }

    public static function Alink($iVar)
    {
        $confirm = $imgortext = $buff = $target = $href = $title = $value = "";
        
        // In PHP, $iVar is expected to be an associative array (NameValueCollection equivalent)
        foreach ($iVar as $key => $value)
        {
            switch ($key)
            {
                case "title":
                    $title = " title=\"" . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
                    break;
                case "target":
                    $target = " target=\"" . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
                    break;
                case "confirm":
                    $confirm = self::JsConfirm("Y");
                    break;
                case "href":
                    $href = " href=\"" . $value . '"';
                    break;
                case "path":
                    $href = " " . self::Href($value);
                    break;
                case "file":
                    $href = " href=\"file:" . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
                    break;
                case "mailto":
                    $href = " href=\"mailto:" . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
                    break;
                case "text":
                    $imgortext = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                    break;
                case "img":
                    $imgortext = self::Img($value);
                    break;
                default:
                    $buff .= sprintf(" %s=\"%s\"", $key, htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
                    break;
            }
        }
        return "<a" . $href . $confirm . $title . $target . $buff . '>' . $imgortext . "</a>";
    }

    public static function marquee($str)
    {
        $ret = "";
        if ($str !== null)
        {
            $ret = "<marquee behavior='scroll' direction='left'>" . htmlspecialchars($str, ENT_QUOTES, 'UTF-8') . "</marquee>";
        }
        return $ret;
    }

    public static function _bold($str, $color = "")
    {
        if (strlen($str) > 0)
        {
            $scolor = $color;
            if (strlen($color) > 0)
            {
                $scolor = " color='" . $color . "'";
            }
            return "<b><i><font size=-1" . $scolor . ">" . htmlspecialchars($str, ENT_QUOTES, 'UTF-8') . "</font></i></b>";
        }
        else
        {
            return $str;
        }
    }

    public static function _setMsg($iStr, $color = "red")
    {
        // if (iStr != null || iStr.Length > 0)
        if (!CString::IsEmpty($iStr))
        {
            $iStr = sprintf("<%s>%s</%s>", "center", self::_bold($iStr, $color), "center");
        }
        return $iStr;
    }

    public static function alertMsg($iStr, $color = "red")
    {
        return self::_setMsg($iStr, $color);
    }

    /**
     * Handles both List<string> and List<SelectListItem> overloads
     */
    public static function dropDnList($selName, $iVarArray, $selVar = "")
    {
        // @Html.DropDownList("DdlPageSize", "", PageSizes, "Letter", ""); only work in Razor file
        $sb = "";
        foreach ($iVarArray as $s)
        {
            $val = "";
            if ($s instanceof SelectListItem) {
                $val = $s->Value;
            } else {
                $val = $s;
            }

            $selected = ($val == $selVar) ? " selected" : "";
            // sb.Append(String.Format("<option{0}>{1}</option>", selected, s));
            $sb .= sprintf("<option%s>%s</option>", $selected, htmlspecialchars($val, ENT_QUOTES, 'UTF-8'));
        }
        // return String.Format("<select id=\"{0}\" name=\"{0}\">{1}</select>", selName, sb);
        return sprintf("<select id=\"%s\" name=\"%s\">%s</select>", htmlspecialchars($selName, ENT_QUOTES, 'UTF-8'), htmlspecialchars($selName, ENT_QUOTES, 'UTF-8'), $sb);
    }

    public static function filterByForm($iSelName, $iVarArray, $iSelVar, $meqs)
    {
        $ret = "";
        if ($iVarArray !== null)
        {
            $ret = self::FrmBeg($meqs, "filterBy") . "Filter by:&nbsp;&nbsp;"
                . self::dropDnList($iSelName, $iVarArray, $iSelVar)
                . "<input type='submit' value='Go'>"
                . self::FrmEnd($meqs);
        }
        return $ret;
    }

    public static function jsGrid_Ajax_Sendemail($url, $reload = "")
    {
        $reld = "";
        if (strlen($reload) > 0)
        {
            $reld = "location.reload();";
        }
        return sprintf("sendItem: function(item) { return $.ajax({ type: \"GET\",url: \"%s\", data: item, success: function(item) { %s } }); },", $url, $reld);
    }

    public static function jsGrid_Ajax_Clone($url, $reload = "")
    {
        $reld = "";
        if (strlen($reload) > 0)
        {
            $reld = "location.reload();";
        }
        return sprintf("cloneItem: function(item) { return $.ajax({ type: \"GET\",url: \"%s\", data: item, success: function(item) { %s } }); },", $url, $reld);
    }

    public static function jsGrid_Ajax_Read($url)
    {
        return sprintf("loadData: function(filter) { return $.ajax({ type: \"GET\", url: \"%s\", data: filter }); },", $url);
    }

    public static function jsGrid_Ajax_Create($url, $reload = "")
    {
        $reld = "";
        if (strlen($reload) > 0)
        {
            $reld = "location.reload();";
        }
        return sprintf("insertItem: function(item) { return $.ajax({ type: \"POST\",url: \"%s\", data: item, success: function(item) { %s } }); },", $url, $reld);
    }

    public static function jsGrid_Ajax_Update($url, $reload = "")
    {
        $reld = "";
        if (strlen($reload) > 0)
        {
            $reld = "location.reload();";
        }
        return sprintf("updateItem: function(item) { return $.ajax({ type: \"PUT\", url: \"%s\", data: item, success: function(item) { %s } }); },", $url, $reld);
    }

    public static function jsGrid_Ajax_Delete($url)
    {
        $ret = "";
        $ret = sprintf("deleteItem: function(item) { return $.ajax({ type: \"DELETE\", url: \"%s\", data: item }); },", $url);
        return $ret;
    }

    // use the one in the site.js instead
    public static function jsGrid_Create_Button()
    {
        return "jsGridCreateButton = function(cls, tooltip, clickHandler) {"
            . "  var grid = this._grid;"
            . "  return $(\"<i>\").addClass(cls).attr({ title: tooltip, })"
            . "    .on(\"click\", function(e) {clickHandler(grid, e); });"
            . "};";
    }

    // output html table rows with params of fieldsname, rows and class of table and row
    public static function OutTblRows($fName, $rows, $clsName)
    {
        $sbh = "";
        $sbr = "";
        $ka = [];
        $stitle = $salign = $sone = "";

        $cnt = 0;
        $sone = sprintf("<tr class=\"%s\">", $clsName["tcls"]);
        $sbh .= $sone;
        foreach (array_keys($fName) as $s)
        {
            $ka = CUtil::Str2a(',', $fName[$s]); // get value of fName[s]
            $stitle = (strlen($ka[0]) > 0) ? $ka[0] : CString::ProperCase($s);
            $sone = sprintf("<th class=\"%s\">%s</th>", htmlspecialchars($clsName["tcls"], ENT_QUOTES, 'UTF-8'), htmlspecialchars($stitle, ENT_QUOTES, 'UTF-8'));
            $sbh .= $sone;
        }
        $sbh .= "</tr>";

        foreach ($rows as $r)
        {
            $cnt++;
            $sone = sprintf("<tr class=\"%s%s\">", htmlspecialchars($clsName["rcls"], ENT_QUOTES, 'UTF-8'), CUtil::evenOrOdd($cnt));
            $sbr .= $sone;
            $rNv = CUtil::dict2nv($r); // convert Nv to get field name
            foreach (array_keys($fName) as $s)
            {
                $ka = CUtil::Str2a(',', $fName[$s]); // get value of fName[s]
                $salign = (strlen($ka[1]) > 0) ? $ka[1] : "center";
                $sone = sprintf("<td align=\"%s\">%s</td>", htmlspecialchars($salign, ENT_QUOTES, 'UTF-8'), htmlspecialchars($rNv[$s], ENT_QUOTES, 'UTF-8'));
                $sbr .= $sone;
            }
        }
        $sbr .= "</tr>";
        return $sbh . $sbr;
    }

    public static function Submit($value)
    {
        return sprintf("<input type=\"submit\" name=\"submit\" value=\"%s\">", htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
    }

    public static function Sanitize($html) {
        // Basic sanitization mimicking the behavior of typical XSS sanitizers
        if (empty($html)) return $html;
        // Strip scripts and styles
        $html = preg_replace('/<(script|style|iframe|object|embed).*?>.*?<\/\1>/si', '', $html);
        // Strip event handlers
        $html = preg_replace('/ on\w+="[^"]*"/i', '', $html);
        $html = preg_replace('/ on\w+=\'[^\']*\'/i', '', $html);
        return $html;
    }  
    
    private static $instance;
    public function User() {
        return new class {
            public function Identity() {
                return new class {
                    public function Name() {
                        return $_SERVER['REMOTE_USER'] ?? '';
                    }
                };
            }
        };
    }
    public static function Current() {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }      
}
?>
