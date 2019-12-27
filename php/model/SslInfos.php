<?php
namespace model;

class SslInfos extends Task {
	
	public  function getCmd () {
		$cmd = "php index.php ssl $this->domain";
		
		$cache = \Cache::instance();
		if($cache->exists("ssl_$this->domain") !== false) {
			$cmd = "# $cmd";
		}
		
		return $cmd;
	}
	
	
	public function execCmd () {
		$f3 = \Base::instance();
		
		$cmd = "rm -f $tmp && echo | openssl s_client -showcerts -servername $this->domain -connect $this->domain:443 2>&1 | openssl x509 -inform pem -noout -text 2>&1";
		exec($cmd, $output, $return_var);
		$response = \implode(PHP_EOL, $output);
		
		$key = "ssl_$this->domain";
		$cache = \Cache::instance();
		$cache->set($key, $response, $f3->get("cache.ssl"));
	}
	
	
	public function extractInfos ($ispconfigInfos) {
		$key = "ssl_$this->domain";
		$cache = \Cache::instance();
		if($cache->exists($key) === false) {
			$this->labelType = 'warning';
			$this->labelString = 'SSL error';
		}
		$rawInfos = $cache->get($key);
		
	    if (preg_match("/Not After : (.*)/", $rawInfos, $matches)) {
	    	$sslExpires = new \DateTime ($matches[1]);
	    	$sslExpires->setTimezone(new \DateTimeZone('Europe/Paris'));
	    }
	    if (preg_match("/verify error:num=(.*):(.*)/", $rawInfos, $matches)) {
			$error = $matches[2];
	    }
	    if (preg_match("/Issuer: (C[\s]?=[\s]?([^,\n]*))?(, )?(O[\s]?=[\s]?([^,\n]*))?(, )?(CN[\s]?=[\s]?([^,\n]*))?\n/m", $rawInfos, $matches)) {
		    $issuer = $matches[8];
	    }
	    $remainingValidityDays = self::getRemainingValidityDays ($sslExpires);
	    
	    if ($ispconfigInfos['ssl'] == 'n') {
	    	$this->labelType = 'danger';
	    	$this->labelString = 'ssl disabled';
	    }
	    elseif (empty($rawInfos)) {
	    	$this->labelType = 'danger';
	    	$this->labelString = 'error getting infos';
	    }
	    elseif (!empty($error)) {
	    	$this->labelType = 'danger';
	    	$this->labelString = $error;
	    }
	    else {
	    	if ($ispconfigInfos['ssl_letsencrypt'] == 'n') {
	    		$this->labelType = 'warning';
	    		$this->labelString = "let's encrypt disabled";
	    	}
	    	if ($issuer !== "Let's Encrypt Authority X3") {
	    		$this->labelType = 'danger';
	    		$this->labelString = "certificate not signed by let's encrypt";
	    	}
	    	elseif ($remainingValidityDays <= 0) {
	    		$this->labelType = 'danger';
	    		$this->labelString = 'certificate expired ' . -$remainingValidityDays . ' days ago';
	    	}
	    	elseif ($remainingValidityDays < 29) {
	    		$this->labelType = 'warning';
	    		$this->labelString = 'certificate not renewed : <br/> ' . $remainingValidityDays . ' days left';
	    	}
	    	else {
	    		$this->labelType = 'success';
	    		$this->labelString = 'OK';
	    	}
	    }
	}
	
	private static function getRemainingValidityDays ($sslExpires) {
		if(!empty($sslExpires)) {
			$now = new \DateTime();
		    $diff = $now->diff($sslExpires);
		    if(!$diff) vdd($sslExpires);
		    $res = $diff->days;
		    if ($diff->invert === 1) {
		        $res = -$res; // expired cert
		    }
		    return $res;
		}
		else {
			return null;
		}
	}

}
