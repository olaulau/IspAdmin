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
		if (!isset ($rawInfos)) {
			$res = [
				'labelType' => 'warning',
				'labelString' => 'WHOIS error',
			];
			return $res;
		}
		
		$ns = $rawInfos->getNameServers();
		
		$res = [
			'labelType' => 'success',
			'labelString' => 'OK',
		];
		
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
