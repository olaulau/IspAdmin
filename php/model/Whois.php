<?php

namespace model;

class Whois extends Task {
	
// 	public $ns;
	
	
	public static function getParent ($domain) {
		preg_match('/(([^.]+\.)*)([^.]+\.[^.]+)/', $domain, $matches);
		$parent_domain = $matches[3];
		return $parent_domain;
	}
	
	
	public static function getcmd ($domain) {
		$cmd = "php index.php whois $domain";
		
		$cache = \Cache::instance();
		if($cache->exists("whois_$domain") !== false) {
// 			$cmd = "# $cmd";
		}
		
		return $cmd;
	}
	
	
	public static function execCmd () {
		$f3 = \Base::instance();
		$logger = $f3->get('logger');
		
		$domain = $f3->get('PARAMS.domain');
		
		$whois = \Iodev\Whois\Whois::create(new \Iodev\Whois\Loaders\SocketLoader(10)); // 10 sec timeout
		$response = $whois->loadDomainInfo($domain);
		
		if (empty($response)) {
		    $logger->write("no response from whois");
		    return;
		}
		$cache = \Cache::instance();
		$cache->set("whois_$domain", $response, 60*60*24*2);
		$response = $cache->get("whois_$domain");
	}
	
	
	public static function extractInfos ($domain, $server) {
		$f3 = \Base::instance();
		
		$cache = \Cache::instance();
		if($cache->exists("whois_$domain") === false) {
			$res = [
				'labelType' => 'warning',
				'labelString' => 'WHOIS error',
			];
			return $res;
		}
		$rawInfos = $cache->get("whois_$domain");
		
		$res = [
			'labelType' => 'success',
			'labelString' => 'OK',
		];
		$actual_ns = $rawInfos->getNameServers();
		$good_ns = $f3->get('tech.dns.nameservers');
		if (!empty (array_diff($actual_ns , $good_ns)) || !empty (array_diff($good_ns, $actual_ns))) {
			// if domain nameservers aren't exactly those in config
			$res['labelType'] = 'warning';
			if (count($ns) === 0) {
				$res['labelString'] = "WHOIS failed";
			}
			else {
				$res['labelString'] = 'bad name servers :'.PHP_EOL . implode(', ', $ns);
			}
		}
		return $res;
	}
	
}
