<?PHP
$sql_server="localhost";
$sql_db="sql-db";
$sql_user="sql-user";
$sql_password="sql-passwd";
$signature_key="sig-key";
$hostname = "hostname";

$channels=Array();
$channels[]=Array("#omnimaga",true);//Default chan, 'cause it's the first in the array.
$exChans=Array();
$exChans[]=Array("#omnimaga-ops",false);

if (isset($_GET['js'])) {
	header('Content-type: text/javascript');
	echo 'HOSTNAME = "hostname";';
}
?> 
