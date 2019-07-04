<?php
require_once __DIR__ . '/../autoload.inc.php';


class IspConfig {
	
	public static function restCall ($method, $data) {
		global $conf;
		
		if(!is_array($data)) return false;
		$json = json_encode($data);
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
		
		curl_setopt($curl, CURLOPT_URL, $conf['ispconfig']['rest']['url'] . '?' . $method);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		
		$result = curl_exec($curl);
		curl_close($curl);
		
		return $result;
	}
	
	
	public static function IspLogin () {
		global $conf;
		$result = IspConfig::restCall('login', array('username' => $conf['ispconfig']['rest']['user'], 'password' => $conf['ispconfig']['rest']['password'], 'client_login' => false));
		if($result) {
			$data = json_decode($result, true);
			if(!$data) return false;
			return $data['response'];
		}
		else {
			return false;
		}
	}
	
	
	public static function IspGetWebsites ($session_id) {
		$result = IspConfig::restCall('sites_web_domain_get', array('session_id' => $session_id, 'primary_id' => ['type' => 'vhost'])); //TODO handle type=alias
		if(!$result) die("error");
		$domain_record = json_decode($result, true)['response'];
		$res = [];
		foreach ($domain_record as $domain) {
			$res[] = $domain['domain'];
		}
		return $domain_record;
	}
	
	
	public static function IspLogout ($session_id) {
		// logout
		$result = IspConfig::restCall('logout', array('session_id' => $session_id));
		if(!$result) print "Could not get logout result\n";
	}
	
	
	public static function IspGetServersConfig($session_id) {
		$result = IspConfig::restCall('server_get', array('session_id' => $session_id, 'server_id' => []));
		if(!$result) die("error");
		$res = json_decode($result, true)['response'];
		return $res;
	}
	
	
	public static function IspGetInfos () {
		$session_id = IspConfig::IspLogin ();
		$servers = IspConfig::IspGetServersConfig($session_id);
		$websites = IspConfig::IspGetWebsites ($session_id);
		IspConfig::IspLogout ($session_id);
		return [$servers, $websites];
	}
	
}
