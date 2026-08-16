<?php
namespace MvcLite;
use DateTimeZone;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

// --- Supporting Classes/Data Structures ---

// Mimic SelectListItem for dropdowns
class SelectListItem
{
    public string $Text;
    public string $Value;

    public function __construct(string $text, string $value)
    {
        $this->Text = $text;
        $this->Value = $value;
    }
}

// Mimic Enums (using PHP classes with constants or PHP 8.1+ Enums)
// For compatibility, using classes with constants.
class PdfPageSize
{
    const A1 = 'A1';
    const A2 = 'A2';
    const A3 = 'A3';
    const A4 = 'A4';
    const A5 = 'A5';
    const Letter = 'Letter';
    const HalfLetter = 'HalfLetter';
    const Ledger = 'Ledger';
    const Legal = 'Legal';

    // Helper to get all possible values for validation
    public static function getAllowedValues(): array
    {
        return [self::A1, self::A2, self::A3, self::A4, self::A5, self::Letter, self::HalfLetter, self::Ledger, self::Legal];
    }
}

class PdfPageOrientation
{
    const Portrait = 'portrait';
    const Landscape = 'landscape';

    public static function getAllowedValues(): array
    {
        return [self::Portrait, self::Landscape];
    }
}

// Mock TimeZoneInfo if you need to replicate this logic in PHP
class TimeZoneInfo
{
    public string $Id;
    public string $DisplayName;

    public function __construct(string $id, string $displayName)
    {
        $this->Id = $id;
        $this->DisplayName = $displayName;
    }

    // Static method to get system time zones (PHP equivalent)
    public static function GetSystemTimeZones(): array
    {
        $zones = [];
        $phpTimezones = DateTimeZone::listIdentifiers(); // Get all available PHP time zones

        foreach ($phpTimezones as $tzId) {
            try {
                $tz = new DateTimeZone($tzId);
                // Display name can be complex to get universally.
                // DateTimeZone::getDisplayNames() can be used, but it's locale-dependent.
                // For simplicity, let's use the ID or a simplified name.
                $displayName = str_replace('_', ' ', $tzId); // Simple display name
                $zones[] = new TimeZoneInfo($tzId, $displayName);
            } catch (Exception $e) {
                // Ignore invalid time zone IDs if any
            }
        }
        return $zones;
    }
}

/**
 * Description of class
 *
 * @author chanhong
 */
// --- Supporting Classes/Data Structures ---

// IMPORTANT: Ensure the FileInfo class is defined and accessible.
// This is a basic representation mirroring C#'s System.IO.FileInfo.
// Adjust based on your actual FileInfo class.
class FileInfo
{
    public string $FullName;     // Full path to the file (e.g., /path/to/dir/file.txt)
    public string $Name;         // File name including extension (e.g., file.txt)
    public string $Extension;    // File extension (e.g., txt)
    public string $DirectoryName;// Directory containing the file (e.g., /path/to/dir)
    // Add other properties/methods as needed (e.g., Length, CreationTime)

    public function __construct(string $fullPath)
    {
        $this->FullName = $fullPath;
        $this->Name = basename($fullPath);
        $this->Extension = pathinfo($fullPath, PATHINFO_EXTENSION);
        $this->DirectoryName = dirname($fullPath);
    }
}



class CFiles
{

    public static function delTree(string $dir): void
    {
        $phpTimezones = DateTimeZone::listIdentifiers(); // Get all available PHP time zones

        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    $path = $dir . DIRECTORY_SEPARATOR . $object;
                    if (is_dir($path)) {
                        self::delTree($path);
                    } else {
                        unlink($path);
                    }
                }
            }
            rmdir($dir);
        }
    }

    public static function dir2Array(string $dir, bool $recursive = false): array
    {
        $oArray = [];
        if (!is_dir($dir)) {
            return $oArray;
        }

        $cdir = scandir($dir);
        foreach ($cdir as $key => $value) {
            if (!in_array($value, array(".", ".."))) {
                if ((is_dir($dir . DIRECTORY_SEPARATOR . $value)) and $recursive == true) {
                    $oArray[$value] = self::dir2Array($dir . DIRECTORY_SEPARATOR . $value, $recursive);
                } else {
                    $oArray[] = $value;
                }
            }
        }
        return $oArray;
    }

    public static function filesList(string $dir, string $ext): array
    {
        $l = array();
        if (!is_dir($dir)) {
            return $l;
        }

        $quotedExt = preg_quote($ext, '/');
        foreach (array_diff(scandir($dir), array('.', '..')) as $f)
            if (
                is_file($dir . DIRECTORY_SEPARATOR . $f)
                && (($ext) ? (preg_match("/$quotedExt$/i", $f)) : 1)
            )

                $l[] = $f;

        return $l;
    }

    public static function filesListNameOnly(string $dir, string $ext): array
    {
        $l = array();
        if (!is_dir($dir)) {
            return $l;
        }

        $quotedExt = preg_quote($ext, '/');
        foreach (array_diff(scandir($dir), array('.', '..')) as $f)
            if (
                is_file($dir . DIRECTORY_SEPARATOR . $f)
                && (($ext) ? (preg_match("/$quotedExt$/i", $f)) : 1)
            )

                $l[] = CUtil::fName($f);

        return $l;
    }

    public static function dirsList(string $dir): array
    {
        $l = array();
        if (!is_dir($dir)) {
            return $l;
        }

        foreach (array_diff(scandir($dir), array('.', '..')) as $f)
            if (is_dir($dir . DIRECTORY_SEPARATOR . $f))
                $l[] = $f;

        return $l;
    }





    public static function getViewDirList($dPath = ".")
    {
        $path = self::$_ctx_c->Server::MapPath($dPath);
        $files = [];
        if (is_dir($path)) {
            $dirFiles = scandir($path);
            foreach ($dirFiles as $file) {
                if ($file !== '.' && $file !== '..' && is_file($path . DIRECTORY_SEPARATOR . $file)) {
                    $files[] = new FileInfo($path . DIRECTORY_SEPARATOR . $file);
                }
            }
        }
        return $files;
    }

    // var files = di.GetFilesByExtensions(".jpg", ".gif", ".png");

    public static function GetFilesByExtensions($directoryPath, ...$extensions)
    {
        $allowedExtensions = array_map('strtolower', $extensions);
        $files = [];
        if (is_dir($directoryPath)) {
            $dirFiles = scandir($directoryPath);
            foreach ($dirFiles as $file) {
                $ext = '.' . pathinfo($file, PATHINFO_EXTENSION);
                if (in_array(strtolower($ext), $allowedExtensions)) {
                    $files[] = new FileInfo($directoryPath . DIRECTORY_SEPARATOR . $file);
                }
            }
        }
        return $files;
    }

    public static function getFilesBefYear($dPath = ".", $year = 2009)
    {
        $path = self::$_ctx_c->Server->MapPath($dPath);
        $StartOfYear = (new DateTime("$year-01-01", new DateTimeZone("UTC")))->getTimestamp();

        $ret = [];
        if (is_dir($path)) {
            $files = scandir($path);
            foreach ($files as $f) {
                $fullPath = $path . DIRECTORY_SEPARATOR . $f;
                if (is_file($fullPath) && filectime($fullPath) < $StartOfYear) {
                    $ret[] = new FileInfo($fullPath);
                }
            }
        }
        return $ret;
    }

    public static function getFilesByPattern($dPath = ".", $pattern = "")
    {
        $path = self::$_ctx_c->Server->MapPath($dPath);
        $ret = [];

        // Note: PHP glob handles pattern matching similarly to .NET EnumerateFiles
        $searchPattern = $path . DIRECTORY_SEPARATOR . (empty($pattern) ? "*" : $pattern);
        $files = glob($searchPattern);

        if ($files) {
            foreach ($files as $f) {
                if (is_file($f)) {
                    $ret[] = new FileInfo($f);
                }
            }
        }
        return $ret;
    }

    public static function getRealViewPath($dPath = ".")
    {
        $vPath = CSetting::get("viewpath");
        $vPath = CUtil::v2BasePath($vPath);
        $vfilePath = $vPath . "/" . $dPath;
        return self::RealFilePath($vfilePath);
    }


    public static function finto_getViewFilesExcl($dPath = ".", $excl = "index")
    {
        $ret = null;
        $viewPath = self::getRealViewPath($dPath);
        if (is_dir($viewPath)) {
            $ret = [];
            $files = scandir($viewPath);
            foreach ($files as $f) {
                $fullPath = $viewPath . DIRECTORY_SEPARATOR . $f;
                if (is_file($fullPath)) {
                    $fileNameNoExt = pathinfo($f, PATHINFO_FILENAME);
                    if (strtolower($fileNameNoExt) != strtolower($excl) && substr($f, 0, 1) != "_") {
                        $ret[] = new FileInfo($fullPath);
                    }
                }
            }
        }
        return $ret;
    }

    public static function getViewDir($dPath = ".")
    {
        $files = self::getViewFilesExcl($dPath, "index");
        return $files;
    }

    public static function getViewDirByExt($dPath = ".")
    {
        $dirPath = self::getRealViewPath($dPath);
        $dirList = self::GetFilesByExtensions($dirPath, ".cshtml");
        return $dirList;
    }

    // Example definition for PdfPageOrientation (replace with your actual enum values)
//class PdfPageOrientation {
//    const Portrait = 'portrait';
//    const Landscape = 'landscape';
    // Add other orientations as needed
//}

    /**
     * Creates a PDF document from HTML content.
     *
     * @param string $targetFolder The directory where the PDF will be saved.
     * @param string $baseUrl The base URL for resolving relative paths in the HTML (e.g., for images, CSS).
     * @return string|false The full path to the saved PDF file on success, or false on failure.
     */
    public static function createPDF($targetFolder, $baseUrl)
    {
        $htmlString = "";
        $webPageWidth = 1024;
        $webPageHeight = 0; // Typically means auto height

        // --- Input Handling and Validation ---
        // $_POST is the direct equivalent of HttpContext.Current.Request.Form for POST data.
        // For GET data, you would use $_GET.
        // It's crucial to validate and sanitize all user input.
        $frm = $_POST;

        // Get and validate Page Size
        $pageSize = PdfPageSize::A4; // Default
        if (isset($frm["DdlPageSize"])) {
            $requestedPageSize = filter_var($frm["DdlPageSize"], FILTER_SANITIZE_STRING);
            // Check if the requested size is one of the allowed enum values (or their string representations)
            // In PHP, you'd typically check against string constants or predefined array of allowed values.
            // Example: If PdfPageSize::A4 is the string 'A4', then check if $requestedPageSize === 'A4'
            // For this example, let's assume your string representations match the enum names, or you have a mapping.
            // A robust way is to have an array of allowed values.
            $allowedPageSizes = ['A4', 'Letter', 'Legal']; // Example allowed values
            if (in_array($requestedPageSize, $allowedPageSizes, true)) {
                $pageSize = $requestedPageSize;
            } else {
                // Log or handle invalid page size input
                error_log("Invalid PdfPageSize provided: " . $requestedPageSize);
            }
        }

        // Get and validate Page Orientation
        $pdfOrientation = PdfPageOrientation::Portrait; // Default
        if (isset($frm["DdlPageOrientation"])) {
            $requestedOrientation = filter_var($frm["DdlPageOrientation"], FILTER_SANITIZE_STRING);
            $allowedOrientations = ['portrait', 'landscape']; // Example allowed values
            if (in_array(strtolower($requestedOrientation), $allowedOrientations, true)) {
                $pdfOrientation = strtolower($requestedOrientation); // Store in consistent case
            } else {
                // Log or handle invalid orientation input
                error_log("Invalid PdfPageOrientation provided: " . $requestedOrientation);
            }
        }

        // Get and validate Width
        if (isset($frm["TxtWidth"])) {
            // Use filter_input for better validation and sanitization
            $widthInput = filter_input(INPUT_POST, "TxtWidth", FILTER_VALIDATE_INT);
            if ($widthInput !== false && $widthInput > 0) { // Ensure it's a positive integer
                $webPageWidth = $widthInput;
            } else {
                // Log or handle invalid width input
                error_log("Invalid TxtWidth provided: " . (isset($frm["TxtWidth"]) ? $frm["TxtWidth"] : 'null'));
            }
        }

        // Get and validate Height
        if (isset($frm["TxtHeight"])) {
            $heightInput = filter_input(INPUT_POST, "TxtHeight", FILTER_VALIDATE_INT);
            // Height can be 0 (auto), so validate for non-negative integer
            if ($heightInput !== false && $heightInput >= 0) {
                $webPageHeight = $heightInput;
            } else {
                // Log or handle invalid height input
                error_log("Invalid TxtHeight provided: " . (isset($frm["TxtHeight"]) ? $frm["TxtHeight"] : 'null'));
            }
        }

        // Get HTML string
        if (isset($frm["TxtHtmlCode"])) {
            // Basic sanitization: remove potential script tags if not strictly necessary.
            // For robust HTML sanitization, use a library like HTML Purifier.
            $htmlString = $frm["TxtHtmlCode"];
            // Example: Remove script tags (use carefully, might remove legitimate content)
            // $htmlString = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $htmlString);
        } else {
            error_log("TxtHtmlCode not provided.");
            return false; // No HTML to convert
        }

        // --- PDF Conversion Logic ---
        // instantiate a html to pdf converter object
        // *** IMPORTANT: Replace 'HtmlToPdf' with the actual class name from your chosen library ***
        // And ensure the library is properly installed and included.
        // Example: If using dompdf: $converter = new Dompdf();
        $converter = new HtmlToPdf(); // Use your actual converter class

        // set converter options
        // Ensure these option names match your specific HTML-to-PDF library
        $converter->Options->PdfPageSize = $pageSize;
        $converter->Options->PdfPageOrientation = $pdfOrientation;
        $converter->Options->WebPageWidth = $webPageWidth;
        $converter->Options->WebPageHeight = $webPageHeight;

        // Ensure targetFolder is a valid, writable directory
        if (!is_dir($targetFolder) || !is_writable($targetFolder)) {
            error_log("Target folder '$targetFolder' is not a valid or writable directory.");
            // Handle error: destination folder is not valid or writable
            return false; // Indicate failure
        }

        try {
            // create a new pdf document converting an html string
            // *** IMPORTANT: Replace 'ConvertHtmlString' with the actual method name ***
            // and the return type ('PdfDocument') with the actual document object type.
            $doc = $converter->ConvertHtmlString($htmlString, $baseUrl);

            // Generate a dynamic filename for better management.
            // Using a fixed "Document2.pdf" will cause overwrites.
            $outputFileName = "Document_" . date('YmdHis') . "_" . uniqid() . ".pdf";
            $fullPath = rtrim($targetFolder, '/\\') . DIRECTORY_SEPARATOR . $outputFileName;

            // save pdf document
            // *** IMPORTANT: Ensure $doc has a 'Save' method and accepts the full path ***
            $doc->Save($fullPath);

            // close pdf document
            // *** IMPORTANT: Ensure $doc has a 'Close' method ***
            $doc->Close();

            // Return the path to the saved file on success
            return $fullPath;

        } catch (Exception $e) {
            // Catch exceptions from the HTML-to-PDF library or file operations
            error_log("PDF Creation Error: " . $e->getMessage());
            // Handle the exception appropriately
            return false; // Indicate failure
        }
    }

    // --- How to use this function (example) ---
// Assuming this `createPDF` function is part of a class, like `PdfGenerator`.
/*
class PdfGenerator {
    // ... (paste the createPDF function here) ...

    // Dummy classes for demonstration if you don't have them
    public static function createPDF($targetFolder, $baseUrl) {
       // ... function body as above ...
       // This is where you would instantiate your actual library.
       // For example:
       // require_once('path/to/dompdf/autoload.inc.php');
       // $dompdf = new Dompdf();
       // $dompdf->loadHtml($htmlString);
       // $dompdf->setPaper($pageSize, $pdfOrientation); // Example setting
       // ...
       // $output = $dompdf->output();
       // file_put_contents($fullPath, $output);
       // return $fullPath;
    }
}

// Example of calling the function:
// $saveDirectory = '/var/www/html/generated_pdfs/'; // Ensure this directory exists and is writable by the web server
// $baseUrlForAssets = 'http://yourwebsite.com/assets/'; // When your HTML needs to load images/CSS from
//
// $_POST = [
//     "DdlPageSize" => "A4",
//     "DdlPageOrientation" => "portrait",
//     "TxtWidth" => "1024",
//     "TxtHeight" => "0",
//     "TxtHtmlCode" => "<h1>Hello World</h1><p>This is a test.</p>"
// ]; // Simulate POST data for testing
//
// $pdfPath = PdfGenerator::createPDF($saveDirectory, $baseUrlForAssets);
//
// if ($pdfPath) {
//     echo "PDF successfully created at: " . htmlspecialchars($pdfPath);
// } else {
//     echo "Failed to create PDF.";
// }
*/
    /*
        public static function createPDF($targetFolder, $baseUrl)
        {
            $htmlString = "";
            $webPageWidth = 1024;
            $webPageHeight = 0;
            $frm = $_POST;

            $pageSize = isset($frm["DdlPageSize"]) ? $frm["DdlPageSize"] : PdfPageSize::A4;
            $pdfOrientation = isset($frm["DdlPageOrientation"]) ? $frm["DdlPageOrientation"] : PdfPageOrientation::Portrait;

            // instantiate a html to pdf converter object
            $converter = new HtmlToPdf();

            // set converter options
            $converter->Options->PdfPageSize = $pageSize;
            $converter->Options->PdfPageOrientation = $pdfOrientation;
            $converter->Options->WebPageWidth = $webPageWidth;
            $converter->Options->WebPageHeight = $webPageHeight;

            try {
                if (isset($frm["TxtWidth"])) {
                    $webPageWidth = (int) $frm["TxtWidth"];
                }
            } catch (Exception $e) {
            }

            try {
                if (isset($frm["TxtHeight"])) {
                    $webPageHeight = (int) $frm["TxtHeight"];
                }
            } catch (Exception $e) {
            }

            try {
                if (isset($frm["TxtHtmlCode"])) {
                    $htmlString = $frm["TxtHtmlCode"];
                }
            } catch (Exception $e) {
            }

            // create a new pdf document converting an url
            $doc = $converter->ConvertHtmlString($htmlString, $baseUrl);

            // save pdf document
            $doc->Save($targetFolder . "Document2.pdf");

            // close pdf document
            $doc->Close();
        }
    */
    public static function uploadFiles($targetFolder)
    {
        $separator = ",";
        $ret = "";
        $statusRet = "";

        if (!isset($_SESSION))
            session_start();

        // PHP $_FILES structure is different for multiple files
        // Normalizing $_FILES to iterate
        foreach ($_FILES as $key => $fileData) {
            if (is_array($fileData['name'])) {
                for ($i = 0; $i < count($fileData['name']); $i++) {
                    $uploadedFile = [
                        'name' => $fileData['name'][$i],
                        'type' => $fileData['type'][$i],
                        'tmp_name' => $fileData['tmp_name'][$i],
                        'error' => $fileData['error'][$i],
                        'size' => $fileData['size'][$i]
                    ];
                    $status = self::uploadOneFile($uploadedFile, $targetFolder);
                    if (strlen($status) > 0) {
                        $statusRet .= $status . $separator;
                    }
                }
            } else {
                $status = self::uploadOneFile($fileData, $targetFolder);
                if (strlen($status) > 0) {
                    $statusRet .= $status . $separator;
                }
            }
        }

        if (strlen($statusRet) > 0) {
            $ret = CUtil::sb2s($statusRet, $separator);
            $_SESSION["feedback"] = $ret;
        }
        return $ret;
    }

    public static function uploadOneFile($uploadedFile, $targetFolder)
    {
        $allowedExtensions = CSetting::get("allowedext");
        $maxSize = (int) CSetting::get("maxfilesize");
        $status = "";
        $fileName = basename($uploadedFile['name']);
        $fileName = str_replace(" ", "_", $fileName); // replace space with _

        if ($uploadedFile['size'] > 0) {
            $fileSize = $uploadedFile['size'] / 1024;
            $path = $targetFolder . DIRECTORY_SEPARATOR . $fileName;

            if (file_exists($path)) {
                $status = sprintf("SKIPPED: [%s] is already existed!", $fileName);
            } else if ($fileSize > $maxSize) {
                $status = sprintf("SKIPPED: [%s] is exceeding the maximum size of %d MB!", $fileName, $maxSize / 1024);
            } else if (self::isAllowedExtension($fileName, $allowedExtensions) == false) {
                $status = sprintf("SKIPPED: [%s] has bad extension!", $fileName);
            } else if (self::goodFileName($fileName) == false) {
                $status = sprintf("SKIPPED: [%s] contains illegal characters!", $fileName);
            } else if (self::isAllowedExtension($fileName, $allowedExtensions) == true) {
                $status = sprintf("GOOD: File is saved as: [%s]", $fileName);
                move_uploaded_file($uploadedFile['tmp_name'], $path);
            } else {
                $status = sprintf("Upload file [%s] failed with unexpected error!", $fileName);
            }
        }
        return $status;
    }

    public static function isAllowedExtension($fileName, $allowedExtensions)
    {
        $fArray = explode(".", $fileName);
        return CUtil::isEqInList(end($fArray), $allowedExtensions);
    }

    public static function goodFileName($onefile)
    {
        $status = false;
        $pattern = "/^([-A-Z0-9_\.]+)+$/i";
        if (preg_match($pattern, $onefile)) {
            $status = true;
        }
        return $status;
    }

    public static function UploadFileEx($uploadfile, $url, $fileFormName, $contenttype, $querystring, $cookies)
    {
        if ($fileFormName == null || strlen($fileFormName) == 0) {
            $fileFormName = "file";
        }

        if ($contenttype == null || strlen($contenttype) == 0) {
            $contenttype = "application/octet-stream";
        }

        $postdata = "?";
        if ($querystring != null) {
            foreach ($querystring as $key => $value) {
                $postdata .= $key . "=" . urlencode($value) . "&";
            }
        }

        $fullUrl = $url . $postdata;
        $boundary = "----------" . dechex(time());

        // Build post message header
        $header = "--" . $boundary . "\r\n";
        $header .= "Content-Disposition: form-data; name=\"" . $fileFormName . "\"; filename=\"" . basename($uploadfile) . "\"\r\n";
        $header .= "Content-Type: " . $contenttype . "\r\n\r\n";

        $footer = "\r\n--" . $boundary . "--\r\n";

        $fileContent = file_get_contents($uploadfile);
        $postDataRaw = $header . $fileContent . $footer;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postDataRaw);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: multipart/form-data; boundary=" . $boundary,
            "Content-Length: " . strlen($postDataRaw)
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Handle cookies if provided (simplified for translation)
        if ($cookies) {
            // Implementation for CookieContainer mapping
        }

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    public static function ftpUploadFiles()
    {
        $uploadUrl = "ftp://localhost/SD/";
        if (!isset($_FILES[0]))
            return;

        $fileToUpload = $_FILES[0];
        $uploadFileName = basename($fileToUpload['name']);
        $buffer = file_get_contents($fileToUpload['tmp_name']);

        $conn_id = ftp_connect("localhost");
        $login_result = ftp_login($conn_id, "username", "password");

        if (ftp_mkdir($conn_id, "SD")) {
            $remote_file = "SD/" . $uploadFileName;
            $fp = fopen('php://temp', 'r+');
            fwrite($fp, $buffer);
            rewind($fp);

            if (ftp_fput($conn_id, $remote_file, $fp, FTP_BINARY)) {
                // Success
            }
            fclose($fp);
        }
        ftp_close($conn_id);
    }

    public static function Basename($fullfilepath)
    {
        return basename($fullfilepath);
    }


    public static function RealFilePath(string $fname): string
    {
        return realpath($fname) ?: $fname;
    }

    public static function FIDirList($dPath = ".")
    {
        $files = [];
        if (is_dir($dPath)) {
            $dirFiles = scandir($dPath);
            foreach ($dirFiles as $file) {
                if ($file !== '.' && $file !== '..' && is_file($dPath . DIRECTORY_SEPARATOR . $file)) {
                    $files[] = new FileInfo($dPath . DIRECTORY_SEPARATOR . $file);
                }
            }
        }
        return $files;
    }

    public static function VirtualDirList($dPath = ".")
    {
        return self::getViewDirList($dPath);
    }

    public static function MakeFolder($iFolderPath)
    {
        if (!is_dir($iFolderPath)) {
            mkdir($iFolderPath, 0777, true);
            $tmp = date("Y-m-d H:i:s", filectime($iFolderPath));
            if (!CString::IsEmpty($tmp) && !CString::IsEmpty($iFolderPath)) {
                $msg = sprintf(" %s is created on %s ", self::Basename($iFolderPath), $tmp);
                CMsg::_msg($msg, "msg");
            }
        }
    }

    public static function FileListing($dPath = ".")
    {
        $files = self::FIDirList($dPath);
        $sb = "";
        foreach ($files as $f) {
            $file = $f->FullName;
            $fullfolderfspec = $file;
            $sb .= $fullfolderfspec . "\n";
        }
        return $sb;
    }

    public static function DelFiles($path)
    {
        if (is_dir($path)) {
            $files = glob($path . DIRECTORY_SEPARATOR . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    self::DelFile($file);
                }
            }
        }
    }


    public static function DelDir($fullFilePath, $recur = true)
    {
        $msg = "";
        if (is_dir($fullFilePath)) {
            if ($recur) {
                self::DelTree($fullFilePath);
            } else {
                rmdir($fullFilePath);
            }
            $msg = sprintf("%s has been deleted!", $fullFilePath);
        }
        CMsg::_msg($msg, "DelDir");
        return $msg;
    }

    public static function DelFile($fullFilePath)
    {
        $msg = "";
        if (file_exists($fullFilePath) && !is_dir($fullFilePath)) {
            unlink($fullFilePath);
            $msg = sprintf("%s has been deleted!", $fullFilePath);
        }
        CMsg::_msg($msg, "DelFile");
        return $msg;
    }

    public static function MvFile($srcFullFile, $dstFullFile)
    {
        self::DelFile($dstFullFile);
        rename($srcFullFile, $dstFullFile);
        $msg = sprintf("%s has been moved!", self::Basename($srcFullFile));
        CMsg::_msg($msg, "MvFile");
        return $msg;
    }

    public static function MvDir($srcFullFile, $dstFullFile)
    {
        CMsg::_msg($srcFullFile, "MvDir");
        self::DelDir($dstFullFile);
        rename($srcFullFile, $dstFullFile);
        $msg = sprintf("%s has been moved!", self::Basename($srcFullFile));
        CMsg::_msg($msg, "MvDir");
        return $msg;
    }

    public static function StrmWrtFile($fullFilePath)
    {
        $handle = fopen($fullFilePath, "w");
        if ($handle) {
            fwrite($handle, "Monica Rathbun\n");
            fwrite($handle, "Vidya Agarwal\n");
            fwrite($handle, "Mahesh Chand\n");
            fwrite($handle, "Vijay Anand\n");
            fwrite($handle, "Jignesh Trivedi\n");
            fclose($handle);
        }
    }

    public static function WrtFile($fullFilePath)
    {
        $folder = "C:\\Temp\\";
        $fileName = "CSharpCornerAuthors.txt";
        $fullPath = $folder . $fileName;
        $authors = ["Mahesh Chand", "Allen O'Neill", "David McCarter", "Raj Kumar", "Dhananjay Kumar"];

        // Write array of strings to a file
        file_put_contents($fullPath, implode("\n", $authors) . "\n");

        // To append text
        $text2 = "This is text to be appended";
        file_put_contents($fullPath, $text2, FILE_APPEND);

        // Read a file
        $readText = file_get_contents($fullPath);
        echo $readText . PHP_EOL;
    }

    public static function RenFile($fromFile, $toFile)
    {
        $ret = false;
        $msg = sprintf("%s is not found!", self::Basename($fromFile));
        if (!CString::IsEmpty($fromFile) && !CString::IsEmpty($toFile)) {
            if (file_exists($fromFile)) {
                rename($fromFile, $toFile);
                $msg = sprintf("%s has been renamed to %s!", self::Basename($fromFile), self::Basename($toFile));
                $ret = true;
            }
        }
        CMsg::_msg($msg, "RenFile");
        return $ret;
    }


    public static function pFiles_grob($dPath = ".")
    {
        $files = glob($dPath);
        foreach ($files as $file) {
            $file = pathinfo($file);
            echo $file['filename'];
        }
    }

    public static function getViewFilesExcl($dPath = ".", $excl = "index")
    {
        $ret = null;
        // Create a DirectoryInfo of the directory of the files to enumerate.
        $viewPath = self::getRealViewPath($dPath); // Assuming getRealViewPath is a defined function

        if (is_dir($viewPath)) {
            $dirInfo = dir($viewPath); // Use PHP's dir() function or DirectoryIterator

            // Alternative 1: Using glob and array_filter (more idiomatic PHP)
            $pattern = $viewPath . '/*';
            $allFiles = glob($pattern);

            $files = array_filter($allFiles, function ($filePath) use ($excl) {
                $fileNameWithoutExt = strtolower(pathinfo($filePath, PATHINFO_FILENAME));
                $baseName = basename($filePath);
                return $fileNameWithoutExt !== strtolower($excl) && substr($baseName, 0, 1) !== '_';
            });

            $ret = $files;

            // Alternative 2: Using DirectoryIterator (closer to the original intent)
            /*
            $iterator = new DirectoryIterator($viewPath);
            $files = [];
            foreach ($iterator as $fileinfo) {
                if ($fileinfo->isFile()) {
                    $fileNameWithoutExt = strtolower($fileinfo->getBasename('.' . $fileinfo->getExtension()));
                    if ($fileNameWithoutExt !== strtolower($excl) && $fileinfo->getBasename()[0] !== '_') {
                        $files[] = $fileinfo->getPathname(); // Or $fileinfo objects
                    }
                }
            }
            $ret = $files;
            */
        }
        return $ret;
    }
    /*
    // Dummy function for getRealViewPath if it's not defined elsewhere
    // In a real scenario, this function would handle path manipulation.
    if (!function_exists('getRealViewPath')) {
        function getRealViewPath($dPath) {
            return rtrim($dPath, '/\\');
        }
    }
        */
    /**
     * Gets an array of file path strings from a specified directory, matching a pattern.
     * This is a more PHP-native approach compared to mimicking C# FileInfo objects.
     *
     * @param string $dirPath The path to the directory. Defaults to "." (current directory).
     * @param string $pattern The pattern to match files (e.g., "*.php", "*.*"). Defaults to "*" (all files).
     * @return array<string> An array of full path strings for the matching files,
     *                       or an empty array if the directory is invalid, not readable, or no files match.
     */
    public static function getFileListByPattern(string $dirPath = ".", string $pattern = "*"): array
    {
        // Resolve the path to an absolute, canonical path.
        $realPath = realpath($dirPath);

        // Check if the path is a valid, readable directory.
        if ($realPath === false || !is_dir($realPath) || !is_readable($realPath)) {
            // error_log("Directory not found or not readable: " . $dirPath);
            return []; // Return an empty array.
        }

        // Construct the glob pattern.
        // Use DIRECTORY_SEPARATOR for cross-platform compatibility.
        $searchPattern = $realPath . DIRECTORY_SEPARATOR . $pattern;

        // Use glob() to find all files matching the pattern.
        // The GLOB_BRACE flag allows for patterns like {*.php,*.html}
        // The GLOB_NOSORT flag can sometimes be faster if order doesn't matter.
        // GLOB_MARK adds a trailing slash to directories if they match (we don't really want that here)
        $matchingFiles = glob($searchPattern);

        // glob() returns false on error, or an empty array if no matches are found.
        if ($matchingFiles === false) {
            // error_log("Error executing glob pattern: " . $searchPattern);
            return []; // Return empty array on error.
        }

        // glob() already returns full pathnames. However, it might return directories too if the pattern allows.
        // We need to filter to ensure we only return actual files.
        $filePaths = array_filter($matchingFiles, function ($path) {
            // Ensure it's a file and not a directory etc.
            return is_file($path);
        });

        // array_filter preserves keys, so re-index the array for a clean list.
        return array_values($filePaths);
    }

    /**
     * Dummy implementation of getViewFilesExcl for demonstration.
     * Replace with your actual implementation.
     * Returns an array of FileInfo objects or null.
     */
    public static function dummy_getViewFilesExcl(string $dPath = ".", string $excl = "index"): ?array
    {
        // --- Placeholder Logic ---
        // In a real scenario, this would contain logic similar to our previous
        // getViewFilesExcl function using scandir or glob.

        $viewPath = realpath($dPath);
        if ($viewPath === false || !is_dir($viewPath) || !is_readable($viewPath)) {
            return null; // Directory not valid
        }

        $filesToReturn = [];
        $dirEntries = scandir($viewPath);
        if ($dirEntries === false) {
            return []; // Error scanning directory
        }

        $lowerExcl = strtolower($excl);

        foreach ($dirEntries as $fileName) {
            if ($fileName === '.' || $fileName === '..')
                continue;
            $itemPath = $viewPath . DIRECTORY_SEPARATOR . $fileName;

            if (is_file($itemPath)) {
                $fileNameNoExt = pathinfo($fileName, PATHINFO_FILENAME);
                $firstChar = substr($fileName, 0, 1);

                if (strtolower($fileNameNoExt) !== $lowerExcl && $firstChar !== "_") {
                    // Create FileInfo objects as the C# code expects them back
                    $filesToReturn[] = new FileInfo($itemPath);
                }
            }
        }
        // --- End Placeholder Logic ---

        return $filesToReturn; // Return the array of FileInfo objects
    }
    // --- Example Usage ---
/*
// Example 1: Get all files in the current directory
$allFiles = getFileListByPattern("."); // Defaults to pattern "*"
echo "All files: \n";
print_r($allFiles);

// Example 2: Get all PHP files in a specific directory
$phpDir = __DIR__; // Current script's directory
$phpFiles = getFileListByPattern($phpDir, "*.php");
echo "\nPHP files: \n";
print_r($phpFiles);

// Example 3: Get files from a non-existent directory
$nonExistentFiles = getFileListByPattern("./non_existent_dir");
echo "\nFiles from non-existent dir: \n";
print_r($nonExistentFiles); // Should be empty array

// Example 4: Get text files
// $textFiles = getFileListByPattern("/path/to/your/files", "*.txt");
// print_r($textFiles);
*/

}


