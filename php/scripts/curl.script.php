<?php
require_once __DIR__ . '/../autoload.inc.php';


$domain = $argv[1];

$response = shell_exec("curl -L -s -o /dev/null -X GET -w '%{http_code}' $domain");

$cache = new PhpFileCacheBis();
$cache->store("curl_$domain", $response, 10);
