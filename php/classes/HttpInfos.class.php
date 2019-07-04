<?php
require_once __DIR__ . '/../autoload.inc.php';


class HttpInfos {
	
	private $domain;
	private $rawInfos;
	private $status;
	private $labelType;
	private $labelString;
	
	function __construct ($website, $rawInfos) {
		$this->domain = $website['domain'];
		$this->rawInfos = $rawInfos;
		$this->extractInfos();
	}
	
	
	public static function getCmd ($domain) {
		$cmd = "php php/curl.script.php $domain";
		
		$cache = new PhpFileCacheBis();
		if (! $cache->isExpired("curl_$domain")) {
			$cmd = "# $cmd"; //TODO faster if no process is created ?
		}
		
		return $cmd;
	}
	
	public static function readInfos($domain) {
		$cache = new PhpFileCacheBis();
		$infos = $cache->retrieve("curl_$domain");
		if(isset($infos->body->errors)) {
			$cache->eraseKey("curl_$domain");
			return null;
		}
		return $infos;
	}
	
	
	public function extractInfos () {
		if($this->rawInfos === '000') {
			$this->status = null;
		}
		else {
			$this->status = $this->rawInfos;
		}
		 
		
		$this->labelType = 'success';
		$this->labelString = 'OK';
		if (empty ($this->status)) {
			$this->labelType = 'danger';
			$this->labelString = "http query failed";
		}
		elseif ($this->status >= 400) {
			$this->labelType = 'danger';
			$this->labelString = "bad status : <br/> " . $this->status;
		}
	}
	
	
	public function labelType () {
		return $this->labelType;
	}
	
	public function labelString () {
	    return $this->labelString;
	}
	
}
