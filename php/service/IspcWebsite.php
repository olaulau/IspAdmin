<?php
namespace service;

use ErrorException;


abstract class IspcWebsite extends IspConfig
{

	public static function get (int $id) : array
	{
		$res = static::IspRestCall('sites_web_domain_get',
		[
			"primary_id" => [
				"domain_id"	=> $id,
			],
		]);
		
		if(count($res) > 1) {
			throw new ErrorException("sites_web_domain_get returned too much results");
		}
		if(count($res) === 1) {
			return $res [0];
		}
		else {
			return null;
		}
		return $res;
	}
	
	
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
	
	
	/**
	 * @return array servers indexed by id, or the specified server
	 */
	public static function getServersConfigs (int $server_id=0) : array
	{
		$params = [];
		if(!empty($server_id)) {
			$params ["server_id"] = $server_id;
		}
		
		$res = static::IspRestCall('server_get', $params);
		return $res;
	}
	
	
	public static function getServerPhps (int $server_id) : array
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
	 * @return array shell users (grouped by parent domain id if no parameter specified)
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
		
		if(empty($website_id)) {
			$res = group2dArray($res, "parent_domain_id");
		}
		return $res;
	}
	
}
