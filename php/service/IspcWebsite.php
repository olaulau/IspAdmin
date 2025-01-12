<?php
namespace service;

use ErrorException;


abstract class IspcWebsite extends IspConfig
{
	 
	 /**
	  * @param string $type vhost / alias / subdomain
	  * @return array indexed by domain_id
	  */
	public static function getAll (string $type="") : array
	{
		$params = [];
		$accepted_type = ["vhost", "alias", "subdomain"];
		if(!empty($type) && array_search($type, $accepted_type) !== false) {
			$params ["type"] = $type;
		}
		$res = static::IspRestCall('sites_web_domain_get',
		[
			'primary_id' => $params,
		]);
		
		// index and sort by "domain_id"
		$res = index2dArray ($res, "domain_id");
		ksort($res);
		return $res;
	}
	
	
	public static function IspUpdateWebsite (array $ispconfigInfos) : array
	{
		$res = static::IspRestCall('sites_web_domain_update', [
			'client_id'		=> null,
			'primary_id'	=> $ispconfigInfos ['domain_id'],
			'params'		=> $ispconfigInfos
		]);
		return $res;
	}
	
	
	public static function getServersConfigs () : array
	{
		$res = static::IspRestCall('server_get', [
			'server_id' => [],
		]);
		return $res;
	}
	
	
	private static function getServerPhps (int $server_id) : array
	{
		$res = static::IspRestCall('server_get_php_versions', [
			"server_id"		=> $server_id,
			"php"			=> "php-fpm",
			"get_full_data" => true,
		
		]);
		if ($res === false) {
			throw new ErrorException("error getting server php");
		}
		$res = array_column($res, null, "server_php_id");
		return $res;
	}
	
	/**
	 * @return array server phps for each server
	 */
	public static function getServersPhps () : array
	{
		$servers_config = self::getServersConfigs();
		$res = [];
		foreach ($servers_config as $server_id => $server) {
			$server_phps = self::getServerPhps($server_id);
			$res [$server_id] = $server_phps;
		}
		return $res;
	}
	
	/**
	 * @return array shell users groupped by parent domain id
	 */
	public static function getShellUser (int $website_id=0) : array
	{
		$params = [];
		if(!empty($website_id)) {
			$params ["parent_domain_id"] = $website_id;
		}
		$res = static::IspRestCall('sites_shell_user_get', [
			"primary_id"	=> $params
		]);
		
		$res = group2dArray($res, "parent_domain_id");
		return $res;
	}
	
}
