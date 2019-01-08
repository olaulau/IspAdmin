<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../php/config.inc.php';
require_once __DIR__ . '/../php/functions.inc.php';
require_once __DIR__ . '/../php/DnsInfos.class.php';
require_once __DIR__ . '/../php/SslInfos.class.php';

use Wruczek\PhpFileCache\PhpFileCache;


$domain = $argv[1];

// Set authentication information
$customer_id = '370958665';
$api_key = '81VKHFB6wmtYp2nrjFt2KA';
Unirest\Request::auth($customer_id, $api_key);
$headers = array("Accept" => "application/json");
$url = "https://jsonwhoisapi.com/api/v1/whois?identifier=$domain";
$response = Unirest\Request::get($url, $headers);

$cache = new PhpFileCache();
$cache->store("whois_$domain", $response, 60*60*24*2); //TODO calculate expiration
