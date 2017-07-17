MVCLite
================

Very lite MVC core class 


Installation
------------

$ ./composer.phar require chanhong/mvclite 1.0.x-dev

copy bootstrap.mvclite.php.dist and rename as bootstrap.mvclite.php

Usage
-----

// might not need this
// add these lines to your index.php
define('MVCLite', DOCROOT .'/vendor/chanhong/mvclite');  
if (file_exists(MVCLite. '/bootstrap.mvclite.php')) 
    require_once(MVCLite. '/bootstrap.mvclite.php');
// might not need this


