<?php
namespace controller;

use model\DnsInfos;

class Ctrl
{
	
	public static function beforeroute() {
		
	}
	
	
	public static function afterroute() {
		
	}
	
	
	public static function GET_index ()
	{
		$view = new \View();
		echo $view->render('index.phtml');
	}
	
	
	public static function GET_websites ()
	{
		$f3 = \Base::instance();
		
		$stats = [];
		
		// get servers and websites list
		$cache = new \PhpFileCacheBis();
		list($servers, $websites) = $cache->refreshIfExpired("IspGetInfos", function () {
			return \IspConfig::IspGetInfos ();
		}, 10);
		unset($cache);
		if(!empty($f3->get('debug.websites_max_number'))) {
			$websites = array_slice($websites, 0, $f3->get('debug.websites_max_number')); // dev test with few domains
		}
		
		// get infos (by running external processes)
		$cmds = [];
		foreach ($websites as $website) {
			$domain = $website['domain'];
			$parentdomain = \model\DnsInfos::getParent($domain);
			
			if ($f3->get('debug.active_modules.whois') === true) {
				$cmds["whois_$parentdomain"] = \model\DnsInfos::getWhoisCmd($parentdomain);
			}
			if ($f3->get('debug.active_modules.lookup') === true) {
				$cmds["lookup_$domain"] = \model\DnsInfos::getLookupCmd($domain);
			}
			if ($f3->get('debug.active_modules.whois') === true) {
				$cmds["ssl_$domain"] = \model\SslInfos::getOpensslCmd($domain);
			}
			if ($f3->get('debug.active_modules.http') === true) {
				$cmds["http_$domain"] = \model\HttpInfos::getCmd($domain);
			}
		}
// 		vdd($cmds);
		$stats['total_cmds'] = count($cmds);
		execMultipleProcesses($cmds, true, true);
		$stats['executed_cmds'] = count($cmds);
// 		vdd($cmds);
		
		foreach ($websites as &$website) {
			$server = $servers[$website["server_id"]];
			$domain = $website['domain'];
			$parentdomain = \model\DnsInfos::getParent($domain);
			
			$whoisRawInfos = \model\DnsInfos::readWhoisInfos($parentdomain);
			$lookupRawInfos = \model\DnsInfos::readLookupInfos($domain);
			$website['dnsInfos'] = new \model\DnsInfos($website, $server, $whoisRawInfos, $lookupRawInfos);
			$sslRawInfos = \model\SslInfos::readRawInfos($domain);
			$website['sslInfos'] = new \model\SslInfos($website, $sslRawInfos);
			$rawInfos = \model\HttpInfos::readInfos($domain);
			$website['httpInfos'] = new \model\HttpInfos($website, $rawInfos);
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
		
		$f3->set('websites', $websites);
		$f3->set('stats', $stats);
		
		$view = new \View();
		echo $view->render('websites.phtml');
	}
	
}
