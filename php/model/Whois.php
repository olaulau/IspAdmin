<?php

namespace model;

class Whois extends Task {
	
	public function getCmd () {
		$cmd = "php index.php whois $this->domain";
		
		$cache = \Cache::instance();
		if($cache->exists("whois_$this->domain") !== false) {
			$cmd = "# $cmd";
		}
		
		return $cmd;
	}
	
	
	public function execCmd () {
		$f3 = \Base::instance();
		$logger = $f3->get('logger');
		
		$whois = \Iodev\Whois\Whois::create(new \Iodev\Whois\Loaders\SocketLoader(10)); // 10 sec timeout
		$response = $whois->loadDomainInfo($this->domain);
		
		if (empty($response)) {
		    $logger->write("no response from whois");
		    return;
		}
		$cache = \Cache::instance();
		$cache->set("whois_$this->domain", $response, $f3->get("cache.whois"));
		$response = $cache->get("whois_$this->domain");
	}
	
	
	public function extractInfos () {
		$f3 = \Base::instance();
		
		$cache = \Cache::instance();
		if($cache->exists("whois_$this->domain") === false) {
			$res = [
				'labelType' => 'warning',
				'labelString' => 'WHOIS error',
			];
			return $res;
		}
		$rawInfos = $cache->get("whois_$this->domain");
		
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
	
	
	public static function getParent ($domain) {
		preg_match('/(([^.]+\.)*)([^.]+\.[^.]+)/', $domain, $matches);
		$parent_domain = $matches[3];
		return $parent_domain;
	}
	
}
