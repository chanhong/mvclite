<?php

namespace MvcLite;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

/**
 * Sends mail. Two entry points:
 *
 *   CMailfile::SmtpClient($frm, $files)  text message plus optional attachments (Test Email page)
 *   (new CMailfile(...))->sendfile()     text message plus attachment(s) (legacy API, same arguments)
 *
 * Attachments: the constructor's $fname is either
 *   - a file path string (legacy: one file, named by $mimetype / $mimeFname), or
 *   - a list of ['tmp_name' => path, 'name' => shown name, 'type' => mime type]
 *     (what uploadedFiles() returns); $mimetype and $mimeFname are ignored then.
 *
 * stg_custom.php "emailmode" picks the sender for both:
 *   'mail'       PHP mail() (needs a working SMTP / sendmail setting in php.ini)
 *   'phpmailer'  real SMTP through PHPMailer (smtphost, smtpport, smtpuser, smtppass, smtpsecure)
 *
 * sendfile() returns true/false; when it returns false, $error says why.
 */
class CMailfile {

    public $subject;

    public $addr_to;

    public $text_body;

    public $text_encoded;

    public $mime_headers;

    public $mime_boundary = "--==================_846811060==_";

    public $smtp_headers;

    public $error = '';

    private $toList = [];

    private $rawFrom = '';

    private $rawMsg = '';

    // cleaned attachments, each ['path' =>, 'name' =>, 'type' =>]
    private $files = [];

    public function __construct($subj, $to, $from, $msg, $fname, $mimetype, $mimeFname = false) {

        $from = self::cleanHeader($from);
        if ($from === '') {
            $from = self::cleanHeader(CCore::getAppTxt('emailfrom'));
        }
        $this->subject = self::cleanHeader($subj);
        $this->toList = self::addrList($to);
        $this->rawFrom = $from;
        $this->rawMsg = (string) $msg;
        $this->files = self::fileList($fname, $mimetype, $mimeFname);

        if (!$this->toList) {
            $this->error = 'No recipient.';
        }
        foreach ($this->toList as $addr) {
            if (filter_var($addr, FILTER_VALIDATE_EMAIL) === false) {
                $this->error = 'Invalid recipient: ' . $addr;
                break;
            }
        }
        if ($this->error === '' && $from !== '' && filter_var($from, FILTER_VALIDATE_EMAIL) === false) {
            $this->error = 'Invalid sender: ' . $from;
        }

        // add <> to meet the new smtp standard when upgrade to PHP 7.1.5
        $this->addr_to = implode(', ', array_map(function ($a) {
            return '<' . $a . '>';
        }, $this->toList));
        $this->smtp_headers = $this->writeSmtpHeaders("<" . $from . ">");
        $this->text_body = $this->writeBody($msg);
        $this->text_encoded = $this->attachFiles();
        $this->mime_headers = $this->writeMimeHeaders('', false);
    }

    // all attachments followed by the closing boundary
    public function attachFiles(): string {

        $out = '';
        foreach ($this->files as $f) {
            $out .= $this->attachPart($f['path'], $f['type'], $f['name']);
        }
        // added -- to notify email client attachment is done
        return $out . "--" . $this->mime_boundary . "--" . "\r\n";
    }

    // legacy single-file API: one attachment followed by the closing boundary
    public function attachFile($filename, $mimetype, $mime_filename) {

        $name = $mime_filename ? self::cleanFileName($mime_filename) : '';
        return $this->attachPart($filename, $mimetype, $name) . "--" . $this->mime_boundary . "--" . "\r\n";
    }

    // one MIME part for one file ('' when there is no file or it cannot be read)
    private function attachPart($filename, $mimetype, string $name): string {

        $filename = is_scalar($filename) ? (string) $filename : '';
        if ($filename === '') {
            return '';
        }
        if (!is_file($filename) || !is_readable($filename)) {
            if ($this->error === '') {
                $this->error = 'Attachment not found or not readable: ' . basename(str_replace('\\', '/', $filename));
            }
            return '';
        }
        // file name only, exclude the path, to show in the attachment
        if ($name === '') {
            $name = self::cleanFileName(basename(str_replace('\\', '/', $filename)));
        }
        $out = "--" . $this->mime_boundary . "\r\n";
        $out = $out . "Content-Type: " . $mimetype . "; name=\"" . self::headerName($name) . "\"\r\n";
        $out = $out . "Content-Transfer-Encoding: base64\r\n";
        $out = $out . "Content-Disposition: attachment; filename=\"" . self::headerName($name) . "\"\r\n\r\n";
        $out = $out . $this->encodeFile($filename) . "\r\n";
        return $out;
    }

    public function encodeFile($sourcefile) {

        $encoded = '';
        if (is_string($sourcefile) && is_file($sourcefile) && is_readable($sourcefile)) {
            $contents = file_get_contents($sourcefile);
            if ($contents !== false) {
                $encoded = chunk_split(base64_encode($contents), 76, "\r\n");
            }
        }
        return $encoded;
    }

    public function sendfile() {

        if ($this->error !== '') {
            return false;   // a problem was found while building the message
        }
        try {
            if (self::mode() === 'phpmailer') {
                self::sendViaPhpMailer(
                    $this->toList,
                    $this->subject,
                    $this->rawMsg,
                    $this->rawFrom,
                    $this->files
                );
            } else {
                $this->sendMimeViaMail();
            }
            return true;
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
            error_log('CMailfile: ' . $this->error);
            return false;
        }
    }

    public function writeBody($msgtext) {

        $out = "--" . $this->mime_boundary . "\r\n";
        $out = $out . "Content-Type: text/plain; charset=\"UTF-8\"\r\n";
        $out = $out . "Content-Transfer-Encoding: base64\r\n\r\n";
        $out = $out . chunk_split(base64_encode((string) $msgtext), 76, "\r\n");
        return $out;
    }

    public function writeMimeHeaders($filename, $mime_filename) {

        $out = "MIME-Version: 1.0\r\n";
        $out = $out . "Content-Type: multipart/mixed; ";
        $out = $out . "boundary=\"$this->mime_boundary\"\r\n";
        return $out;
    }

    public function writeSmtpHeaders($addr_from) {

        $out = '';
        if (trim((string) $addr_from, '<> ') !== '') {
            $out = "From: $addr_from\r\n";
            $out = $out . "Reply-To: $addr_from\r\n";
        }
        $out = $out . "X-Mailer: MvcLite\r\n";
        return $out;
    }

    private function sendMimeViaMail(): void
    {
        $headers = rtrim($this->smtp_headers . $this->mime_headers, "\r\n");
        $message = $this->text_body . $this->text_encoded;

        if (!mail($this->addr_to, self::encodeSubject($this->subject), $message, $headers)) {
            throw new \RuntimeException('Unable to send email: ' . (error_get_last()['message'] ?? 'unknown'));
        }
    }

    // $files (optional): list of ['tmp_name' =>, 'name' =>, 'type' =>] from uploadedFiles()
    public static function SmtpClient(array $frm, array $files = []): void
    {
        $to = $frm['emailAddress'] ?? '';
        $subject = $frm['emailSubject'] ?? '';
        $body = $frm['emailBody'] ?? '';
        $from = (string) CCore::getAppTxt('emailfrom');

        if (!is_string($to) || filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('Invalid email recipient.');
        }
        if (!is_string($subject) || !is_string($body)) {
            throw new \InvalidArgumentException('Invalid email content.');
        }
        if (preg_match('/[\r\n]/', $subject)) {
            throw new \InvalidArgumentException('Invalid email subject.');
        }
        if ($from !== '' && filter_var($from, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('Invalid email sender.');
        }

        // with attachments, go through the legacy constructor + sendfile() (it honours emailmode itself)
        if ($files) {
            $m = new self($subject, $to, $from, $body, $files, '');
            if (!$m->sendfile()) {
                throw new \RuntimeException('Unable to send email: ' . $m->error);
            }
            return;
        }

        // the one-line switch: 'mail' (default) or 'phpmailer'
        if (self::mode() === 'phpmailer') {
            self::sendViaPhpMailer([$to], $subject, $body, $from);
        } else {
            self::sendViaMail($to, $subject, $body, $from);
        }
    }

    // $_FILES[$field] (single or multiple) => list of ['tmp_name', 'name', 'type']; throws on a failed upload
    public static function uploadedFiles(string $field): array
    {
        $out = [];
        $f = $_FILES[$field] ?? null;
        if (!$f) {
            return $out;
        }
        $names = (array) $f['name'];
        $tmps = (array) $f['tmp_name'];
        $types = (array) $f['type'];
        $errs = (array) $f['error'];
        foreach ($names as $i => $name) {
            $err = (int) ($errs[$i] ?? UPLOAD_ERR_NO_FILE);
            if ($err === UPLOAD_ERR_NO_FILE) {
                continue;   // nothing chosen
            }
            if ($err !== UPLOAD_ERR_OK) {
                throw new \RuntimeException("Upload of '" . basename((string) $name) . "' failed (PHP error code $err).");
            }
            $out[] = ['tmp_name' => $tmps[$i], 'name' => (string) $name, 'type' => (string) ($types[$i] ?? '')];
        }
        return $out;
    }

    private static function mode(): string
    {
        return strtolower(trim((string) CCore::getAppTxt('emailmode'))) ?: 'mail';
    }

    private static function sendViaMail(string $to, string $subject, string $body, string $from): void
    {
        $headers = ($from === '' ? '' : "From: {$from}\r\nReply-To: {$from}\r\n")
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64";
        $body = chunk_split(base64_encode($body), 76, "\r\n");

        if (!mail($to, self::encodeSubject($subject), $body, $headers)) {
            $err = error_get_last()['message'] ?? 'unknown';
            throw new \RuntimeException('Unable to send email: ' . $err);
        }
    }

    // $to is a list of addresses; $files (optional) is the cleaned list from fileList()
    private static function sendViaPhpMailer(array $to, string $subject, string $body, string $from,
        array $files = []): void
    {
        if (!class_exists(PHPMailer::class)) {
            throw new \RuntimeException('PHPMailer is not installed. Run: composer require phpmailer/phpmailer');
        }

        $mail = new PHPMailer(true);   // true = throw exceptions
        try {
            $mail->isSMTP();
            $mail->Host = (string) (CCore::getAppTxt('smtphost') ?: 'localhost');
            $mail->Port = (int) (CCore::getAppTxt('smtpport') ?: 25);

            $user = (string) CCore::getAppTxt('smtpuser');
            $secure = strtolower((string) CCore::getAppTxt('smtpsecure'));  // '', 'tls' or 'ssl'

            if ($user !== '') {
                $mail->SMTPAuth = true;
                $mail->Username = $user;
                $mail->Password = (string) CCore::getAppTxt('smtppass');
            } else {
                $mail->SMTPAuth = false;     // smtp4dev needs no login
            }

            if ($secure === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;      // port 465
            } elseif ($secure === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;   // port 587
            } else {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;  // don't try STARTTLS on a plain local server
            }

            $mail->CharSet = 'UTF-8';
            $mail->setFrom($from !== '' ? $from : 'noreply@example.com');
            if ($from !== '') {
                $mail->addReplyTo($from);
            }
            foreach ($to as $addr) {
                $mail->addAddress($addr);
            }
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->isHTML(false);
            foreach ($files as $f) {
                $mail->addAttachment($f['path'], $f['name'], PHPMailer::ENCODING_BASE64, $f['type']);
            }

            $mail->send();
        } catch (MailException $e) {
            throw new \RuntimeException('Unable to send email: ' . $mail->ErrorInfo, 0, $e);
        }
    }

    // ---- small helpers -------------------------------------------------

    // a file path string (legacy) or a list of ['tmp_name','name','type'] => cleaned list of ['path','name','type']
    private static function fileList($fname, $mimetype, $mimeFname): array
    {
        $items = [];
        if (is_array($fname)) {
            foreach ($fname as $f) {
                if (is_array($f) && !empty($f['tmp_name'])) {
                    $items[] = [(string) $f['tmp_name'], (string) ($f['name'] ?? ''), (string) ($f['type'] ?? '')];
                }
            }
        } elseif (is_scalar($fname) && (string) $fname !== '') {
            $items[] = [(string) $fname, (string) ($mimeFname ?: ''), (string) $mimetype];
        }
        $out = [];
        foreach ($items as [$path, $name, $type]) {
            $type = self::cleanHeader($type);
            if (!preg_match('~^[\w.+-]+/[\w.+-]+$~', $type)) {
                $type = "application/octet-stream";
            }
            $name = self::cleanFileName($name !== '' ? $name : basename(str_replace('\\', '/', $path)));
            $out[] = ['path' => $path, 'name' => $name, 'type' => $type];
        }
        return $out;
    }

    // strip line breaks so a value can never add extra mail headers
    private static function cleanHeader($s): string
    {
        return is_scalar($s) ? trim(str_replace(["\r", "\n", "\0"], '', (string) $s)) : '';
    }

    // "a@x.com, b@y.com" (or ; separated, or an array) => ['a@x.com', 'b@y.com']
    private static function addrList($to): array
    {
        $items = is_array($to) ? $to : preg_split('/[;,]/', (string) $to);
        $out = [];
        foreach ($items as $a) {
            $a = self::cleanHeader($a);
            if ($a !== '') {
                $out[] = $a;
            }
        }
        return $out;
    }

    // a file name that is safe inside a quoted header value
    private static function cleanFileName($name): string
    {
        $name = str_replace(["\r", "\n", "\0", '"', '\\', '/'], '_', (string) $name);
        return $name === '' ? 'attachment' : $name;
    }

    // plain ASCII names stay as they are; other names become an RFC 2047 encoded word (what PHPMailer does too)
    private static function headerName(string $name): string
    {
        return preg_match('/[^\x20-\x7e]/', $name) ? '=?UTF-8?B?' . base64_encode($name) . '?=' : $name;
    }

    private static function encodeSubject(string $subject): string
    {
        if (!preg_match('/[^\x20-\x7e]/', $subject)) {
            return $subject;
        }
        if (function_exists('mb_encode_mimeheader')) {
            return mb_encode_mimeheader($subject, 'UTF-8', 'B', "\r\n");
        }
        return '=?UTF-8?B?' . base64_encode($subject) . '?=';
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
