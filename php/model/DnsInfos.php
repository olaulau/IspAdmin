<?php

namespace model;

class DnsInfos extends Task {
	
	public  function getCmd () {
		$cmd = "php index.php dns $this->domain";
		
		$cache = new \PhpFileCacheBis(); ///////////////
		$key = "dns_$this->domain";
		if (! $cache->isExpired($key)) {
			$cmd = "# $cmd";
		}
		
		return $cmd;
	}
	
	
	public function execCmd () {
		putenv('RES_OPTIONS=retrans:1 retry:1 timeout:1 attempts:1');
		$response = gethostbyname($this->domain);
		
		$cache = new \PhpFileCacheBis();
		$key = "dns_$this->domain";
		$cache->store($key, $response, 600);
	}
	
	
	public function extractInfos () {
		$cache = new \PhpFileCacheBis(); ////////////////////////
		$key = "dns_$this->domain";
		$dnsRawInfos = $cache->retrieve($key);
		if(isset($dnsRawInfos->body->errors)) {
			$cache->eraseKey($key);
			$dnsRawInfos =  null;
		}
		
		$this->labelType = 'success';
		$this->labelString = 'OK';
		
		if ($this->server["server"]["ip_address"] !== $dnsRawInfos) {
			// if resolved ip address isn't the IP of the server hosting the website
			$this->labelType = 'danger';
			
			if (empty($dnsRawInfos)) {
				$this->labelString = "DNS resolution failed";
			}
			elseif ($this->domain === $dnsRawInfos) {
				$this->labelString = "domain doesn't exist in DNS";
			}
			else {
				$this->labelString = "DNS doesn't resolve to server IP : <br/> " . $this->server["server"]["ip_address"] . " !== " . $dnsRawInfos;
			}
		}
	}
	
	
	public static function getParent ($domain) {
		preg_match('/(([^.]+\.)*)([^.]+\.[^.]+)/', $domain, $matches);
		$parent_domain = $matches[3];
		return $parent_domain;
	}
	
}
