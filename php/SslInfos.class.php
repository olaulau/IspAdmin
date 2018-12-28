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
	
	function __construct ($domain, $rawInfos) {
		$this->domain = $domain;
		if (empty($rawInfos)) {
			$this->labelType = 'danger';
			$this->labelString = 'error getting infos';
		}
		else {
			$this->rawInfos = $rawInfos;
			$this->extractInfos ();
		}
	}
	
	public static function execOpenssl ($domain) {
	    $tmp = tempnam("/tmp/ssl/", "ssl");
		$cmd = "echo | openssl s_client -showcerts -servername $domain -connect $domain:443 2>> $tmp | openssl x509 -inform pem -noout -text >> $tmp && cat $tmp" . " && rm $tmp";
		$handle = popen ($cmd, "r");
		echo stream_get_contents($handle);
	}
	
	
	public function extractInfos () {
	    if (preg_match("/Not After : (.*)/", $this->rawInfos, $matches)) {
			$this->sslExpires = new DateTime ($matches[1]);
			$this->sslExpires->setTimezone(new DateTimeZone('Europe/Paris'));
	    }
	    
	    if (preg_match("/verify error:num=(.*):(.*)/", $this->rawInfos, $matches)) {
			$this->error = $matches[2];
	    }
	    
	    if (preg_match("/Issuer: (C = ([^,]*)){0,1}(, ){0,1}(O = ([^,]*)){0,1}(, ){0,1}(CN = ([^,\n]*)){0,1}\n/", $this->rawInfos, $matches)) {
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
	    	$this->labelString = 'certificate not renewed : ' . $this->getRemainingValidityDays() . ' days left';
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
