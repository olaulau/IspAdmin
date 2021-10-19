<?php

use model\DnsInfos;

class IspConfig {
	
	private static function restCall ($method, $data) {
		$f3 = \Base::instance();
		
		if(!is_array($data))
			return false;
		$json = json_encode($data);
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
		curl_setopt($curl, CURLOPT_URL, $f3->get('tech.ispconfig.rest.url') . '?' . $method);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		
		$result = curl_exec($curl);
		if ($result === FALSE)
			die( "error : " . curl_errno($curl) . ' : ' . curl_error($curl) . '<br/>' );
		curl_close($curl);
		
		try {
			$res = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
		}
		catch(JsonException $ex) {
			die("json decode error $ex->code : $ex->message");
		}
		return $res["response"];
	}
	
	
	public static function IspLogin () {
		$f3 = \Base::instance();
		
		$data = \IspConfig::restCall('login', ['username' => $f3->get('tech.ispconfig.rest.user'), 'password' => $f3->get('tech.ispconfig.rest.password'), 'client_login' => false]);
		if($data === false) {
			die("IspConfig login failed");
		}
		return $data;
	}
	
	
	public static function IspLogout ($session_id) {
		// logout
		$result = \IspConfig::restCall('logout', ['session_id' => $session_id]);
		if(!$result)
		    die("Could not get logout result\n");
	}
	
	
	public static function IspGetWebsites ($session_id) {
		$domain_record = \IspConfig::restCall('sites_web_domain_get', ['session_id' => $session_id, 'primary_id' => ['type' => 'vhost']]); //TODO handle type=alias
		$res = [];
		foreach ($domain_record as $ispinfo) {
			$domain = $ispinfo['domain'];
			$parent = DnsInfos::getParent($domain);
			$res[$domain]['ispconfigInfos'] = $ispinfo;
			$res[$domain]['2LD'] = $parent;
		}
		return $res;
	}
	
	
	public static function IspUpdateWebsite ($session_id, $ispconfigInfos) {
		$res = \IspConfig::restCall('sites_web_domain_update', ['session_id' => $session_id, 'client_id' => null, 'primary_id' => $ispconfigInfos['domain_id'], 'params' => $ispconfigInfos]);
		return $res;
	}
	
	
	public static function IspGetServersConfig($session_id) {
		$res = \IspConfig::restCall('server_get', ['session_id' => $session_id, 'server_id' => []]);
		return $res;
	}
	
	
	public static function IspGetServersPhps($session_id) { //TODO server id !!!
		$res = \IspConfig::restCall('server_get_php_versions', ['session_id' => $session_id, 'server_id' => 1, "php" => "php-fpm", "get_full_data" => true]);
		$res = array_column($res, null, "server_php_id");
		return $res;
	}
	
	
	public static function IspGetMailUsers($session_id, $domain) {
		$res = \IspConfig::restCall('mail_user_get', [ 'session_id' => $session_id, 'primary_id' => ["email" => "%@$domain"] ]);
		$res = array_column($res, null, "mailuser_id");
		return $res;
	}
	
	
	public static function IspGetMailUser($session_id, $email) {
		$res = \IspConfig::restCall('mail_user_get', [ 'session_id' => $session_id, 'primary_id' => ["email" => $email] ]);
		if(!empty($res))
			return $res[0];
		else
			return null;
	}
	
	
	public static function IspDeleteMailUser($session_id, $mail_user_id) {
		$res = \IspConfig::restCall('mail_user_delete', [ 'session_id' => $session_id, 'primary_id' => $mail_user_id ]);
		if($res !== 1) {
			die("error deleting mail user id #$mail_user_id : $res");
		}
		//TODO return something ?
	}
	
	
	public static function IspAddMailUser($session_id, $server_id, $client_id, $email, $password, $quota) {
		list($email_username, $email_domain) = explode("@", $email);
		$params = [
				"server_id"	=> $server_id,
				"email"		=> $email,
				"login"		=> $email,
				"password"	=> $password,
				"quota"		=> $quota,
				"uid"		=> 5000,
				"gid"		=> 5000,
				"maildir"	=> "/var/vmail/$email_domain/$email_username",
				"homedir"	=> "/var/vmail",
				"custom_mailfilter" => "",
				"move_junk" => "n",
				"postfix" => "y",
				"backup_interval" => "monthly", //TODO
				"backup_copies" => 2, //TODO
		];
		$res = \IspConfig::restCall('mail_user_add', [ 'session_id' => $session_id, "client_id" => $client_id, 'params' => $params ]);
		$res = intval($res);
		return $res;
	}
	
	
	public static function IspGetMailDomain($session_id, $domain) {
		$res = \IspConfig::restCall('mail_domain_get_by_domain', [ 'session_id' => $session_id, 'domain' => $domain]);
		if(!empty($res))
			return $res[0];
		else
			return null;
	}
	
	
	public static function IspGetClientIdFomUserId($session_id, $sys_userid) {
		$res = \IspConfig::restCall('client_get_id', [ 'session_id' => $session_id, 'sys_userid' => $sys_userid]);
		return $res;
	}
	
	
	public static function IspGetInfos () {
		$session_id = \IspConfig::IspLogin ();
		$servers = \IspConfig::IspGetServersConfig($session_id);
		$websites = \IspConfig::IspGetWebsites ($session_id);
		$phps = \IspConfig::IspGetServersPhps ($session_id);
		\IspConfig::IspLogout ($session_id);
		return [$servers, $websites, $phps];
	}
	
	
	public static function IspGetDomains () {
		$session_id = \IspConfig::IspLogin ();
		$result = \IspConfig::restCall( 'dns_zone_get', ['session_id' => $session_id, 'primary_id' => []] );
		return $result;
	}
	
	
	public static function IspGetDomainEntries ($domain_id) {
		$session_id = \IspConfig::IspLogin ();
		$result = \IspConfig::restCall( 'dns_a_get', ['session_id' => $session_id, 'primary_id' => []] );
		
// 		$columns = array_column($array, 'name');
// 		array_multisort($columns, SORT_ASC, $array);
		
		return $result;
	}
	
	
}
