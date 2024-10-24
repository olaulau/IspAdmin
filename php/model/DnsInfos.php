<?php
namespace model;


class DnsInfos extends Task {
	
	public  function getCmd () {
	    $f3 = \Base::instance();
	    
	    $php_binary = $f3->get("tech.PHP_BINARY");
		$cmd = "$php_binary index.php dns $this->domain";
		
		$cache = \Cache::instance();
		if($cache->exists("dns_$this->domain") !== false) {
			$cmd = "# $cmd";
		}
		
		return $cmd;
	}
	
	
	public function execCmd () {
		$f3 = \Base::instance();
		putenv('RES_OPTIONS=retrans:1 retry:1 timeout:1 attempts:1');
		$response = gethostbyname($this->domain);
		
		$key = "dns_$this->domain";
		$cache = \Cache::instance();
		$cache->set($key, $response, $f3->get("cache.dns"));
	}
	
	
	public function extractInfos ($ispconfigInfos) {
		$key = "dns_$this->domain";
		
		$cache = \Cache::instance();
		$rawInfos = $cache->get($key);
		if(isset($rawInfos->body->errors)) {
			$cache->clear($key);
			$rawInfos =  null;
		}
		
		$this->labelType = 'success';
		$this->labelString = 'OK';
		
		if ($this->server["server"]["ip_address"] !== $rawInfos) {
			// if resolved ip address isn't the IP of the server hosting the website
			$this->labelType = 'danger';
			
			if (empty($rawInfos)) {
				$this->labelString = "failed";
				$this->labelTitle = "DNS resolution failed";
			}
			elseif ($this->domain === $rawInfos) {
				$this->labelString = "not in DNS";
				$this->labelTitle = "domain doesn't exist in DNS";
			}
			else {
				$this->labelString = "resolv";
				$this->labelTitle = "DNS doesn't resolve to server IP : <br/> " . $this->server["server"]["ip_address"] . " !== " . $rawInfos;
			}
		}
	}
	
	
	public static function getParent ($domain) {
		preg_match('/(([^.]+\.)*)([^.]+\.[^.]+)/', $domain, $matches);
		$parent_domain = $matches[3];
		return $parent_domain;
	}
	
}
