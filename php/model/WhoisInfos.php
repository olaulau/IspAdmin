<?php

namespace model;

class WhoisInfos extends Task {
	
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
	}
	
	
	public function extractInfos () {
		$f3 = \Base::instance();
		
		$cache = \Cache::instance();
		if($cache->exists("whois_$this->domain") === false) {
			$this->labelType = 'warning';
			$this->labelString = 'WHOIS error';
		}
		$rawInfos = $cache->get("whois_$this->domain");
		
		$this->labelType = 'success';
		$this->labelString = 'OK';
		
		$actual_ns = $rawInfos->getNameServers();
		$good_ns = $f3->get('tech.dns.nameservers');
		if (!empty (array_diff($actual_ns , $good_ns)) || !empty (array_diff($good_ns, $actual_ns))) {
			// if domain nameservers aren't exactly those in config
			$this->labelType = 'warning';
			if (count($ns) === 0) {
				$this->labelString = "WHOIS failed";
			}
			else {
				$this->labelString = 'bad name servers :'.PHP_EOL . implode(', ', $ns);
			}
		}
	}
	
}
