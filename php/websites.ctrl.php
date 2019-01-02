<?php
require_once 'php/config.inc.php';
require_once 'php/functions.inc.php';
require_once 'php/SslInfos.class.php';

$session_id = IspLogin ();
$servers = IspGetServersConfig($session_id);
$websites = IspGetWebsites ($session_id);
IspLogout ($session_id);

// fork processes to query ssl infos simultaneously
$cmds = [];
foreach ($websites as $website) {
	$cmds[] = SslInfos::getOpensslCmd($website['domain']);
}
execMultipleProcesses($cmds, true);

// get ssl infos
foreach ($websites as &$website) {
	$sslRawInfos = SslInfos::readRawInfos($website['domain']);
	$website['sslInfos'] = new SslInfos($website, $sslRawInfos);
}
unset($website);


// get php infos
foreach ($websites as &$website) {
	$regex = "/^[^\d]*((\d+\.\d+)(\.\d+)?)[^\d]*:[^:]*:[^:]*:[^:]*$/";
	if (!empty ($website['fastcgi_php_version']) && preg_match($regex, $website['fastcgi_php_version'], $matches)) {
		$website['php_label_string'] = $matches[1];
	}
	else {
		$regex = "/^[^\d]*((\d+\.\d+)(\.\d+)?)[^\d]*$/";
		$website['php_label_string'] = $servers[$website['server_id']]["web"]["php_default_name"];
		if (preg_match($regex, $website['php_label_string'], $matches)) {
			$website['php_label_string'] = $matches[1];
		}
	}
	if ($website['php_label_string'] < '7.0') {
		$website['php_label_type'] = 'danger';
	}
	elseif ($website['php_label_string'] < '7.2') {
		$website['php_label_type'] = 'warning';
	}
	else {
		$website['php_label_type'] = 'success';
	}
}
unset($website);

// sort table
sort2dArray ($websites, 'domain'); //TODO sort by status ?
