<?php
namespace controller;

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

		$cache = new \PhpFileCacheBis();
		list($servers, $websites) = $cache->refreshIfExpired("IspGetInfos", function () {
			return \IspConfig::IspGetInfos ();
		}, 10);
		unset($cache);
		// $websites = [$websites[0]]; // dev test with only 1 domain
		
		// get infos (by running external processes)
		$cmds = [];
		foreach ($websites as $website) {
			//TODO handle cache to limit up to 1000 whois queries / month
			$domain = $website['domain'];
			$parentdomain = \model\DnsInfos::getParent($domain);
			$cmds["whois_$parentdomain"] = \model\DnsInfos::getWhoisCmd($parentdomain);
			$cmds["lookup_$domain"] = \model\DnsInfos::getLookupCmd($domain);
			$cmds["ssl_$domain"] = \model\SslInfos::getOpensslCmd($domain);
			$cmds["http_$domain"] = \model\HttpInfos::getCmd($domain);
		}
		// vdd($cmds);
		execMultipleProcesses($cmds, true, true);
		
		foreach ($websites as &$website) {
			$domain = $website['domain'];
			$parentdomain = \model\DnsInfos::getParent($domain);
			$whoisRawInfos = \model\DnsInfos::readWhoisInfos($parentdomain);
			$lookupRawInfos = \model\DnsInfos::readLookupInfos($domain);
			$server = $servers[$website["server_id"]];
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
		
		$view = new \View();
		echo $view->render('websites.phtml');
	}
	
}
