<?php
namespace service;


abstract class IspcDomain extends IspConfig
{
	
	public static function IspGetDomains () : array
	{
		$session_id = static::IspLogin ();
		$result = static::restCall( 'dns_zone_get', [
			'session_id' => $session_id,
			'primary_id' => [],
		]);
		return $result;
	}
	
	
	public static function IspGetDomainEntries () : array
	{
		$session_id = static::IspLogin ();
		$result = static::restCall( 'dns_a_get', [
			'session_id' => $session_id,
			'primary_id' => [],
		]);
		return $result;
	}
	
	
	public static function IspSetDomainAData ($domain_entry_id, $data) : array
	{
		$session_id = static::IspLogin ();
		
		$dns_entry = static::restCall( 'dns_a_get', [
			'session_id' => $session_id,
			'primary_id' => $domain_entry_id,
		]);
		$dns_entry["data"] = $data;
		
		$result = static::restCall( 'dns_a_update', [
			'session_id' => $session_id,
			'client_id' => null,
			'primary_id' => $domain_entry_id,
			'params' => $dns_entry,
		] );
		return $result;
	}
	
	
	public static function IspSetDomainAName ($domain_entry_id, $name) : array
	{
		$session_id = static::IspLogin ();
		
		$dns_entry = static::restCall( 'dns_a_get', [
			'session_id' => $session_id,
			'primary_id' => $domain_entry_id,
		]);
		$dns_entry["name"] = $name;
		
		$result = static::restCall( 'dns_a_update', [
			'session_id' => $session_id,
			'client_id' => null,
			'primary_id' => $domain_entry_id,
			'params' => $dns_entry,
		] );
		return $result;
	}

	
	public static function IspSetDomainParams ($domain_entry_id, $name, $data) : int
	{
		$session_id = static::IspLogin ();
		
		$dns_entry = static::restCall( 'dns_a_get', [
			'session_id' => $session_id,
			'primary_id' => $domain_entry_id,
		]);
		
		if(!empty($name) && empty($data)) { // set name
			$dns_entry["name"] = $name;
		}
		elseif(empty($name) && !empty($data)) { // set data
			$dns_entry["data"] = $data;
		}
		else {
			throw new \Exception("bad name / data parameters");
		}
		
		$result = static::restCall( "dns_" . strtolower($dns_entry["type"]) . "_update", [
			'session_id' => $session_id,
			'client_id' => null,
			'primary_id' => $domain_entry_id,
			'params' => $dns_entry,
		] );
		return $result;
	}
	
}
