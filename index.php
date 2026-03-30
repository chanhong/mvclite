<?php
define('DOCROOT', __DIR__ );
define('MVCLite', DOCROOT .'/vendor/chanhong/mvclite');
if (file_exists(MVCLite. '/bootstrap.mvclite.php')) require_once(MVCLite. '/bootstrap.mvclite.php'); 
echo 'hello world';
?>