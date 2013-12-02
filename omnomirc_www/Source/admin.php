<?php
function adminWriteConfig($output=true){
	global $OmnomIRC_version,$oirc_installed,$sql_server,$sql_db,$sql_user,$sql_password,$signature_key,$hostname,$searchNamesUrl,$checkLoginUrl,$securityCookie,$curidFilePath,$calcKey,$externalStyleSheet,$channels,$exChans,$opGroups,$hotlinks,$ircBot_servers,$ircBot_serversT,$ircBot_ident,$ircBot_identT,$ircBot_botPasswd,$ircBot_botNick,$ircBot_topicBotNick;
	$config = '<?php
/* This is a automatically generated config-file by OmnomIRC, please use the admin pannel to edit it! */
include_once(realpath(dirname(__FILE__)).\'/Source/sql.php\');
include_once(realpath(dirname(__FILE__)).\'/Source/sign.php\');
include_once(realpath(dirname(__FILE__)).\'/Source/userlist.php\');
include_once(realpath(dirname(__FILE__)).\'/Source/admin.php\');
include_once(realpath(dirname(__FILE__)).\'/Source/cachefix.php\');

$OmnomIRC_version = "'.$OmnomIRC_version.'";
$oirc_installed = '.($oirc_installed?'true':'false').';
$sql_server="'.$sql_server.'";
$sql_db="'.$sql_db.'";
$sql_user="'.$sql_user.'";
$sql_password="'.$sql_password.'";
$signature_key="'.$signature_key.'";
$hostname="'.$hostname.'";
$searchNamesUrl="'.$searchNamesUrl.'";
$checkLoginUrl="'.$checkLoginUrl.'";
$curidFilePath="'.$curidFilePath.'";
$calcKey="'.$calcKey.'";
$externalStyleSheet="'.$externalStyleSheet.'";
$securityCookie="'.$securityCookie.'";

$channels=Array();
';foreach($channels as $chan){if($chan && is_array($chan)){$config .= '$channels[]=Array("'.$chan[0].'",'.((bool)$chan[1]?'true':'false').");\n";}}$config .= '
$exChans=Array();
';foreach($exChans as $chan){if($chan && is_array($chan)){$config .= '$exChans[]=Array("'.$chan[0].'",'.((bool)$chan[1]?'true':'false').");\n";}}$config .= '
$opGroups = array(';$temp = '';foreach($opGroups as $g){$temp .= '"'.$g.'",';}$config .= substr($temp,0,-1);$config.=');

$hotlinks=Array();
';foreach($hotlinks as $link){$config .= '$hotlinks[]=Array('."\n";$temp = '';foreach($link as $key => $value)$temp .= "\t'".$key."' => '".str_replace("'","\\'",$value)."',\n";$config .= substr($temp,0,-2);$config .= "\n);\n";}$config.='
$defaultChan = $channels[0][0];
if(isset($_GET[\'js\'])){
	header(\'Content-type: text/javascript\');
	echo "HOSTNAME = \'$hostname\';\nSEARCHNAMESURL=\'$searchNamesUrl\';";
}

$ircBot_servers = Array();
';foreach($ircBot_servers as $s){if($s && is_array($s)){$config .= '$ircBot_servers[]=Array("'.$s[0].'",'.$s[1].");\n";}}$config .= '
$ircBot_serversT = Array();
';foreach($ircBot_serversT as $s){if($s && is_array($s)){$config .= '$ircBot_serversT[]=Array("'.$s[0].'",'.$s[1].");\n";}}$config .= '
$ircBot_ident = Array();
';foreach($ircBot_ident as $i){$config .= '$ircBot_ident[]="'.str_replace("\r","\\r",str_replace("\n","\\n",addslashes($i)))."\";\n";}$config .= '
$ircBot_identT = Array();
';foreach($ircBot_identT as $i){$config .= '$ircBot_identT[]="'.str_replace("\r","\\r",str_replace("\n","\\n",addslashes($i)))."\";\n";}$config .= '
$ircBot_botPasswd="'.$ircBot_botPasswd.'";
$ircBot_botNick="'.$ircBot_botNick.'";
$ircBot_topicBotNick="'.$ircBot_topicBotNick.'";
?>';
	if(file_put_contents('config.php',$config)){
		if($output)
			echo 'Config written';
		return true;
	}
	if($output)
		echo 'Couldn\'t write config';
	return false;
}
function getRandKey(){
	$randKey = rand(100,9999).'-'.Rand(10000,999999);
	$randKey = md5($randKey);
	$randKey = base64_encode($randKey);
	return md5($randKey);
}
?>