<?php
namespace service;

use ErrorException;
use JsonException;


abstract class IspConfig
{
	
	private static string $session_id;
	private static mixed $last_query_response;
	
	
	private static function rest ($method, $data) : mixed
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
		
		static::$last_query_response = $res;
		return $res;
	}
	
	
	public static function restCall ($method, $data) : mixed
	{
		$res = self::rest($method, $data);
		return $res["response"];
	} // TODO remove later, kept for backward compatibility
	
	public static function getLastQueryResponse () : mixed
	{
		return self::$last_query_response;
	} //TODO remove, useless with usage of IspRestCall()
	
	
	public static function IspRestCall ($method, $data) : array
	{
		$session_id = self::getSessionId();
		$data ["session_id"] = $session_id;
		$res = static::rest($method, $data);
		if($res ["code"] !== "ok") {
			throw new ErrorException("{$res ["code"]} : {$res ["message"]}");
		}
		return $res ["response"];
	} //TODO use instead of restCall()
	
	
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
	
	
	public static function getSessionId () : string
	{
		if(empty(static::$session_id)) {
			$session_id = static::IspLogin();
			static::$session_id = $session_id;
		}
		return static::$session_id;
	}
	
	
	public static function IspGetClientIdFromUserId($sys_userid) : int
	{
		$session_id = static::IspLogin ();
		$res = static::restCall('client_get_id', [
			'session_id' => $session_id,
			'sys_userid' => $sys_userid,
		]);
		return $res;
	}
	
}
