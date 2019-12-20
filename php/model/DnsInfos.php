<?php
namespace model;

class DnsInfos {
	
	private $labelType;
	private $labelString;
	
	
	public static function getParent ($domain) {
		preg_match('/(([^.]+\.)*)([^.]+\.[^.]+)/', $domain, $matches);
		$parent_domain = $matches[3];
		return $parent_domain;
	}
	
	
	public static function getLookupCmd ($domain) {
		$cmd = "php index.php lookup $domain";
		
		$cache = new \PhpFileCacheBis();
		$key = "lookup_$domain";
		if (! $cache->isExpired($key)) {
			$cmd = "# $cmd";
		}
		
		return $cmd;
	}
	
	public static function readLookupInfos($domain) {
		$cache = new \PhpFileCacheBis();
		$key = "lookup_$domain";
		$infos = $cache->retrieve($key);
		if(isset($infos->body->errors)) {
			$cache->eraseKey($key);
			return null;
		}
		return $infos;
	}
	
	
	public function extractInfos ($server, $dnsRawInfos) {
		$f3 = \Base::instance();
		
		$this->labelType = 'success';
		$this->labelString = 'OK';
		
		if ($server["server"]["ip_address"] !== $dnsRawInfos) {
			// if resolved ip address isn't the IP of the server hosting the website
			$this->labelType = 'danger';
			
			if (empty($dnsRawInfos)) {
				$this->labelString = "DNS resolution failed";
			}
			elseif ($this->domain === $dnsRawInfos) {
				$this->labelString = "domain doesn't exist in DNS";
			}
			else {
			    $this->labelString = "DNS doesn't resolve to server IP : <br/> " . $server["server"]["ip_address"] . " !== " . $dnsRawInfos;
			}
		}
	}
	
	
	public function getLabelType () {
		return $this->labelType;
	}
	
	public function getLabelString () {
	    return $this->labelString;
	}
	
}
