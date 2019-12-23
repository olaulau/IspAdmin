<?php
namespace controller;

class Cli
{
	
	public static function beforeroute() {
		
	}
	
	
	public static function afterroute() {
		
	}
	
	
	public static function curl () {
		$f3 = \Base::instance();
		
		$domain = $f3->get('PARAMS.domain');
		
		$response = shell_exec("curl -L -s -o /dev/null -X GET -w '%{http_code}' $domain");

		$cache = new \PhpFileCacheBis();
		$cache->store("curl_$domain", $response, 60);
	}
}
