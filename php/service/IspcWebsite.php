<?php
namespace service;

use model\DnsInfos;


abstract class IspcWebsite extends IspConfig
{
	
	public static function IspGetWebsites () : array
	{
		$session_id = parent::getSessionId();
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
	
	
	public static function IspGetServersConfig() : array
	{
		$session_id = parent::getSessionId();
		$res = static::restCall('server_get', [
			'session_id' => $session_id,
			'server_id' => [],
		]);
		return $res;
	}
	
	
	public static function IspGetServersPhps() : array
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
		$session_id = parent::getSessionId();
		$servers = static::IspGetServersConfig($session_id);
		$websites = static::IspGetWebsites ($session_id);
		$phps = static::IspGetServersPhps ($session_id);
		parent::IspLogout ();
		return [$servers, $websites, $phps];
	}
	
}
