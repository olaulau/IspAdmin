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
		$f3 = \Base::instance();
		
		$PAGE = [
				"name" => "index",
				"title" => "Isp Admin",
		];
		$f3->set("PAGE", $PAGE);
		
		$view = new \View();
		echo $view->render('index.phtml');
	}
	
	
	public static function GET_websites ()
	{
		$f3 = \Base::instance();
		
		// get servers and websites list
		$cache = \Cache::instance();
		$key = "ispconfig";
		if ($cache->exists($key, $ispconfigRawinfos) === false) { //TODO count in stats
			$ispconfigRawinfos = \IspConfig::IspGetInfos ();;
			$cache->set($key, $ispconfigRawinfos, $f3->get("cache.ispconfig"));
		}
		list($servers, $websites) = $ispconfigRawinfos;

		if(!empty($f3->get('debug.websites_filter'))) {
			array_walk ( $websites , function ( $value , $key , $filter ) use ( &$websites ) {
				if (strpos($key, $filter) === false) {
					unset ($websites[$key]); // dev test by filtering domain
				}
			} , $f3->get('debug.websites_filter') );
		}
		
		if(!empty($f3->get('debug.websites_max_number'))) {
			$websites = array_slice($websites, 0, $f3->get('debug.websites_max_number')); // dev test with few domains
		}
// 		var_dump($websites); die;
		
		// group domains by 2LD
		$stats = [];
		$stats["websites_count"] = count($websites);
		$websites = group2dArray($websites, "2LD");
		$stats["2LD_count"] = count($websites);
// 		var_dump($websites); die;
		
		// get infos (by running external processes)
		$cmds = [];
		$tasks = [];
		foreach ($websites as $parent => $group) {
			foreach ($group as $domain => $website) {
				if ($website["ispconfigInfos"]['active']==='y') {
					$server = $servers[$website["ispconfigInfos"]["server_id"]];
					if ($f3->get('active_modules.whois') === true) {
						$t = new \model\WhoisInfos ($parent, $server);
						$tasks["whois"][$parent] = $t;
						$cmds["whois_$parent"] = $t->getCmd();
						//TODO do not recreate things if parents domain has already been done
					}
					if ($f3->get('active_modules.dns') === true) {
						$t = new \model\DnsInfos ($domain, $server);
						$tasks["dns"][$domain] = $t;
						$cmds["dns_$domain"] = $t->getCmd();
					}
					if ($f3->get('active_modules.ssl') === true) {
						$t = new \model\SslInfos ($domain, $server);
						$tasks["ssl"][$domain] = $t;
						$cmds["ssl_$domain"] = $t->getCmd();
					}
					if ($f3->get('active_modules.http') === true) {
						$t = new \model\HttpInfos ($domain, $server);
						$tasks["http"][$domain] = $t;
						$cmds["http_$domain"] = $t->getCmd();
					}
				}
			}
		}
// 		vdd($cmds);
		
		$stats['total_cmds'] = count($cmds);
		execMultipleProcesses($cmds, true, true);
		$stats['total_executed_cmds'] = count($cmds);
		$stats['executed_cmds'] = $cmds;
// 		vdd($cmds);
		
		// extract infos
		foreach ($websites as $parent => &$group) {
			foreach ($group as $domain => &$website) {
				if ($website["ispconfigInfos"]['active']==='y') {
					$server = $servers[$website["ispconfigInfos"]["server_id"]];
					
				    if ($f3->get('active_modules.whois') === true) {
				    	$tasks["whois"][$parent]->extractInfos($website['ispconfigInfos']);
				    	$website['whoisInfos'] = $tasks["whois"][$parent];
				    }
				    
					if ($f3->get('active_modules.dns') === true) {
						$tasks["dns"][$domain]->extractInfos($website['ispconfigInfos']);
					     $website['dnsInfos'] = $tasks["dns"][$domain];
					}
					
					if ($f3->get('active_modules.ssl') === true) {
						$tasks["ssl"][$domain]->extractInfos($website['ispconfigInfos']);
						$website['sslInfos'] = $tasks["ssl"][$domain];
					}
					if ($f3->get('active_modules.http') === true) {
						$tasks["http"][$domain]->extractInfos($website['ispconfigInfos']);
						$website['httpInfos'] = $tasks["http"][$domain];
					}
				}
				unset($website);
			}
			unset($group);
		}
		
		
		// get PHP infos
		//TODO put in a separate class
		$min_version_security_support = $f3->get('php.min_version_security_support');
		$min_version_active_support = $f3->get('php.min_version_active_support');
		if ($f3->get('active_modules.php') === true) {
			foreach ($websites as $parent => &$group) {
				foreach ($group as $domain => &$website) {
					$php = $website['ispconfigInfos']['php'];
					if ($php === "no") {
					    $website['phpInfos']['label_string'] = "disabled";
					    $website['phpInfos']['label_type'] = "warning";
					}
					elseif ($php === "fast-cgi") {
					    $website['phpInfos']['label_string'] = "fast cgi should not work";
					    $website['phpInfos']['label_type'] = "warning";
					}
					elseif ($php === "mod") {
					    $website['phpInfos']['label_string'] = "apache mod isn't recomended";
					    $website['phpInfos']['label_type'] = "warning";
					}
					elseif ($php === "php-fpm") {
    					$regex = "/^[^\d]*((\d+\.\d+)(\.\d+)?)[^\d]*:[^:]*:[^:]*:[^:]*$/";
    					$fastcgi_php_version = $website['ispconfigInfos']['fastcgi_php_version'];
    					if (!empty ($fastcgi_php_version) && preg_match($regex, $fastcgi_php_version, $matches)) {
    						$website['phpInfos']['label_string'] = $matches[1]; // alternative server PHP version
    					}
    					else {
    						$regex = "/^[^\d]*((\d+\.\d+)(\.\d+)?)[^\d]*$/";
    						$php_default_name = $servers [$website['ispconfigInfos']['server_id']] ["web"] ["php_default_name"];
    						if (preg_match($regex, $php_default_name, $matches)) {
    							$website['phpInfos']['label_string'] = $matches[1]; // default server PHP version
    						}
    						else {
    						    $website['phpInfos']['label_string'] = '??'; // unknown
    						}
    					}
    					if ($website['phpInfos']['label_string'] < $min_version_security_support) { //TODO fetch infos from php.net !
    						$website['phpInfos']['label_type'] = 'danger';
    					}
    					elseif ($website['phpInfos']['label_string'] < $min_version_active_support) { //TODO same
    						$website['phpInfos']['label_type'] = 'warning';
    					}
    					else {
    						$website['phpInfos']['label_type'] = 'success';
    					}
					}
					else {
					    //unforeseen
					    $website['phpInfos']['label_string'] = "error";
					    $website['phpInfos']['label_type'] = "danger";
					}
					unset($website);
				}
				unset($group);
			}
		}
// 		var_dump($websites); die;
		
		$f3->set('websites', $websites);
		$f3->set('stats', $stats);
		
		$generation_end = microtime(true);
		$generation_time = number_format ( (($generation_end - self::$generation_start) * 1000 ), 0 , "," , " " ); // µs -> ms
		$f3->set("generation_time", $generation_time);
		
		$PAGE = [
			"name" => "websites",
			"title" => "Web Sites",
		];
		$f3->set("PAGE", $PAGE);
		
		$view = new \View();
		echo $view->render('websites.phtml');
	}
	
	
	public static function GET_emails ()
	{
		$f3 = \Base::instance();
		
		$PAGE = [
				"name" => "emails",
				"title" => "E-mails",
		];
		$f3->set("PAGE", $PAGE);
		
		$view = new \View();
		echo $view->render('emails.phtml');
	}
	
}
