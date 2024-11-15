<?php
namespace controller;

use service\IspConfig;
use service\IspcWebsite;


class CliCtrl
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
		$key = "servers_configs";
		if ($cache->exists($key, $servers_configs) === false) { //TODO count in stats
			$servers_configs = IspcWebsite::getServersConfigs ();
			$cache->set($key, $servers_configs, $f3->get("cache.ispconfig"));
		}
		$key = "websites";
		if ($cache->exists($key, $websites) === false) { //TODO count in stats
			$websites = IspcWebsite::getVhostsPlusPlus ();
			$cache->set($key, $websites, $f3->get("cache.ispconfig"));
		}
		
		if(!empty($f3->get ('debug.websites_max_number'))) {
			$websites = array_slice ($websites, 0, $f3->get ('debug.websites_max_number')); // dev test with few domains
		}
		
		if(!empty ($f3->get ('debug.websites_filter'))) {
			array_walk ( $websites , function ( $value , $key , $filter ) use ( &$websites ) {
				if (strpos ($key, $filter) === false) {
					unset ($websites [$key]); // dev test by filtering domain
				}
			} , $f3->get ('debug.websites_filter'));
		}
		// var_dump($websites); die;
		
		
		// get SSLinfos (by running external processes)
		$cmds = [];
		$tasks = [];
		foreach ($websites as $domain => $website) {
			$server = $servers_configs [$website ["ispconfigInfos"] ["server_id"]];
			if ($f3->get('active_modules.ssl') === true) {
				$t = new \model\SslInfos ($domain, $server);
				$tasks ["ssl"] [$domain] = $t;
				$cmds ["ssl_$domain"] = $t->getCmd();
			}
		}
		// vdd($cmds);
		execMultipleProcesses ($cmds, true, true);
		// vdd($cmds);
		
		
		// add ssl remaining validity days with direct access for auto sort
		foreach ($websites as $domain => &$website) {
			/* @var \model\SslInfos $sslInfos */
			$sslInfos = $tasks ["ssl"] [$domain];
			$sslInfos->extractInfos ($website ['ispconfigInfos']);
			$website ['sslRemainingValidityDays'] = $sslInfos->getRemainingValidityDays ();
		}
		
		// sort table
		sort2dArray ($websites, 'sslRemainingValidityDays');
		
		
		// vdd($websites);
		$session_id = IspConfig::IspLogin ();
		foreach ($websites as $domain => &$website) {
			if ($website ['ispconfigInfos']['ssl'] === 'y' && $website ['ispconfigInfos'] ['ssl_letsencrypt'] === 'y') {
				if ($website ['sslRemainingValidityDays'] !== null && $website ['sslRemainingValidityDays'] < 30) {
					echo $domain . " : " . $website['sslRemainingValidityDays'] . " days left " . PHP_EOL;
					// renew
					$website ['ispconfigInfos'] ['ssl_letsencrypt'] = 'n';
					IspcWebsite::IspUpdateWebsite ($session_id, $website ['ispconfigInfos']);
					$website ['ispconfigInfos'] ['ssl_letsencrypt'] = 'y';
					IspcWebsite::IspUpdateWebsite ($session_id, $website ['ispconfigInfos']);
				}
			}
		}
	}
	
}
