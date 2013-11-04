<?php
/*
    OmnomIRC COPYRIGHT 2010,2011 Netham45

    This file is part of OmnomIRC.

    OmnomIRC is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    OmnomIRC is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with OmnomIRC.  If not, see <http://www.gnu.org/licenses/>.
*/
include_once(realpath(dirname(__FILE__)).'/config.php');
if(strpos($_SERVER['HTTP_USER_AGENT'],'textmode;')!==false || isset($_GET['textmode'])){
	if(isset($_COOKIE[$securityCookie]))
		header('Location: '.$checkLoginUrl.'?textmode&sid='.urlencode(htmlspecialchars(str_replace(";","%^%",$_COOKIE[$securityCookie]))));
	else
		header('Location: '.$checkLoginUrl.'?textmode');
}elseif(isset($_GET['options'])){
?>
<html>
<head>
<title>OmnomIRC Options</title>
<link rel="stylesheet" type="text/css" href="style.css" />
<?php
if($externalStyleSheet!='')
	echo '<link rel="stylesheet" type="text/css" href="'.$externalStyleSheet.'" />';
?>
<script src="Omnom_Options.js"></script>
<style type="text/css">
body,td,tr,pre,table{
	font-size: 13px;font-family:verdana,sans-serif;line-height:17px;
}
td
{
	width:auto;
	height:20px;
	white-space: inherit;
	word-wrap: inherit;
}
table
{
	height:auto;
}
tr td:nth-child(4) {
	padding-left:10px;
}
tr td:nth-child(2) {
	border-right:1px solid;
}
tr td:nth-child(5) {
	border-right:1px solid;
}
</style>
<script type="text/javascript">
function warning(){
	alert("-READ THIS-\nNot all extra channels are owned and controlled by Omnimaga. We cannot be held liable for the content of them.\n\nBy using them, you agree to be governed by the rules inside them.\n\nOmnimaga rules still apply for OmnomIRC communication.");
	setOption(9,'T');
}
</script>
</head>
<!--
Options:
1 - highlight bold
2 - highlight red
3 - color names
4 - currentChannel
5 - enabled
6 - alternating line highlight
7 - enable chrome notifications
8 - ding on highlight
9 - show extra channels
10 - show timestamps
11 - show updates in status bar
12 - show smileys
13 - highlight number chars
14 - hide userlist
15 - show scrollbar
16 - enable main-window scrolling
-->
<body>
<table>
<tr><td>
Highlight Bold:</td><td> <script type="text/javascript"> document.write(getHTMLToggle(getOption(1,"T") == "T", "Yes", "No", "setOption(1,\'T\');", "setOption(1,\'F\');"));</script>
</td><td>
Highlight Red:</td><td> <script type="text/javascript"> document.write(getHTMLToggle(getOption(2,"T") == "T", "Yes", "No", "setOption(2,\'T\');", "setOption(2,\'F\');"));</script>
</td></tr><tr><td>
Colored Names:</td><td> <script type="text/javascript"> document.write(getHTMLToggle(getOption(3,"F") == "T", "Yes", "No", "setOption(3,\'T\');", "setOption(3,\'F\');"));</script>
</td><td>
Show extra Channels:</td><td> <script type="text/javascript"> document.write(getHTMLToggle(getOption(9,"F") == "T", "Yes", "No", "warning();", "setOption(9,\'F\');"));</script>
</td></tr><tr><td>
Alternating Line Highlight:</td><td> <script type="text/javascript"> document.write(getHTMLToggle(getOption(6,"T") == "T", "Yes", "No", "setOption(6,\'T\');", "setOption(6,\'F\');"));</script>
</td><td>
Enabled:</td><td> <script type="text/javascript"> document.write(getHTMLToggle(getOption(5,"T") == "T", "Yes", "No", "setOption(5,\'T\');", "setOption(5,\'F\');"));</script>
</td></tr><tr><td>
Ding on Highlight:</td><td><script type="text/javascript"> document.write(getHTMLToggle(getOption(8,"F") == "T", "Yes", "No", "setOption(8,\'T\');", "setOption(8,\'F\');"));</script>
</td><td>
Show Timestamps:</td><td><script type="text/javascript"> document.write(getHTMLToggle(getOption(10,"F") == "T", "Yes", "No", "setOption(10,\'T\');", "setOption(10,\'F\');"));</script>
</td></tr><tr><td>
Show Updates in Browser Status Bar:</td><td><script type="text/javascript"> document.write(getHTMLToggle(getOption(11,"T") == "T", "Yes", "No", "setOption(11,\'T\');", "setOption(11,\'F\');"));</script>
</td><td>
Show smileys:</td><td><script type="text/javascript"> document.write(getHTMLToggle(getOption(12,"T") == "T", "Yes", "No", "setOption(12,\'T\');", "setOption(12,\'F\');"));</script>
</td></tr><tr><td>
Hide Userlist:</td><td><script type="text/javascript"> document.write(getHTMLToggle(getOption(14,"F") == "T", "Yes", "No", "setOption(14,\'T\');", "setOption(14,\'F\');"));</script>
</td><td>
Number chars for Highlighting:
</td><td colspan="2" style="border-right:none;">
<select onchange="setOption(13,this.value);">
<script type="text/javascript">
var currentOption = getOption(13,"3");
for (var i=0;i<10;i++) {
	if  (parseInt(currentOption)==i)
		document.write("<option selected='selected' value='"+i.toString()+"'>"+(i+1).toString()+"</option>");
	else
		document.write("<option value='"+i.toString()+"'>"+(i+1).toString()+"</option>");
}
</script>
</select>
</td></tr><tr><td>
Show Scrollbar:</td><td><script type="text/javascript"> document.write(getHTMLToggle(getOption(15,"T") == "T", "Yes", "No", "setOption(15,\'T\');", "setOption(15,\'F\');"));</script>
</td><td>
Enable Scrollwheel:</td><td><script type="text/javascript"> document.write(getHTMLToggle(getOption(16,"F") == "T", "Yes", "No", "setOption(16,\'T\');", "setOption(16,\'F\');"));</script>
</td></tr>
<tr><td>Browser Notifications:</td><td style="border-right:1px solid;"><script type="text/javascript">document.write(getHTMLToggle(getOption(7,"F") == "T","Yes","No","setAllowNotification();","setOption(7,'F')"));</script>
</td></tr>
</tr>
</table>
<a href="#" onclick="clearCookies()">Clear Cookies</a>
<br/><br/>
<br/><br/>
<div style="top:100%;margin-top:-33pt;position:absolute;"><a href="index.php"><span style="font-size:30pt;">&#8592;</span><span style="font-size:18pt;top:-3pt;position:relative;">Back<span></a></div>
</body>
</html>
<?php
}elseif(isset($_GET['admin'])){
	function adminWriteConfig(){
		global $OmnomIRC_version,$sql_server,$sql_db,$sql_user,$sql_password,$signature_key,$hostname,$searchNamesUrl,$checkLoginUrl,$securityCookie,$curidFilePath,$calcKey,$externalStyleSheet,$channels,$exChans,$opGroups,$hotlinks,$ircBot_servers,$ircBot_serversT,$ircBot_ident,$ircBot_identT,$ircBot_botPasswd,$ircBot_botNick,$ircBot_topicBotNick;
		$config = '<?php
/* This is a automatically generated config-file by OmnomIRC, please use the admin pannel to edit it! */
include_once(realpath(dirname(__FILE__)).\'/Source/sql.php\');
include_once(realpath(dirname(__FILE__)).\'/Source/sign.php\');
include_once(realpath(dirname(__FILE__)).\'/Source/userlist.php\');
include_once(realpath(dirname(__FILE__)).\'/Source/cachefix.php\');

$OmnomIRC_version = "'.$OmnomIRC_version.'";
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
		if (file_put_contents('config2.php',$config)){
			echo 'Config written';
			return true;
		}
		echo 'Couldn\'t write config';
		return false;
	}
	function adminGetLinkHTML($inner,$page){
		return "<a onclick='getPage(\"$page\");return false'>$inner</a>";
	}
	if(isset($_GET['server'])){
		if(isset($_GET['nick']) && isset($_GET['sig']) && isset($_GET['id']) && isGlobalOp(base64_url_decode($_GET['nick']),base64_url_decode($_GET['sig']),$_GET['id'])){
			if(isset($_GET['page'])){
				switch($_GET['page']){
					case 'index':
						echo 'OmnomIRC Admin Pannel<br>Please note that it is still WIP<br>';
						echo "OmnomIRC Version: $OmnomIRC_version";
					break;
					case 'channels':
						if(!isset($_POST['chans']) || !isset($_POST['exChans'])){
							echo '<div style="font-weight:bold">Channels</div>';
							echo '<div id="channelCont"></div>';
							echo '<div style="font-weight:bold">extra Channels (only for irc bot)</div>';
							echo '<div id="exChannelCont"></div>';
							echo '<button onclick="saveChannels()">Save Changes</button>';
							echo '<img src="omni.png" onload="';
							echo 'channels = [';
							$chanStr = '';
							foreach($channels as $chan){
								$chanStr.='[\''.$chan[0].'\','.($chan[1]?'true':'false').'],';
							}
							echo substr($chanStr,0,-1);
							echo '];';
							echo 'exChannels = [';
							$chanStr = '';
							foreach($exChans as $chan){
								$chanStr.='[\''.$chan[0].'\','.($chan[1]?'true':'false').'],';
							}
							echo substr($chanStr,0,-1);
							echo '];drawChannels();';
							echo '" style="display:none;">';
						}else{
							$channels=Array();
							$temp = explode(';',$_POST['chans']);
							foreach($temp as $t){
								if($t && $t!=''){
									$e = explode(':',$t);
									$temp = false;
									if($e[1]=='true')
										$temp = true;
									$channels[]=Array(base64_url_decode($e[0]),$temp);
								}
							}
							$exChans=Array();
							$temp = explode(';',$_POST['exChans']);
							foreach($temp as $t){
								if($t && $t!=''){
									$e = explode(':',$t);
									$temp = false;
									if($e[1]=='true')
										$temp = true;
									$exChans[]=Array(base64_url_decode($e[0]),$temp);
								}
							}
							adminWriteConfig();
						}
					break;
					case 'hotlinks':
						if(!isset($_POST['links'])){
							echo '<div style="font-weight:bold">Hotlinks Settings</div>';
							echo '<span class="highlight">Please don\'t change the toggle, about and admin option!</span><br>';
							echo '<div id="hotlinksCont"></div>';
							echo '<button onclick="saveHotlinks()">Save Changes</button>';
							echo '<img src="omni.png" onload="';
							echo 'hotlinks=[';
							$temp = '';
							foreach($hotlinks as $h){
								$temp2 = '';
								$temp .= '[';
								foreach($h as $key => $link)
									$temp .= "['".$key."','".str_replace("'","\\'",$link)."'],";
								$temp .= substr($temp2,0,-1);
								$temp .= '],';
							}
							echo substr($temp,0,-1);
							echo '];drawHotlinks();';
							echo '" style="display:none;">';
						}else{
							$hotlinks = Array();
							$temp = explode(';',$_POST['links']);
							foreach($temp as $t){
								if($t!=''){
									$hotlinks[] = Array();
									$uemp = explode(':',$t);
									foreach($uemp as $u){
										if($u!=''){
											$vemp = explode('.',$u);
											$hotlinks[count($hotlinks)-1][base64_url_decode($vemp[0])] = base64_url_decode($vemp[1]);
										}
									}
								}
							}
							adminWriteConfig();
						}
					break;
					case 'sql':
						if(!isset($_POST['sql_db']) || !isset($_POST['sql_user']) || !isset($_POST['sql_password']) || !isset($_POST['sql_server'])){
							echo '<div style="font-weight:bold">SQL Settings</div>';
							echo '<span class="highlight">Don\'t change this unless you are <b>really</b> sure what you are doing.</span><br>';
							echo 'SQL Server:<input type="text" value="'.$sql_server.'" id="sqlServer"><br>';
							echo 'SQL Database:<input type="text" value="'.$sql_db.'" id="sqlDb"><br>';
							echo 'SQL User:<input type="text" value="'.$sql_user.'" id="sqlUser"><br>';
							echo 'SQL Password:<input type="password" id="sqlPassword"><br>';
							
							echo '<button onclick="if(document.getElementById(\'sqlPassword\').value!=\'\'){';
							echo 'setPage(\'sql\',\'sql_server=\'+base64.encode(document.getElementById(\'sqlServer\').value)';
							echo '+\'&sql_db=\'+base64.encode(document.getElementById(\'sqlDb\').value)';
							echo '+\'&sql_user=\'+base64.encode(document.getElementById(\'sqlUser\').value)';
							echo '+\'&sql_password=\'+base64.encode(document.getElementById(\'sqlPassword\').value)';
							echo ');document.getElementById(\'sqlPassword\').value=\'\';}else{alert(\'you need to set a password\');}';
							echo '">Save Changes</button>';
						}else{
							$sql_server = base64_url_decode($_POST['sql_server']);
							$sql_db = base64_url_decode($_POST['sql_db']);
							$sql_user = base64_url_decode($_POST['sql_user']);
							$sql_password = base64_url_decode($_POST['sql_password']);
							$sql_connection=mysql_connect($sql_server,$sql_user,$sql_password);
							if (!$sql_connection)
								echo 'Couldn\'t connect to sql server';
							else
								adminWriteConfig();
						}
					break;
					case 'op':
						if(!isset($_POST['opGroups'])){
							echo '<div style="font-weight:bold">Operator Settings</div>';
							echo '<div id="opGroupsCont"></div>';
							echo '<img src="omni.png" onload="';
							echo 'opGroups=[';
							$temp = '';
							foreach($opGroups as $o){
								$temp .= "'".$o."',";
							}
							echo substr($temp,0,-1);
							echo '];drawOpGroups();';
							echo '" style="display:none;">';
							echo '<button onclick="saveOp()">Save Changes</button>';
						}else{
							$opGroups = Array();
							$temp = explode(':',$_POST['opGroups']);
							foreach($temp as $t)
								if($t && $t!='')
									$opGroups[] = base64_url_decode($t);
							adminWriteConfig();
						}
					break;
					case 'irc':
						if(!isset($_POST['botCont']) || !isset($_POST['botContT']) || !isset($_POST['ircBotBotPasswd']) || !isset($_POST['ircBotBotNick']) || !isset($_POST['ircBotTopicBotNick'])){
							echo '<div style="font-weight:bold">IRC-Bot Settings</div>';
							echo '<span class="highlight">You will have to manually restart the irc bots after preforming changes here!</span><br>';
							echo 'Bot Password:<input type="text" value="'.$ircBot_botPasswd.'" id="ircBotBotPasswd"><br>';
							echo 'Main Bot Nick:<input type="text" value="'.$ircBot_botNick.'" id="ircBotBotNick"><br>';
							echo 'Topic Bot Nick:<input type="text" value="'.$ircBot_topicBotNick.'" id="ircBotTopicBotNick"><br>';
							echo '<span style="font-weight:bold">Main Bot</span><br><div id="botCont"></div>';
							echo '<span style="font-weight:bold">Topic Bot</span><br><div id="botContT"></div>';
							echo '<img src="omni.png" onload="';
							echo 'ircBot=[';
							$i = 0;
							$temp = '';
							foreach($ircBot_servers as $s){
								$temp .= '[\''.$s[0].'\','.$s[1].',\''.str_replace("\r","\\\\r",str_replace("\n","\\\\n",addslashes($ircBot_ident[$i]))).'\'],';
								$i++;
							}
							echo substr($temp,0,-1);
							echo '];';
							echo 'ircBotT=[';
							$i = 0;
							$temp = '';
							foreach($ircBot_serversT as $s){
								$temp .= '[\''.$s[0].'\','.$s[1].',\''.str_replace("\r","\\\\r",str_replace("\n","\\\\n",addslashes($ircBot_identT[$i]))).'\'],';
								$i++;
							}
							echo substr($temp,0,-1);
							echo '];drawBotSettings();';
							echo '" style="display:none;">';
							echo '<button onclick="saveBotCfg()">Save Changes</button>';
						}else{
							$ircBot_botPasswd = base64_url_decode($_POST['ircBotBotPasswd']);
							$ircBot_botNick = base64_url_decode($_POST['ircBotBotNick']);
							$ircBot_topicBotNick = base64_url_decode($_POST['ircBotTopicBotNick']);
							$ircBot_servers = Array();
							$ircBot_ident = Array();
							$temp = explode(';',$_POST['botCont']);
							$i = 0;
							foreach($temp as $t){
								if($t && $t!=''){
									$e = explode(':',$t);
									$ircBot_servers[]=Array(base64_url_decode($e[0]),(int)$e[1]);
									$ircBot_ident[]=base64_url_decode($e[2]);
									$i++;
								}
							}
							$ircBot_serversT = Array();
							$ircBot_identT = Array();
							$temp = explode(';',$_POST['botContT']);
							$i = 0;
							foreach($temp as $t){
								if($t && $t!=''){
									$e = explode(':',$t);
									$ircBot_serversT[]=Array(base64_url_decode($e[0]),(int)$e[1]);
									$ircBot_identT[]=base64_url_decode($e[2]);
									$i++;
								}
							}
							adminWriteConfig();
						}
					break;
					case 'misc':
						if(!isset($_POST['searchNamesUrl']) || !isset($_POST['checkLoginUrl']) || !isset($_POST['hostname']) || !isset($_POST['securityCookie']) ||
								!isset($_POST['curidFilePath']) || !isset($_POST['calcKey']) || !isset($_POST['externalStyleSheet'])){
							echo '<div style="font-weight:bold">Misc Settings</div>';
							echo '<span class="highlight">Some of these values shouldn\'t be messed with - beware.</span><br>';
							echo 'Hostname:<input type="text" value="'.$hostname.'" id="hostname"><br>';
							echo 'Name Search URL:<input type="text" value="'.$searchNamesUrl.'" id="searchNamesUrl"><br>';
							echo 'Check Login URL:<input type="text" value="'.$checkLoginUrl.'" id="checkLoginUrl"><br>';
							echo 'Security Cookie:<input type="text" value="'.$securityCookie.'" id="securityCookie"><br>';
							echo 'Cur-id file path:<input type="text" value="'.$curidFilePath.'" id="curidFilePath"><br>';
							echo 'Calculator key:<input type="text" value="'.$calcKey.'" id="calcKey"><br>';
							echo 'External Stylesheet:<input type="text" value="'.$externalStyleSheet.'" id="externalStyleSheet"><br>';
							
							echo '<button onclick="';
							echo 'setPage(\'misc\',\'hostname=\'+base64.encode(document.getElementById(\'hostname\').value)';
							echo '+\'&searchNamesUrl=\'+base64.encode(document.getElementById(\'searchNamesUrl\').value)';
							echo '+\'&checkLoginUrl=\'+base64.encode(document.getElementById(\'checkLoginUrl\').value)';
							echo '+\'&securityCookie=\'+base64.encode(document.getElementById(\'securityCookie\').value)';
							echo '+\'&curidFilePath=\'+base64.encode(document.getElementById(\'curidFilePath\').value)';
							echo '+\'&calcKey=\'+base64.encode(document.getElementById(\'calcKey\').value)';
							echo '+\'&externalStyleSheet=\'+base64.encode(document.getElementById(\'externalStyleSheet\').value)';
							echo ')';
							echo '">Save Changes</button>';
						}else{
							$hostname = base64_url_decode($_POST['hostname']);
							$searchNamesUrl = base64_url_decode($_POST['searchNamesUrl']);
							$checkLoginUrl = base64_url_decode($_POST['checkLoginUrl']);
							$securityCookie = base64_url_decode($_POST['securityCookie']);
							$curidFilePath = base64_url_decode($_POST['curidFilePath']);
							$calcKey = base64_url_decode($_POST['calcKey']);
							$externalStyleSheet = base64_url_decode($_POST['externalStyleSheet']);
							adminWriteConfig();
						}
					breaK;
					default:
						echo 'Invalid Page';
				}
			}else{
				echo 'Invalid arguments';
			}
		}else{
			echo 'Permission denied';
		}
	}else{
		?>
		<html style="height:100%">
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>OmnomIRC V2</title>
		<link rel="stylesheet" type="text/css" href="style.css" />
		<?php
		if($externalStyleSheet!='')
			echo '<link rel="stylesheet" type="text/css" href="'.$externalStyleSheet.'" />';
		?>
		<script src="config.php?js"></script>
		<script src="btoa.js"></script>
		<script type='text/javascript'>
			function getPage(name){
				var getPage = new XMLHttpRequest();
				getPage.onreadystatechange=function(){
					if(getPage.readyState==4 && getPage.status==200){
						document.getElementById('adminContent').innerHTML = getPage.responseText;
					}
				}
				getPage.open('GET','?admin&server&page='+name+'&nick='+base64.encode(userName)+'&sig='+base64.encode(Signature)+'&id='+omnimagaUserId,true);
				getPage.setRequestHeader('Content-type','application/x-www-form-urlencoded');
				getPage.send();
			}
			function setPage(name,value){
				var getPage = new XMLHttpRequest();
				getPage.onreadystatechange=function(){
					if(getPage.readyState==4 && getPage.status==200){
						alert(getPage.responseText);
					}
				}
				getPage.open('POST','?admin&server&page='+name+'&nick='+base64.encode(userName)+'&sig='+base64.encode(Signature)+'&id='+omnimagaUserId,true);
				getPage.setRequestHeader('Content-type','application/x-www-form-urlencoded');
				getPage.send(value);
			}
			function saveChannels(){
				var chanStr = '',
					exChanStr = '';
				if(channels.length!=0){
					for(var i=0;i<channels.length;i++){
						chanStr+=base64.encode(channels[i][0])+':'+(channels[i][1]?'true':'false')+';';
					}
				}
				if(exChannels.length!=0){
					for(var i=0;i<exChannels.length;i++){
						exChanStr+=base64.encode(exChannels[i][0])+':'+(exChannels[i][1]?'true':'false')+';';
					}
				}
				setPage('channels','chans='+chanStr+'&exChans='+exChanStr);
			}
			function deleteFromArray(a,n){
				a.splice(n,1);
				return a
			}
			function moveArrayUp(a,n){
				if(n!=0){
					var temp = a[n];
					a[n] = a[n-1];
					a[n-1] = temp;
					return a;
				}
				return a;
			}
			function moveArrayDown(a,n){
				if(n!=a.length-1){
					var temp = a[n];
					a[n] = a[n+1];
					a[n+1] = temp;
					return a;
				}
				return a;
			}
			function setChannel(num,is,type){
				if(!type){
					channels[num][1] = is;
				}else{
					exChannels[num][1] = false;
				}
				drawChannels();
			}
			function drawChannels(){
				var elem = document.getElementById('channelCont');
				elem.innerHTML = '';
				if(channels.length!=0){
					for(var i=0;i<channels.length;i++){
						elem.innerHTML += '<a onclick="channels=deleteFromArray(channels,'+i+');drawChannels();return false">x</a> '+channels[i][0]+'<input type="checkbox" '+(channels[i][1]?'checked="checked" onclick="setChannel('+i+',false,false)"':'onclick="setChannel('+i+',true,false)"')+'>'+
							' <a onclick="channels=moveArrayUp(channels,'+i+');drawChannels();return false">^</a> <a onclick="channels=moveArrayDown(channels,'+i+');drawChannels();return false">v</a><br>';
					}
				}
				elem.innerHTML += 'New Channel: <span><input type="text"><button onclick="channels.push([this.parentNode.getElementsByTagName(\'input\')[0].value,true]);this.parentNode.getElementsByTagName(\'input\')[0].value=\'\';drawChannels();">Add</button></span>'
				elem = document.getElementById('exChannelCont');
				elem.innerHTML = '';
				if(exChannels.length!=0){
					for(var i=0;i<exChannels.length;i++){
						elem.innerHTML += '<a onclick="exChannels=deleteFromArray(exChannels,'+i+');drawChannels();return false">x</a> '+exChannels[i][0]+
							' <a onclick="exChannels=moveArrayUp(exChannels,'+i+');drawChannels();return false">^</a> <a onclick="exChannels=moveArrayDown(exChannels,'+i+');drawChannels();return false">v</a><br>';
					}
				}
				elem.innerHTML += 'New Channel: <span><input type="text"><button onclick="exChannels.push([this.parentNode.getElementsByTagName(\'input\')[0].value,true]);this.parentNode.getElementsByTagName(\'input\')[0].value=\'\';drawChannels();">Add</button></span>';
				
			}
			function saveBotCfg(){
				var botStr = '',
					botTStr = '';
				if(ircBot.length!=0){
					for(var i=0;i<ircBot.length;i++){
						botStr+=base64.encode(ircBot[i][0])+':'+ircBot[i][1].toString()+':'+base64.encode(ircBot[i][2].split("\\n").join("\n").split("\\r").join("\r"))+';';
					}
				}
				if(ircBotT.length!=0){
					for(var i=0;i<ircBotT.length;i++){
						botTStr+=base64.encode(ircBotT[i][0])+':'+ircBotT[i][1].toString()+':'+base64.encode(ircBotT[i][2].split("\\n").join("\n").split("\\r").join("\r"))+';';
					}
				}
				setPage('irc','botCont='+botStr+'&botContT='+botTStr+'&ircBotBotPasswd='+base64.encode(document.getElementById('ircBotBotPasswd').value)+'&ircBotBotNick='+base64.encode(document.getElementById('ircBotBotNick').value)+
					'&ircBotTopicBotNick='+base64.encode(document.getElementById('ircBotTopicBotNick').value));
			}
			function saveBot(num,type){
				var elem,a;
				if(type){
					elem = document.getElementById('b'+num);
					a = ircBot;
				}else{
					elem = document.getElementById('bt'+num);
					a = ircBotT;
				}
				a[num][0] = elem.getElementsByTagName('input')[0].value;
				a[num][1] = parseInt(elem.getElementsByTagName('input')[1].value);
				a[num][2] = elem.getElementsByTagName('input')[2].value;
				if(type)
					ircBot = a;
				else
					ircBotT = a;
				drawBotSettings();
			}
			function editBot(num,type){
				var elem,a;
				if(type){
					elem = document.getElementById('b'+num);
					a = ircBot;
				}else{
					elem = document.getElementById('bt'+num);
					a = ircBotT;
				}
				elem.innerHTML = '<input type="text" value="'+a[num][0]+'" name="server"><input type="text" value="'+a[num][1].toString()+'" name="port">'+
					'<input type="text" value="'+a[num][2]+'" name="ident"><a onclick="saveBot('+num+','+type+');">done</a>';
			}
			function drawBotSettings(){
				var elem = document.getElementById('botCont');
				elem.innerHTML = '';
				if(ircBot.length!=0){
					for(var i=0;i<ircBot.length;i++){
						elem.innerHTML += '<span id="b'+i+'"><a onclick="ircBot=deleteFromArray(ircBot,'+i+');drawBotSettings();return false">x</a> '+ircBot[i][0]+':'+ircBot[i][1].toString()+' '+ircBot[i][2]+
							' <a onclick="editBot('+i+',true);return false">edit</a> <a onclick="ircBot=moveArrayUp(ircBot,'+i+');drawBotSettings();return false">^</a> <a onclick="ircBot=moveArrayDown(ircBot,'+i+');drawBotSettings();return false">v</a></span><br>';
					}
				}
				elem.innerHTML += '<a onclick="ircBot.push([\'&amp;lt;server&amp;gt;\',6667,\'&amp;lt;ident&amp;gt;\']);drawBotSettings();editBot(ircBot.length-1,true);return false">Add server</a>';
				elem = document.getElementById('botContT');
				elem.innerHTML = '';
				if(ircBotT.length!=0){
					for(var i=0;i<ircBotT.length;i++){
						elem.innerHTML += '<span id="bt'+i+'"><a onclick="ircBotT=deleteFromArray(ircBotT,'+i+');drawBotSettings();return false">x</a> '+ircBotT[i][0]+':'+ircBotT[i][1].toString()+' '+ircBotT[i][2]+
							' <a onclick="editBot('+i+',false);return false">edit</a> <a onclick="ircBotT=moveArrayUp(ircBotT,'+i+');drawBotSettings();return false">^</a> <a onclick="ircBotT=moveArrayDown(ircBotT,'+i+');drawBotSettings();return false">v</a></span><br>';
					}
				}
				elem.innerHTML += '<a onclick="ircBotT.push([\'&amp;lt;server&amp;gt;\',6667,\'&amp;lt;ident&amp;gt;\']);drawBotSettings();editBot(ircBotT.length-1,false);return false">Add server</a>';
			}
			function saveOp(){
				var opStr = '';
				if(opGroups.length!=0){
					for(var i=0;i<opGroups.length;i++){
						opStr+=base64.encode(opGroups[i])+':';
					}
				}
				setPage('op','opGroups='+opStr);
			}
			function drawOpGroups(){
				elem = document.getElementById('opGroupsCont');
				elem.innerHTML = '';
				if(opGroups.length!=0){
					for(var i=0;i<opGroups.length;i++){
						elem.innerHTML += '<a onclick="opGroups=deleteFromArray(opGroups,'+i+');drawOpGroups();return false">x</a> '+opGroups[i]+'<br>';
					}
				}
				elem.innerHTML += '<input id="newOpGroup" type="text"><button onclick="opGroups.push(document.getElementById(\'newOpGroup\').value);document.getElementById(\'newOpGroup\').value=\'\';drawOpGroups();">Add</button>';
			}
			function saveHotlinks(){
				var hotStr = '';
				if(hotlinks.length!=0){
					for(var i=0;i<hotlinks.length;i++){
						if(hotlinks[i].length!=0){
							for(var j=0;j<hotlinks[i].length;j++){
								if(hotlinks[i][j].length!=0){
									hotStr += base64.encode(hotlinks[i][j][0])+'.'+base64.encode(hotlinks[i][j][1])+':';
								}
							}
							hotStr+=';';
						}
					}
				}
				setPage('hotlinks','links='+hotStr);
			}
			function saveHotlink(i,j){
				var elem = document.getElementById('h'+i+':'+j);
				hotlinks[i][j][0] = elem.getElementsByTagName('input')[0].value;
				hotlinks[i][j][1] = elem.getElementsByTagName('input')[1].value;
				drawHotlinks();
			}
			function editHotlink(i,j){
				document.getElementById('h'+i+':'+j).innerHTML = '<input type="text" value="'+hotlinks[i][j][0]+'"><input type="text" value="'+hotlinks[i][j][1]+'">'+
					'<a onclick="saveHotlink('+i+','+j+');">done</a>';
			}
			function drawHotlinks(){
				elem = document.getElementById('hotlinksCont');
				elem.innerHTML = '';
				if(hotlinks.length!=0){
					for(var i=0;i<hotlinks.length;i++){
						elem.innerHTML += '<a onclick="hotlinks=deleteFromArray(hotlinks,'+i+');drawHotlinks();return false">x</a> Hotlink: '+
							' <a onclick="hotlinks=moveArrayUp(hotlinks,'+i+');drawHotlinks();return false">^</a> <a onclick="hotlinks=moveArrayDown(hotlinks,'+i+');drawHotlinks();return false">v</a><br>';
						if(hotlinks[i].length!=0){
							for(var j=0;j<hotlinks[i].length;j++){
								elem.innerHTML += '<span id="h'+i+':'+j+'" style="margin-left:50px"><a onclick="hotlinks['+i+']=deleteFromArray(hotlinks['+i+'],'+j+');drawHotlinks();return false">x</a> '+hotlinks[i][j][0]+' => '+hotlinks[i][j][1]+
									' <a onclick="editHotlink('+i+','+j+');return false">edit</a></span><br>';
							}
						}
						elem.innerHTML += '<span style="margin-left:50px"><a onclick="hotlinks['+i+'].push([\'attr\',\'value\']);drawHotlinks();editHotlink('+i+',hotlinks['+i+'].length-1);return false">Add attribute</a></span><br>';
					}
				}
				elem.innerHTML += '<a onclick="hotlinks.push([[\'inner\',\'myHotlink\']]);drawHotlinks();editHotlink(hotlinks.length-1,hotlinks[hotlinks.length-1].length-1);return false">Add hotlink</a>';
			}
			function resize(){
				var offset = 20;
				document.getElementById('container').style.height=(document.getElementsByTagName('html')[0].clientHeight-document.getElementById('adminFooter').clientHeight-offset).toString()+"px";
			}
			window.addEventListener('resize',resize,false);
		</script>
		</head>
		<body>
		<div id='container' style='overflow-y:auto;'>
		<div style='font-weight:bold;'>OmnomIRC Admin Pannel</div>
		<div><a onclick="getPage('index');return false">Index</a> | <a onclick="getPage('channels');return false">Channels</a> | <a onclick="getPage('hotlinks');return false">Hotlinks</a> | <a onclick="getPage('sql');return false">SQL</a> | 
			<a onclick="getPage('op');return false">Ops</a> | <a onclick="getPage('irc');return false">IRC</a> | <a onclick="getPage('misc');return false">Misc</a></div>
		<div id='adminContent'>Loading...</div>
		</div>
		<div id='adminFooter'><a href='.'>Back to OmnomIRC</a></div>
		<script type="text/javascript">
			function signCallback(sig,nick,id) {
				Signature = sig;
				userName = nick;
				omnimagaUserId = id;
				resize();
				getPage('index');
			}
			var script= document.createElement('script'),
				body= document.getElementsByTagName('body')[0];
			script.type= 'text/javascript';
			script.src=<?php if(isset($_COOKIE[$securityCookie])) echo '"'.$checkLoginUrl.'?sid='.urlencode(htmlspecialchars(str_replace(";","%^%",$_COOKIE[$securityCookie]))).'";'."\n"; ?>
			body.appendChild(script);
		</script>
		</body>
		</html>
		<?php
	}
}else{
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>OmnomIRC V2</title>
<link rel="stylesheet" type="text/css" href="style.css" />
<?php
if($externalStyleSheet!='')
	echo '<link rel="stylesheet" type="text/css" href="'.$externalStyleSheet.'" />';
?>
<script src="config.php?js"></script>
<script src="btoa.js"></script>
<script type="text/javascript">
	document.domain=HOSTNAME;
	
	function AJAXSend(){
		Message = document.getElementById("message").value;
		sendAJAXMessage(userName,Signature,Message,"#omnimaga",omnimagaUserId);
		oldMessages.push(Message);
		document.getElementById("message").value = "";
		document.getElementById("message").focus();
		if (oldMessages.length>20)
			oldMessages.shift();
		messageCounter = oldMessages.length;
		if (supports_html5_storage())
			localStorage.setItem("oldMessages-"+getChannelEn(),oldMessages.join("\n"));
		else
			setCookie("oldMessages-"+getChannelEn(),oldMessages.join("\n"),30);
	}
	
	function resize(){
		var offset = 42;
		if ("\v" != "v"){
			winbg2=document.getElementById("windowbg2");
			msg = document.getElementById("message");
			send = document.getElementById("send");
			newHeight = window.innerHeight - (msg.clientHeight + 14) + "px";
			winbg2.style.height = newHeight;

			//messageBox.style.height = winbg2.clientHeight - offset + "px";
			mBoxCont.style.height = winbg2.clientHeight - offset + "px";
			mBoxCont.scrollTop = mBoxCont.scrollHeight;
			//msg.style.width = mBoxCont.clientWidth - send.clientWidth - "39" + "px";
			//msg.style.left = "0px";
		}else{
			page = document.getElementsByTagName("html")[0];
			winbg2=document.getElementById("windowbg2");
			msg = document.getElementById("message");
			send = document.getElementById("send");
			winbg2.style.height = page.clientHeight - msg.clientHeight - offset + "px";
			//messageBox.style.height = winbg2.clientHeight - offset + "px";
			mBoxCont.style.height = winbg2.clientHeight - offset + "px";
			mBoxCont.scrollTop = mBoxCont.scrollHeight;
			//msg.style.width = mBoxCont.clientWidth - send.clientWidth - "39" + "px";
			//msg.style.left = "0px";
		}
	}
	window.onresize = resize;
</script>
</head>
<body style="overflow:hidden;margin:0px;padding:0px;*height:100%">
<div class="windowbg2" id="windowbg2">
<div id="Channels">
<div id="ChanListButtons">
	<span style="font-size:10pt;" class="arrowButton" onmousedown="menul=setInterval('document.getElementById(\'ChanListCont\').scrollLeft -= 9',50)" onmouseup="clearInterval(menul)" onmouseout="clearInterval(menul)">&#9668;</span>
	<span style="font-size:10pt;" class="arrowButton" onmousedown="menur=setInterval('document.getElementById(\'ChanListCont\').scrollLeft += 9',50)" onmouseup="clearInterval(menur)" onmouseout="clearInterval(menur)">&#9658;</span>
</div>

<div id="ChanListCont">
	<div id="ChanList"></div>
</div>
</div>
<div id="topicbox">
	<div id="TopicButtons">
		<span style="font-size:8pt;" class="arrowButton" onmousedown="menul=setInterval('document.getElementById(\'topicCont\').scrollLeft -= 9',50)" onmouseup="clearInterval(menul)" onmouseout="clearInterval(menul)">&#9668;</span>
		<span style="font-size:8pt;" class="arrowButton" onmousedown="menur=setInterval('document.getElementById(\'topicCont\').scrollLeft += 9',50)" onmouseup="clearInterval(menur)" onmouseout="clearInterval(menur)">&#9658;</span>
	</div>
	<div id="topicCont">
		<div id="topic" style="white-space:nowrap;"></div>
	</div>
</div>
<br/>
<br/>
<br/>
<div id="mboxCont"></div>
	<span class="arrowButtonHoriz3"><div style="font-size:12pt;width:12px;height:9pt;top:0;position:absolute;font-weight:bolder;margin-top:10pt;margin-left:-10pt;" class="arrowButtonHoriz2">&#9650;</div>
	<div style="font-size:12pt;width:12px;height:9pt;top:0;position:absolute;margin-top:10pt;margin-left:-10pt;" onmousedown="downIntM = setInterval('document.getElementById(\'mboxCont\').scrollTop -= 9;scrolledDown=false;',50);" onmouseout="clearInterval(downIntM);" onmouseup="clearInterval(downIntM);"></div></span>
	<span class="arrowButtonHoriz3"><div style="font-size:12pt;width:12px;height:9pt;bottom:9pt;position:absolute;margin-top:-10pt;margin-left:-10pt;font-weight:bolder;" class="arrowButtonHoriz2">&#9660;</div>
	<div style="font-size:12pt;width:12px;height:9pt;bottom:9pt;position:absolute;margin-top:-10pt;margin-left:-10pt;" onmousedown="upIntM = setInterval('document.getElementById(\'mboxCont\').scrollTop += 9;if (mBoxCont.scrollTop+mBoxCont.clientHeight==mBoxCont.scrollHeight)scrolledDown=true;',50);" onmouseout="clearInterval(upIntM);" onmouseup="clearInterval(upIntM);"></div></span>

<div id="UserListContainer">
	<table id="hotlinks">
		<?php
		$i = true;
		foreach($hotlinks as $link){
			if($i){
				echo '<tr>';
			}
			echo '<td><a ';
			foreach($link as $key => $value)
				if($key!='inner')
					echo $key.'="'.$value.'" ';
			echo '>'.$link['inner'].'</a></td>';
			if(!$i){
				echo '</tr>';
			}
			$i = !$i;
		}
		if(!$i){
			echo '</tr>';
		}
		?>
	</table>
	<div id="UserListInnerCont"><div id="UserList"></div></div>
	<span class="arrowButtonHoriz3"><div style="width:12px;height:9pt;top:0pt;position:absolute;font-weight:bolder;margin-top:10pt;" class="arrowButtonHoriz2">&#9650;</div>
	<div style="width:12px;height:9pt;top:0pt;position:absolute;margin-top:10pt;" onmousedown="downInt = setInterval('userListDiv.scrollTop -= 9',50);" onmouseout="clearInterval(downInt);" onmouseup="clearInterval(downInt);"></div></span>
	<span class="arrowButtonHoriz3"><div style="width:12px;height:9pt;top:100%;position:absolute;margin-top:-10pt;font-weight:bolder;" class="arrowButtonHoriz2">&#9660;</div>
	<div style="width:12px;height:9pt;top:100%;position:absolute;margin-top:-10pt;" onmousedown="upInt = setInterval('userListDiv.scrollTop += 9',50);" onmouseout="clearInterval(upInt);" onmouseup="clearInterval(upInt);"></div></span>
	</div>
</div>
</div><img id="smileyMenuButton" src="smileys/smiley.gif" style="cursor:pointer;margin-left:2px;margin-right:2px;" onclick="if(showSmileys){if(document.getElementById('smileyselect').style.display==''){document.getElementById('smileyselect').style.display='none';this.src='smileys/smiley.gif';}else{document.getElementById('smileyselect').style.display='';this.src='smileys/tongue.gif';}}"><form style="Display:inline;" name="irc" action="javascript:void(0)" onSubmit="AJAXSend()"><input autocomplete="off" accesskey="i" type="text" name="message" id="message" size="128" maxlength="256" alt="OmnomIRC" title="OmnomIRC"/><input type="submit" value="Send" id="send" /></form>
<div id="about" style="display:none;"><div style="position: relative; left: -50%;"><span style="position:absolute;z-index:9002;top:1px;right:2px"><a onclick="document.getElementById('about').style.display='none';">Close</a></span>
	<div style="text-align:center;"><img src="omnomirc.png" alt="omnomirc"></div>
	<p>OmnomIRC is developed by <a href="http://www.omnimaga.org" alt="Omnimaga" target="_blank">Omnimaga</a></p>
	<h1>Programmers</h1>
	<ul><li><a href="http://netham45.org/" target="_blank">Netham45</a></li><li><a href="http://www.sorunome.de" target="_blank">Sorunome</a></li><li><a href="http://eeems.ca/" target="_blank">Eeems</a></li></ul>
	<h1>Style</h1>
	<ul><li><a href="http://www.omnimaga.org/index.php?action=profile;u=691" target="_blank">Darl181</a></li></ul>
	<a href="https://github.com/Sorunome/OmnomIRC2" target="_blank">GitHub</a>
</div></div>
<div id="smileyselect" style="display:none;">
	<img src="smileys/smiley.gif" alt="Smiley" title="Smiley" onclick="replaceText(' :)', document.forms.irc.message); return false;">
	<img src="smileys/wink.gif" alt="Wink" title="Wink" onclick="replaceText(' ;)', document.forms.irc.message); return false;">
	<img src="smileys/cheesy.gif" alt="Cheesy" title="Cheesy" onclick="replaceText(' :D', document.forms.irc.message); return false;">
	<img src="smileys/grin.gif" alt="Grin" title="Grin" onclick="replaceText(' ;D', document.forms.irc.message); return false;">
	<img src="smileys/angry.gif" alt="Angry" title="Angry" onclick="replaceText(' &gt;:(', document.forms.irc.message); return false;">
	<img src="smileys/sad.gif" alt="Sad" title="Sad" onclick="replaceText(' :(', document.forms.irc.message); return false;">
	<img src="smileys/shocked.gif" alt="Shocked" title="Shocked" onclick="replaceText(' :o', document.forms.irc.message); return false;">
	<img src="smileys/cool.gif" alt="Cool" title="Cool" onclick="replaceText(' 8)', document.forms.irc.message); return false;">
	<img src="smileys/huh.gif" alt="Huh?" title="Huh?" onclick="replaceText(' ???', document.forms.irc.message); return false;">
	<img src="smileys/rolleyes.gif" alt="Roll Eyes" title="Roll Eyes" onclick="replaceText(' ::)', document.forms.irc.message); return false;">
	<img src="smileys/tongue.gif" alt="Tongue" title="Tongue" onclick="replaceText(' :P', document.forms.irc.message); return false;">
	<img src="smileys/embarrassed.gif" alt="Embarrassed" title="Embarrassed" onclick="replaceText(' :-[', document.forms.irc.message); return false;">
	<img src="smileys/lipsrsealed.gif" alt="Lips Sealed" title="Lips Sealed" onclick="replaceText(' :-X', document.forms.irc.message); return false;">
	<img src="smileys/undecided.gif" alt="Undecided" title="Undecided" onclick="replaceText(' :-\\', document.forms.irc.message); return false;">
	<img src="smileys/kiss.gif" alt="Kiss" title="Kiss" onclick="replaceText(' :-*', document.forms.irc.message); return false;">
	<img src="smileys/cry.gif" alt="Cry" title="Cry" onclick="replaceText(' :\'(', document.forms.irc.message); return false;">
	<img src="smileys/thumbsupsmiley.gif" alt="Good job" title="Good job" onclick="replaceText(' :thumbsup:', document.forms.irc.message); return false;">
	<img src="smileys/evil.gif" alt="Evil" title="Evil" onclick="replaceText(' &gt;:D', document.forms.irc.message); return false;">
	<img src="smileys/shocked2.gif" alt="shocked" title="shocked" onclick="replaceText(' O.O', document.forms.irc.message); return false;">
	<img src="smileys/azn.gif" alt="Azn" title="Azn" onclick="replaceText(' ^-^', document.forms.irc.message); return false;">
	<img src="smileys/alien2.gif" alt="Alien" title="Alien" onclick="replaceText(' &gt;B)', document.forms.irc.message); return false;">
	<img src="smileys/banghead.gif" alt="Frustrated" title="Frustrated" onclick="replaceText(' :banghead:', document.forms.irc.message); return false;">
	<img src="smileys/ange.gif" alt="Angel" title="Angel" onclick="replaceText(' :angel:', document.forms.irc.message); return false;">
	<img src="smileys/blah.gif" alt="Blah" title="Blah" onclick="replaceText(' ._.', document.forms.irc.message); return false;">
	<img src="smileys/devil.gif" alt="Devil" title="Devil" onclick="replaceText(' :devil:', document.forms.irc.message); return false;">
	<img src="smileys/dry.gif" alt="&lt;_&lt;" title="&lt;_&lt;" onclick="replaceText(' &lt;_&lt;', document.forms.irc.message); return false;">
	<img src="smileys/evillaugh.gif" alt="Evil Laugh" title="Evil Laugh" onclick="replaceText(' :evillaugh:', document.forms.irc.message); return false;">
	<img src="smileys/fou.gif" alt="Crazy" title="Crazy" onclick="replaceText(' :crazy:', document.forms.irc.message); return false;">
	<img src="smileys/happy0075.gif" alt="You just lost the game" title="You just lost the game" onclick="replaceText(' :hyper:', document.forms.irc.message); return false;">
	<img src="smileys/love.gif" alt="Love" title="Love" onclick="replaceText(' :love:', document.forms.irc.message); return false;">
	<img src="smileys/mad.gif" alt="Mad" title="Mad" onclick="replaceText(' :mad:', document.forms.irc.message); return false;">
	<img src="smileys/smiley_woot.gif" alt="w00t" title="w00t" onclick="replaceText(' :w00t:', document.forms.irc.message); return false;">
	<img src="smileys/psychedelicO_O.gif" alt="I must have had too much radiation for breakfast..." title="I must have had too much radiation for breakfast..." onclick="replaceText(' *.*', document.forms.irc.message); return false;">
	<img src="smileys/bigfrown.gif" alt="Big frown" title="Big frown" onclick="replaceText(' D:', document.forms.irc.message); return false;">
	<img src="smileys/XD.gif" alt="Big smile" title="Big smile" onclick="replaceText(' XD', document.forms.irc.message); return false;">
	<img src="smileys/X_X.gif" alt="x.x" title="x.x" onclick="replaceText(' x.x', document.forms.irc.message); return false;">
	<img src="smileys/ninja.gif" alt="Get Ninja'd" title="Get Ninja'd" onclick="replaceText(' :ninja:', document.forms.irc.message); return false;">
</div>
<div id="indicator" style="position:absolute;z-index:44;margin:0;padding:0;top:0;right:0;"></div>
<div id="lastSeenCont" style="display:none;"></div>

<script src="Omnom_Options.js"></script>
<script src="Omnom_Parser.js"></script>
<script src="Omnom_Tab.js"></script>
<script src="Omnom_Misc.js"></script>

<script type="text/javascript">
	startIndicator();
	
	function signCallback(sig,nick,id) {
		Signature = sig;
		userName = nick;
		omnimagaUserId = id;
		load();
	}
	resize();
	var body= document.getElementsByTagName('body')[0];
	var script= document.createElement('script');
	script.type= 'text/javascript';
	script.src=<?php if(isset($_COOKIE[$securityCookie])) echo '"'.$checkLoginUrl.'?sid='.urlencode(htmlspecialchars(str_replace(";","%^%",$_COOKIE[$securityCookie]))).'";'."\n"; ?>
	body.appendChild(script);
</script>
<audio id="ding" src="beep.wav" hidden></audio>
</body>
</html>
<?php
}
?>
