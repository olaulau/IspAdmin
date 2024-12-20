<?php
namespace model;


class SslInfos extends Task {
	
	private $remainingValidityDays;
	
	public function getRemainingValidityDays () {
		return $this->remainingValidityDays;
	}
	
	
	public function getCmd () {
		$f3 = \Base::instance();
		
		$php_binary = $f3->get("tech.PHP_BINARY");
		$cmd = "$php_binary index.php ssl $this->domain";
		
		$cache = \Cache::instance();
		if($cache->exists("ssl_$this->domain") !== false) {
			$cmd = "# $cmd";
		}
		
		return $cmd;
	}
	
	
	public function execCmd () {
		$f3 = \Base::instance();
		
		$cmd = "openssl s_client -showcerts -servername $this->domain -connect $this->domain:443 2>&1 | openssl x509 -inform pem -noout -text 2>&1";
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
		else {
			$this->labelType = "warning";
			$this->labelString = "??";
			$this->labelTitle = "couldn't find expiration infos";
			return;
		}
		if (preg_match("/verify error:num=(.*):(.*)/", $rawInfos, $matches)) {
			$error = $matches[2];
		}
		if (preg_match("/Issuer: (C[\s]?=[\s]?([^,\n]*))?(, )?(O[\s]?=[\s]?([^,\n]*))?(, )?(CN[\s]?=[\s]?([^,\n]*))?\n/m", $rawInfos, $matches)) {
			$issuer = $matches[5];
		}
		$this->remainingValidityDays = self::calculateRemainingValidityDays ($sslExpires);
		
		if ($ispconfigInfos ["type"] === "vhost") {
			if ($ispconfigInfos['ssl'] == 'n') {
				$this->labelType = 'danger';
				$this->labelString = 'disabled';
				$this->labelTitle = 'ssl disabled';
			}
			elseif (empty($rawInfos)) {
				$this->labelType = 'danger';
				$this->labelString = 'error getting infos';
				$cache->clear($key);
			}
			elseif (!empty($error)) {
				$this->labelType = 'danger';
				$this->labelString = $error;
			}
			else {
				if ($ispconfigInfos['ssl_letsencrypt'] == 'n') {
					$this->labelType = 'warning';
					$this->labelString = "disabled";
					$this->labelTitle = "let's encrypt disabled";
				}
				if ($issuer !== "Let's Encrypt") {
					$this->labelType = 'danger';
					$this->labelString = "issuer";
					$this->labelTitle = "certificate not signed by let's encrypt ($issuer)";
				}
				elseif ($this->remainingValidityDays <= 0) {
					$this->labelType = 'danger';
					$this->labelString = "expired";
					$this->labelTitle = -$this->remainingValidityDays . ' days ago';
				}
				elseif ($this->remainingValidityDays < 29) {
					$this->labelType = 'warning';
					$this->labelString = "not renewed";
					$this->labelTitle = $this->remainingValidityDays . " days left";
				}
				else {
					$this->labelType = 'success';
					$this->labelString = 'OK';
				}
			}
		}
		elseif ($ispconfigInfos ["type"] === "alias" || $ispconfigInfos ["type"] === "subdomain") {
			if ($ispconfigInfos ["ssl_letsencrypt_exclude"] === "y") {
				$this->labelType = 'danger';
				$this->labelString = "disabled";
				$this->labelTitle = "not added to  let's encrypt";
			}
			else { //TODO copy parent vhost result
				$this->labelType = 'warning';
				$this->labelString = "same";
				$this->labelTitle = "same as parent vhost";
			}
		}
	}
	
	private static function calculateRemainingValidityDays ($sslExpires) {
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
