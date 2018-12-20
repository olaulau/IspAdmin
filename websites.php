<?php
require_once 'config.inc.php';
require_once 'functions.inc.php';

$websites = IspGetActiveWebsites ();

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
			foreach ($websites as $website) {
			?>
				<tr>
					<td><?= $website ?></td> <td><?= '' ?></td>
				</tr>
			<?php
			}
			?>
			</tbody>
		</table>
	</body>
</html>
