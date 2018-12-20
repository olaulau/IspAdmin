<?php

require_once 'soap_config.php';
require_once 'functions.inc.php';


$client = new SoapClient(null, array('location' => $soap_location,
		'uri'      => $soap_uri,
		'trace' => 1,
		'exceptions' => 1));

try {
	if($session_id = $client->login($username, $password)) {
		echo 'Logged successfull. Session ID:'.$session_id."<br/>\n";
	}

	$domain_record = $client->sites_web_domain_get($session_id, ['active' => 'y']);
	echo count($domain_record) . "<br>\n";
	foreach ($domain_record as $domain) {
		echo $domain['domain']."<br>\n";
		vd($domain);
	}

	if($client->logout($session_id)) {
		echo 'Logged out.<br/>\n';
	}
} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}
