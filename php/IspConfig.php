<?php

class IspConfig {
	
	private static function restCall ($method, $data) {
		$f3 = \Base::instance();
		
		if(!is_array($data)) return false;
		$json = json_encode($data);
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
		
		curl_setopt($curl, CURLOPT_URL, $f3->get('tech.ispconfig.rest.url') . '?' . $method);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		
		$result = curl_exec($curl);
		if ($result === FALSE)
			echo curl_errno($curl) . ' : ' . curl_error($curl) . '<br/>';
		curl_close($curl);
		
		return $result;
	}
	
	
	public static function IspLogin () {
		$f3 = \Base::instance();
		
		$result = \IspConfig::restCall('login', array('username' => $f3->get('tech.ispconfig.rest.user'), 'password' =>$f3->get('tech.ispconfig.rest.password'), 'client_login' => false));
		if($result) {
			$data = json_decode($result, true);
			if(!$data) return false;
			return $data['response'];
		}
		else {
			return false;
		}
	}
	
	
	public static function IspLogout ($session_id) {
		// logout
		$result = \IspConfig::restCall('logout', array('session_id' => $session_id));
		if(!$result) print "Could not get logout result\n";
	}
	
	
	public static function IspGetWebsites ($session_id) {
		$result = \IspConfig::restCall('sites_web_domain_get', array('session_id' => $session_id, 'primary_id' => ['type' => 'vhost'])); //TODO handle type=alias
		if(!$result) die("error");
		$domain_record = json_decode($result, true)['response'];
		$res = [];
		foreach ($domain_record as $domain) {
			$res[] = $domain['domain'];
		}
		return $domain_record;
	}
	
	
	public static function IspGetServersConfig($session_id) {
		$result = \IspConfig::restCall('server_get', array('session_id' => $session_id, 'server_id' => []));
		if(!$result) die("error");
		$res = json_decode($result, true)['response'];
		return $res;
	}
	
	
	public static function IspUpdateWebsite ($session_id, $website) {
		$result = \IspConfig::restCall('sites_web_domain_update', array('session_id' => $session_id, 'client_id' => null, 'primary_id' => $website['domain_id'], 'params' => $website));
		if(!$result) die("error");
		$res = json_decode($result, true)['response'];
		return $res;
	}
	
	
	public static function IspGetInfos () {
		$session_id = \IspConfig::IspLogin ();
		$servers = \IspConfig::IspGetServersConfig($session_id);
		$websites = \IspConfig::IspGetWebsites ($session_id);
		\IspConfig::IspLogout ($session_id);
		return [$servers, $websites];
	}
	
}
