<!doctype html>
<html lang="fr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Websites</title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/twbs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="css/sticky-footer-navbar.css" rel="stylesheet">
  </head>

  <body>

    <header>
      <!-- Fixed navbar -->
      <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
        <a class="navbar-brand" href="#">Isp Admin</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
          <ul class="navbar-nav mr-auto">
            <li class="nav-item active">
              <a class="nav-link" href="#"> Websites <span class="sr-only">(current)</span></a>
            </li>
<!--             <li class="nav-item"> -->
<!--               <a class="nav-link" href="#">Link</a> -->
<!--             </li> -->
<!--             <li class="nav-item"> -->
<!--               <a class="nav-link disabled" href="#">Disabled</a> -->
<!--             </li> -->
          </ul>
<!--           <form class="form-inline mt-2 mt-md-0"> -->
<!--             <input class="form-control mr-sm-2" type="text" placeholder="Search" aria-label="Search"> -->
<!--             <button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button> -->
<!--           </form> -->
        </div>
      </nav>
    </header>

    <!-- Begin page content -->
    <main role="main" class="container">
		<table class="table table-sm table-bordered ">
			<thead class="thead-dark">
				<tr>
					<th>Domain</th>
					<th>DNS</th>
					<th>SSL</th>
					<th>HTTP</th>
					<th>PHP</th>
				</tr>
			</thead>
			<tbody class="table-hover">
			<?php
			foreach ($websites as $website) {
			?>
				<tr>
					<td class="<?= $website['active']==='n' ? 'table-danger' : '' ?>"> <a href="https://<?= $website['domain'] ?>/"><?= $website['domain'] ?></a> </td>
					<td class="table-<?= $website['dnsInfos']->getLabelType() ?>"><?= $website['dnsInfos']->getLabelString() ?></td>
					<td class="table-<?= $website['sslInfos']->getLabelType() ?>"><?= $website['sslInfos']->getLabelString() ?></td>
					<td class="table-<?= $website['httpInfos']->getLabelType() ?>"><?= $website['httpInfos']->getLabelString() ?></td>
					<td class="<?= !empty($website['php_label_type']) ? 'table-'.$website['php_label_type'] : '' ?>"> <?= $website['php_label_string'] ?> </td>
				</tr>
			<?php
			}
			?>
			</tbody>
		</table>
    </main>

    <footer class="footer">
      <div class="container">
        <span class="text-muted">Place sticky footer content here.</span>
      </div>
    </footer>

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="vendor/components/jquery/jquery.min.js"></script>
    <script src="vendor/twbs/bootstrap/dist/js/bootstrap.min.js"></script>
  </body>
</html>
