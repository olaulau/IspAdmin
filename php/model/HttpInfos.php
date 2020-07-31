<?php
namespace model;

class HttpInfos extends Task {
	
	public function getCmd () {
	    $f3 = \Base::instance();
	    
	    $php_binary = $f3->get("tech.PHP_BINARY");
		$cmd = "$php_binary index.php http $this->domain";
		
		$key = "http_$this->domain";
		$cache = \Cache::instance();
		if ($cache->exists("http_$this->domain")) {
			$cmd = "# $cmd";
		}
		
		return $cmd;
	}
	
	
	public function execCmd () {
		$f3 = \Base::instance();
		$this->domain = $f3->get('PARAMS.domain');
		
		$response = shell_exec("curl --connect-timeout 2 --max-time 10 -L -s -o /dev/null -X GET -w '%{http_code}' $this->domain");
		$cache = \Cache::instance();
		$cache->set("http_$this->domain", $response, $f3->get("cache.http"));
	}
	
	
	public function extractInfos ($ispconfigInfos) {
		$cache = \Cache::instance();
		$rawInfos = $cache->get("http_$this->domain");
		if(isset($rawInfos->body->errors)) {
			$cache->clear("http_$this->domain");
			$rawInfos = null;
		}
		
		$this->labelType = 'success';
		$this->labelString = 'OK';
		
		if (empty (intval($rawInfos))) {
			$cache->clear("http_$this->domain");
			$this->labelType = 'danger';
			$this->labelString = "http query failed";
		}
		elseif ($rawInfos >= 500) {
			$this->labelType = 'danger';
			$this->labelString = "server side error : <br/> " . $rawInfos;
		}
		elseif ($rawInfos >= 400) {
			$this->labelType = 'warning';
			$this->labelString = "client side error : <br/> " . $rawInfos;
		}
	}
	
}
