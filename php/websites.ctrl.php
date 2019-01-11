<?php
require_once __DIR__ . '/autoload.inc.php';

$cache = new PhpFileCacheBis();
list($servers, $websites) = $cache->refreshIfExpired("IspGetInfos", function () {
	return IspGetInfos ();
}, 10);
unset($cache);
// $websites = [$websites[0]]; // dev test with only 1 domain


// get DNS infos
$cmds = [];
foreach ($websites as $website) {
	//TODO handle cache to limit up to 1000 whois queries / month
	$parentdomain = DnsInfos::getParent($website['domain']);
	$cmds["whois_$parentdomain"] = DnsInfos::getWhoisCmd($parentdomain);
	$cmds[] = DnsInfos::getLookupCmd($website['domain']);
}
execMultipleProcesses($cmds, true, true);
foreach ($websites as &$website) {
	$whoisRawInfos = DnsInfos::readWhoisInfos(DnsInfos::getParent($website['domain']));
	$lookupRawInfos = DnsInfos::readLookupInfos($website['domain']);
	$server = $servers[$website["server_id"]];
	$website['dnsInfos'] = new DnsInfos($website, $server, $whoisRawInfos, $lookupRawInfos);
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


// get HTTP infos
$cmds = [];
foreach ($websites as $website) {
	$cmds[] = HttpInfos::getCmd($website['domain']);
}
execMultipleProcesses($cmds, true, true);
foreach ($websites as &$website) {
	$rawInfos = HttpInfos::readInfos($website['domain']);
	$website['httpInfos'] = new HttpInfos($website, $rawInfos);
}
unset($website);


// get PHP infos
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
	if ($website['php_label_string'] < '7.0') { //TODO put into config, or fetch infos from php.net !
		$website['php_label_type'] = 'danger';
	}
	elseif ($website['php_label_string'] < '7.2') { //TODO same
		$website['php_label_type'] = 'warning';
	}
	else {
		$website['php_label_type'] = 'success';
	}
}
unset($website);

// sort table
sort2dArray ($websites, 'domain'); //TODO sort by status ?
