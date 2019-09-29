<?php
namespace model;

class DnsInfos {
	
	private $domain;
	private $whoisRawInfos;
	private $lookupRawInfos;
	private $ns;
	private $labelType;
	private $labelString;
	
	function __construct ($website, $server, $whoisRawInfos, $lookupRawInfos) {
		$this->domain = $website['domain'];
		$this->whoisRawInfos = $whoisRawInfos;
		$this->lookupRawInfos = $lookupRawInfos;
		$this->extractInfos($server);
	}
	
	
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
	
	
	public function extractInfos ($server) {
		$f3 = \Base::instance();
		
		if (isset ($this->whoisRawInfos)) {
			$this->ns = $this->whoisRawInfos->getNameServers();
		}
		else {
			$this->ns = [];
		}
		
		$this->labelType = 'success';
		$this->labelString = 'OK';
		
		if (!empty (array_diff($this->ns , $f3->get('tech.dns.nameservers'))) || !empty (array_diff($f3->get('tech.dns.nameservers'), $this->ns))) {
			// if domaine nameservers aren't exactly those in config
			$this->labelType = 'warning';
			if (count($this->ns) === 0) {
				$this->labelString = "WHOIS failed";
			}
			else {
				$this->labelString = 'bad name servers :<br/>' . implode(', ', $this->ns);
			}
		}
	    
		if ($server["server"]["ip_address"] !== $this->lookupRawInfos) {
			// if resolved ip address isn't the IP of the server hosting the website
			$this->labelType = 'danger';
			
			if (empty($this->lookupRawInfos)) {
				$this->labelString = "DNS resolution failed";
			}
			elseif ($this->domain === $this->lookupRawInfos) {
				$this->labelString = "domain doesn't exist in DNS";
			}
			else {
				$this->labelString = "DNS doesn't resolve to server IP : <br/> " . $server["server"]["ip_address"] . " !== " . $this->lookupRawInfos;
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
