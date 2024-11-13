<?php
namespace service;

use JsonException;
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
			var_dump($result); die;
			die("json decode error " . $ex->getCode() . " : " . $ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
		}
		return $res["response"];
	}
	
	
	public static function IspLogin () {
		$f3 = \Base::instance();
		
		$data = static::restCall('login', [
			'username' => $f3->get('tech.ispconfig.rest.user'),
			'password' => $f3->get('tech.ispconfig.rest.password'),
			'client_login' => false,
		]);
		if($data === false) {
			die("IspConfig login failed");
		}
		return $data;
	}
	
	
	public static function IspLogout ($session_id) {
		// logout
		$result = static::restCall('logout', ['session_id' => $session_id]);
		if(!$result)
		    die("Could not get logout result\n");
	}
	
	
	public static function IspGetWebsites ($session_id) {
		$domain_record = static::restCall('sites_web_domain_get', [
			'session_id' => $session_id,
			'primary_id' => ['type' => 'vhost'],
		]); //TODO handle type=alias
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
		$res = static::restCall('sites_web_domain_update', [
			'session_id' => $session_id,
			'client_id' => null,
			'primary_id' => $ispconfigInfos['domain_id'],
			'params' => $ispconfigInfos
		]);
		return $res;
	}
	
	
	public static function IspGetServersConfig($session_id) {
		$res = static::restCall('server_get', [
			'session_id' => $session_id,
			'server_id' => [],
		]);
		return $res;
	}
	
	
	public static function IspGetServersPhps($session_id) { //TODO server id !!!
		$res = static::restCall('server_get_php_versions', [
			'session_id' => $session_id,
			'server_id' => 1,
			"php" => "php-fpm",
			"get_full_data" => true,
		
		]);
		$res = array_column($res, null, "server_php_id");
		return $res;
	}
	
	
	public static function IspGetMailUsers($session_id, $domain) {
		$res = static::restCall('mail_user_get', [
			'session_id' => $session_id,
			'primary_id' => ["email" => "%@$domain"],
		]);
		$res = array_column($res, null, "mailuser_id");
		return $res;
	}
	
	
	public static function IspGetMailUser($session_id, $email) {
		$res = static::restCall('mail_user_get', [
			'session_id' => $session_id,
			'primary_id' => ["email" => $email],
		]);
		if(!empty($res))
			return $res[0];
		else
			return null;
	}
	
	
	public static function IspDeleteMailUser($session_id, $mail_user_id) {
		$res = static::restCall('mail_user_delete', [
			'session_id' => $session_id,
			'primary_id' => $mail_user_id,
		]);
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
		$res = static::restCall('mail_user_add', [ 'session_id' => $session_id, "client_id" => $client_id, 'params' => $params ]);
		$res = intval($res);
		return $res;
	}
	
	
	public static function IspGetMailDomain($session_id, $domain) {
		$res = static::restCall('mail_domain_get_by_domain', [
			'session_id' => $session_id,
			'domain' => $domain,
		]);
		if(!empty($res))
			return $res[0];
		else
			return null;
	}
	
	
	public static function IspGetClientIdFromUserId($session_id, $sys_userid) {
		$res = static::restCall('client_get_id', [
			'session_id' => $session_id,
			'sys_userid' => $sys_userid,
		]);
		return $res;
	}
	
	
	public static function IspGetInfos () {
		$session_id = static::IspLogin ();
		$servers = static::IspGetServersConfig($session_id);
		$websites = static::IspGetWebsites ($session_id);
		$phps = static::IspGetServersPhps ($session_id);
		static::IspLogout ($session_id);
		return [$servers, $websites, $phps];
	}
	
	
	public static function IspGetDomains () {
		$session_id = static::IspLogin ();
		$result = static::restCall( 'dns_zone_get', [
			'session_id' => $session_id,
			'primary_id' => [],
		]);
		return $result;
	}
	
	
	public static function IspGetDomainEntries () {
		$session_id = static::IspLogin ();
		$result = static::restCall( 'dns_a_get', [
			'session_id' => $session_id,
			'primary_id' => [],
		]);
		return $result;
	}
	
	
	public static function IspSetDomainAData ($domain_entry_id, $data) {
		$session_id = static::IspLogin ();
		
		$dns_entry = static::restCall( 'dns_a_get', [
			'session_id' => $session_id,
			'primary_id' => $domain_entry_id,
		]);
		$dns_entry["data"] = $data;
		
		$result = static::restCall( 'dns_a_update', [
			'session_id' => $session_id,
			'client_id' => null,
			'primary_id' => $domain_entry_id,
			'params' => $dns_entry,
		] );
		return $result;
	}
	
	
	public static function IspSetDomainAName ($domain_entry_id, $name) {
		$session_id = static::IspLogin ();
		
		$dns_entry = static::restCall( 'dns_a_get', [
			'session_id' => $session_id,
			'primary_id' => $domain_entry_id,
		]);
		$dns_entry["name"] = $name;
		
		$result = static::restCall( 'dns_a_update', [
			'session_id' => $session_id,
			'client_id' => null,
			'primary_id' => $domain_entry_id,
			'params' => $dns_entry,
		] );
		return $result;
	}

	
	public static function IspSetDomainParams ($domain_entry_id, $name, $data) {
		$session_id = static::IspLogin ();
		
		$dns_entry = static::restCall( 'dns_a_get', [
			'session_id' => $session_id,
			'primary_id' => $domain_entry_id,
		]);
		
		if(!empty($name) && empty($data)) { // set name
			$dns_entry["name"] = $name;
		}
		elseif(empty($name) && !empty($data)) { // set data
			$dns_entry["data"] = $data;
		}
		else {
			throw new \Exception("bad name / data parameters");
		}
		
		$result = static::restCall( "dns_" . strtolower($dns_entry["type"]) . "_update", [
			'session_id' => $session_id,
			'client_id' => null,
			'primary_id' => $domain_entry_id,
			'params' => $dns_entry,
		] );
		return $result;
	}
	
}
