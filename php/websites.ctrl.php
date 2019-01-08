<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../php/config.inc.php';
require_once __DIR__ . '/../php/functions.inc.php';
require_once __DIR__ . '/../php/DnsInfos.class.php';
require_once __DIR__ . '/../php/SslInfos.class.php';

use Wruczek\PhpFileCache\PhpFileCache;


$cache = new PhpFileCache();
list($servers, $websites) = $cache->refreshIfExpired("IspGetInfos", function () {
	return IspGetInfos ();
}, 10);
// dev test with only 1 domain
// $websites = [$websites[0]];


// get DNS infos
$cmds = [];
foreach ($websites as $website) {
	//TODO handle cache to limit up to 1000 queries / month
	$cmds[] = DnsInfos::getWhoisCmd(DnsInfos::getParent($website['domain']));
}
execMultipleProcesses($cmds, true, true);
foreach ($websites as &$website) {
	$whoisRawInfos = DnsInfos::readWhoisInfos(DnsInfos::getParent($website['domain']));
	$website['dnsInfos'] = new DnsInfos($website, $whoisRawInfos);
}
unset($website);


// get SSL infos
$cmds = [];
foreach ($websites as $website) {
	$cmds[] = SslInfos::getOpensslCmd($website['domain']);
}
execMultipleProcesses($cmds, true, true);
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
