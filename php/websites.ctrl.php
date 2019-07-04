<?php
require_once __DIR__ . '/autoload.inc.php';

$cache = new PhpFileCacheBis();
list($servers, $websites) = $cache->refreshIfExpired("IspGetInfos", function () {
	return IspGetInfos ();
}, 10);
unset($cache);
// $websites = [$websites[0]]; // dev test with only 1 domain


// get infos (by running external processes)
$cmds = [];
foreach ($websites as $website) {
	//TODO handle cache to limit up to 1000 whois queries / month
	$domain = $website['domain'];
	$parentdomain = DnsInfos::getParent($domain);
	$cmds["whois_$parentdomain"] = DnsInfos::getWhoisCmd($parentdomain); //TODO pb d'indice de tableaux ??
	$cmds["lookup_$domain"] = DnsInfos::getLookupCmd($domain);
	$cmds["ssl_$domain"] = SslInfos::getOpensslCmd($domain);
	$cmds["http_$domain"] = HttpInfos::getCmd($domain);
}
execMultipleProcesses($cmds, true, true);
foreach ($websites as &$website) {
	$domain = $website['domain'];
	$parentdomain = DnsInfos::getParent($domain);
	$whoisRawInfos = DnsInfos::readWhoisInfos($parentdomain);
	$lookupRawInfos = DnsInfos::readLookupInfos($domain);
	$server = $servers[$website["server_id"]];
	$website['dnsInfos'] = new DnsInfos($website, $server, $whoisRawInfos, $lookupRawInfos);
	$sslRawInfos = SslInfos::readRawInfos($domain);
	$website['sslInfos'] = new SslInfos($website, $sslRawInfos);
	$rawInfos = HttpInfos::readInfos($domain);
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
