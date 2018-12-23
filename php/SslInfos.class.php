<?php
require_once __DIR__ . '/functions.inc.php';

class SslInfos {
	
	private $domain;
	private $rawInfos;
	private $sslExpires;
	private $error;
	private $issuer;
	
	function __construct ($domain) {
		$this->domain = $domain;
		$cmd = "echo | openssl s_client -showcerts -servername $domain -connect $domain:443 2>> /tmp/test.txt | openssl x509 -inform pem -noout -text >> /tmp/test.txt && cat /tmp/test.txt && rm /tmp/test.txt";
		$handle = popen ($cmd, "r");
		$this->rawInfos = stream_get_contents($handle);
		
		if (preg_match("/Not After : (.*)/", $this->rawInfos, $matches))
			$this->sslExpires = $matches[1];
		
		if (preg_match("/verify error:num=(.*):(.*)/", $this->rawInfos, $matches))
			$this->error = $matches[2];
		
		if (preg_match("/Issuer: (C = ([^,]*)){0,1}(, ){0,1}(O = ([^,]*)){0,1}(, ){0,1}(CN = ([^,\n]*)){0,1}\n/", $this->rawInfos, $matches))
		    $this->issuer = $matches[8];
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
}

$domain = "";
$s = new SslInfos($domain);
echo $s->getSslExpires();
echo $s->getError();
echo $s->getIssuer();
