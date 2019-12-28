<?php

function vd ($var) {
	echo "<pre>";
	var_dump($var);
	echo "</pre>";
}
function vdd ($var) {
	vd ($var);
	die;
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
	uasort($table, function($a, $b) use ($column, $reverse) {
		if($reverse)
			return $b[$column] <=> $a[$column];
		else
			return $a[$column] <=> $b[$column];
	});
}


function execMultipleProcesses(&$cmds, $fork=true, $wait=true) {
	// don't execute commented commands
	foreach ($cmds as $i => $cmd) {
		if (strpos($cmd, '#') === 0) {
			unset($cmds[$i]);
		}
	}
	
	if ($fork) {
		// fork processes
		$pipe = [];
		foreach ($cmds as $i => $cmd) {
			$pipe[$i] = popen($cmd, 'r');
		}
		if ($wait) {
			//  wait for them to finish
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
