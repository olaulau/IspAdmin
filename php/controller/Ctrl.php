<?php
namespace controller;

use model\DnsInfos;

class Ctrl
{
	
	private static $generation_start;
	
	public static function beforeroute() {
		self::$generation_start = microtime(true);
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
		
		// get infos (by running external processes)
		$cmds = [];
		$tasks = [];
		$parents = [];
		foreach ($websites as $domain => $website) {
			$server = $servers[$website["ispconfigInfos"]["server_id"]];
			$parents[$domain] = \model\DnsInfos::getParent($domain);
			if ($f3->get('active_modules.whois') === true) {
				$t = new \model\Whois ($parents[$domain], $server);
				$tasks["whois"][$parents[$domain]] = $t;
				$cmds["whois_$parents[$domain]"] = $t->getCmd();
				//TODO do not recreate things if parents domain has already been done
			}
			if ($f3->get('active_modules.dns') === true) {
				$t = new \model\DnsInfos ($domain, $server);
				$tasks["dns"][$domain] = $t;
				$cmds["dns_$domain"] = $t->getCmd();
			}
			if ($f3->get('active_modules.ssl') === true) {
				$cmds["ssl_$domain"] = \model\SslInfos::getOpensslCmd($domain);
			}
			if ($f3->get('active_modules.http') === true) {
				$cmds["http_$domain"] = \model\HttpInfos::getCmd($domain);
			}
		}
// 		vdd($cmds);
		$stats = [];
		$stats['total_cmds'] = count($cmds);
		execMultipleProcesses($cmds, true, true);
		$stats['executed_cmds'] = count($cmds);
// 		vdd($cmds);
		
		// extract infos
		foreach ($websites as $domain => &$website) {
			$server = $servers[$website["ispconfigInfos"]["server_id"]];
			
		    if ($f3->get('active_modules.whois') === true) {
		    	$website['whoisInfos'] = $tasks["whois"][$parents[$domain]]->extractInfos();
		    }
		    
			if ($f3->get('active_modules.dns') === true) {
			     $tasks["dns"][$domain]->extractInfos();
			     $website['dnsInfos'] = $tasks["dns"][$domain];
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
		
		$generation_end = microtime(true);
		$generation_time = number_format ( (($generation_end - self::$generation_start) * 1000 ), 0 , "," , " " ); // µs -> ms
		$f3->set("generation_time", $generation_time);
		
		$view = new \View();
		echo $view->render('websites.phtml');
	}
	
}
