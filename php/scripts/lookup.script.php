<?php
require_once __DIR__ . '/../autoload.inc.php';


$domain = $argv[1];

$response = gethostbyname($domain);
putenv('RES_OPTIONS=retrans:1 retry:1 timeout:1 attempts:3');

$cache = new PhpFileCacheBis();
$key = "lookup_$domain";
$cache->store($key, $response, 60);
