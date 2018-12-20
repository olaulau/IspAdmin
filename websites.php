<?php
require_once 'config.inc.php';
require_once 'functions.inc.php';

$websites = IspGetActiveWebsites ();

$data = [];
foreach ($websites as $domain) {
	$data[] = [
		'domain' => $domain,
		'sslExpires' => sslExpires ($domain),
	];
}

?>
<html>
	<head>
		<title>websites</title>
	</head>
	<body>
		<table>
			<thead>
				<tr>
					<th>Domain</th> <th>SSL</th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach ($data as $row) {
			?>
				<tr>
					<td><?= $row['domain'] ?></td> <td><?= $row['sslExpires'] ?></td>
				</tr>
			<?php
			}
			?>
			</tbody>
		</table>
	</body>
</html>
