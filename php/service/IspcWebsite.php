<?php
namespace service;

use ErrorException;


abstract class IspcWebsite extends IspConfig
{
	/**
	 * types :
	 * array(3) { [0]=> string(5) "vhost" [9]=> string(5) "alias" [99]=> string(9) "subdomain" } 
	 * 
	 * results are indexed by domain_id
	 */
	public static function getAll () : array
	{
		$res = static::IspRestCall('sites_web_domain_get',
		[
			'primary_id' => [],
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
	
}
