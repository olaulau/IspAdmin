<?php
namespace model;

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
		$cmd = "php index.php curl $domain";
		
		$cache = new \PhpFileCacheBis();
		if (! $cache->isExpired("curl_$domain")) {
			$cmd = "# $cmd"; //TODO faster if no process is created ?
		}
		
		return $cmd;
	}
	
	public static function readInfos($domain) {
		$cache = new \PhpFileCacheBis();
		$infos = $cache->retrieve("curl_$domain");
		if(isset($infos->body->errors)) {
			$cache->eraseKey("curl_$domain");
			return null;
		}
		return $infos;
	}
	
	
	public function extractInfos () {
		$this->status = $this->rawInfos;
		$this->labelType = 'success';
		$this->labelString = 'OK';
		
		if (empty ($this->status)) {
			$this->labelType = 'danger';
			$this->labelString = "http query failed";
		}
		elseif ($this->status >= 500) {
			$this->labelType = 'danger';
			$this->labelString = "server side error : <br/> " . $this->status;
		}
		elseif ($this->status >= 400) {
			$this->labelType = 'warning';
			$this->labelString = "client side error : <br/> " . $this->status;
		}
	}
	
	
	public function getLabelType () {
		return $this->labelType;
	}
	
	public function getLabelString () {
	    return $this->labelString;
	}
	
}
