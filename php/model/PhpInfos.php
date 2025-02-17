<?php
namespace model;


class PhpInfos extends Task
{
	
	protected $website;
	protected $phps;
	
	public function __construct ($domain, $server, $website, $phps)
	{
		parent::__construct($domain, $server);
		$this->website = $website;
		$this->phps = $phps;
	}
	
	
	public function getCmd ()
	{
		return "#"; // no background task
	}
	
	
	public function execCmd ()
	{
		return; // never execute in background
	}
	
	
	public function extractInfos ($ispconfigInfos)
	{
		$f3 = \Base::instance();
		$min_version_security_support = $f3->get('php.min_version_security_support');
		$min_version_active_support = $f3->get('php.min_version_active_support');
		
		
		$php = $this->website ['php'];
		
		if ($ispconfigInfos ["type"] === "vhost") {
			if ($php === "no") {
				$this->labelString = "disabled";
				$this->labelType = "warning";
				$this->labelTitle = "";
			}
			elseif ($php === "fast-cgi") {
				$this->labelString = "fcgi";
				$this->labelType = "warning";
				$this->labelTitle = "fast cgi should not work";
			}
			elseif ($php === "mod") {
				$this->labelString = "mod";
				$this->labelType = "warning";
				$this->labelTitle = "apache mod isn't recommended";
			}
			
			elseif ($php === "php-fpm") {
				if ($this->website ["server_php_id"] === null) {
					$this->labelString = "no";
					$this->labelType = "danger";
					$this->labelTitle = "no PHP version";
				}
				elseif ($this->website ["server_php_id"] === "0") { // default php version
					$regex = "/^[^\d]*((\d+\.\d+)(\.\d+)?)[^\d]*$/";
					$php_default_name = $this->server ["web"] [ "php_default_name"];
					if (preg_match($regex, $php_default_name, $matches)) {
						$this->labelString = $matches [1]; // default server PHP version
					}
					else {
						$this->labelString = '??'; // unknown
						$this->labelType = "danger";
						$this->labelTitle = "unknown PHP version";
					}
				}
				else {
					$server_php_id = $this->website ["server_php_id"];
					if(isset($this->phps [$server_php_id])) {
						$php = $this->phps [$server_php_id];
						$this->labelString = $php ["name"];
						$this->labelTitle = "";
					}
					else {
						$this->labelString = "???"; // PHP version not found
						$this->labelType = "danger";
						$this->labelTitle = "PHP version not found";
					}
				}
	
				if(empty($this->labelType)) {
					if ($this->labelString < $min_version_security_support) { // TODO fetch infos from php.net !
						$this->labelType = 'danger';
						$this->labelTitle = "not maintained anymore";
					}
					elseif ($this->labelString < $min_version_active_support) { // TODO same
						$this->labelType = 'warning';
						$this->labelTitle = "security support only";
					}
					else {
						$this->labelType = 'success';
						$this->labelTitle = "";
					}
				}
			}
			
			else { // unforeseen
				$this->labelString = "unknown";
				$this->labelType = "danger";
				$this->labelTitle = "unknown php type";
			}
		}
		
		elseif ($ispconfigInfos ["type"] === "alias" || $ispconfigInfos ["type"] === "subdomain") {
			if ($php === "y") {
				//TODO copy parent vhost result
				$this->labelString = "same";
				$this->labelType = "warning";
				$this->labelTitle = "same as parent vhost";
			}
		}
	}
	
}
