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
$ADMINPAGE = true;
include_once(realpath(dirname(__FILE__)).'/omnomirc.php');
function getRandKey(){
	$randKey = rand(100,9999).'-'.rand(10000,999999);
	$randKey = md5($randKey);
	$randKey = base64_url_encode($randKey);
	return md5($randKey);
}
function writeConfig($silent){
	global $config,$json;
	$file = '<?php
/* This is a automatically generated config-file by OmnomIRC, please use the admin pannel to edit it! */

function getConfig(){
	$cfg = explode("\\n",file_get_contents(realpath(dirname(__FILE__))."/config.php"));
	$searchingJson = true;
	$json = "";
	foreach($cfg as $line){
		if($searchingJson){
			if(trim($line)=="//JSONSTART"){
				$searchingJson = false;
			}
		}else{
			if(trim($line)=="//JSONEND"){
				break;
			}
			$json .= "\\n".$line;
		}
	}
	$json = implode("\\n",explode("\\n//",$json));
	return json_decode($json,true);
}
$config = getConfig();
//JSONSTART
//'.json_encode($config).'
//JSONEND
if(isset($_GET["js"])){
	include_once(realpath(dirname(__FILE__))."/omnomirc.php");
	header("Content-type: text/json");
	$channels = Array();
	foreach($config["channels"] as $chan){
		if($chan["enabled"]){
			foreach($chan["networks"] as $cn){
				if($cn["id"] == $_GET["network"]){
					$channels[] = Array(
						"chan" => $cn["name"],
						"high" => false,
						"ex" => $cn["hidden"],
						"id" => $chan["id"],
						"order" => $cn["order"]
					);
				}
			}
		}
	}
	usort($channels,function($a,$b){
		if($a["order"] == $b["order"]){
			return 0;
		}
		return (($a["order"] < $b["order"])?-1:1);
	});
	$net = $networks->get($you->getNetwork());
	$defaults = $net["config"]["defaults"];
	$cl = $net["config"]["checkLogin"];
	$ts = time();
	$cl .= "?sid=".urlencode(htmlspecialchars(str_replace(";","%^%",hash("sha512",$_SERVER["REMOTE_ADDR"].$config["security"]["sigKey"].$ts)."|".$ts)));
	echo json_encode(Array(
		"hostname" => $config["settings"]["hostname"],
		"channels" => $channels,
		"smileys" => $vars->get("smileys"),
		"networks" => $config["networks"],
		"network" => $you->getNetwork(),
		"checkLoginUrl" => $cl,
		"defaults" => $defaults
	));
}
?>';
	if(file_put_contents(realpath(dirname(__FILE__)).'/config.php',$file)){
		if(!$silent){
			$json->add('message','config written');
		}
	}else{
		$json->addError('Couldn\'t write config');
	}
}
if(isset($_GET['finishUpdate']) && ($you->isGlobalOp() || !$config['info']['installed'])){
	file_put_contents(realpath(dirname(__FILE__)).'/updater.php',"<?php\nheader('Location: index.php');\n?>");
	if(!$config['info']['installed']){
		$config['info']['installed'] = true;
		writeConfig();
		file_put_contents(realpath(dirname(__FILE__)).'/config.backup.php',file_get_contents(realpath(dirname(__FILE__)).'/config.php'));
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
			case 'op':
				$json->add('opGroups',$config['opGroups']);
				break;
			case 'smileys':
				$json->add('smileys',$vars->get('smileys'));
				break;
			case 'networks':
				$json->add('networks',$config['networks']);
				break;
			case 'misc':
				$json->add('hostname',$config['settings']['hostname']);
				$json->add('curidFilePath',$config['settings']['curidFilePath']);
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
				if(file_put_contents(realpath(dirname(__FILE__)).'/config.backup.php',file_get_contents(realpath(dirname(__FILE__)).'/config.php'))){
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
				//writeConfig();
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
			case 'op':
				$config['opGroups'] = $jsonData;
				writeConfig();
				break;
			case 'smileys':
				$vars->set('smileys',$jsonData);
				//writeConfig();
				break;
			case 'networks':
				$config['networks'] = $jsonData;
				writeConfig();
				break;
			case 'misc':
				$config['settings']['hostname'] = $jsonData['hostname'];
				$config['settings']['curidFilePath'] = $jsonData['curidFilePath'];
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
}
echo $json->get();
?>
