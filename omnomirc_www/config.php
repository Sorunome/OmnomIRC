<?PHP
$sql_server="localhost";
$sql_db="sql-db";
$sql_user="sql-user";
$sql_password="sql-passwd";
$signature_key="sig-key";
$hostname = "hostname";
$searchNamesUrl = "http://www.omnimaga.org/index.php?action=ezportal;sa=page;p=13&userSearch=";
$checkLoginUrl = "http://www.omnimaga.org/checkLogin.php";
$securityCookie = "__cfduid";
$curidFilePath = "/run/omnomirc_curid";

$channels=Array();
$channels[]=Array("#omnimaga",true);//Default chan, 'cause it's the first in the array.
$exChans=Array();
$exChans[]=Array("#omnimaga-ops",false);

$opGroups = array("Support Staff","President","Administrator","Coder Of Tomorrow","Anti-Riot Squad");

$defaultChan = $channels[0][0];
if (isset($_GET['js'])) {
	header('Content-type: text/javascript');
	echo "HOSTNAME = '$hostname';\nSEARCHNAMESURL='$searchNamesUrl'\nCHECKLOGINURL='$checkLoginUrl'";
}

if(isset($IRCBOT) && $IRCBOT){
	$servers = Array();
	$servers[] = Array("<first irc server>",6667);
	$servers[] = Array("<second irc server>",6667);
	$ident = Array();
	$ident[] = "PASS NONE\nUSER OmnomIRC OmnomIRC OmnomIRC :OmnomIRC\nNICK OmnomIRC\n";
	$ident[] = "PASS NONE\nUSER OmnomIRC OmnomIRC OmnomIRC :OmnomIRC\nNICK OmnomIRC\n";
	$botPasswd = "<password>";
	$topicBotNick = "TopicBot";
}
?> 