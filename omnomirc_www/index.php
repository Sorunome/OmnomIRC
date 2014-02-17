<?php
/*
    OmnomIRC COPYRIGHT 2010,2011 Netham45
                       2012-2014 Sorunome

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
error_reporting(E_ALL);
ini_set('display_errors','1');
include_once(realpath(dirname(__FILE__)).'/config.php');
if(strpos($_SERVER['HTTP_USER_AGENT'],'textmode;')!==false || isset($_GET['textmode'])){
	if(isset($_COOKIE[$config['security']['cookie']]))
		header('Location: '.$config['settings']['checkLoginUrl'].'?textmode&sid='.urlencode(htmlspecialchars(str_replace(";","%^%",$_COOKIE[$config['security']['cookie']]))));
	else
		header('Location: '.$config['settings']['checkLoginUrl'].'?textmode');
}elseif(isset($_GET['options'])){
?>
<html>
<head>
<title>OmnomIRC Options</title>
<link rel="icon" type="image/png" href="omni.png">
<link rel="stylesheet" type="text/css" href="style.css" />
<?php
if($config['settings']['externalStyleSheet']!='')
	echo '<link rel="stylesheet" type="text/css" href="'.$config['settings']['externalStyleSheet'].'" />';

?>
<script src="btoa.js"></script>
<script type="text/javascript" src="jquery-1.11.0.min.js"></script>
<script src="omnomirc.js"></script>
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
<div style="font-size:20px;font-weight:bold;margin-top:5px;">OmnomIRC Options</div>
<div id='options'></div>
<div style="top:100%;margin-top:-33pt;position:absolute;"><a href="index.php"><span style="font-size:30pt;">&#8592;</span><span style="font-size:18pt;top:-3pt;position:relative;">Back<span></a></div>
</body>
</html>
<?php
}elseif(isset($_GET['admin']) || !$config['info']['installed']){
	include_once(realpath(dirname(__FILE__)).'/Source/admin.php');
	if(isset($_GET['server'])){
		if(!$config['info']['installed'] || (isset($_GET['nick']) && isset($_GET['sig']) && isset($_GET['id']) && isGlobalOp(base64_url_decode($_GET['nick']),base64_url_decode($_GET['sig']),$_GET['id']))){
			if($config['security']['sigKey']==''){
				$config['security']['sigKey'] = getRandKey();
				adminWriteConfig(false);
			}
			if($config['security']['calcKey']==''){
				$config['security']['calcKey'] = getRandKey();
				adminWriteConfig(false);
			}
			if($config['irc']['password']==''){
				$config['irc']['password'] = getRandKey();
				adminWriteConfig(false);
			}
			if(isset($_GET['page'])){
				switch($_GET['page']){
					case 'index':
						if((!(isset($_POST['install']) && !$config['info']['installed'])) && !isset($_POST['backup'])){
							echo '<b>OmnomIRC Admin Pannel</b><br>';
							echo 'OmnomIRC Version: '.$config['info']['version'].'<br>';
							echo '<button onclick="setPage(\'index\',\'backup=1\');">Back up config</button><br>';
							if(!$config['info']['installed']){
								echo '<span class="highlight">You are currently in installation mode!</span><br>';
								echo '<button onclick="setPage(\'index\',\'install=1\');">Install</button>';
							}
						}elseif((isset($_POST['install']) && !$config['info']['installed'])){
							$queries = explode(";",str_replace("\n","",file_get_contents(realpath(dirname(__FILE__)).'/omnomirc.sql')));
							foreach($queries as $query){
								$sql->query($query);
							}
							$config['info']['installed'] = true;
							adminWriteConfig(false);
							echo 'Successfully installed OmnomIRC!';
						}elseif(isset($_POST['backup'])){
							if(file_put_contents(realpath(dirname(__FILE__)).'/config.backup.php',file_get_contents(realpath(dirname(__FILE__)).'/config.php')))
								echo 'Backed up!';
							else
								echo 'Couldn\'t write backup file!';
						}
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
							foreach($config['channels'] as $chan){
								$chanStr.='[\''.$chan['chan'].'\','.($chan['visible']?'true':'false').'],';
							}
							echo substr($chanStr,0,-1);
							echo '];';
							echo 'exChannels = [';
							$chanStr = '';
							foreach($config['exChans'] as $chan){
								$chanStr.='[\''.$chan['chan'].'\','.($chan['visible']?'true':'false').'],';
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
									$channels[]=Array('chan' => base64_url_decode($e[0]),'visible' => $temp);
								}
							}
							$config['channels'] = $channels;
							$exChans=Array();
							$temp = explode(';',$_POST['exChans']);
							foreach($temp as $t){
								if($t && $t!=''){
									$e = explode(':',$t);
									$temp = false;
									if($e[1]=='true')
										$temp = true;
									$exChans[]=Array('chan' => base64_url_decode($e[0]),'visible' => $temp);
								}
							}
							$config['exChans'] = $exChans;
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
							foreach($config['hotlinks'] as $h){
								$temp2 = '';
								$temp .= '[';
								foreach($h as $key => $link)
									$temp2 .= "['".$key."','".str_replace("'","\\'",$link)."'],";
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
							$config['hotlinks'] = $hotlinks;
							adminWriteConfig();
						}
					break;
					case 'sql':
						if(!isset($_POST['sql_db']) || !isset($_POST['sql_user']) || !isset($_POST['sql_password']) || !isset($_POST['sql_server'])){
							echo '<div style="font-weight:bold">SQL Settings</div>';
							echo '<span class="highlight">Don\'t change this unless you are <b>really</b> sure what you are doing.</span><br>';
							echo 'SQL Server:<input type="text" value="'.$config['sql']['server'].'" id="sqlServer"><br>';
							echo 'SQL Database:<input type="text" value="'.$config['sql']['db'].'" id="sqlDb"><br>';
							echo 'SQL User:<input type="text" value="'.$config['sql']['user'].'" id="sqlUser"><br>';
							echo 'SQL Password:<input type="password" id="sqlPassword"><br>';
							
							echo '<button onclick="if(document.getElementById(\'sqlPassword\').value!=\'\'){';
							echo 'setPage(\'sql\',\'sql_server=\'+base64.encode(document.getElementById(\'sqlServer\').value)';
							echo '+\'&sql_db=\'+base64.encode(document.getElementById(\'sqlDb\').value)';
							echo '+\'&sql_user=\'+base64.encode(document.getElementById(\'sqlUser\').value)';
							echo '+\'&sql_password=\'+base64.encode(document.getElementById(\'sqlPassword\').value)';
							echo ');document.getElementById(\'sqlPassword\').value=\'\';}else{alert(\'you need to set a password\');}';
							echo '">Save Changes</button>';
						}else{
							$config['sql']['server'] = base64_url_decode($_POST['sql_server']);
							$config['sql']['db'] = base64_url_decode($_POST['sql_db']);
							$config['sql']['user'] = base64_url_decode($_POST['sql_user']);
							$config['sql']['passwd'] = base64_url_decode($_POST['sql_password']);
							$sql_connection=@mysqli_connect($config['sql']['server'],$config['sql']['user'],$config['sql']['passwd'],$config['sql']['db']);
							if (mysqli_connect_errno($sql_connection)!=0) 
								die('Could not connect to SQL DB: '.mysqli_connect_errno($sql_connection).' '.mysqli_connect_error($sql_connection));
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
							foreach($config['opGroups'] as $o){
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
							$config['opGroups'] = $opGroups;
							adminWriteConfig();
						}
					break;
					case 'irc':
						if(!isset($_POST['botCont']) || !isset($_POST['botContT']) || !isset($_POST['ircBotBotPasswd']) || !isset($_POST['ircBotBotNick']) || !isset($_POST['ircBotTopicBotNick'])){
							echo '<div style="font-weight:bold">IRC-Bot Settings</div>';
							echo '<span class="highlight">You will have to manually restart the irc bots after preforming changes here!</span><br>';
							echo 'Bot Password:<input type="text" value="'.$config['irc']['password'].'" id="ircBotBotPasswd"><br>';
							echo 'Main Bot Nick:<input type="text" value="'.$config['irc']['main']['nick'].'" id="ircBotBotNick"><br>';
							echo 'Topic Bot Nick:<input type="text" value="'.$config['irc']['topic']['nick'].'" id="ircBotTopicBotNick"><br>';
							echo '<span style="font-weight:bold">Main Bot</span><br><div id="botCont"></div>';
							echo '<span style="font-weight:bold">Topic Bot</span><br><div id="botContT"></div>';
							echo '<img src="omni.png" onload="';
							echo 'ircBot=[';
							$temp = '';
							foreach($config['irc']['main']['servers'] as $s){
								$temp .= '[\''.$s['server'].'\','.$s['port'].',\''.$s['nickserv'].'\','.$s['network'].'],';
							}
							echo substr($temp,0,-1);
							echo '];';
							echo 'ircBotT=[';
							$temp = '';
							foreach($config['irc']['topic']['servers'] as $s){
								$temp .= '[\''.$s['server'].'\','.$s['port'].',\''.$s['nickserv'].'\','.$s['network'].'],';
							}
							echo substr($temp,0,-1);
							echo '];drawBotSettings();';
							echo '" style="display:none;">';
							echo '<button onclick="saveBotCfg()">Save Changes</button>';
						}else{
							$config['irc']['password'] = base64_url_decode($_POST['ircBotBotPasswd']);
							$config['irc']['main']['nick'] = base64_url_decode($_POST['ircBotBotNick']);
							$config['irc']['topic']['nick'] = base64_url_decode($_POST['ircBotTopicBotNick']);
							$ircBot_servers = Array();
							$temp = explode(';',$_POST['botCont']);
							foreach($temp as $t){
								if($t && $t!=''){
									$e = explode(':',$t);
 									$ircBot_servers[]=Array('server' => base64_url_decode($e[0]),'port' => (int)$e[1],'nickserv' => base64_url_decode($e[2]),'network' => (int)$e[3]);
								}
							}
							$ircBot_serversT = Array();
							$temp = explode(';',$_POST['botContT']);
							$i = 0;
							foreach($temp as $t){
								if($t && $t!=''){
									$e = explode(':',$t);
									$ircBot_serversT[]=Array('server' => base64_url_decode($e[0]),'port' => (int)$e[1],'nickserv' => base64_url_decode($e[2]),'network' => (int)$e[3]);
								}
							}
							$config['irc']['main']['servers'] = $ircBot_servers;
							$config['irc']['topic']['servers'] = $ircBot_serversT;
							adminWriteConfig();
						}
					break;
					case 'misc':
						if(!isset($_POST['checkLoginUrl']) || !isset($_POST['hostname']) || !isset($_POST['securityCookie']) ||
								!isset($_POST['curidFilePath']) || !isset($_POST['calcKey']) || !isset($_POST['externalStyleSheet'])){
							echo '<div style="font-weight:bold">Misc Settings</div>';
							echo '<span class="highlight">Some of these values shouldn\'t be messed with - beware.</span><br>';
							echo 'Hostname:<input type="text" value="'.$config['settings']['hostname'].'" id="hostname"><br>';
							echo 'Check Login URL:<input type="text" value="'.$config['settings']['checkLoginUrl'].'" id="checkLoginUrl"><br>';
							echo 'Security Cookie:<input type="text" value="'.$config['security']['cookie'].'" id="securityCookie"><br>';
							echo 'Cur-id file path:<input type="text" value="'.$config['settings']['curidFilePath'].'" id="curidFilePath"><br>';
							echo 'Calculator key:<input type="text" value="'.$config['security']['calcKey'].'" id="calcKey"><br>';
							echo 'External Stylesheet:<input type="text" value="'.$config['settings']['externalStyleSheet'].'" id="externalStyleSheet"><br>';
							
							echo '<button onclick="';
							echo 'setPage(\'misc\',\'hostname=\'+base64.encode(document.getElementById(\'hostname\').value)';
							echo '+\'&checkLoginUrl=\'+base64.encode(document.getElementById(\'checkLoginUrl\').value)';
							echo '+\'&securityCookie=\'+base64.encode(document.getElementById(\'securityCookie\').value)';
							echo '+\'&curidFilePath=\'+base64.encode(document.getElementById(\'curidFilePath\').value)';
							echo '+\'&calcKey=\'+base64.encode(document.getElementById(\'calcKey\').value)';
							echo '+\'&externalStyleSheet=\'+base64.encode(document.getElementById(\'externalStyleSheet\').value)';
							echo ')';
							echo '">Save Changes</button>';
						}else{
							$config['settings']['hostname'] = base64_url_decode($_POST['hostname']);
							$config['settings']['checkLoginUrl'] = base64_url_decode($_POST['checkLoginUrl']);
							$config['security']['cookie'] = base64_url_decode($_POST['securityCookie']);
							$config['settings']['curidFilePath'] = base64_url_decode($_POST['curidFilePath']);
							$config['security']['calcKey'] = base64_url_decode($_POST['calcKey']);
							$config['settings']['externalStyleSheet'] = base64_url_decode($_POST['externalStyleSheet']);
							adminWriteConfig();
						}
					break;
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
		<link rel="icon" type="image/png" href="omni.png">
		<link rel="stylesheet" type="text/css" href="style.css" />
		<?php
		if($config['settings']['externalStyleSheet']!='')
			echo '<link rel="stylesheet" type="text/css" href="'.$config['settings']['externalStyleSheet'].'" />';
		?>
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
						botStr+=base64.encode(ircBot[i][0])+':'+ircBot[i][1].toString()+':'+base64.encode(ircBot[i][2].toString())+':'+ircBot[i][3].toString()+';';
					}
				}
				if(ircBotT.length!=0){
					for(var i=0;i<ircBotT.length;i++){
						botTStr+=base64.encode(ircBotT[i][0])+':'+ircBotT[i][1].toString()+':'+base64.encode(ircBotT[i][2].toString())+':'+ircBotT[i][3].toString()+';';
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
				a[num][3] = parseInt(elem.getElementsByTagName('input')[3].value);
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
					'<input type="text" value="'+a[num][2]+'" name="nickserv"><input type="number" value="'+a[num][3].toString()+'" name="network"><a onclick="saveBot('+num+','+type+');">done</a>';
			}
			function drawBotSettings(){
				var elem = document.getElementById('botCont');
				elem.innerHTML = '';
				if(ircBot.length!=0){
					for(var i=0;i<ircBot.length;i++){
						elem.innerHTML += '<span id="b'+i+'"><a onclick="ircBot=deleteFromArray(ircBot,'+i+');drawBotSettings();return false">x</a> '+ircBot[i][0]+':'+ircBot[i][1].toString()+' '+ircBot[i][2]+' '+ircBot[i][3].toString()+
							' <a onclick="editBot('+i+',true);return false">edit</a> <a onclick="ircBot=moveArrayUp(ircBot,'+i+');drawBotSettings();return false">^</a> <a onclick="ircBot=moveArrayDown(ircBot,'+i+');drawBotSettings();return false">v</a></span><br>';
					}
				}
				elem.innerHTML += '<a onclick="ircBot.push([\'&amp;lt;server&amp;gt;\',6667,\'\',4]);drawBotSettings();editBot(ircBot.length-1,true);return false">Add server</a>';
				elem = document.getElementById('botContT');
				elem.innerHTML = '';
				if(ircBotT.length!=0){
					for(var i=0;i<ircBotT.length;i++){
						elem.innerHTML += '<span id="bt'+i+'"><a onclick="ircBotT=deleteFromArray(ircBotT,'+i+');drawBotSettings();return false">x</a> '+ircBotT[i][0]+':'+ircBotT[i][1].toString()+' '+ircBotT[i][2]+' '+ircBotT[i][3].toString()+
							' <a onclick="editBot('+i+',false);return false">edit</a> <a onclick="ircBotT=moveArrayUp(ircBotT,'+i+');drawBotSettings();return false">^</a> <a onclick="ircBotT=moveArrayDown(ircBotT,'+i+');drawBotSettings();return false">v</a></span><br>';
					}
				}
				elem.innerHTML += '<a onclick="ircBotT.push([\'&amp;lt;server&amp;gt;\',6667,\'\',4]);drawBotSettings();editBot(ircBotT.length-1,false);return false">Add server</a>';
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
		<div id='adminFooter'><a href='index.php'>Back to OmnomIRC</a></div>
		<script type="text/javascript" src="jquery-1.11.0.min.js"></script>
		<script type="text/javascript">
			function signCallback(sig,nick,id) {
				Signature = sig;
				userName = nick;
				omnimagaUserId = id;
				resize();
				getPage('index');
			}
			<?php
			if($config['info']['installed']){
			?>
			$.getJSON('config.php?js',function(data){
				HOSTNAME = data.hostname;
				$.getJSON(<?php if(isset($_COOKIE[$config['security']['cookie']]))echo '"'.$config['settings']['checkLoginUrl'].'?sid='.urlencode(htmlspecialchars(str_replace(";","%^%",$_COOKIE[$config['security']['cookie']]))).'"'; else echo '"'.$config['settings']['checkLoginUrl'].'?sid=THEGAME"'; ?>+'&jsoncallback=?',function(data){
					signCallback(data.signature,data.nick,data.uid);
				});
			});
			<?php
			}else{
				echo 'signCallback("","",0);';
			}
			?>
		</script>
		</body>
		</html>
		<?php
	}
}elseif(isset($_GET['chans'])){
}else{
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>OmnomIRC V2</title>
<link rel="icon" type="image/png" href="omni.png">
<link rel="stylesheet" type="text/css" href="style.css" />
<?php
if($config['settings']['externalStyleSheet']!='')
	echo '<link rel="stylesheet" type="text/css" href="'.$config['settings']['externalStyleSheet'].'" />';
?>
<script type="text/javascript" src="btoa.js"></script>
<script type="text/javascript" src="jquery-1.11.0.min.js"></script>
<script type="text/javascript" src="omnomirc.js"></script>
<script type="text/javascript">
document.domain=<?php echo '"'.$config['settings']['hostname'].'"'?>;
</script>
</head>
<body>
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
<div id="mBoxCont">
	<table id="MessageBox" cellpadding="0px" cellspacing="0px" style="width:100%;height:100%;">
	</table>
</div>
<div id="UserListContainer">
	<table id="hotlinks">
		<?php
		$i = true;
		foreach($config['hotlinks'] as $link){
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
		if($i){
			echo '<tr style="display:none;" id="adminLink"><td><a href="?admin">Admin</a></td>';
		}else{
			echo '<td style="display:none;" id="adminLink"><a href="?admin">Admin</a></td>';
		}
		echo '</tr>';
		?>
	</table>
	<div id="UserListInnerCont"><div id="UserList"></div></div>
</div>
</div>
</div>
<img id="smileyMenuButton" src="smileys/smiley.gif" style="margin-left:2px;margin-right:2px;"><form style="Display:inline;" name="irc" action="javascript:void(0)" id="sendMessage"><input autocomplete="off" accesskey="i" type="text" name="message" id="message" size="128" maxlength="256" alt="OmnomIRC" title="OmnomIRC"/><input type="submit" value="Send" id="send" /></form>
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
</div>
<div id="lastSeenCont" style="display:none;"></div>
<audio id="ding" src="beep.wav" hidden></audio>
</body>
</html>
<?php
}
?>
