<?php

function vd ($var) {
	echo "<pre>";
	var_dump($var);
	echo "</pre>";
}


function restCall($method, $data) {
	global $remote_url;
	
	if(!is_array($data)) return false;
	$json = json_encode($data);
	
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
	
	// needed for self-signed cert
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	// end of needed for self-signed cert
	
	curl_setopt($curl, CURLOPT_URL, $remote_url . '?' . $method);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	
	$result = curl_exec($curl);
	curl_close($curl);
	
	return $result;
}
