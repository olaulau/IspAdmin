<?php

class SslInfos {
	
	private $domain;
	private $rawInfos;
	private $sslExpires;
	private $error;
	
	function __construct ($domain) {
		$this->domain = $domain;
		$cmd = "echo | openssl s_client -showcerts -servername $domain -connect $domain:443 2> /tmp/test.txt | openssl x509 -inform pem -noout -text > /tmp/test/txt";
// 		$this->rawInfos = shell_exec ($cmd);
		$handle = popen ($cmd, "r");
		$this->rawInfos = stream_get_contents($handle);
		
		if (preg_match("/Not After : (.*)/", $this->rawInfos, $matches))
			$this->sslExpires = $matches[1];
		
		if (preg_match("/verify error:num=(.*):(.*)/", $this->rawInfos, $matches))
			$this->error = $matches[2];
	}
	
	public function getSslExpires () {
		return $this->sslExpires;
	}
	
	public function getError () {
		return $this->error;
	}
	
}

// $domain = "";
// $s = new SslInfos($domain);
// echo $s->getSslExpires();


$domain = "";
$s = new SslInfos($domain);
var_dump($s);
