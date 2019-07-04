<?php
require_once __DIR__ . '/php/autoload.inc.php';

$cache = new PhpFileCacheBis();
list($servers, $websites) = $cache->refreshIfExpired("IspGetInfos", function () {
	return IspConfig::IspGetInfos ();
}, 10);
unset($cache);
// $websites = [$websites[0]]; // dev test with only 1 domain


// get SSLinfos (by running external processes)
$cmds = [];
foreach ($websites as $website) {
	//TODO handle cache to limit up to 1000 whois queries / month
	$domain = $website['domain'];
	$cmds["ssl_$domain"] = SslInfos::getOpensslCmd($domain);
}
execMultipleProcesses($cmds, true, true);
foreach ($websites as &$website) {
	$domain = $website['domain'];
	$lookupRawInfos = DnsInfos::readLookupInfos($domain);
	$sslRawInfos = SslInfos::readRawInfos($domain);
	$website['sslInfos'] = new SslInfos($website, $sslRawInfos);
}
unset($website);


// add ssl remaining validity days with  direct access for auto sort
foreach ($websites as &$website) {
	/* @var SslInfos $sslInfos */
	$sslInfos = $website['sslInfos'];
	$website['sslRemainingValidityDays'] = $sslInfos->getRemainingValidityDays();
}

// sort table
sort2dArray ($websites, 'sslRemainingValidityDays');


// vdd($websites);
foreach ($websites as &$website) {
	if ($website['sslRemainingValidityDays'] !== null && $website['sslRemainingValidityDays'] < 30) {
		echo $website['domain'] . " : " . $website['sslRemainingValidityDays'] . " days left <br/>";
		// renew
		//////////
		
		break;
	}
}
