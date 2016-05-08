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
			if(!($m = OIRC::$json->getIndex('message'))){
				$m = '';
			}
			OIRC::$json->add('message',$m."\nconfig written");
		}
		if(!INTERNAL && OIRC::$config['settings']['useBot']){
			OIRC::$relay->sendLine('','','server_updateconfig','');
			OIRC::$relay->commitBuffer();
		}
	}else{
		OIRC::$json->addError('Couldn\'t write config');
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
		OIRC::$json->addWarning('SigKey wasn\'t set, storing random value');
		writeConfig(true);
	}
	if(OIRC::$config['security']['ircPwd']==''){
		OIRC::$config['security']['ircPwd'] = getRandKey();
		OIRC::$json->addWarning('IRC bot password wasn\'t set, storing random value');
		writeConfig(true);
	}
	if(isset($_GET['get'])){
		switch($_GET['get']){
			case 'index':
				OIRC::$json->add('installed',OIRC::$config['info']['installed']);
				OIRC::$json->add('version',OIRC::$config['info']['version']);
				if(strpos(file_get_contents(realpath(dirname(__FILE__)).'/updater.php'),'//UPDATER FROMVERSION='.OIRC::$config['info']['version'])!==false){
					OIRC::$json->add('updaterReady',true);
				}else{
					OIRC::$json->add('updaterReady',false);
				}
				break;
			case 'themes':
				if(!$a = OIRC::$vars->get('themes')){
					$a = array();
				}
				OIRC::$json->add('themes',$a);
				break;
			case 'channels':
				OIRC::$json->add('channels',OIRC::$config['channels']);
				OIRC::$json->add('nets',OIRC::$networks->getNetsArray());
				break;
			case 'hotlinks':
				OIRC::$json->add('hotlinks',OIRC::$vars->get('hotlinks'));
				break;
			case 'sql':
				OIRC::$json->add('sql',array(
					'server' => OIRC::$config['sql']['server'],
					'db' => OIRC::$config['sql']['db'],
					'user' => OIRC::$config['sql']['user'],
					'prefix' => OIRC::$config['sql']['prefix'],
					'passwd' => ''
				));
				OIRC::$json->add('pattern',array(
					array(
						'name' => 'Server',
						'type' => 'text',
						'var' => 'server'
					),
					array(
						'name' => 'Database',
						'type' => 'text',
						'var' => 'db'
					),
					array(
						'name' => 'User',
						'type' => 'text',
						'var' => 'user'
					),
					array(
						'name' => 'Password',
						'type' => 'text',
						'var' => 'passwd'
					),
					array(
						'name' => 'Database Prefix',
						'type' => 'text',
						'var' => 'prefix'
					)
				));
				break;
			case 'smileys':
				OIRC::$json->add('smileys',OIRC::$vars->get('smileys'));
				break;
			case 'networks':
				foreach(OIRC::$config['networks'] as &$n){
					if($n['type'] == 1){
						$v = OIRC::$vars->get(array('extra_chan_msg_'.(string)$n['id'],'defaults_'.(string)$n['id']));
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
						$n['config'] = OIRC::$vars->get('net_config_'.(string)$n['id']);
					}
				}
				OIRC::$json->add('networks',OIRC::$config['networks']);
				
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
				if(OIRC::$config['settings']['useBot']){
					if($socket = OIRC::$relay->getSocket()){
						$s = json_encode(array('t' => 'server_getRelayTypes'))."\n";
						socket_write($socket,$s,strlen($s));
						$b = '';
						socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,array('sec' => 2,'usec' => 0));
						while($buf = socket_read($socket,2048)){
							$b .= $buf;
							if(strpos($b,"\n")!==false){
								break;
							}
						}
						socket_close($socket);
						if($a = json_decode($b,true)){
							foreach($a as $b){
								$networkTypes[$b['id']] = $b;
							}
						}
					}
				}
				OIRC::$json->add('networkTypes',$networkTypes);
				break;
			case 'checkLogin':
				$success = false;
				if(isset($_GET['i']) && isset(OIRC::$config['networks'][$_GET['i']]) && OIRC::$config['networks'][$_GET['i']]['type'] == 1){
					$s = @file_get_contents(OIRC::$config['networks'][$_GET['i']]['config']['checkLogin'].'?server='.getCheckLoginChallenge().'&action=get');
					if($s != ''){
						$success = true;
						$json->add('checkLogin',json_decode($s,true));
					}
				}
				OIRC::$json->add('success',$success);
				break;
			case 'ws':
				OIRC::$json->add('websockets',OIRC::$config['websockets']);
				OIRC::$json->add('pattern',array(
					array(
						'name' => 'Enable Websockets',
						'type' => 'checkbox',
						'var' => 'use'
					),
					array(
						'name' => 'SSL',
						'type' => 'checkbox',
						'var' => 'ssl',
						'pattern' => array(
							array(
								'name' => 'Certificate File',
								'type' => 'text',
								'var' => 'certfile'
							),
							array(
								'name' => 'Private Key File',
								'type' => 'text',
								'var' => 'keyfile'
							)
						)
					),
					array(
						'name' => 'Advanced settings',
						'type' => 'more',
						'pattern' => array(
							array(
								'name' => 'Host (only set if different from setting in "misc")',
								'type' => 'text',
								'var' => 'host'
							),
							array(
								'name' => 'Enable Port-poking (will disable internal port)',
								'type' => 'checkbox',
								'var' => 'portpoking'
							),
							array(
								'name' => 'External Port',
								'type' => 'number',
								'var' => 'port'
							),
							array(
								'name' => 'Internal Port (e.g. for nginx/apache forwarding)',
								'type' => 'text',
								'var' => 'intport'
							),
							array(
								'name' => 'Force client-sided ssl (e.g. for nginx/apache forwarding)',
								'type' => 'checkbox',
								'var' => 'fssl'
							)
						)
					)
				));
				break;
			case 'misc':
				OIRC::$json->add('misc',array(
					'botSocket' => OIRC::$config['settings']['botSocket'],
					'hostname' => OIRC::$config['settings']['hostname'],
					'curidFilePath' => OIRC::$config['settings']['curidFilePath'],
					'signatureKey' => OIRC::$config['security']['sigKey'],
					'ircPasswd' => OIRC::$config['security']['ircPwd'],
					'experimental' => OIRC::$config['settings']['experimental']
				));
				OIRC::$json->add('pattern',array(
					array(
						'name' => 'bot socket',
						'type' => 'text',
						'var' => 'botSocket'
					),
					array(
						'name' => 'hostname',
						'type' => 'text',
						'var' => 'hostname'
					),
					array(
						'name' => 'curid file path',
						'type' => 'text',
						'var' => 'curidFilePath'
					),
					array(
						'name' => 'signature key',
						'type' => 'text',
						'var' => 'signatureKey'
					),
					array(
						'name' => 'irc password',
						'type' => 'text',
						'var' => 'ircPasswd'
					),
					array(
						'type' => 'newline'
					),
					array(
						'name' => 'Turn on experimental settings (not recommended)',
						'type' => 'checkbox',
						'var' => 'experimental'
					)
				));
				break;
			case 'ex':
				OIRC::$json->add('ex',array(
					'useBot' => OIRC::$config['settings']['useBot'],
					'minified' => !isset(OIRC::$config['settings']['minified'])||OIRC::$config['settings']['minified'],
					'betaUpdates' => isset(OIRC::$config['settings']['betaUpdates'])&&OIRC::$config['settings']['betaUpdates']
				));
				OIRC::$json->add('pattern',array(
					array(
						'name' => 'use bot',
						'type' => 'checkbox',
						'var' => 'useBot'
					),
					array(
						'name' => 'use minfied sources',
						'type' => 'checkbox',
						'var' => 'minified'
					),
					array(
						'name' => 'fetch beta updates',
						'type' => 'checkbox',
						'var' => 'betaUpdates'
					)
				));
				break;
			case 'releaseNotes':
				OIRC::$json->add('version',OIRC::$config['info']['version']);
				break;
			default:
				OIRC::$json->addError('Invalid page');
		}
	}elseif(isset($_GET['set'])){
		$jsonData = json_decode($_POST['data'],true);
		switch($_GET['set']){
			case 'getUpdater':
				if(isset($jsonData['path'])){
					if($file = file_get_contents('https://omnomirc.omnimaga.org/'.$jsonData['path'])){
						if(file_put_contents(realpath(dirname(__FILE__)).'/updater.php',$file)){
							OIRC::$json->add('message','Downloaded updater');
						}else{
							OIRC::$json->addError('couldn\'t write to updater');
						}
					}else{
						OIRC::$json->addError('File not found');
					}
				}else{
					OIRC::$json->addError('Path not specified');
				}
				break;
			case 'backupConfig':
				if(file_put_contents(realpath(dirname(__FILE__)).'/config.backup.php',file_get_contents(realpath(dirname(__FILE__)).'/config.json.php'))){
					OIRC::$json->add('message','Backed up config!');
				}else{
					OIRC::$json->addError('Couldn\'t back up config');
				}
				break;
			case 'themes':
				foreach($jsonData as &$t){
					if($t['lastModified'] == -1){
						$t['lastModified'] = time();
					}
				}
				OIRC::$vars->set('themes',$jsonData);
				OIRC::$json->add('message','Themes saved!');
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
					OIRC::$sql->query_prepare('DELETE FROM {db_prefix}channels WHERE `chan`=?',array($removeChan));
					OIRC::$sql->query_prepare('DELETE FROM {db_prefix}lines WHERE `channel`=?',array($removeChan));
					OIRC::$sql->query_prepare('DELETE FROM {db_prefix}lines_old WHERE `channel`=?',array($removeChan));
					OIRC::$sql->query_prepare('DELETE FROM {db_prefix}permissions WHERE `channel`=?',array($removeChan));
					OIRC::$sql->query_prepare('DELETE FROM {db_prefix}users WHERE `channel`=?',array($removeChan));
				}
				OIRC::$config['channels'] = $jsonData;
				writeConfig();
				break;
			case 'hotlinks':
				OIRC::$vars->set('hotlinks',$jsonData);
				OIRC::$json->add('message','Config saved!');
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
						OIRC::$json->addError('Could not connect to SQL DB: '.mysqli_connect_errno($sql_connection).' '.mysqli_connect_error($sql_connection));
					}else{
						writeConfig();
					}
				}else{
					writeConfig();
				}
				break;
			case 'smileys':
				OIRC::$vars->set('smileys',$jsonData);
				OIRC::$json->add('message','Config saved!');
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
							OIRC::$vars->set('extra_chan_msg_'.(string)$n['id'],$n['config']['extraChanMsg']);
							unset($n['config']['extraChanMsg']);
						}
						if(isset($n['config']['defaults'])){
							OIRC::$vars->set('defaults_'.(string)$n['id'],$n['config']['defaults']);
							unset($n['config']['defaults']);
						}
						if(isset($n['config']['checkLoginHook'])){
							$res = json_decode(file_get_contents($n['config']['checkLogin'].'?server='.getCheckLoginChallenge().'&action=set&var=hook&val='.base64_url_encode($n['config']['checkLoginHook'])),true);
							if($res===NULL || (isset($res['auth']) && !$res['auth']) || !$res['success']){
								OIRC::$json->add('message','Couldn\'t update checkLogin');
							}
							unset($n['config']['checkLoginHook']);
						}
					}elseif(is_array($n['config'])){
						OIRC::$vars->set('net_config_'.(string)$n['id'],$n['config']);
						$n['config'] = true;
					}
				}
				OIRC::$config['networks'] = $jsonData;
				writeConfig();
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
				writeConfig();
				break;
			case 'ex':
				OIRC::$config['settings']['useBot'] = $jsonData['useBot'];
				OIRC::$config['settings']['minified'] = $jsonData['minified'];
				OIRC::$config['settings']['betaUpdates'] = $jsonData['betaUpdates'];
				writeConfig();
				break;
			default:
				OIRC::$json->addError('Invalid page');
		}
	}else{
		OIRC::$json->addError('Unknown operation');
	}
}else{
	OIRC::$json->addError('Permission denied');
	OIRC::$json->add('denied',true);
}
echo OIRC::$json->get();
