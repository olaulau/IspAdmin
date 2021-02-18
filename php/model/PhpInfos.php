<?php

namespace model;

class PhpInfos extends Task {
	
	public function getCmd () {
		return "#"; // no background task
	}
	
	
	public function execCmd () {
		// never executer in background
	}
	
	
	public function extractInfos ($ispconfigInfos) {
		$key = "whois_$this->domain";
		$f3 = \Base::instance();
		
		$cache = \Cache::instance();
		if($cache->exists($key) === false) {
			$this->labelType = 'warning';
			$this->labelString = 'WHOIS error';
			return;
		}
		$rawInfos = $cache->get($key);
		
		$this->labelType = 'success';
		$this->labelString = 'OK';
		
		$actual_ns = $rawInfos->getNameServers();
		$good_ns = $f3->get('tech.dns.nameservers');
		if (!empty (array_diff($actual_ns , $good_ns)) || !empty (array_diff($good_ns, $actual_ns))) {
			// if domain nameservers aren't exactly those in config
			$this->labelType = 'warning';
			if (count($actual_ns) === 0) {
				$this->labelString = "WHOIS failed";
			}
			else {
				$this->labelString = 'bad name servers :'.PHP_EOL . implode(', ', $actual_ns);
			}
		}
	}
	
}
