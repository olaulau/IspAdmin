<?php

require_once 'rest_config.php';
require_once 'functions.inc.php';


$result = restCall('login', array('username' => $remote_user, 'password' => $remote_pass, 'client_login' => false));
if($result) {
	$data = json_decode($result, true);
	if(!$data) die("ERROR!\n");
	$session_id = $data['response'];
	
	
	// get all actives web sites
	$result = restCall('sites_web_domain_get', array('session_id' => $session_id, 'primary_id' => ['active' => 'y']));
	if(!$result) die("error");
// 	vd(json_decode($result, true));	exit;
	$domain_record = json_decode($result, true)['response'];
	echo count($domain_record) . "<br>\n";
	foreach ($domain_record as $domain) {
		echo $domain['domain']."<br>\n";
		vd($domain);
	}
	
	
	/*
	$result = restCall('client_get', array('session_id' => $session_id, 'client_id' => array('username' => 'dsconnectic')));
	if($result) vd(json_decode($result, true));
	else print "Could not get client_get result\n";
	
	// or by id
	$result = restCall('client_get', array('session_id' => $session_id, 'client_id' => 2));
	if($result) vd(json_decode($result, true));
	else print "Could not get client_get result\n";
	
	// or all
	$result = restCall('client_get', array('session_id' => $session_id, 'client_id' => array()));
	if($result) vd(json_decode($result, true));
	else print "Could not get client_get result\n";
	*/
	
	// logout
	$result = restCall('logout', array('session_id' => $session_id));
	if($result) vd(json_decode($result, true));
	else print "Could not get logout result\n";
}
