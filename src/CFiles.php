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



class CFiles
{

    public static function delTree(string $dir): void
    {
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





        public static function getViewDirList($dPath = ".") {
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

        public static function GetFilesByExtensions($directoryPath, ...$extensions) {
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

        public static function getFilesBefYear($dPath = ".", $year = 2009) {
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

        public static function getFilesByPattern($dPath = ".", $pattern = "") {
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

        public static function getRealViewPath($dPath = ".") {
            $vPath = CUtil::getAppTxt("viewpath");
            $vPath = CUtil::v2BasePath($vPath);
            $vfilePath = $vPath . "/" . $dPath;
            return self::RealFilePath($vfilePath);
        }

        public static function getViewFilesExcl($dPath = ".", $excl = "index") {
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

        public static function getViewDir($dPath = ".") {
            $files = self::getViewFilesExcl($dPath, "index");
            return $files;
        }

        public static function getViewDirByExt($dPath = ".") {
            $dirPath = self::getRealViewPath($dPath);
            $dirList = self::GetFilesByExtensions($dirPath, ".cshtml");
            return $dirList;
        }

        public static function createPDF($targetFolder, $baseUrl) {
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
                    $webPageWidth = (int)$frm["TxtWidth"];
                }
            } catch (Exception $e) {}

            try {
                if (isset($frm["TxtHeight"])) {
                    $webPageHeight = (int)$frm["TxtHeight"];
                }
            } catch (Exception $e) {}

            try {
                if (isset($frm["TxtHtmlCode"])) {
                    $htmlString = $frm["TxtHtmlCode"];
                }
            } catch (Exception $e) {}

            // create a new pdf document converting an url
            $doc = $converter->ConvertHtmlString($htmlString, $baseUrl);

            // save pdf document
            $doc->Save($targetFolder . "Document2.pdf");

            // close pdf document
            $doc->Close();
        }

        public static function uploadFiles($targetFolder) {
            $separator = ",";
            $ret = "";
            $statusRet = "";
            
            if (!isset($_SESSION)) session_start();

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

        public static function uploadOneFile($uploadedFile, $targetFolder) {
            $allowedExtensions = CUtil::getAppTxt("allowedext");
            $maxSize = (int)CUtil::getAppTxt("maxfilesize");
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

        public static function isAllowedExtension($fileName, $allowedExtensions) {
            $fArray = CUtil::explode(".", $fileName);
            return CUtil::isEqInList(end($fArray), $allowedExtensions);
        }

        public static function goodFileName($onefile) {
            $status = false;
            $pattern = "/^([-A-Z0-9_\.]+)+$/i";
            if (preg_match($pattern, $onefile)) {
                $status = true;
            }
            return $status;
        }

        public static function UploadFileEx($uploadfile, $url, $fileFormName, $contenttype, $querystring, $cookies) {
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

        public static function ftpUploadFiles() {
            $uploadUrl = "ftp://localhost/SD/";
            if (!isset($_FILES[0])) return;
            
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

        public static function Basename($fullfilepath) {
            return basename($fullfilepath);
        }

        public static function RealFilePath($fname) {
            return self::$_ctx_c->Server->MapPath($fname);
        }

        public static function FIDirList($dPath = ".") {
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

        public static function VirtualDirList($dPath = ".") {
            return self::getViewDirList($dPath);
        }

        public static function MakeFolder($iFolderPath) {
            if (!is_dir($iFolderPath)) {
                mkdir($iFolderPath, 0777, true);
                $tmp = date("Y-m-d H:i:s", filectime($iFolderPath));
                if (!CString::IsEmpty($tmp) && !CString::IsEmpty($iFolderPath)) {
                    $msg = sprintf(" %s is created on %s ", self::Basename($iFolderPath), $tmp);
                    CMsg::_pdmsg($msg, "msg");
                }
            }
        }

        public static function FileListing($dPath = ".") {
            $files = self::FIDirList($dPath);
            $sb = "";
            foreach ($files as $f) {
                $file = $f->FullName;
                $fullfolderfspec = $file;
                $sb .= $fullfolderfspec . "\n";
            }
            return $sb;
        }

        public static function DelFiles($path) {
            if (is_dir($path)) {
                $files = glob($path . DIRECTORY_SEPARATOR . '*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        self::DelFile($file);
                    }
                }
            }
        }


        public static function DelDir($fullFilePath, $recur = true) {
            $msg = "";
            if (is_dir($fullFilePath)) {
                if ($recur) {
                    self::DelTree($fullFilePath);
                } else {
                    rmdir($fullFilePath);
                }
                $msg = sprintf("%s has been deleted!", $fullFilePath);
            }
            CMsg::_pdmsg($msg, "DelDir");
            return $msg;
        }

        public static function DelFile($fullFilePath) {
            $msg = "";
            if (file_exists($fullFilePath) && !is_dir($fullFilePath)) {
                unlink($fullFilePath);
                $msg = sprintf("%s has been deleted!", $fullFilePath);
            }
            CMsg::_pdmsg($msg, "DelFile");
            return $msg;
        }

        public static function MvFile($srcFullFile, $dstFullFile) {
            self::DelFile($dstFullFile);
            rename($srcFullFile, $dstFullFile);
            $msg = sprintf("%s has been moved!", self::Basename($srcFullFile));
            CMsg::_pdmsg($msg, "MvFile");
            return $msg;
        }

        public static function MvDir($srcFullFile, $dstFullFile) {
            CMsg::_pdmsg($srcFullFile, "MvDir");
            self::DelDir($dstFullFile);
            rename($srcFullFile, $dstFullFile);
            $msg = sprintf("%s has been moved!", self::Basename($srcFullFile));
            CMsg::_pdmsg($msg, "MvDir");
            return $msg;
        }

        public static function StrmWrtFile($fullFilePath) {
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

        public static function WrtFile($fullFilePath) {
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

        public static function RenFile($fromFile, $toFile) {
            $ret = false;
            $msg = sprintf("%s is not found!", self::Basename($fromFile));
            if (!CString::IsEmpty($fromFile) && !CString::IsEmpty($toFile)) {
                if (file_exists($fromFile)) {
                    rename($fromFile, $toFile);
                    $msg = sprintf("%s has been renamed to %s!", self::Basename($fromFile), self::Basename($toFile));
                    $ret = true;
                }
            }
            CMsg::_pdmsg($msg, "RenFile");
            return $ret;
        }
    }


