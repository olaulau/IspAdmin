<?php

use model\DnsInfos;

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
		
		$result = \IspConfig::restCall('login', array('username' => $f3->get('tech.ispconfig.rest.user'), 'password' => $f3->get('tech.ispconfig.rest.password'), 'client_login' => false));
		if($result) {
			$data = json_decode($result, true);
			if(!$data)
			    return false;
			return $data['response'];
		}
		else {
			return false;
		}
	}
	
	
	public static function IspLogout ($session_id) {
		// logout
		$result = \IspConfig::restCall('logout', array('session_id' => $session_id));
		if(!$result)
		    print "Could not get logout result\n";
	}
	
	
	public static function IspGetWebsites ($session_id) {
		$result = \IspConfig::restCall('sites_web_domain_get', array('session_id' => $session_id, 'primary_id' => ['type' => 'vhost'])); //TODO handle type=alias
		if(!$result)
		    die("error");
		$domain_record = json_decode($result, true)['response'];
		$res = [];
		foreach ($domain_record as $ispinfo) {
			$domain = $ispinfo['domain'];
			$parent = DnsInfos::getParent($domain);
			$res[$domain]['ispconfigInfos'] = $ispinfo;
			$res[$domain]['2LD'] = $parent;
		}
		return $res;
	}
	
	
	public static function IspGetServersConfig($session_id) {
		$result = \IspConfig::restCall('server_get', array('session_id' => $session_id, 'server_id' => []));
		if(!$result)
		    die("error");
		$res = json_decode($result, true)['response'];
		return $res;
	}
	
	
	public static function IspUpdateWebsite ($session_id, $ispconfigInfos) {
		$result = \IspConfig::restCall('sites_web_domain_update', array('session_id' => $session_id, 'client_id' => null, 'primary_id' => $ispconfigInfos['domain_id'], 'params' => $ispconfigInfos));
		if(!$result)
		    die("error");
		$res = json_decode($result, true)['response'];
		return $res;
	}
	
	
	public static function IspGetServersPhps($session_id) { //TODO server id !!!
		$result = \IspConfig::restCall('server_get_php_versions', ['session_id' => $session_id, 'server_id' => 1, "php" => "php-fpm", "get_full_data" => true]);
		if(!$result)
		    die("error");
		$res = json_decode($result, true)['response'];
		$res = array_column($res, null, "server_php_id");
		return $res;
	}
	
	
	public static function IspGetMailUsers($session_id, $domain) {
		$result = \IspConfig::restCall('mail_user_get', [ 'session_id' => $session_id, 'primary_id' => ["email" => "%@$domain"] ]);
		if(!$result)
		    die("error");
		$res = json_decode($result, true)['response'];
		$res = array_column($res, null, "mailuser_id");
		return $res;
	}
	
	public static function IspGetMailUser($session_id, $email) {
		$result = \IspConfig::restCall('mail_user_get', [ 'session_id' => $session_id, 'primary_id' => ["email" => $email] ]);
		if(!$result)
			die("error");
		$res = json_decode($result, true)['response'];
		if(!empty($res))
			return $res[0];
		else
			return null;
	}
	
	public static function IspDeleteMailUser($session_id, $mail_user_id) {
		$result = \IspConfig::restCall('mail_user_delete', [ 'session_id' => $session_id, 'primary_id' => $mail_user_id ]);
		if(!$result)
		    die("error");
		$res = json_decode($result, true)['response'];
		if($res !== 1) {
			die("error deleting mail user id #$mail_user_id : $res");
		}
	}
	
	public static function IspAddMailUser($session_id, $server_id, $email, $password, $quota) {
		$params = [
				"server_id"	=> $server_id,
				"email"		=> $email,
				"login"		=> $email,
				"password"	=> $password,
				"quota"		=> $quota,
				
				"uid"		=> 5000,
				"gid"		=> 5000,
				"maildir"	=> "/var/vmail/domain/username", // TODO
				"homedir"	=> "/var/vmail",
				"custom_mailfilter" => "",
				"move_junk" => "n",
				"postfix" => "y",
				"backup_interval" => "monthly", //TODO
				"backup_copies" => 2, //TODO
		];
		$result = \IspConfig::restCall('mail_user_add', [ 'session_id' => $session_id, "client_id" => 0, 'params' => $params ]);
		if(!$result)
		    die("error");
		$res = json_decode($result, true);
		if($res["code"] !== "ok") {
			die("error : ".$res["message"]);
		}
		$res = intval($res['response']);
		return $res;
	}
	
	public static function IspGetMailDomain($session_id, $domain) {
		$result = \IspConfig::restCall('mail_domain_get_by_domain', [ 'session_id' => $session_id, 'domain' => $domain]);
		if(!$result)
			die("error");
		$res = json_decode($result, true);
		if($res["code"] !== "ok") {
			die("error : ".$res["message"]);
		}
		$res = $res['response'];
		if(!empty($res))
			return $res[0];
		else
			return null;
	}
	
	
	public static function IspGetInfos () {
		$session_id = \IspConfig::IspLogin ();
		$servers = \IspConfig::IspGetServersConfig($session_id);
		$websites = \IspConfig::IspGetWebsites ($session_id);
		$phps = \IspConfig::IspGetServersPhps ($session_id);
		\IspConfig::IspLogout ($session_id);
		return [$servers, $websites, $phps];
	}
	
}
