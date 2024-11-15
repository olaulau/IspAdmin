<?php
namespace service;

use model\DnsInfos;


abstract class IspcWebsite extends IspConfig
{
	/**
	 * types :
	 * array(3) { [0]=> string(5) "vhost" [9]=> string(5) "alias" [99]=> string(9) "subdomain" } 
	 */
	
	
	/**
	 * results are grouped by parent_domain_id (and will by indexed by id)
	 */
	public static function getAliases () : array
	{
		$res = static::IspRestCall('sites_web_domain_get',
		[
			'primary_id' => ['type' => 'alias'],
		]);
		
		//TODO first index result by id
		
		// group by 'parent_domain_id'
		$test = group2dArray($res, "parent_domain_id");
		var_dump($test);
		
		die; /////////////////
		
		$res = [];
		foreach ($res as $ispinfo) {
			$domain = $ispinfo['domain'];
			$parent = DnsInfos::getParent($domain);
			$res[$domain]['ispconfigInfos'] = $ispinfo;
			$res[$domain]['2LD'] = $parent;
		}
		return $res;
	}
	
	
	public static function getVhostsPlusPlus () : array
	{
		$domain_record = self::IspRestCall('sites_web_domain_get',
		[
			'primary_id' => ['type' => 'vhost'],
		]); //TODO handle type=alias
		$res = [];
		foreach ($domain_record as $ispinfo) {
			$domain = $ispinfo ['domain'];
			$parent = DnsInfos::getParent($domain);
			$res [$domain] ['ispconfigInfos'] = $ispinfo;
			$res [$domain] ['2LD'] = $parent;
		}
		return $res;
	}
	
	
	public static function getVhosts () : array
	{
		$res = self::IspRestCall('sites_web_domain_get',
		[
			'primary_id' => ['type' => 'vhost'],
		]);
		
		// index and sort by "domain_id"
		$res = index2dArray ($res, "domain_id");
		ksort($res);
		return $res;
	}
	
	
	public static function IspUpdateWebsite ($ispconfigInfos) : array
	{
		$session_id = parent::getSessionId();
		$res = static::restCall('sites_web_domain_update', [
			'session_id' => $session_id,
			'client_id' => null,
			'primary_id' => $ispconfigInfos['domain_id'],
			'params' => $ispconfigInfos
		]);
		return $res;
	}
	
	
	public static function getServersConfigs () : array
	{
		$session_id = parent::getSessionId();
		$res = static::restCall('server_get', [
			'session_id' => $session_id,
			'server_id' => [],
		]);
		return $res;
	}
	
	
	public static function getServerPhps () : array
	{
		$session_id = parent::getSessionId();
		$res = static::restCall('server_get_php_versions', [
			'session_id' => $session_id,
			'server_id' => 1, //TODO server id !!!
			"php" => "php-fpm",
			"get_full_data" => true,
		
		]);
		$res = array_column($res, null, "server_php_id");
		return $res;
	}
	
	
	public static function IspGetInfos () : array
	{
		$servers = static::getServersConfigs ();
		$websites = static::getVhostsPlusPlus ();
		$phps = static::getServerPhps ();
		return [$servers, $websites, $phps];
	} //TODO remove, this is ugly
	
}
