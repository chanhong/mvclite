<?php
namespace MvcLite;

// simple class that encapsulates mail() with addition of mime file attachment.
class Mailfile {

    public $subject;

    public $addr_to;

    public $text_body;

    public $text_encoded;

    public $mime_headers;

    public $mime_boundary = "--==================_846811060==_";

    public $smtp_headers;

    public function __construct($subj, $to, $from, $msg, $fname, $mimetype, $mimeFname = false) {

        if (empty($mimetype)) {
            $mimetype = "application/octet-stream";
        }
        $this->subject = $subj;
        // add <> to meet the new smtp standard when upgrade to PHP 7.1.5
        $this->addr_to = "<".$to.">";
        $this->smtp_headers = $this->writeSmtpHeaders("<".$from.">");
        $this->text_body = $this->writeBody($msg);
        $this->text_encoded = $this->attachFile($fname, $mimetype, $mimeFname);
        $this->mime_headers = $this->writeMimeHeaders($fname, $mimeFname);
    }

    public function attachFile($filename, $mimetype, $mime_filename) {

        $encoded = $this->encodeFile($filename);
        $path_parts = pathinfo($filename);
        $filename = $path_parts['basename']; // get file name only exclude path to show in attachment
        if ($mime_filename) {
            $filename = $mime_filename;
        }
        $out = "--" . $this->mime_boundary . "\n";
        $out = $out . "Content-type: " . $mimetype . "; name=\"$filename\";\n";
        $out = $out . "Content-Transfer-Encoding: base64\n";
        $out = $out . "Content-disposition: attachment; filename=\"$filename\"\n\n";
        $out = $out . $encoded . "\n";
        $out = $out . "--" . $this->mime_boundary . "--" . "\n";
        return $out;
// added -- to notify email client attachment is done
    }

    public function encodeFile($sourcefile) {

        if (is_readable($sourcefile)) {
            $fd = fopen($sourcefile, "r");
            $contents = fread($fd, filesize($sourcefile));
            $encoded = chunk_split(base64_encode($contents));
            fclose($fd);
        }
        return $encoded;
    }

    public function sendfile() {

        $headers = $this->smtp_headers . $this->mime_headers;
        $message = $this->text_body . $this->text_encoded;
        mail($this->addr_to, $this->subject, $message, $headers);
    }

    public function writeBody($msgtext) {

        $out = "--" . $this->mime_boundary . "\n";
        $out = $out . "Content-Type: text/plain; charset=\"us-ascii\"\n\n";
        $out = $out . $msgtext . "\n";
        return $out;
    }

    public function writeMimeHeaders($filename, $mime_filename) {

        if ($mime_filename) {
            $filename = $mime_filename;
        }
        $out = "MIME-version: 1.0\n";
        $out = $out . "Content-type: multipart/mixed; ";
        $out = $out . "boundary=\"$this->mime_boundary\"\n";
        $out = $out . "Content-transfer-encoding: 7BIT\n";
        $out = $out . "X-attachments: $filename;\n\n";
        return $out;
    }

    public function writeSmtpHeaders($addr_from) {

        $out = "From: $addr_from\n";
        $out = $out . "Reply-To: $addr_from\n";
        $out = $out . "X-Mailer: PHP3\n";
        $out = $out . "X-Sender: $addr_from\n";
        return $out;
    }
}
// end class
// Splits a string by RFC2045 semantics (76 chars per line, end with \r\n).
// This is not in all PHP versions so I define one here manuall.
/* cause fatal error: cannot redeclare function.
  function my_chunk_split($str)
  {
  $stmp = $str;
  $len = strlen($stmp);
  $out = "";
  while ($len > 0) {
  if ($len >= 76) {
  $out = $out . substr($stmp, 0, 76) . "\r\n";
  $stmp = substr($stmp, 76);
  $len = $len - 76;
  }
  else {
  $out = $out . $stmp . "\r\n";
  $stmp = ""; $len = 0;
  }
  }
  return $out;
  }
 */
// end script
