<?php
require_once __DIR__ . '/../autoload.inc.php';


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
	
	
	public static function getWhoisCmd ($parent_domain) {
		$cmd = "php php/whois.script.php $parent_domain";
		
		$cache = new PhpFileCacheBis();
		if (! $cache->isExpired("whois_$parent_domain")) {
			$cmd = "# $cmd"; //TODO faster if no process is created ?
		}
		
		return $cmd;
	}
	
	public static function readWhoisInfos($parent_domain) {
		$cache = new PhpFileCacheBis();
		$infos = $cache->retrieve("whois_$parent_domain");
		if(isset($infos->body->errors)) {
			$cache->eraseKey("whois_$parent_domain");
			return null;
		}
		return $infos;
	}
	
	
	public static function getLookupCmd ($domain) {
		$cmd = "php php/lookup.script.php $domain";
		
		$cache = new PhpFileCacheBis();
		$key = "lookup_$domain";
		if (! $cache->isExpired($key)) {
			$cmd = "# $cmd"; //TODO faster if no process is created ?
		}
		
		return $cmd;
	}
	
	public static function readLookupInfos($domain) {
		$cache = new PhpFileCacheBis();
		$key = "lookup_$domain";
		$infos = $cache->retrieve($key);
		if(isset($infos->body->errors)) {
			$cache->eraseKey($key);
			return null;
		}
		return $infos;
	}
	
	
	public function extractInfos ($server) {
		global $conf;
		if (isset ($this->whoisRawInfos->body->nameservers)) {
			$this->ns = $this->whoisRawInfos->body->nameservers;
		}
		else {
			$this->ns = [];
		}
		
		$this->labelType = 'success';
		$this->labelString = 'OK';
		if (!empty (array_diff($this->ns , $conf['dns']['nameservers'])) || !empty (array_diff($conf['dns']['nameservers'], $this->ns))) {
			$this->labelType = 'warning';
			$this->labelString = 'bad name servers :<br/>' . implode(', ', $this->ns);
		}
	    
		if ($server["server"]["ip_address"] !== $this->lookupRawInfos) {
			$this->labelType = 'danger';
			$this->labelString = "DNS doesn't resolve to server IP : <br/> " . $server["server"]["ip_address"] . " !== " . $this->lookupRawInfos;
		}
	}
	
	
	public function labelType () {
		return $this->labelType;
	}
	
	public function labelString () {
	    return $this->labelString;
	}
	
}
