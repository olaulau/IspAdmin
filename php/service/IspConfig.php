<?php
namespace service;

use ErrorException;
use JsonException;


abstract class IspConfig
{
	
	private static string $session_id;
	
	
	private static function rest ($method, $data) : mixed
	{
		$f3 = \Base::instance();
		
		if(!is_array($data))
			return false;
		$json = json_encode($data);
		
		$isp_rest_conf = self::IspRestConf();
		$url = $isp_rest_conf ["url"];
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
		curl_setopt($curl, CURLOPT_URL, $url . "?" . $method);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		
		$result = curl_exec($curl);
		if ($result === false) {
			throw new ErrorException("error : " . curl_errno($curl) . " : " . curl_error($curl));
		}
		curl_close($curl);
		
		try {
			$res = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
		}
		catch(JsonException $ex) {
			throw new ErrorException("json decode error " . $ex->getCode() . " : " . $ex->getMessage(), 0, E_ERROR, null, null, $ex);
		}
		
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
		$isp_rest_conf = self::IspRestConf();
		$data = static::restCall('login', [
			'username' => $isp_rest_conf ["user"],
			'password' => $isp_rest_conf ["password"],
			'client_login' => false,
		]);
		if($data === false) {
			throw New ErrorException("IspConfig login failed");
		}
		return $data;
	}
	
	
	public static function IspLogout () : void
	{
		$result = static::IspRestCall('logout', []);
		if(!$result) {
			throw new ErrorException("Could not get logout result");
		}
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


	private static function IspRestConf (int $id=null) : array
	{
		$f3 = \Base::instance();

		$confs = $f3->get("tech.ispconfig.rest");
		if (!is_array($confs) || empty($confs)) {
			throw new ErrorException("empty ispconfig.rest conf");
		}
		
		if($id === null) {
			//TODO get choosen conf id from UI / Session
			$id = array_key_first($confs);
		}
		if(!is_int($id)) {
			throw new ErrorException("invalid ispconfig.rest conf id");
		}
		$conf = $confs[$id];

		if (!is_array($conf) || empty($conf)) {
			throw new ErrorException("empty ispconfig.rest conf #$id");
		}
		if (empty($conf ["user"]) || empty($conf ["password"])  || empty($conf ["url"])) {
			throw new ErrorException("incomplete ispconfig.rest conf #$id");
		}

		return $conf;
	}
	
}
