<?php
require_once 'config.inc.php';
require_once 'functions.inc.php';

$websites = IspGetActiveWebsites ();

// fork processes to query sslExpires simultaneously
foreach ($websites as $website) {
	$pipe[$website['domain']] = popen('php ./sslExpires.php ' . $website['domain'], 'r');
}

// wait for them to finish
foreach ($websites as &$website) {
	$website['sslExpires'] = datestring_parse (fgets ($pipe[$website['domain']]));
	pclose($pipe[$website['domain']]);
}
unset($website);

// sort table by sslExpires
sort2dArray ($websites, 'sslExpires', true);

?>
<html>
	<head>
		<title>websites</title>
	</head>
	<body>
		<table>
			<thead>
				<tr>
					<th>Domain</th>
					<th>SSL</th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach ($websites as $website) {
			?>
				<tr>
					<td><?= $website['domain'] ?></td>
					<td><?= datetime_format ($website['sslExpires']) ?></td>
				</tr>
			<?php
			}
			?>
			</tbody>
		</table>
	</body>
</html>
