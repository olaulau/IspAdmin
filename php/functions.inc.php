<?php
require_once 'config.inc.php';


function vd ($var) {
	echo "<pre>";
	var_dump($var);
	echo "</pre>";
}
function vdd ($var) {
	vd ($var);
	die;
}


function restCall ($method, $data) {
	global $conf;
	
	if(!is_array($data)) return false;
	$json = json_encode($data);
	
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
	
	curl_setopt($curl, CURLOPT_URL, $conf['ispconfig']['rest']['url'] . '?' . $method);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	
	$result = curl_exec($curl);
	curl_close($curl);
	
	return $result;
}


function IspLogin () {
	global $conf;
	$result = restCall('login', array('username' => $conf['ispconfig']['rest']['user'], 'password' => $conf['ispconfig']['rest']['password'], 'client_login' => false));
	if($result) {
		$data = json_decode($result, true);
		if(!$data) return false;
		return $data['response'];
	}
	else {
		return false;
	}
}


function IspGetWebsites ($session_id) {
	$result = restCall('sites_web_domain_get', array('session_id' => $session_id, 'primary_id' => ['type' => 'vhost'])); //TODO handle type=alias
	if(!$result) die("error");
	$domain_record = json_decode($result, true)['response'];
	$res = [];
	foreach ($domain_record as $domain) {
		$res[] = $domain['domain'];
	}
	return $domain_record;
}


function IspLogout ($session_id) {
	// logout
	$result = restCall('logout', array('session_id' => $session_id));
	if(!$result) print "Could not get logout result\n";
}


function sslExpires ($domain) {
	$cmd = "echo | openssl s_client -showcerts -servername $domain -connect $domain:443 2>/dev/null | openssl x509 -inform pem -noout -text | grep 'Not After'";
	$res = shell_exec ( $cmd );
	if (!preg_match("/Not After : (.*)/", $res, $matches)) return false;
	return $matches[1];
}


function datestring_parse($date) {
	$d = new DateTime($date);
	$tz = new DateTimeZone('Europe/Paris');
	$d->setTimezone($tz);
	return $d;
}
function datetime_format($d) {
	return $d->format("d/m/Y H:i:s");
}


function sort2dArray (&$table, $column, $reverse=false) {
	usort($table, function($a, $b) use ($column, $reverse) {
		if($reverse)
			return $b[$column] <=> $a[$column];
		else
			return $a[$column] <=> $b[$column];
	});
}


function IspGetServersConfig($session_id) {
	$result = restCall('server_get', array('session_id' => $session_id, 'server_id' => []));
	if(!$result) die("error");
	$res = json_decode($result, true)['response'];
	return $res;
}


function execMultipleProcesses($cmds, $fork=true, $wait=true) {
	if ($fork) {
		// fork processes
		$pipe = [];
		foreach ($cmds as $i => $cmd) {
			$pipe[$i] = popen($cmd, 'r');
		}
		if ($wait) {
			//  wait for them to finish and get output
			foreach ($cmds as $i => $cmd) {
				pclose($pipe[$i]);
			}
		}
	}
	else {
		foreach ($cmds as $i => $cmd) {
			if (!$wait)
				$cmd = $cmd . ' &';
			exec($cmd);
		}
	}
}


function IspGetInfos () {
	$session_id = IspLogin ();
	$servers = IspGetServersConfig($session_id);
	$websites = IspGetWebsites ($session_id);
	IspLogout ($session_id);
	return [$servers, $websites];
}