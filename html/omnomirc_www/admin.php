<?php
/*
    OmnomIRC COPYRIGHT 2010,2011 Netham45
                       2012-2016 Sorunome

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
namespace oirc;
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
	$file = '<?php
/* This is a automatically generated config-file by OmnomIRC, please use the admin pannel to edit it! */
header("Location:index.php");
exit;
?>
'.json_encode(OIRC::$config);
	if(file_put_contents(realpath(dirname(__FILE__)).'/config.json.php',$file)){
		if(!$silent){
			if(!($m = Json::getIndex('message'))){
				$m = '';
			}
			Json::add('message',$m."\nconfig written");
		}
		if(!INTERNAL){
			Relay::sendRaw(array('t' => 'server_updateconfig'));
		}
	}else{
		Json::addError('Couldn\'t write config');
	}
}
function getCheckLoginChallenge(){
	$s = generateRandomString(40);
	$ts = (string)time();
	return urlencode(hash_hmac('sha512',$s,OIRC::$config['security']['sigKey'].$ts).'|'.$ts.'|'.$s);
}
if(isset($_GET['finishUpdate'])){
	file_put_contents(realpath(dirname(__FILE__)).'/updater.php',"<?php\nheader('Location: index.php');\n?>");
	if(!OIRC::$config['info']['installed']){
		OIRC::$config['info']['installed'] = true;
		writeConfig();
		file_put_contents(realpath(dirname(__FILE__)).'/config.backup.php',file_get_contents(realpath(dirname(__FILE__)).'/config.json.php'));
		header('Location: index.php');
	}else{
		header('Location: index.php?admin&network='.OIRC::$you->getNetwork().'#releaseNotes');
	}
}
if(INTERNAL){
	if(isset($_GET['internalAction'])){
		switch($_GET['internalAction']){
			case 'activateBot':
				OIRC::$config['settings']['useBot'] = true;
				break;
			case 'deactivateBot':
				OIRC::$config['settings']['useBot'] = false;
				break;
			case 'setWsPort':
				OIRC::$config['websockets']['port'] = (int)$_GET['port'];
				break;
		}
		writeConfig(true);
	}
}
if(OIRC::$you->isGlobalOp()){
	if(OIRC::$config['security']['sigKey']==''){
		OIRC::$config['security']['sigKey'] = getRandKey();
		Json::addWarning('SigKey wasn\'t set, storing random value');
		writeConfig(true);
	}
	if(OIRC::$config['security']['ircPwd']==''){
		OIRC::$config['security']['ircPwd'] = getRandKey();
		Json::addWarning('IRC bot password wasn\'t set, storing random value');
		writeConfig(true);
	}
	if(isset($_GET['get'])){
		switch($_GET['get']){
			case 'index':
				Json::add('installed',OIRC::$config['info']['installed']);
				Json::add('version',OIRC::$config['info']['version']);
				if(strpos(file_get_contents(realpath(dirname(__FILE__)).'/updater.php'),'//UPDATER FROMVERSION='.OIRC::$config['info']['version'])!==false){
					Json::add('updaterReady',true);
				}else{
					Json::add('updaterReady',false);
				}
				break;
			case 'themes':
				if(!$a = Vars::get('themes')){
					$a = array();
				}
				Json::add('themes',$a);
				break;
			case 'channels':
				Json::add('channels',OIRC::$config['channels']);
				Json::add('nets',Networks::getNetsArray());
				break;
			case 'hotlinks':
				Json::add('hotlinks',Vars::get('hotlinks'));
				break;
			case 'sql':
				Json::add('sql',array(
					'server' => OIRC::$config['sql']['server'],
					'db' => OIRC::$config['sql']['db'],
					'user' => OIRC::$config['sql']['user'],
					'prefix' => OIRC::$config['sql']['prefix'],
					'passwd' => ''
				));
				Json::add('pattern',json_decode(file_get_contents(realpath(dirname(__FILE__)).'/editPatterns/sql.json'),true));
				break;
			case 'smileys':
				Json::add('smileys',Vars::get('smileys'));
				break;
			case 'networks':
				foreach(OIRC::$config['networks'] as &$n){
					if($n['type'] == 1){
						$v = Vars::get(array('extra_chan_msg_'.(string)$n['id'],'defaults_'.(string)$n['id']));
						$m = $v['extra_chan_msg_'.(string)$n['id']];
						if($m!==NULL){
							$n['config']['extraChanMsg'] = $m;
						}else{
							$n['config']['extraChanMsg'] = '';
						}
						$m = $v['defaults_'.(string)$n['id']];
						if($m!==NULL){
							$n['config']['defaults'] = $m;
						}else{
							$n['config']['defaults'] = array();
						}
					}elseif($n['config'] === true){
						$n['config'] = Vars::get('net_config_'.(string)$n['id']);
					}
				}
				Json::add('networks',OIRC::$config['networks']);
				
				// time to fetch which network types we have!
				$networkTypes = array(
					1 => array(
						'id' => 1,
						'name' => 'OmnomIRC',
						'defaultCfg' => array(
							'checkLogin' => 'link to checkLogin file',
							'theme' => -1,
							'defaults' => array(),
							'opGroups' => array(),
							'guests' => 0,
							'editPattern' => false
						)
					)
				);
				if($a = Relay::sendRaw(array('t' => 'server_getRelayTypes'))){
					foreach($a as $b){
						$networkTypes[$b['id']] = $b;
					}
				}
				Json::add('networkTypes',$networkTypes);
				break;
			case 'checkLogin':
				$success = false;
				if(isset($_GET['i']) && isset(OIRC::$config['networks'][$_GET['i']]) && OIRC::$config['networks'][$_GET['i']]['type'] == 1){
					$s = @file_get_contents(OIRC::$config['networks'][$_GET['i']]['config']['checkLogin'].'?server='.getCheckLoginChallenge().'&action=get');
					if($s != ''){
						$success = true;
						Json::add('checkLogin',json_decode($s,true));
					}
				}
				Json::add('success',$success);
				break;
			case 'ws':
				Json::add('websockets',OIRC::$config['websockets']);
				Json::add('pattern',json_decode(file_get_contents(realpath(dirname(__FILE__)).'/editPatterns/websockets.json'),true));
				break;
			case 'misc':
				Json::add('misc',array(
					'botSocket' => OIRC::$config['settings']['botSocket'],
					'hostname' => OIRC::$config['settings']['hostname'],
					'curidFilePath' => OIRC::$config['settings']['curidFilePath'],
					'signatureKey' => OIRC::$config['security']['sigKey'],
					'ircPasswd' => OIRC::$config['security']['ircPwd'],
					'experimental' => OIRC::$config['settings']['experimental'],
					'cache' => OIRC::$config['cache']
				));
				Json::add('pattern',json_decode(file_get_contents(realpath(dirname(__FILE__)).'/editPatterns/misc.json'),true));
				break;
			case 'ex':
				Json::add('ex',array(
					'useBot' => OIRC::$config['settings']['useBot'],
					'minified' => !isset(OIRC::$config['settings']['minified'])||OIRC::$config['settings']['minified'],
					'betaUpdates' => isset(OIRC::$config['settings']['betaUpdates'])&&OIRC::$config['settings']['betaUpdates']
				));
				Json::add('pattern',json_decode(file_get_contents(realpath(dirname(__FILE__)).'/editPatterns/experimental.json'),true));
				break;
			case 'releaseNotes':
				Json::add('version',OIRC::$config['info']['version']);
				break;
			default:
				Json::addError('Invalid page');
		}
	}elseif(isset($_GET['set'])){
		$jsonData = json_decode($_POST['data'],true);
		switch($_GET['set']){
			case 'getUpdater':
				if(isset($jsonData['path'])){
					if($file = file_get_contents('https://omnomirc.omnimaga.org/'.$jsonData['path'])){
						if(file_put_contents(realpath(dirname(__FILE__)).'/updater.php',$file)){
							Json::add('message','Downloaded updater');
						}else{
							Json::addError('couldn\'t write to updater');
						}
					}else{
						Json::addError('File not found');
					}
				}else{
					Json::addError('Path not specified');
				}
				break;
			case 'backupConfig':
				if(file_put_contents(realpath(dirname(__FILE__)).'/config.backup.php',file_get_contents(realpath(dirname(__FILE__)).'/config.json.php'))){
					Json::add('message','Backed up config!');
				}else{
					Json::addError('Couldn\'t back up config');
				}
				break;
			case 'themes':
				foreach($jsonData as &$t){
					if($t['lastModified'] == -1){
						$t['lastModified'] = time();
					}
				}
				Vars::set('themes',$jsonData);
				Json::add('message','Themes saved!');
				break;
			case 'channels':
				$remChans = array();
				foreach(OIRC::$config['channels'] as $c){
					$found = false;
					foreach($jsonData as $cc){
						if($c['id'] == $cc['id']){
							$found = true;
							break;
						}
					}
					if(!$found){
						$remChans[] = $c['id'];
					}
				}
				foreach($remChans as $removeChan){
					Sql::query('DELETE FROM {db_prefix}channels WHERE `chan`=?',array($removeChan));
					Sql::query('DELETE FROM {db_prefix}lines WHERE `channel`=?',array($removeChan));
					Sql::query('DELETE FROM {db_prefix}lines_old WHERE `channel`=?',array($removeChan));
					Sql::query('DELETE FROM {db_prefix}permissions WHERE `channel`=?',array($removeChan));
					Sql::query('DELETE FROM {db_prefix}users WHERE `channel`=?',array($removeChan));
				}
				OIRC::$config['channels'] = $jsonData;
				writeConfig();
				break;
			case 'hotlinks':
				Vars::set('hotlinks',$jsonData);
				Json::add('message','Config saved!');
				break;
			case 'sql':
				OIRC::$config['sql']['prefix'] = $jsonData['prefix'];
				if($jsonData['passwd'] != '' || OIRC::$config['sql']['server'] != $jsonData['server'] || OIRC::$config['sql']['user'] != $jsonData['user'] || OIRC::$config['sql']['db'] != $jsonData['db']){
					OIRC::$config['sql']['server'] = $jsonData['server'];
					OIRC::$config['sql']['db'] = $jsonData['db'];
					OIRC::$config['sql']['user'] = $jsonData['user'];
					OIRC::$config['sql']['passwd'] = $jsonData['passwd'];
					$sql_connection=@mysqli_connect(OIRC::$config['sql']['server'],OIRC::$config['sql']['user'],OIRC::$config['sql']['passwd'],OIRC::$config['sql']['db']);
					if (mysqli_connect_errno($sql_connection)!=0){
						Json::addError('Could not connect to SQL DB: '.mysqli_connect_errno($sql_connection).' '.mysqli_connect_error($sql_connection));
					}else{
						writeConfig();
					}
				}else{
					writeConfig();
				}
				break;
			case 'smileys':
				Vars::set('smileys',$jsonData);
				Json::add('message','Config saved!');
				break;
			case 'networks':
				if(sizeof($jsonData) > sizeof(OIRC::$config['networks'])){ // new network!
					foreach(array_diff_key($jsonData,OIRC::$config['networks']) as $n){
						foreach(OIRC::$config['channels'] as $i => &$c){
							if(sizeof(OIRC::$config['networks']) <= sizeof($c['networks']) + 1 /* plus one due to hidden server network */){
								$c['networks'][] = array(
									'id' => $n['id'],
									'name' => (isset($c['networks'][0])?$c['networks'][0]['name']:$c['alias']),
									'hidden' => false,
									'order' => $i
								);
							}
						}
					}
				}
				foreach($jsonData as &$n){
					if($n['type'] == 1){ // oirc network
						if(isset($n['config']['extraChanMsg'])){
							Vars::set('extra_chan_msg_'.(string)$n['id'],$n['config']['extraChanMsg']);
							unset($n['config']['extraChanMsg']);
						}
						if(isset($n['config']['defaults'])){
							Vars::set('defaults_'.(string)$n['id'],$n['config']['defaults']);
							unset($n['config']['defaults']);
						}
						if(isset($n['config']['checkLoginHook'])){
							$res = json_decode(file_get_contents($n['config']['checkLogin'].'?server='.getCheckLoginChallenge().'&action=set&var=hook&val='.base64_url_encode($n['config']['checkLoginHook'])),true);
							if($res===NULL || (isset($res['auth']) && !$res['auth']) || !$res['success']){
								Json::add('message','Couldn\'t update checkLogin');
							}
							unset($n['config']['checkLoginHook']);
						}
					}elseif(is_array($n['config'])){
						Vars::set('net_config_'.(string)$n['id'],$n['config']);
						$n['config'] = true;
					}
				}
				OIRC::$config['networks'] = $jsonData;
				writeConfig();
				break;
			case 'restartNetwork':
				if(isset($_GET['id']) && (int)$_GET['nid'] == $_GET['nid']){
					Relay::sendRaw(array('t' => 'server_restartNetwork','nid' => (int)$_GET['nid']));
				}else{
					Json::addError('Invalid network ID');
				}
				break;
			case 'ws':
				OIRC::$config['websockets'] = $jsonData;
				writeConfig();
				break;
			case 'misc':
				OIRC::$config['settings']['botSocket'] = $jsonData['botSocket'];
				OIRC::$config['settings']['hostname'] = $jsonData['hostname'];
				OIRC::$config['settings']['curidFilePath'] = $jsonData['curidFilePath'];
				OIRC::$config['security']['sigKey'] = $jsonData['signatureKey'];
				OIRC::$config['security']['ircPwd'] = $jsonData['ircPasswd'];
				OIRC::$config['settings']['experimental'] = $jsonData['experimental'];
				OIRC::$config['cache'] = $jsonData['cache'];
				writeConfig();
				break;
			case 'ex':
				OIRC::$config['settings']['useBot'] = $jsonData['useBot'];
				OIRC::$config['settings']['minified'] = $jsonData['minified'];
				OIRC::$config['settings']['betaUpdates'] = $jsonData['betaUpdates'];
				writeConfig();
				break;
			default:
				Json::addError('Invalid page');
		}
	}else{
		Json::addError('Unknown operation');
	}
}else{
	Json::addError('Permission denied');
	Json::add('denied',true);
}
echo Json::get();
