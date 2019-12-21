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
		
		if(!empty($f3->get('debug.websites_filter'))) {
			array_walk ( $websites , function ( $value , $key , $filter ) use ( &$websites ) {
				if (strpos($key, $filter) === false) {
					unset ($websites[$key]); // dev test by filtering domain
				}
			} , "toto" );
		}
// 		var_dump($websites); die;
		
		// get infos (by running external processes)
		$cmds = [];
		foreach ($websites as $domain => $website) {
			$parentDomain = \model\DnsInfos::getParent($domain);
			
			if ($f3->get('active_modules.whois') === true) {
				$cmds["whois_$parentDomain"] = \model\Whois::getcmd($parentDomain);
			}
			if ($f3->get('active_modules.dns') === true) {
				$cmds["lookup_$domain"] = \model\DnsInfos::getLookupCmd($domain);
			}
			if ($f3->get('active_modules.ssl') === true) {
				$cmds["ssl_$domain"] = \model\SslInfos::getOpensslCmd($domain);
			}
			if ($f3->get('active_modules.http') === true) {
				$cmds["http_$domain"] = \model\HttpInfos::getCmd($domain);
			}
		}
// 		vdd($cmds);
		$stats['total_cmds'] = count($cmds);
		execMultipleProcesses($cmds, true, true);
		$stats['executed_cmds'] = count($cmds);
// 		vdd($cmds);
		
		foreach ($websites as $domain => &$website) {
			$server = $servers[$website["ispconfigInfos"]["server_id"]];
			$parentDomain = \model\DnsInfos::getParent($domain);
			
		    if ($f3->get('active_modules.whois') === true) {
		        $website['whoisInfos'] = \model\Whois::extractInfos($parentDomain, $server);
		    }
		    
			if ($f3->get('active_modules.dns') === true) {
			     $dnsRawInfos = \model\DnsInfos::readLookupInfos($domain);
			     $dnsInfos = new \model\DnsInfos();
			     $dnsInfos->extractInfos($server, $dnsRawInfos);
			     $website['dnsInfos'] = $dnsInfos;
			}
			
			if ($f3->get('active_modules.ssl') === true) {
				$sslRawInfos = \model\SslInfos::readRawInfos($domain);
				$website['sslInfos'] = new \model\SslInfos($website, $sslRawInfos);
			}
			if ($f3->get('active_modules.http') === true) {
				$rawInfos = \model\HttpInfos::readInfos($domain);
				$website['httpInfos'] = new \model\HttpInfos($website, $rawInfos);
			}
		}
		unset($website);
		
		
		// get PHP infos
		if ($f3->get('active_modules.php') === true) {
			foreach ($websites as &$website) {
				$regex = "/^[^\d]*((\d+\.\d+)(\.\d+)?)[^\d]*:[^:]*:[^:]*:[^:]*$/";
				$php = $website['ispconfigInfos']['fastcgi_php_version'];
				if (!empty ($php) && preg_match($regex, $php, $matches)) {
					$website['phpInfos']['label_string'] = $matches[1]; // alternative server PHP version
				}
				else {
					$regex = "/^[^\d]*((\d+\.\d+)(\.\d+)?)[^\d]*$/";
					$php = $servers [$website['ispconfigInfos']['server_id']] ["web"] ["php_default_name"];
					if (preg_match($regex, $php, $matches)) {
						$website['phpInfos']['label_string'] = $matches[1]; // default server PHP version
					}
					else {
					    $website['phpInfos']['label_string'] = '??'; // unknown
					}
				}
				if ($website['phpInfos']['label_string'] < '7.0') { //TODO put into config, or fetch infos from php.net !
					$website['phpInfos']['label_type'] = 'danger';
				}
				elseif ($website['phpInfos']['label_string'] < '7.2') { //TODO same
					$website['phpInfos']['label_type'] = 'warning';
				}
				else {
					$website['phpInfos']['label_type'] = 'success';
				}
			}
			unset($website);
		}
// 		var_dump($websites); die;
		
		// sort table
// 		sort2dArray ($websites, 'domain'); //TODO sort by status ?
		ksort($websites);
		
		$f3->set('websites', $websites);
		$f3->set('stats', $stats);
		
		$view = new \View();
		echo $view->render('websites.phtml');
	}
	
}
