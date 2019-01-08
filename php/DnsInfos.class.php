<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../php/config.inc.php';
require_once __DIR__ . '/../php/functions.inc.php';
require_once __DIR__ . '/../php/DnsInfos.class.php';
require_once __DIR__ . '/../php/SslInfos.class.php';

use Wruczek\PhpFileCache\PhpFileCache;


class DnsInfos {
	
	private $domain;
	private $whoisRawInfos;
	private $ns;
	private $labelType;
	private $labelString;
	
	function __construct ($website, $whoisRawInfos) {
		$this->domain = $website['domain'];
		$this->whoisRawInfos = $whoisRawInfos;
		$this->extractInfos();
	}
	
	
	public static function getParent ($domain) {
		preg_match('/(([^.]+\.)*)([^.]+\.[^.]+)/', $domain, $matches);
		$parent_domain = $matches[3];
		return $parent_domain;
	}
	
	public static function getWhoisCmd ($parent_domain) {
		$cmd = "php php/whois.script.php $parent_domain";
		
		$cache = new PhpFileCache();
		if (! $cache->isExpired("whois_$parent_domain")) {
			$cmd = "# $cmd"; //TODO faster if no process is created ?
		}
		
		return $cmd;
	}
	
	public static function readWhoisInfos($parent_domain) {
		$cache = new PhpFileCache();
		$infos = $cache->retrieve("whois_$parent_domain");
		if(isset($infos->body->errors)) {
			$cache->eraseKey("whois_$parent_domain");
			return null;
		}
		return $infos;
	}
	
	
	public function extractInfos () {
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
	    
	}
	
	
	public function labelType () {
		return $this->labelType;
	}
	
	public function labelString () {
	    return $this->labelString;
	}
	
}
