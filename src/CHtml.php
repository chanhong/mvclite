<?php

namespace MvcLite;
use Exception;

class CHtml
{
    // HttpContext.Current does not have a direct 1:1 equivalent in PHP. 
    // We maintain the static property for structural parity.
    public static $_ctx_c = null;
    public static $_lineBreak = false;
    public $Title;
    public $ut;
    public $lineBreak;

    public function __construct(?CUtil $ut = null)
    {
        $this->ut = $ut ?? new CUtil();
        $this->lineBreak = self::$_lineBreak ? "\n" : "";
    }

    private static function lineBreakPrefix(): string
    {
        return self::$_lineBreak ? "\n" : "";
    }

    public static function urlKeyPair($path, $keys)
    {
        $parts = explode('/', ltrim(CUtil::clean((string) $path, 'txt'), '/'));
        $query = '';
        foreach ($keys as $index => $key) {
            $value = $parts[$index] ?? '';
            if ($value !== '') {
                $query .= ($index === 0 ? '?' : '&') . $key . '=' . $value;
            }
        }
        return $query;
    }

    public static function path2URL($path, $keys)
    {
        $path = (string) $path;
        if (str_starts_with($path, '/')) {
            return self::urlKeyPair($path, $keys);
        }
        return str_starts_with($path, '?') ? $path : '?';
    }

    public static function tap($path)
    {
        return self::path2URL($path, ['t', 'a', 'p1', 'p2']);
    }

    public static function tai($path)
    {
        return self::path2URL($path, ['t', 'a', 'id']);
    }

    public static function bad_alink($options)
    {
        return self::alink($options);
    }

    public static function bold($text, $color = "darkgreen")
    {
        $color = empty($color) ? 'darkgreen' : $color;
        $colorAttribute = $color !== ""
            ? " color=\"" . htmlspecialchars((string) $color, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\""
            : "";
        return "<b><i><font size=+1" . $colorAttribute . ">"
            . htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . "</font></i></b>";
    }

    public static function select($options, $selected, $name)
    {
        $html = '';
        foreach ($options as $option) {
            $value = (string) $option;
            $selectedAttribute = ((string) $selected === $value) ? ' selected' : '';
            $html .= '<option' . $selectedAttribute . '>'
                . htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '</option>';
        }
        return '<select id ="' . htmlspecialchars((string) $name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '" name ="' . htmlspecialchars((string) $name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '">' . $html . '</select>&nbsp;';
    }

    public static function less($file = "screen.less", $title = "", $media = "all")
    {
        $titleAttribute = !CString::IsEmpty($title)
            ? ' title="' . htmlspecialchars((string) $title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"'
            : '';
        return self::lineBreakPrefix() . '<link rel="stylesheet/less" type="text/css" href="'
            . htmlspecialchars((string) $file, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"'
            . $titleAttribute . ' media="'
            . htmlspecialchars((string) $media, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" />';
    }

    public static function classTROddOrEven($lineCount, $className)
    {
        $suffix = $lineCount % 2 === 0 ? 'Even' : 'Odd';
        return ' class="' . htmlspecialchars((string) $className . $suffix, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
    }

    public static function classOddOrEven($html, $lineCount, $className)
    {
        return '<tr' . self::classTROddOrEven($lineCount, $className) . '>' . $html . '</tr>';
    }

    public static function meta($charset)
    {
        return '<meta http-equiv="X-UA-Compatible" content="text/html; IE=edge; charset='
            . htmlspecialchars((string) $charset, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '" />';
    }

    public static function FrmBeg($postUrl = "", $cls = "")
    {
        return sprintf(
            "<form method=\"post\" action=\"%s\" class=\"%s\">",
            htmlspecialchars((string) ($postUrl ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars((string) ($cls ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        );
    }

    public static function FrmEnd($retUrl = "?")
    {
        return sprintf(
            "<input name=\"rurl\" type = \"hidden\" value=\"%s\"></form>",
            htmlspecialchars((string) ($retUrl ?? '?'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        );
    }

    public static function Tag_cs($iTag, $iTitle, $iTip = "") // bad???
    {
        $cTag = strtolower($iTag);
        $stip = (CString::IsEmpty($iTip) == false)
            ? sprintf(" title=\"%s\"", htmlspecialchars($iTip, ENT_QUOTES, 'UTF-8'))
            : "";
        return sprintf("<%s %s>%s</%s>", $cTag, $stip, htmlspecialchars($iTitle, ENT_QUOTES, 'UTF-8'), $cTag);
    }
    public static function Tag($iTag, $iTitle, $iTip = "")
    {
        $cTag = self::safeTagName($iTag);
        $stip = self::titleAttribute($iTip);
        $title = htmlspecialchars((string) $iTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return sprintf("<%s%s>%s</%s>", $cTag, $stip, $title, $cTag);
    }

    public static function TagWithHtml($iTag, $html, $iTip = "")
    {
        $cTag = self::safeTagName($iTag);
        $stip = self::titleAttribute($iTip);
        return sprintf("<%s%s>%s</%s>", $cTag, $stip, $html, $cTag);
    }

    private static function safeTagName($iTag): string
    {
        $tag = strtolower((string) $iTag);
        if (!preg_match('/^[a-z][a-z0-9-]*$/', $tag)) {
            throw new \InvalidArgumentException('Invalid HTML tag name.');
        }

        return $tag;
    }

    private static function titleAttribute($iTip): string
    {
        return CString::IsEmpty($iTip) == false
            ? sprintf(" title=\"%s\"", htmlspecialchars((string) $iTip, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'))
            : "";
    }

    public static function Img($iVar)
    {
        $raw = trim((string) $iVar);
        $src = $raw;

        if (preg_match('/<\s*img\b[^>]*\s+src\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))[^>]*>/is', $raw, $matches)) {
            $src = $matches[1] ?? $matches[2] ?? $matches[3] ?? $raw;
        }

        $src = trim($src);
        if ($src === '' || preg_match('/^\s*(?:javascript|vbscript|data)\s*:/i', $src) || str_contains($src, 'onerror=')) {
            $src = 'about:blank';
        }

        if (preg_match('/^(?:[a-z]+:\s*)?\/?(?:etc|var|proc|root|home)\//i', $src) || preg_match('/^\/+etc\//i', $src)) {
            $src = 'about:blank';
        }

        if (preg_match('/[<>"\']/', $src)) {
            $src = preg_replace('/[<>"\']+/', '', $src) ?? '';
        }

        $encoded = htmlspecialchars($src, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<img class="icon" src="' . $encoded . '">';
    }

    public static function JsxSrc($file)
    {
        return "<script type=\"text/babel\" src=\"" . htmlspecialchars($file, ENT_QUOTES, 'UTF-8') . "\"></script>";
    }

    public static function JsSrc($file)
    {
        return self::lineBreakPrefix()
            . "<script type=\"text/javascript\" src=\"" . $file . "\"></script>";
    }

    public static function favicon()
    {
        return self::lineBreakPrefix()
            . "<link href=\"favicon.ico\" rel=\"icon\" type=\"image/x-icon\">";
    }

    public static function Css($file = "screen.css", $iTitle = "", $media = "all")
    {
        // all or screen
        $title = (!CString::IsEmpty($iTitle)) ? " title=\"" . htmlspecialchars($iTitle, ENT_QUOTES, 'UTF-8') . "\"" : "";
        return self::lineBreakPrefix()
            . "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . htmlspecialchars($file, ENT_QUOTES, 'UTF-8') . "\"" . $title . " media=\"" . htmlspecialchars($media, ENT_QUOTES, 'UTF-8') . "\" />";
    }

    public static function Href_selfurl($iVar)
    {
        return "href=\"" . CUtil::selfUrl() . "/" . CUtil::tap($iVar) . '"';
    }
    public static function Href_cs($iVar)
    {
        return "href=\"" . CUtil::tap($iVar) . '"';
    }

    public static function ahref($v, $s)
    {
        return "<a href=\"" . $v . "\">" . $s . "</a>";
    }

    static function href($iVar)
    {
        return 'href="' . htmlspecialchars(
            CUtil::selfURL() . "/" . self::tap($iVar),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        ) . '"';
    }
    public static function JsConfirm($Yes = "")
    {
        return (CString::IsEmpty($Yes) == false) ? " onclick=\"return confirm('Are you sure?');\"" : "";
    }

    static function alink($iVar) // WORK well
    {
//           error_log((new Exception)->getTraceAsString());
        if (empty($iVar) or (!is_array($iVar)))
            return '';  // ← return empty string, not null //Warning: Array to string conversion

        $confirm = $target = $title = $imgortext = $buff = $href = '';
        foreach ($iVar as $key => $value) {
            switch ($key) {
                case "confirm":
                    $confirm = self::jsConfirm('Y');
                    break;
                case "path":
                    $clean = is_string($value) ? trim($value) : '';
                    if ($clean === '' || preg_match('/^\s*(?:javascript|vbscript|data)\s*:/i', $clean)) {
                        $clean = '#';
                    } else {
                        $clean = CUtil::tap($clean);
                    }
                    $href = ' href="' . htmlspecialchars($clean, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
                    break;
                case "href":
                    $clean = is_string($value) ? trim((string) $value) : '';
                    if ($clean === '' || preg_match('/^\s*(?:javascript|vbscript|data)\s*:/i', $clean)) {
                        $clean = '#';
                    }
                    $href = ' href="' . htmlspecialchars($clean, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
                    break;
                case "text":
                    $imgortext = htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    break;
                case "title_hlp":
                    $title = $imgortext = $text = ucfirst((string) $value);
                    break;
                case "title":
                    if ($imgortext === '') {
                        $imgortext = ucfirst((string) $value);
                    }
                    $title = " title=\"" . htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
                    break;
                case "target":
                    $target = " target=\"" . htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
                    break;
                case "mailto":
                    $href = " href=\"mailto:" . htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
                    break;
                case "file":
                    $href = " href=\"file:" . htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
                    break;
                case "img":
                    if (!empty($value))
                        $imgortext = self::Img($value);
                    break;
                default:
                    $buff .= " $key=\"" . htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
            }
        }
        $ret = '<a' . $href . $confirm . $title . $target . rtrim($buff) . '>' . $imgortext . '</a>';
        return $ret;
    }

    public static function alink_bad_wo_selfurl($iVar)
    {
        $confirm = $imgortext = $buff = $target = $href = $title = $value = "";
        //        pln($iVar,'Alink');
        // In PHP, $iVar is expected to be an associative array (NameValueCollection equivalent)
        foreach ($iVar as $key => $value) {
            switch ($key) {
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
                    $href = " " . self::Href_cs($value);
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
                    //                    $buff .= sprintf(" %s=\"%s\"", $key, htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
                    $buff .= "$key='$value'";

                    break;
            }
        }
        $ret = "<a " . $href . $confirm . $title . $target . $buff . '>' . $imgortext . "</a>";
        $ret = '<a ' . $href . $confirm . $target . $buff . '>' . $imgortext . '</a>';
        //        pln($ret,'alink');
        return $ret;
    }





    public static function marquee($str)
    {
        if ($str === null || $str === '') {
            return '';
        }
        return self::lineBreakPrefix() . "<marquee behavior='scroll' direction='left'>"
            . htmlspecialchars((string) $str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . "</marquee>";
    }

    public static function _bold($str, $color = "")
    {
        if (strlen($str) > 0) {
            $scolor = $color;
            if (strlen($color) > 0) {
                $scolor = " color='" . $color . "'";
            }
            return "<b><i><font size=-1" . $scolor . ">" . htmlspecialchars($str, ENT_QUOTES, 'UTF-8') . "</font></i></b>";
        } else {
            return $str;
        }
    }

    public static function _setMsg($iStr, $color = "red")
    {
        // if (iStr != null || iStr.Length > 0)
        if (!CString::IsEmpty($iStr)) {
            $iStr = sprintf("<%s>%s</%s>", "center", self::_bold($iStr, $color), "center");
        }
        return $iStr;
    }

    public static function alertMsg($iStr, $color = "red")
    {
        return self::_setMsg($iStr, $color);
    }

    /**
     * Handles string options, SelectListItem objects, and Value/Text option arrays.
     */
    public static function dropDnList($selName, $iVarArray, $selVar = "")
    {
        // @Html.DropDownList("DdlPageSize", "", PageSizes, "Letter", ""); only work in Razor file
        $sb = "";
        foreach ($iVarArray as $s) {
            if ($s instanceof SelectListItem) {
                $val = $s->Value;
                $text = $s->Text;
            } elseif (is_array($s)) {
                $val = $s['Value'];
                $text = $s['Text'];
            } else {
                $val = $s;
                $text = $s;
            }

            $selected = ((string) $val == (string) $selVar) ? " selected" : "";
            $sb .= sprintf(
                '<option value="%s"%s>%s</option>',
                htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8'),
                $selected,
                htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8')
            );
        }
        // return String.Format("<select id=\"{0}\" name=\"{0}\">{1}</select>", selName, sb);
        return sprintf("<select id=\"%s\" name=\"%s\">%s</select>", htmlspecialchars($selName, ENT_QUOTES, 'UTF-8'), htmlspecialchars($selName, ENT_QUOTES, 'UTF-8'), $sb);
    }

    public static function filterByForm($iSelName, $iVarArray, $iSelVar, $meqs)
    {
        if (empty($iVarArray)) {
            return '';
        }

        if (self::isActionUrl($iSelName) || !self::isActionUrl($meqs)) {
            return self::filterByFormLegacy($iSelName, $iVarArray, $iSelVar, $meqs);
        }

        return self::lineBreakPrefix() . self::FrmBeg($meqs, "filterBy") . "Filter by:&nbsp;&nbsp;"
            . self::dropDnList($iSelName, $iVarArray, $iSelVar)
            . "<input type='submit' value='Go'>"
            . self::FrmEnd($meqs);
    }

    private static function isActionUrl($value): bool
    {
        return is_string($value) && preg_match('~^(?:[/?]|[a-z][a-z0-9+.-]*://)~i', $value) === 1;
    }

    private static function filterByFormLegacy($action, $options, $selected, $selectName): string
    {
        return self::lineBreakPrefix() . '<FORM class="filterBy" method="post" action="'
            . htmlspecialchars((string) $action, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '">Filter by:&nbsp;&nbsp;'
            . self::select($options, $selected, $selectName)
            . '<input type="submit" value="Go"></FORM>';
    }

    public static function jsGrid_Ajax_Sendemail($url, $reload = "")
    {
        return self::jsGridAjaxMutation('sendItem', 'GET', $url, $reload);
    }

    public static function jsGrid_Ajax_Clone($url, $reload = "")
    {
        return self::jsGridAjaxMutation('cloneItem', 'GET', $url, $reload);
    }

    public static function jsGrid_Ajax_Read($url)
    {
        return sprintf("loadData: function(filter) { return $.ajax({ type: \"GET\", url: \"%s\", data: filter }); },", $url);
    }

    public static function jsGrid_Ajax_Create($url, $reload = "")
    {
        return self::jsGridAjaxMutation('insertItem', 'POST', $url, $reload);
    }

    public static function jsGrid_Ajax_Update($url, $reload = "")
    {
        return self::jsGridAjaxMutation('updateItem', 'PUT', $url, $reload, true);
    }

    private static function jsGridAjaxMutation($operation, $method, $url, $reload, $spaceAfterType = false)
    {
        $reld = "";
        if (strlen($reload) > 0) {
            $reld = "location.reload();";
        }

        $space = $spaceAfterType ? " " : "";
        return sprintf(
            "%s: function(item) { return $.ajax({ type: \"%s\",%surl: \"%s\", data: item, success: function(item) { %s } }); },",
            $operation,
            $method,
            $space,
            $url,
            $reld
        );
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
        foreach (array_keys($fName) as $s) {
            $ka = CUtil::Str2a(',', isset($fName[$s]) ? (string)$fName[$s][0] : ""); // get value of fName[s]
            $stitle = (strlen($ka[0]) > 0) ? $ka[0] : CString::ProperCase($s);
            $sone = sprintf("<th class=\"%s\">%s</th>", htmlspecialchars($clsName["tcls"], ENT_QUOTES, 'UTF-8'), htmlspecialchars($stitle, ENT_QUOTES, 'UTF-8'));
            $sbh .= $sone;
        }
        $sbh .= "</tr>";

        foreach ($rows as $r) {
            $cnt++;
            $sone = sprintf("<tr class=\"%s%s\">", htmlspecialchars($clsName["rcls"], ENT_QUOTES, 'UTF-8'), CUtil::evenOrOdd($cnt));
            $sbr .= $sone;
            $rNv = CUtil::dict2nv($r); // convert Nv to get field name
            foreach (array_keys($fName) as $s) {
                $ka = CUtil::Str2a(',', isset($fName[$s]) ? (string)$fName[$s][0] : ""); // get value of fName[s]
                $salign = (isset($ka[1]) && strlen($ka[1]) > 0) ? $ka[1] : "center";
                $sone = sprintf("<td align=\"%s\">%s</td>", htmlspecialchars($salign, ENT_QUOTES, 'UTF-8'), htmlspecialchars($rNv[$s] ?? "", ENT_QUOTES, 'UTF-8'));
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

    public static function getLiMenu($menu)
    {
        if (!is_array($menu) || empty($menu)) {
            return $menu;
        }

        $html = '';
        foreach ($menu as $item) {
            $html .= self::TagWithHtml('li', self::Alink($item));
        }
        return $html;
    }

    public static function Sanitize($html)
    {
        // Basic sanitization mimicking the behavior of typical XSS sanitizers
        if (empty($html))
            return $html;
        // Strip scripts and styles
        $html = preg_replace('/<(script|style|iframe|object|embed).*?>.*?<\/\1>/si', '', $html);
        // Strip event handlers
        $html = preg_replace('/ on\w+="[^"]*"/i', '', $html);
        $html = preg_replace('/ on\w+=\'[^\']*\'/i', '', $html);
        return $html;
    }

    private static $instance;
    public function User()
    {
        return new class {
            public function Identity()
            {
                return new class {
                    public function Name()
                    {
                        return $_SERVER['REMOTE_USER'] ?? '';
                    }
                };
            }
        };
    }
    public static function Current()
    {
        if (!self::$instance)
            self::$instance = new self();
        return self::$instance;
    }
}
?>