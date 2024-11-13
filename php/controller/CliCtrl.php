<?php
namespace controller;


class Cli
{
	
	public static function beforeroute(\Base $f3, array $url, string $controler)
	{
		
	}
	
	
	public static function afterroute(\Base $f3, array $url, string $controler)
	{
		
	}
	
	
	//TODO really usefull ?
	public static function ssl_auto_renew (\Base $f3, array $url, string $controler)
	{
		$f3 = \Base::instance();
		
		// get servers and websites list
		$cache = \Cache::instance();
		$key = "ispconfig";
		if ($cache->exists($key, $ispconfigRawinfos) === false) {
			$ispconfigRawinfos = \IspConfig::IspGetInfos ();
			$cache->set($key, $ispconfigRawinfos, $f3->get("cache.ispconfig"));
		}
		list($servers, $websites) = $ispconfigRawinfos;
		
		if(!empty($f3->get('debug.websites_max_number'))) {
			$websites = array_slice($websites, 0, $f3->get('debug.websites_max_number')); // dev test with few domains
		}
		
		if(!empty($f3->get('debug.websites_filter'))) {
			array_walk ( $websites , function ( $value , $key , $filter ) use ( &$websites ) {
				if (strpos($key, $filter) === false) {
					unset ($websites[$key]); // dev test by filtering domain
				}
			} , $f3->get('debug.websites_filter') );
		}
		// var_dump($websites); die;
		
		
		// get SSLinfos (by running external processes)
		$cmds = [];
		$tasks = [];
		foreach ($websites as $domain => $website) {
			$server = $servers[$website["ispconfigInfos"]["server_id"]];
			if ($f3->get('active_modules.ssl') === true) {
				$t = new \model\SslInfos ($domain, $server);
				$tasks["ssl"][$domain] = $t;
				$cmds["ssl_$domain"] = $t->getCmd();
			}
		}
		// vdd($cmds);
		execMultipleProcesses($cmds, true, true);
		// vdd($cmds);
		
		
		// add ssl remaining validity days with direct access for auto sort
		foreach ($websites as $domain => &$website) {
			/* @var \model\SslInfos $sslInfos */
			$sslInfos = $tasks["ssl"][$domain];
			$sslInfos->extractInfos($website['ispconfigInfos']);
			$website['sslRemainingValidityDays'] = $sslInfos->getRemainingValidityDays();
		}
		
		// sort table
		sort2dArray ($websites, 'sslRemainingValidityDays');
		
		
		// vdd($websites);
		$session_id = \IspConfig::IspLogin ();
		foreach ($websites as $domain => &$website) {
			if ($website['ispconfigInfos']['ssl'] === 'y' && $website['ispconfigInfos']['ssl_letsencrypt'] === 'y') {
				if ($website['sslRemainingValidityDays'] !== null && $website['sslRemainingValidityDays'] < 30) {
					echo $domain . " : " . $website['sslRemainingValidityDays'] . " days left " . PHP_EOL;
					// renew
					$website['ispconfigInfos']['ssl_letsencrypt'] = 'n';
					\IspConfig::IspUpdateWebsite($session_id, $website['ispconfigInfos']);
					$website['ispconfigInfos']['ssl_letsencrypt'] = 'y';
					\IspConfig::IspUpdateWebsite($session_id, $website['ispconfigInfos']);
				}
			}
		}
	}
	
}
