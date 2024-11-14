<?php
namespace service;

use ErrorException;
use JsonException;


abstract class IspConfig
{
	
	private static string $session_id;
	
	
	protected static function restCall ($method, $data) : mixed
	{
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
	
	
	public static function IspLogin () : string
	{
		$f3 = \Base::instance();
		
		$data = static::restCall('login', [
			'username' => $f3->get('tech.ispconfig.rest.user'),
			'password' => $f3->get('tech.ispconfig.rest.password'),
			'client_login' => false,
		]);
		if($data === false) {
			Throw New ErrorException("IspConfig login failed");
		}
		return $data;
	}
	
	
	public static function IspLogout () : void
	{
		$session_id = static::getSessionId();
		// logout
		$result = static::restCall('logout', ['session_id' => $session_id]);
		if(!$result)
		    die("Could not get logout result\n");
	}
	
	
	protected static function getSessionId () : string
	{
		if(empty(static::$session_id)) {
			$session_id = static::IspLogin();
			static::$session_id = $session_id;
		}
		return static::$session_id;
	}
	
	
	/* ------------------------ */
	
	
	public static function IspGetMailUsers($session_id, $domain) : array
	{
		$res = static::restCall('mail_user_get', [
			'session_id' => $session_id,
			'primary_id' => ["email" => "%@$domain"],
		]);
		$res = array_column($res, null, "mailuser_id");
		return $res;
	}
	
	
	public static function IspGetMailUser($session_id, $email) : array | null
	{
		$res = static::restCall('mail_user_get', [
			'session_id' => $session_id,
			'primary_id' => ["email" => $email],
		]);
		if(!empty($res))
			return $res[0];
		else
			return null;
	}
	
	
	public static function IspDeleteMailUser($session_id, $mail_user_id) : void
	{
		$res = static::restCall('mail_user_delete', [
			'session_id' => $session_id,
			'primary_id' => $mail_user_id,
		]);
		if($res !== 1) {
			die("error deleting mail user id #$mail_user_id : $res");
		}
		//TODO return something ?
	}
	
	
	public static function IspAddMailUser($session_id, $server_id, $client_id, $email, $password, $quota) : int
	{
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
	
	
	public static function IspGetMailDomain($session_id, $domain) : array | null
	{
		$res = static::restCall('mail_domain_get_by_domain', [
			'session_id' => $session_id,
			'domain' => $domain,
		]);
		if(!empty($res))
			return $res[0];
		else
			return null;
	}
	
	
	/* ------------------------ */
	
	
	public static function IspGetClientIdFromUserId($session_id, $sys_userid) : array
	{
		$res = static::restCall('client_get_id', [
			'session_id' => $session_id,
			'sys_userid' => $sys_userid,
		]);
		return $res;
	}
	
}
