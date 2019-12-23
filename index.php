<?php
require 'vendor/autoload.php';
require_once __DIR__ . '/php/functions.inc.php';

$f3 = \Base::instance();
$f3->config('conf/globals.ini');
$f3->set('logger',  new Log('log.log'));

$f3->run();
