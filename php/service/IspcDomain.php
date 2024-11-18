<?php
namespace service;


abstract class IspcDomain extends IspConfig
{
	
	public static function IspGetDomains () : array
	{
		$result = static::IspRestCall( 'dns_zone_get', [
			'primary_id' => [],
		]);
		return $result;
	}
	
	
	public static function IspGetDomainEntries () : array
	{
		$result = static::IspRestCall( 'dns_a_get', [
			'primary_id' => [],
		]);
		return $result;
	}
	
	
	public static function IspSetDomainAData ($domain_entry_id, $data) : array
	{
		$dns_entry = static::IspRestCall( 'dns_a_get', [
			'primary_id' => $domain_entry_id,
		]);
		$dns_entry["data"] = $data;
		
		$result = static::IspRestCall( 'dns_a_update', [
			'client_id' => null,
			'primary_id' => $domain_entry_id,
			'params' => $dns_entry,
		] );
		return $result;
	}
	
	
	public static function IspSetDomainAName ($domain_entry_id, $name) : array
	{
		$dns_entry = static::IspRestCall( 'dns_a_get', [
			'primary_id' => $domain_entry_id,
		]);
		$dns_entry["name"] = $name;
		
		$result = static::IspRestCall( 'dns_a_update', [
			'client_id' => null,
			'primary_id' => $domain_entry_id,
			'params' => $dns_entry,
		] );
		return $result;
	}

	
	public static function IspSetDomainParams ($domain_entry_id, $name, $data) : int
	{
		$dns_entry = static::IspRestCall( 'dns_a_get', [
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
		
		$result = static::IspRestCall( "dns_" . strtolower($dns_entry["type"]) . "_update", [
			'client_id' => null,
			'primary_id' => $domain_entry_id,
			'params' => $dns_entry,
		] );
		return $result;
	}
	
}
