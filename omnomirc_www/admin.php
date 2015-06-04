<?php
/*
    OmnomIRC COPYRIGHT 2010,2011 Netham45
                       2012-2015 Sorunome

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
$ADMINPAGE = true;
include_once(realpath(dirname(__FILE__)).'/omnomirc.php');
function generateRandomString($length = 10) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for($i = 0;$i < $length;$i++){
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}
function getRandKey(){
	return generateRandomString(40);
}
function writeConfig($silent = false){
	global $config,$json;
	$file = '<?php
/* This is a automatically generated config-file by OmnomIRC, please use the admin pannel to edit it! */
header("Location:index.php");
//JSONSTART
//'.json_encode($config).'
//JSONEND
?>';
	if(file_put_contents(realpath(dirname(__FILE__)).'/config.json.php',$file)){
		if(!$silent){
			if(!($m = $json->getIndex('message'))){
				$m = '';
			}
			$json->add('message',$m."\nconfig written");
		}
	}else{
		$json->addError('Couldn\'t write config');
	}
}
function getCheckLoginChallenge(){
	global $config;
	$s = generateRandomString(40);
	$ts = (string)time();
	return urlencode(hash_hmac('sha512',$s,$config['security']['sigKey'].$ts).'|'.$ts.'|'.$s);
}
if(isset($_GET['finishUpdate'])){
	file_put_contents(realpath(dirname(__FILE__)).'/updater.php',"<?php\nheader('Location: index.php');\n?>");
	if(!$config['info']['installed']){
		$config['info']['installed'] = true;
		writeConfig();
		file_put_contents(realpath(dirname(__FILE__)).'/config.backup.php',file_get_contents(realpath(dirname(__FILE__)).'/config.json.php'));
		header('Location: index.php');
	}else{
		header('Location: index.php?admin&network='.$you->getNetwork().'#releaseNotes');
	}
}
if($you->isGlobalOp()){
	if($config['security']['sigKey']==''){
		$config['security']['sigKey'] = getRandKey();
		$json->addWarning('SigKey wasn\'t set, storing random value');
		writeConfig(true);
	}
	if($config['security']['ircPwd']==''){
		$config['security']['ircPwd'] = getRandKey();
		$json->addWarning('IRC bot password wasn\'t set, storing random value');
		writeConfig(true);
	}
	if(isset($_GET['get'])){
		switch($_GET['get']){
			case 'index':
				$json->add('installed',$config['info']['installed']);
				$json->add('version',$config['info']['version']);
				if(strpos(file_get_contents(realpath(dirname(__FILE__)).'/updater.php'),'//UPDATER FROMVERSION='.$config['info']['version'])!==false){
					$json->add('updaterReady',true);
				}else{
					$json->add('updaterReady',false);
				}
				break;
			case 'channels':
				$json->add('channels',$config['channels']);
				$json->add('nets',$networks->getNetsArray());
				break;
			case 'hotlinks':
				$json->add('hotlinks',$vars->get('hotlinks'));
				break;
			case 'sql':
				$json->add('server',$config['sql']['server']);
				$json->add('db',$config['sql']['db']);
				$json->add('user',$config['sql']['user']);
				break;
			case 'smileys':
				$json->add('smileys',$vars->get('smileys'));
				break;
			case 'networks':
				foreach($config['networks'] as &$n){
					if($n['type'] == 1){
						$m = $vars->get('extra_chan_msg_'.(string)$n['id']);
						if($m!==NULL){
							$n['config']['extraChanMsg'] = $m;
						}else{
							$n['config']['extraChanMsg'] = '';
						}
					}
				}
				$json->add('networks',$config['networks']);
				break;
			case 'checkLogin':
				if(isset($_GET['i']) && isset($config['networks'][$_GET['id']]) && $config['networks'][$_GET['id']]['type'] == 1){
					$json->add('checkLogin',json_decode(file_get_contents($config['networks'][$_GET['i']]['config']['checkLogin'].'?server='.getCheckLoginChallenge().'&action=get'),true));
				}else{
					$json->add('success',false);
				}
				break;
			case 'ws':
				$json->add('websockets',$config['websockets']);
				break;
			case 'misc':
				$json->add('hostname',$config['settings']['hostname']);
				$json->add('curidFilePath',$config['settings']['curidFilePath']);
				$json->add('signatureKey',$config['security']['sigKey']);
				$json->add('ircPasswd',$config['security']['ircPwd']);
				break;
			case 'releaseNotes':
				$json->add('version',$config['info']['version']);
				break;
			default:
				$json->addError('Invalid page');
		}
	}elseif(isset($_GET['set'])){
		$jsonData = json_decode($_POST['data'],true);
		switch($_GET['set']){
			case 'getUpdater':
				if(isset($jsonData['path'])){
					if($file = file_get_contents($jsonData['path'])){
						if(file_put_contents(realpath(dirname(__FILE__)).'/updater.php',$file)){
							$json->add('message','Downloaded updater');
						}else{
							$json->addError('couldn\'t write to updater');
						}
					}else{
						$json->addError('File not found');
					}
				}else{
					$json->addError('Path not specified');
				}
				break;
			case 'backupConfig':
				if(file_put_contents(realpath(dirname(__FILE__)).'/config.backup.php',file_get_contents(realpath(dirname(__FILE__)).'/config.json.php'))){
					$json->add('message','Backed up config!');
				}else{
					$json->addError('Couldn\'t back up config');
				}
				break;
			case 'channels':
				$config['channels'] = $jsonData;
				writeConfig();
				break;
			case 'hotlinks':
				$vars->set('hotlinks',$jsonData);
				$json->add('message','Config saved!');
				break;
			case 'sql':
				$config['sql']['server'] = $jsonData['server'];
				$config['sql']['db'] = $jsonData['db'];
				$config['sql']['user'] = $jsonData['user'];
				$config['sql']['passwd'] = $jsonData['passwd'];
				$sql_connection=@mysqli_connect($config['sql']['server'],$config['sql']['user'],$config['sql']['passwd'],$config['sql']['db']);
				if (mysqli_connect_errno($sql_connection)!=0){
					$json->addError('Could not connect to SQL DB: '.mysqli_connect_errno($sql_connection).' '.mysqli_connect_error($sql_connection));
				}else{
					writeConfig();
				}
				break;
			case 'smileys':
				$vars->set('smileys',$jsonData);
				$json->add('message','Config saved!');
				break;
			case 'networks':
				foreach($jsonData as &$n){
					if($n['type'] == 1){ // oirc network
						if(isset($n['config']['extraChanMsg'])){
							$vars->set('extra_chan_msg_'.(string)$n['id'],$n['config']['extraChanMsg']);
							unset($n['config']['extraChanMsg']);
						}
						if(isset($n['config']['checkLoginHook'])){
							$res = json_decode(file_get_contents($n['config']['checkLogin'].'?server='.getCheckLoginChallenge().'&action=set&var=hook&val='.base64_url_encode($n['config']['checkLoginHook'])),true);
							if($res===NULL || (isset($res['auth']) && !$res['auth']) || !$res['success']){
								$json->add('message','Couldn\'t update checkLogin');
							}
							unset($n['config']['checkLoginHook']);
						}
					}
				}
				$config['networks'] = $jsonData;
				writeConfig();
				break;
			case 'ws':
				$config['websockets'] = $jsonData;
				writeConfig();
				break;
			case 'misc':
				$config['settings']['hostname'] = $jsonData['hostname'];
				$config['settings']['curidFilePath'] = $jsonData['curidFilePath'];
				$config['security']['sigKey'] = $jsonData['signatureKey'];
				$config['security']['ircPwd'] = $jsonData['ircPasswd'];
				writeConfig();
				break;
			default:
				$json->addError('Invalid page');
		}
	}else{
		$json->addError('Unknown operation');
	}
}else{
	$json->addError('Permission denied');
	$json->add('denied',true);
}
echo $json->get();
?>
