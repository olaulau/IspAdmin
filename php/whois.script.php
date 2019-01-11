<?php
require_once __DIR__ . '/autoload.inc.php';


$domain = $argv[1];

// Set authentication information
$customer_id = '370958665';
$api_key = '81VKHFB6wmtYp2nrjFt2KA';
Unirest\Request::auth($customer_id, $api_key);
$headers = array("Accept" => "application/json");
$url = "https://jsonwhoisapi.com/api/v1/whois?identifier=$domain";
$response = Unirest\Request::get($url, $headers);

$cache = new PhpFileCacheBis();
$cache->store("whois_$domain", $response, 60*60*24*2); //TODO calculate expiration
