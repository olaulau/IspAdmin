<?php
require 'vendor/autoload.php';

$f3 = \Base::instance();

$f3->config('conf/globals.ini');
require_once __DIR__ . '/php/autoload.inc.php';

// $cache = \Cache::instance();

$f3->run();
