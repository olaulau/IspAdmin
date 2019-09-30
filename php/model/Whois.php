<?php

namespace model;

class Whois extends Task {
	
	public $ns;
	
	
	public static function getParent ($domain) {
		preg_match('/(([^.]+\.)*)([^.]+\.[^.]+)/', $domain, $matches);
		$parent_domain = $matches[3];
		return $parent_domain;
	}
	
	
	public static function getcmd ($domain) {
		$cmd = "php index.php whois $domain";
		
		$cache = \Cache::instance();
		if($cache->exists("whois_$domain") !== false) {
			$cmd = "# $cmd";
		}
		
		return $cmd;
	}
	
	
	public static function execCmd () {
		$f3 = \Base::instance();
		$domain = $f3->get('PARAMS.domain');
		
		$whois = \Iodev\Whois\Whois::create(new \Iodev\Whois\Loaders\SocketLoader(10)); // 10 sec timeout
		$response = $whois->loadDomainInfo($domain);
		
		if (!empty($response)) {
			$cache = \Cache::instance();
			$cache->set("whois_$domain", $response, 60*60*24*2);
		}
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
		
		$res = [
			'labelType' => 'success',
			'labelString' => 'OK',
		];

		$rawInfos = $cache->get("whois_$domain");
		$ns = $rawInfos->getNameServers();
		if (!empty (array_diff($ns , $f3->get('tech.dns.nameservers'))) || !empty (array_diff($f3->get('tech.dns.nameservers'), $ns))) {
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
