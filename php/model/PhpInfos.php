<?php

namespace model;

class PhpInfos extends Task {
	
	protected $website;
	protected $phps;
	
	public function __construct ($domain, $server, $website, $phps) {
		parent::__construct($domain, $server);
		$this->website = $website;
		$this->phps = $phps;
	}
	
	
	public function getCmd () {
		return "#"; // no background task
	}
	
	
	public function execCmd () {
		// never execute in background
		return;
	}
	
	
	public function extractInfos ($ispconfigInfos) {
		$f3 = \Base::instance();
		
		$this->labelType = 'success';
		$this->labelString = 'OK';
		
		$min_version_security_support = $f3->get('php.min_version_security_support');
		$min_version_active_support = $f3->get('php.min_version_active_support');
		
		$website = $this->website; //////////
		
		$php = $website['ispconfigInfos']['php'];
		if ($php === "no")
		{
			$this->labelString = "disabled";
			$this->labelType = "warning";
		}
		elseif ($php === "fast-cgi")
		{
			$this->labelString = "fast cgi should not work";
			$this->labelType = "warning";
		}
		elseif ($php === "mod")
		{
			$this->labelString = "apache mod isn't recommended";
			$this->labelType = "warning";
		}
		elseif ($php === "php-fpm")
		{
			if ($website["ispconfigInfos"]["server_php_id"] == 0)
			{ // default php version ?
				$regex = "/^[^\d]*((\d+\.\d+)(\.\d+)?)[^\d]*$/";
				$php_default_name = $servers[$website['ispconfigInfos']['server_id']]["web"]["php_default_name"];
				if (preg_match($regex, $php_default_name, $matches))
				{
					$this->labelString = $matches[1]; // default server PHP version
				}
				else
				{
					$this->labelString = '??'; // unknown
				}
			}
			else
			{
				$this->labelString = $this->phps[$website["ispconfigInfos"]["server_php_id"]]["name"];
			}

			if ($this->labelString < $min_version_security_support)
			{ // TODO fetch infos from php.net !
				$this->labelType = 'danger';
			}
			elseif ($this->labelString < $min_version_active_support)
			{ // TODO same
				$this->labelType = 'warning';
			}
			else
			{
				$this->labelType = 'success';
			}
		}
		else
		{ // unforeseen
			$this->labelString = "error";
			$this->labelType = "danger";
		}
	}
	
}
