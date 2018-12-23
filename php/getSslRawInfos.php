<?php
require_once 'php/config.inc.php';
require_once 'php/functions.inc.php';
require_once 'php/SslInfos.class.php';

SslInfos::execOpenssl($argv[1]);
