<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="description" content="">
		<meta name="author" content="">
		<link rel="icon" href="https://www.omnimaga.org/favicon.ico">

		<title>OmnomIRC</title>

		<!-- Bootstrap core CSS -->
		<link href="bootstrap.min.css" rel="stylesheet">

		<!-- Custom styles for this template -->
		<link href="styles.css" rel="stylesheet">

		<!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
		<!--[if lt IE 9]>
			<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
			<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
		<![endif]-->
	</head>

	<body>

		<div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
			<div class="container">
				<div class="navbar-header">
					<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-collapse">
						<span class="sr-only">Toggle navigation</span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</button>
					<a class="navbar-brand" href="#">OmnomIRC</a>
				</div>
				<div class="navbar-collapse collapse">
					<ul class="nav navbar-nav">
						<li class="active"><a href="#">Home</a></li>
						<li><a href="https://github.com/Sorunome/OmnomIRC/releases">Downloads</a></li>
						<li><a href="https://github.com/Sorunome/OmnomIRC">GitHub</a></li>
						<li><a href="https://github.com/Sorunome/OmnomIRC/wiki">Documentation</a></li>
						<li><a href="https://www.omnimaga.org/omnomirc-and-spybot45-development/">Support</a></li>
					</ul>
				</div><!--/.navbar-collapse -->
			</div>
		</div>

		<!-- Main jumbotron for a primary marketing message or call to action -->
		<div class="jumbotron">
			<div class="container">
				<h1>OmnomIRC</h1>
				<p>OmnomIRC is a fully customizable and open source IRC-style chatbox software, ready to be integrated into your forum or website. Supports SMF, myBB, AcmlmBoard XD and phpBB3!</p>
				<img alt="oirc" src="oirc.png"><br>
				<?php
				function file_get_contents_curl($url){
					$ch = curl_init();
					curl_setopt($ch,CURLOPT_HEADER,0);
					curl_setopt($ch,CURLOPT_RETURNTRANSFER,1); //Set curl to return the data instead of printing it to the browser.
					curl_setopt($ch,CURLOPT_URL,$url);
					curl_setopt($ch,CURLOPT_USERAGENT,'Ponies/42.69.1337');
					$data = curl_exec($ch);
					curl_close($ch);
					return $data;
				}
				$latest = json_decode(file_get_contents_curl('https://api.github.com/repos/Sorunome/OmnomIRC/tags'),true)[0];
				echo '<p>Latest release: <a href="https://github.com/Sorunome/OmnomIRC/releases/tag/'.$latest['name'].'">'.$latest['name'].'</a></p>'.
					'<p>Downloads: <a class="btn btn-primary btn-lg" role="button" href="'.$latest['zipball_url'].'">.zip &raquo;</a> <a class="btn btn-primary btn-lg" role="button" href="'.$latest['tarball_url'].'">.tar.gz &raquo;</a></p>'.
					'<p>Release Notes:</p>'.
					'<p>'.json_decode(file_get_contents('https://omnomirc.omnimaga.org/getReleaseNotes.php?version='.$latest['name']),true)['notes'].'</p>';
				?>
				
			</div>
		</div>

		<div class="container">
			<!-- Example row of columns -->
			<div class="row">
				<div class="col-md-4">
					<h2>Features</h2>
					<p>
						<ul>
							<li>Multiple channels</li>
							<li>Multi-network support</li>
							<li>Integration with IRC and Calcnet</li>
							<li>Integration with your forum's authentication</li>
							<li>Automatic updates</li>
							<li>IRC-like experience</li>
							<li>A whole ton of features</li>
							<li>Websockets with fallback</li>
						</ul>
					</p>
					<!--p><a class="btn btn-default" href="#" role="button">View details &raquo;</a></p-->
				</div>
				<div class="col-md-4">
					<h2>Installation</h2>
					<p>Just dump all the files in omnomirc_www into a public directory, visit it via your web-browser and follow the instructions there.</p>
					<!--p><a class="btn btn-default" href="#" role="button">View details &raquo;</a></p-->
				</div>
				<div class="col-md-4">
					<h2>Forum mods</h2>
					<ul>
						<li>SMF 2.x</li>
						<li>phpBB 3</li>
						<li>ABXD</li>
					</ul>
				</div>
			</div>

			<hr>

			<footer>
				<p>&copy; <a href="https://www.omnimaga.org">Netham45, Sorunome, Eeems, juju2143</a> 2010-2015</p>
			</footer>
		</div> <!-- /container -->


		<!-- Bootstrap core JavaScript
		================================================== -->
		<!-- Placed at the end of the document so the pages load faster -->
		<script src="jquery.min.js"></script>
		<script src="bootstrap.min.js"></script>
	</body>
</html>
