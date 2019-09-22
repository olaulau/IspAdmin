<?php
require_once __DIR__ . '/../autoload.inc.php';


$domain = $argv[1];

Unirest\Request::auth($conf['jsonwhoisapi']['customer_id'], $conf['jsonwhoisapi']['api_key']);
$headers = array("Accept" => "application/json");
$url = "https://jsonwhoisapi.com/api/v1/whois?identifier=$domain";
$response = Unirest\Request::get($url, $headers);

$cache = new PhpFileCacheBis();
$cache->store("whois_$domain", $response, 60*60*24*2); //TODO calculate expiration
