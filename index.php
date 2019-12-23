<?php
require 'vendor/autoload.php';
require_once __DIR__ . '/php/functions.inc.php';

$f3 = \Base::instance();
$f3->config('conf/globals.ini');
$f3->set('logger',  new Log('log.log'));

// $f3->set('ONERROR',
// 	function($f3) {
// 		echo $f3->get('ERROR.text');
// 	}
// );

$f3->run();
