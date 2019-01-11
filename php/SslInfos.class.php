<?php
require_once __DIR__ . '/functions.inc.php';

class SslInfos {
	
	private $domain;
	private $rawInfos;
	private $sslExpires;
	private $error;
	private $issuer;
	private $labelType;
	private $labelString;
	
	function __construct ($website, $rawInfos) {
		$this->domain = $website['domain'];
		if ($website['ssl'] == 'n') {
			$this->labelType = 'danger';
			$this->labelString = 'ssl disabled';
		}
		elseif ($website['ssl_letsencrypt'] == 'n') {
			$this->labelType = 'warning';
			$this->labelString = "let's encrypt disabled";
		}
		elseif (empty($rawInfos)) {
			$this->labelType = 'danger';
			$this->labelString = 'error getting infos';
		}
		else {
			$this->rawInfos = $rawInfos;
			$this->extractInfos ();
		}
	}
	
	public static function getOpensslCmd ($domain) {
		$tmp = "./tmp/ssl/" . $domain;
		$cmd = "rm -f $tmp && echo | openssl s_client -showcerts -servername $domain -connect $domain:443 2>> $tmp | openssl x509 -inform pem -noout -text >> $tmp";
		return $cmd;
	}
	
	public static function readRawInfos($domain) {
		$tmp = "./tmp/ssl/" . $domain;
		return file_get_contents($tmp);
	}
	
	
	public function extractInfos () {
	    if (preg_match("/Not After : (.*)/", $this->rawInfos, $matches)) {
			$this->sslExpires = new DateTime ($matches[1]);
			$this->sslExpires->setTimezone(new DateTimeZone('Europe/Paris'));
	    }
	    
	    if (preg_match("/verify error:num=(.*):(.*)/", $this->rawInfos, $matches)) {
			$this->error = $matches[2];
	    }
	    
	    if (preg_match("/Issuer: (C[\s]?=[\s]?([^,\n]*))?(, )?(O[\s]?=[\s]?([^,\n]*))?(, )?(CN[\s]?=[\s]?([^,\n]*))?\n/m", $this->rawInfos, $matches)) {
		    $this->issuer = $matches[8];
	    }
	    
	    if ($this->issuer !== "Let's Encrypt Authority X3") {
	    	$this->labelType = 'danger';
	    	$this->labelString = "certificate not signed by let's encrypt";
	    }
	    elseif ($this->getRemainingValidityDays() <= 0) {
	    	$this->labelType = 'danger';
	    	$this->labelString = 'certificate expired ' . -$this->getRemainingValidityDays() . ' days ago';
	    }
	    elseif ($this->getRemainingValidityDays() < 30) {
	    	$this->labelType = 'warning';
	    	$this->labelString = 'certificate not renewed : <br/> ' . $this->getRemainingValidityDays() . ' days left';
	    }
	    else {
	    	$this->labelType = 'success';
	    	$this->labelString = 'OK';
	    }
	    
	    if ($this->labelType !== 'danger' && !empty($this->error)) {
	    	$this->labelType = 'danger';
	    	$this->labelString = $this->error;
	    }
	}
	
	
	public function getSslExpires () {
		return $this->sslExpires;
	}
	
	public function getError () {
		return $this->error;
	}
	
	public function getIssuer () {
	    return $this->issuer;
	}
	
	
	public function getRemainingValidityDays () {
	    $now = new DateTime();
	    $diff = $now->diff($this->getSslExpires());
	    if(!$diff) vdd($this->getSslExpires());
	    $res = $diff->days;
	    if ($diff->invert === 1) {
	        $res = -$res;
	    }
	    return $res;
	}
	
	
	public function labelType () {
		return $this->labelType;
	}
	
	public function labelString () {
	    return $this->labelString;
	}
	
}
