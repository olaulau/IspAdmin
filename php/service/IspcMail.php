<?php
namespace service;


abstract class IspcMail extends IspConfig
{
	
	public static function IspGetMailUsers($domain) : array
	{
		$session_id = static::IspLogin ();
		$res = static::restCall('mail_user_get', [
			'session_id' => $session_id,
			'primary_id' => ["email" => "%@$domain"],
		]);
		$res = array_column($res, null, "mailuser_id");
		return $res;
	}
	
	
	public static function IspGetMailUser($email) : array | null
	{
		$session_id = static::IspLogin ();
		$res = static::restCall('mail_user_get', [
			'session_id' => $session_id,
			'primary_id' => ["email" => $email],
		]);
		if(!empty($res))
			return $res[0];
		else
			return null;
	}
	
	
	public static function IspDeleteMailUser($mail_user_id) : void
	{
		$session_id = static::IspLogin ();
		$res = static::restCall('mail_user_delete', [
			'session_id' => $session_id,
			'primary_id' => $mail_user_id,
		]);
		if($res !== 1) {
			die("error deleting mail user id #$mail_user_id : $res");
		}
		//TODO return something ?
	}
	
	
	public static function IspAddMailUser($server_id, $client_id, $email, $password, $quota) : int
	{
		$session_id = static::IspLogin ();
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
	
	
	public static function IspGetMailDomain($domain) : array | null
	{
		$session_id = static::IspLogin ();
		$res = static::restCall('mail_domain_get_by_domain', [
			'session_id' => $session_id,
			'domain' => $domain,
		]);
		if(!empty($res))
			return $res[0];
		else
			return null;
	}
	
}
