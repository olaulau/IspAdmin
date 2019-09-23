<?php
namespace controller;

class Cli
{
	
	public static function beforeroute() {
		
	}
	
	
	public static function afterroute() {
		
	}
	
	
	public static function whois ()
	{
		$f3 = \Base::instance();
		$domain = $f3->get('PARAMS.domain');
		
		\Unirest\Request::auth($conf['jsonwhoisapi']['customer_id'], $conf['jsonwhoisapi']['api_key']);
		$headers = array("Accept" => "application/json");
		$url = "https://jsonwhoisapi.com/api/v1/whois?identifier=$domain";
		$response = \Unirest\Request::get($url, $headers);
		
		$cache = new \PhpFileCacheBis();
		$cache->store("whois_$domain", $response, 60*60*24*2); //TODO calculate expiration
	}
	
	
	public static function lookup ()
	{
		$f3 = \Base::instance();
		$domain = $f3->get('PARAMS.domain');
		
		$response = gethostbyname($domain);
		putenv('RES_OPTIONS=retrans:1 retry:1 timeout:1 attempts:3');
		
		$cache = new \PhpFileCacheBis();
		$key = "lookup_$domain";
		$cache->store($key, $response, 60);
	}
	
	
	public static function curl () {
		$f3 = \Base::instance();
		$domain = $f3->get('PARAMS.domain');
		
		$response = shell_exec("curl -L -s -o /dev/null -X GET -w '%{http_code}' $domain");

		$cache = new \PhpFileCacheBis();
		$cache->store("curl_$domain", $response, 10);
	}
}
