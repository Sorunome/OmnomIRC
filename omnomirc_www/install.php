<?PHP

	error_reporting(E_ALL);
	ini_set('display_errors', '1');
	date_default_timezone_set("America/Denver"); //Leave me alone.
	if (isset($_GET['back']))
		$back=true;
	else
		$back=false;
	session_start();
	if (!isset($_SESSION['stage']))
		$_SESSION['stage']=0;
	else
		if($back)
			$_SESSION['stage']--;
		else
			$_SESSION['stage']++;
	if ($_SESSION['stage'] < 0) $_SESSION['stage']++;
	$stage = $_SESSION['stage'];
	if (!isset($_SESSION['server']))   $_SESSION['server']   = "";
	if (!isset($_SESSION['database'])) $_SESSION['database'] = "";
	if (!isset($_SESSION['username'])) $_SESSION['username'] = "";
	if (!isset($_SESSION['password'])) $_SESSION['password'] = "";
	if (!isset($_SESSION['hostname'])) $_SESSION['hostname'] = "";
	
?>
<html>
<head>
<title>OmnomIRC Installer</title>
</head>
<body>
OmnomIRC installer<br/>
<form action="install.php" method="post">
<?PHP
	switch($stage)
	{
		case 0://Welcome
			echo "Welcome to OmnomIRC! This is the installer. To use OmnomIRC, you must have a PHP install and a MySQL server.<br/>";
			echo '<input type="submit" value="Next"/>';
		break;
		case 1://I am bender, please insert data
			echo 'I need a few pieces of information from you to set up OmnomIRC.<br/>';
			if (isset($_SESSION['error']))
			{
				echo ($_SESSION['error']);
				unset($_SESSION['error']);
			}
			echo 'SQL Info:<br/>
			<table>
			<tr><td>
				<label  for="server">Server:</label>
				</td><td>
				<input type="text" name="server" id="server" value="'.$_SESSION['server'].'"/><br/>
				</td></tr><tr><td>
				<label  for="database">Database:</label>
				</td><td>
				<input type="text" name="database" id="database" value="'.$_SESSION['database'].'"/><br/>
				</td></tr><tr><td>
				<label  for="username">Username:</label>
				</td><td>
				<input type="text" name="username" id="username" value="'.$_SESSION['username'].'"/><br/>
				</td></tr><tr><td>
				<label  for="password">Password:</label>
				</td><td>
				<input type="password" name="password" id="password"/>
				</td></tr><tr><td>
				<label  for="hostname">Hostname (the one OmnomIRC is on):</label>
				</td><td>
				<input type="text" name="hostname" id="hostname" value="'.$_SESSION['hostname'].'"/><br/>
				</td></tr>
				</table>';
				echo '<br/><input type="button" value="Back" onclick="window.location=\'install.php?back=1\';"/>';
				echo '<input type="submit" value="Next"/>';
		break;
		case 2://Confirmation
			if ($_POST['server']=="") $_SESSION['error'] = '<span style="color:#F00">Please enter a server</span><br/>';
			if ($_POST['database']=="") $_SESSION['error'] = $_SESSION['error'] . '<span style="color:#F00">Please enter a database</span><br/>';
			if ($_POST['username']=="") $_SESSION['error'] = $_SESSION['error'] . '<span style="color:#F00">Please enter a username</span><br/>';
			if ($_POST['password']=="") $_SESSION['error'] = $_SESSION['error'] . '<span style="color:#F00">Please enter a password</span><br/>';
			if ($_POST['hostname']=="") $_SESSION['error'] = $_SESSION['error'] . '<span style="color:#F00">Please enter a hostname</dpan><br/>';
			$_SESSION['server'] = $_POST['server'];
			$_SESSION['database'] = $_POST['database'];
			$_SESSION['username'] = $_POST['username'];
			$_SESSION['password'] = $_POST['password'];
			$_SESSION['hostname'] = $_POST['hostname'];
			if (isset($_SESSION['error']))
			{
				$_SESSION['stage'] = 0;
				echo '<script type="text/javascript">window.location.reload(true);</script>';
				break;
			}
			$sql_connection=mysql_connect($_POST['server'],$_POST['username'],$_POST['password']);
			if (!$sql_connection)
				$_SESSION['error'] = $_SESSION['error'] . '<span style="color:#F00">Could not connect to server. Please check your input again.</span><br/>';
			if (isset($_SESSION['error']))
			{
				$_SESSION['stage'] = 0;
				echo '<script type="text/javascript">window.location.reload(true);</script>';
			}
			if (mysql_select_db($_POST['database'],$sql_connection))
				echo '<span style="font-size;18pt;color:#AA0">WARNING</span><br/><span style="color:#AA0">Database ' . $_POST['database'] . ' already exists! If you continue, all data in it will be lost!<br/>Click back to enter a new database name</span><br/>';
			echo '<span style="color:#0A0">Your data checks out! Hit next to install.</span>';
			echo '<br/><input type="button" value="Back" onclick="window.location=\'install.php?back=1\';"/>';
			echo '<input type="submit" value="Next"/>';
		break;
		case 3://Success
			$randKey = rand(100,9999).'-'.Rand(10000,999999);
			$randKey = md5($randKey);
			$randKey = base64_encode($randKey);
			$randKey = md5($randKey);
			$config = '<?PHP
	$sql_server="'.$_SESSION['server'].'";
	$sql_db="'.$_SESSION['database'].'";
	$sql_user="'.$_SESSION['username'].'";
	$sql_password="'.$_SESSION['password'].'";
	$signature_key="'.$randKey.'";
?>';
			
			if (file_put_contents("config.php",$config))
				echo "Config written<br/>";
			else
				echo "I could not write to the config file. Please check permissions and try again.<br/>";
			$jsconfig = 'HOSTNAME="'.$_SESSION['hostname'].'";';
			if (file_put_contents("config.js",$jsconfig))
				echo "JS Config written<br/>";
			else
				echo "Could not write JS config. Please check permissions and try again.<br/>";
			$sql_connection=mysql_connect($_SESSION['server'],$_SESSION['username'],$_SESSION['password']);
			if (!mysql_query("DROP DATABASE IF EXISTS " . $_SESSION['database'],$sql_connection))
				echo "I could not drop the database. Do you have permission?<br/>";
			if (!mysql_query("CREATE DATABASE " . $_SESSION['database'],$sql_connection))
				echo "I could not drop the database. Do you have permission?<br/>";
			if (!mysql_select_db($_SESSION['database'],$sql_connection))
				echo "I could not select the database. Did it create properly?<br/>";
			$sql = file("dbinfo.sql");
			foreach ($sql as $line)
				if (!mysql_query($line,$sql_connection))
					echo "I could not import the data. Has anything else failed?<br/>";
			echo "Finished!<br/>For security purposes, it is highly reccomended that you now delete the install.php file out of the folder when done.";
		break;
		default://wat
			$_SESSION['stage']=-1;
			echo "Something went wrong. Please try again.";
		break;
	}
?>

</form>
</body>
</html>