<?php
namespace service;

use ErrorException;
use JsonException;


abstract class IspConfig
{
	
	private static string $session_id;
	// private static mixed $last_query_response;
	
	
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
		
		// self::$last_query_response = $res;
		return $res;
	}
	
	private static function restCall ($method, $data) : mixed
	{
		$res = self::rest($method, $data);
		return $res["response"];
	}
	
	public static function IspRestCall ($method, $data) : mixed
	{
		$session_id = self::getSessionId();
		$data ["session_id"] = $session_id;
		$res = static::rest($method, $data);
		if($res ["code"] !== "ok") {
			throw new ErrorException("{$res ["code"]} : {$res ["message"]}");
		}
		return $res ["response"];
	}
	
	/**
	 * for testing only
	 */
	protected static function IspRestRawCall ($method, $data) : mixed
	{
		$session_id = self::getSessionId();
		$data ["session_id"] = $session_id;
		$res = static::rest($method, $data);
		return $res;
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
		$result = static::IspRestCall('logout', []);
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
		$res = static::IspRestCall('client_get_id', [
			'sys_userid' => $sys_userid,
		]);
		return $res;
	}
	
}
