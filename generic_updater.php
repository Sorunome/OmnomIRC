<?php
//UPDATER FROMVERSION=SED_INSERT_FROMVERSION
namespace oirc\updater;
error_reporting(0);
$NEWVERSION='SED_INSERT_NEWVERSION';

$files = array(SED_INSERT_FILES);
$clfiles = array(SED_INSERT_CLFILES);
$updateHooks = false; // do we need to update hooks? true for all, array for specific hooks
$botFiles = array(SED_INSERT_BOTFILES);


function lastUpdateStuff(){
	global $config,$NEWVERSION,$DOWNLOADDIR,$sql;
	$msg = '';
	
	$config['info']['version'] = $NEWVERSION;
	
	// extra code will go here
	
	
	writeConfig();
	return $msg;
}


$DOWNLOADDIR = 'https://omnomirc.omnimaga.org/core/'.$NEWVERSION;
$DOWNLOADDIR_PATH = '/core/'.$NEWVERSION;

array_unshift($files,'');

function getConfig(){
	$cfg = explode("\n",file_get_contents(realpath(dirname(__FILE__)).'/config.json.php'));
	$searchingJson = true;
	$json = "";
	foreach($cfg as $line){
		if($searchingJson){
			if(trim($line)=='?>'){
				$searchingJson = false;
			}
		}else{
			$json .= "\n".$line;
		}
	}
	return json_decode($json,true);
}
$config = getConfig();

function updateCheckLogins(){
	global $config,$clfiles,$updateHooks,$DOWNLOADDIR_PATH;
	$validHooks = array('abxd','generic','mybb','phpbb3','smf');
	$msg = '';
	foreach($config['networks'] as &$n){
		if($n['type'] == 1){
			$clfilesNetwork = array_merge(array(),$clfiles);
			if($updateHooks){
				if(is_array($updateHooks)){
					$validHooks_run = array_intersect($validHooks,$updateHooks);
				}else{
					$validHooks_run = $validHooks;
				}
				$hooks = json_decode(file_get_contents($n['config']['checkLogin'].'?server='.getCheckLoginChallenge().'&action=get'),true);
				foreach($hooks['hooks'] as $h){
					if(in_array($h,$validHooks_run)){
						$clfilesNetwork[] = 'hook-'.$h.'.php';
					}elseif(!in_array($h,$validHooks)){
						$msg .= 'WARNING: Hook '.$h.' is present in network '.$n['name']." but it isn't supported by OmnomIRC! Be sure to update it accordingly!";
					}
				}
			}
			foreach($clfilesNetwork as $cf){
				$resp = json_decode(file_get_contents($n['config']['checkLogin'].'?server='.getCheckLoginChallenge().'&action=update&a='.base64_url_encode($DOWNLOADDIR_PATH.'/checkLogin/'.$cf.'.s').'&b='.base64_url_encode($cf)),true);
				if((isset($resp['auth']) && !$resp['auth']) || !$resp['success']){
					if(!isset($resp['message'])){
						$resp['message'] = '';
					}
					$msg .= "ERROR: Couldn't update checkLogin file $cf for network ".$n['name'].': '.$resp['message'].'<br>';
				}
			}
		}
	}
	return $msg;
}

function generateRandomString($length = 10) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for($i = 0;$i < $length;$i++){
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}
function getCheckLoginChallenge(){
	global $config;
	$s = generateRandomString(40);
	$ts = (string)time();
	return urlencode(hash_hmac('sha512',$s,$config['security']['sigKey'].$ts).'|'.$ts.'|'.$s);
}
function base64_url_encode($input) {
	return strtr(base64_encode($input),'+/=','-_,');
}
//IMPORTANT!!!! SQLI OBJECT IGNORES ERRORS AND IS ONLY FOR UPDATES
class Sqli{
	private $mysqliConnection;
	private function connectSql(){
		global $config;
		if(isset($this->mysqliConnection)){
			return $this->mysqliConnection;
		}
		$mysqli = new \mysqli($config['sql']['server'],$config['sql']['user'],$config['sql']['passwd'],$config['sql']['db']);
		if($mysqli->connect_errno){
			die('{"errors":["ERROR: Couldn\'t connect to SQL. Maybe insufficiant Database priviliges?"],"step":1}');
		}
		if(!$mysqli->set_charset('utf8')){
			die('{"errors":["ERROR: Couldn\'t use UTF-8, please check your SQL settings"],"step":1}');
		}
		$this->mysqliConnection = $mysqli;
		return $mysqli;
	}
	public function query(){
		global $config;
		//ini_set('memory_limit','-1');
		$mysqli = $this->connectSql();
		while($mysqli->more_results()){
				$mysqli->next_result();
				if($result = $mysqli->store_result()){
						$result->free();
				}
		}

		$params = func_get_args();
		$query = $params[0];
		$query = str_replace('{db_prefix}',$config['sql']['prefix'],$query);
		$args = Array();
		for($i=1;$i<count($params);$i++)
			$args[$i-1] = $mysqli->real_escape_string($params[$i]);
		$mysqli->multi_query(vsprintf($query,$args));
		if($mysqli->errno==1065){ //empty
			return array();
		}
		if($mysqli->errno!=0){
			return;
			die('{"errors":["ERROR: Insufficient permissions to execute SQL query, please grant your user all priviliges to the database"],"step":1}');
		}
		if(!($result = $mysqli->store_result())){
			return array();
		}
		if($result===true){ //nothing returned
			return array();
		}
		$res = array();
		$i = 0;
		while($row = $result->fetch_assoc()){
			$res[] = $row;
			if($i++>=1000)
				break;
		}
		if($res === Array()){
			$fields = $result->fetch_fields();
			for($i=0;$i<count($fields);$i++)
				$res[$fields[$i]->name] = NULL;
			$res = array($res);
		}
		$result->free();
		return $res;
	}
}
$sql = new Sqli();

function getSocket(){
	global $config;
	$sock = $config['settings']['botSocket'];
	$socket = false;
	if(substr($sock,0,5) == 'unix:'){
		$socket = socket_create(AF_UNIX,SOCK_STREAM,0);
		socket_connect($socket,substr($sock,5));
	}else{
		$matches = array();
		preg_match('/^([\\w\\.]+):(\\d+)/',$sock,$matches);
		if($matches){
			$socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
			socket_connect($socket,$matches[1],$matches[2]);
		}
	}
	return $socket;
}

function writeConfig(){
	global $config;
		$file = '<?php
/* This is a automatically generated config-file by OmnomIRC, please use the admin pannel to edit it! */
header("Location:index.php");
exit;
?>
'.json_encode($config);
	if(!file_put_contents(realpath(dirname(__FILE__)).'/config.json.php',$file)){
		die('{"errors":["ERROR: Couldn\'t write config, please make file config.json.php writeable for PHP"],"step":1}');
	}
}
function getCacheConfig(){
	global $sql;
	$defaults = array(
		'use' => false,
		'email' => '',
		'key' => '',
		'url' => ''
	);
	$res = $sql->query("SELECT `value` FROM {db_prefix}vars WHERE `name`='update_cache'");
	$a = json_decode($res[0]['value'],true);
	if(!$a){
		return $defaults;
	}
	return array_merge($defaults,$a);
}
function getPage($title,$head,$body){
	global $config;
	$theme = -1;
	if(isset($_GET['network'])){
		foreach($config['networks'] as $n){
			if($n['id'] == $_GET['network']){
				if(isset($n['config']['theme'])){
					$theme = $n['config']['theme'];
				}
				break;
			}
		}
	}
	return '<!DOCTYPE html>'.
			'<html>'.
			'<head>'.
				'<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'.
				'<link rel="icon" type="image/png" href="omni.png">'.
				'<link rel="stylesheet" type="text/css" href="skins/lobster/style.min.css" />'.
				($theme!=-1?'<link rel="stylesheet" type="text/css" href="theme.php?theme='.$theme.'" />':'').
				'<script src="btoa.js"></script>'.
				'<script type="text/javascript" src="jquery-1.11.3.min.js"></script>'.
				'<title>'.$title.'</title>'.
				$head.
			'</head>'.
			'<body>'.
				$body.
			'</body>'.
			'</html>';
}
if(!isset($_GET['server'])){
	$cacheconfig = getCacheConfig();
	echo getPage('OmnomIRC Installer Updater '.$NEWVERSION,'
	<script type="text/javascript">
		(function($){
			var downloadFile = function(fileName,step,suffix){
					if(suffix === undefined){
						suffix = "";
					}
					$("#container").append(
						"Downloading "+fileName+suffix+"..."
					);
					getStep(step,{});
				},
				downloadFileFailed = function(step){
					$("#container").append(
						$("<button>").text("retry").click(function(e){
							e.preventDefault();
							getStep(step-2,{});
						}),
						"&nbsp;",
						$("<button>").text("continue").click(function(e){
							e.preventDefault();
							getStep(step,{});
						})
						
					);
				},
				files = '.json_encode($files).',
				botFiles = '.json_encode($botFiles).'
				doStep = function(step,data){
					var offset_after_files = ((files.length + botFiles.length)*2);
					$("#container").empty();
					if(data.errors!==undefined){
						$.map(data.errors,function(err){
							$("#container").append(
								$("<span>").addClass("highlight").html(err),
								"<br>"
							);
						});
					}
					if(step == 1){
						$("#container").append(
							$("<button>").text("Reload").click(function(e){
								e.preventDefault();
								location.reload();
							})
						);
					}else if(step%2 == 0 && (files.length*2)>step){
						downloadFile(files[step/2],step);
					}else if(step%2 == 1 && (files.length*2)>step){
						downloadFileFailed(step+1);
					}else if(step%2 == 0 && ((files.length + botFiles.length)*2)>step){
						downloadFile(botFiles[(step/2) - files.length],step," for bot");
					}else if(step%2 == 1 && ((files.length + botFiles.length)*2)>step){
						downloadFileFailed(step+1);
					}else if(offset_after_files == step){
						$("#container").append(
							"Updating config..."
						);
						getStep(step,{});
					}else if(offset_after_files+1 == step || offset_after_files+3 == step){
						$("#container").append(
							data.msg,
							$("<button>").text("continue").click(function(e){
								e.preventDefault();
								getStep(step+1,{});
							})
						);
					}else if(offset_after_files+2 == step){
						$("#container").append(
							"Updating checkLogin files..."
						);
						getStep(step,{});
					}else if(offset_after_files+4 == step){
						$("#container").append(
							"Restarting bot if needed..."
						);
						getStep(step,{});
					}else if(offset_after_files+5 == step){
						$("#container").append(
							(data.msg?data.msg+"<br><br>":""),
							"To finish the update hit the button below.<br><br>",
							"Automatically update Cloudflare cache:",
							$("<input>").attr("id","cache_use").attr("type","checkbox")'.($cacheconfig['use']?'.attr("checked","checked")':'').',"<br>",
							"Email:",$("<input>").attr("id","cache_email").attr("type","text").val('.json_encode($cacheconfig['email']).'),"<br>",
							"API key:",$("<input>").attr("id","cache_key").attr("type","text").val('.json_encode($cacheconfig['key']).'),"<br>",
							"URL:",$("<input>").attr("id","cache_url").attr("type","text").val('.json_encode($cacheconfig['url']).'),"<br><br>",
							$("<button>").text("Finish").click(function(e){
								e.preventDefault();
								if($("#cache_use")[0].checked){
									getStep(step,{
										"email":$("#cache_email").val(),
										"key":$("#cache_key").val(),
										"url":$("#cache_url").val()
									});
								}else{
									window.location.href = "admin.php?finishUpdate'.(isset($_GET['network'])?'&network='.$_GET['network']:'').'";
								}
							})
						);
					}else if(offset_after_files+6 == step){
						window.location.href = "admin.php?finishUpdate'.(isset($_GET['network'])?'&network='.$_GET['network']:'').'";
					}else{
						$("#container").append("Something went wrong!");
					}
				},
				getStep = function(step,data){
					$.post("updater.php?server&step="+step,data).done(function(data){
						doStep(data.step,data);
					});
				};
			$(document).ready(function(){
				getStep(1,{});
			});
		})(jQuery);
	</script>
	','
	<h2>OmnomIRC Installer Updater '.$NEWVERSION.'</h2>
	<div id="container">Loading...</div>
	');
}else{
	header('Content-Type: application/json');
	
	$step = (int)$_GET['step'];
	$offset_after_files = (sizeof($files) + sizeof($botFiles))*2;
	if($step == 1){
		writeConfig();
		echo '{"step":2}';
	}elseif($step%2 == 0 && sizeof($files)*2 > $step){
		$file = file_get_contents($DOWNLOADDIR.'/html/'.$files[$step/2].'.s');
		if(!$file || $file == ''){
			echo json_encode(array(
				'errors' => array(
					'ERROR: no route to download server ('.$DOWNLOADDIR.'/html/'.$files[$step/2].'.s)'
				),
				'step' => $step+1
			));
			die();
		}
		$folder = dirname(dirname(__FILE__).'/'.$files[$step/2]);
		if(!is_dir($folder) && !mkdir($folder,0777,true)){
			echo json_encode(array(
				'errors' => array(
					'Unable to create directory '.$folder.', please create it manually or change permissions and retry'
				),
				'step' => $step+1
			));
			die();
		}
		if(!file_put_contents(realpath(dirname(__FILE__)).'/'.$files[$step/2],$file)){
			echo json_encode(array(
				'errors' => array(
					'Unable to write to '.$files[$step/2].', please download <a href="'.$DOWNLOADDIR.'/'.$files[$step/2].'.s">the file</a> manually or change permissions and retry.'
				),
				'step' => $step+1
			));
			die();
		}
		echo '{"step":'.($step+2).'}';
	}elseif($step%2 == 0 && (sizeof($files)+sizeof($botFiles))*2 > $step){
		$success = false;
		if($config['settings']['useBot']){
			if($socket = getSocket()){
				$fname = $botFiles[($step/2)-count($files)];
				$s = json_encode(array(
					't' => 'server_updateFile',
					'n1' => $DOWNLOADDIR_PATH.'/bot/'.$fname.'.s',
					'n2' => $fname
				))."\n";
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
				if(strpos($b,'success') !== false){
					$success = true;
				}
			}
		}
		if(!$success && $step == sizeof($files)*2){ // first bot file to be downloaded!
			echo json_encode(array(
				'errors' => array(
					'No bot found! Either you are not running it, or it wasn\'t reachable. You can check that it is running, retry, or, if you are not running it just hit continue.',
					'If you are running the bot, maybe the files aren\'t writable for it'
				),
				'step' => $step+1
			));
		}else{
			echo '{"step":'.($step+2).'}';
		}
	
	}elseif($offset_after_files == $step){
		$msg = lastUpdateStuff();
		echo json_encode(Array(
			'step' => $step+($msg == ''?2:1),
			'msg' => $msg
		));
	}elseif($offset_after_files+2 == $step){
		$msg = updateCheckLogins();
		echo json_encode(Array(
			'step' => $step+($msg == ''?2:1),
			'msg' => $msg
		));
	}elseif($offset_after_files+4 == $step){
		if(sizeof($botFiles) > 0 && $config['settings']['useBot']){
			if($socket = getSocket()){
				$s = json_encode(array(
					't' => 'server_restart'
				))."\n";
				socket_write($socket,$s,strlen($s));
				socket_close($socket);
			}
		}
		echo '{"step":'.($step+1).'}';
	}elseif($offset_after_files+5 == $step){
		$email = $_POST['email'];
		$key = $_POST['key'];
		$domain = isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:$_SERVER['SERVER_NAME'];
		
		$ch = curl_init();
		curl_setopt_array($ch,array(
			CURLOPT_VERBOSE => false,
			CURLOPT_FORBID_REUSE => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER => false,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTPHEADER => array(
				'X-Auth-Email: '.$email,
				'X-Auth-Key: '.$key,
				'Content-type: application/json'
			),
			CURLOPT_URL => 'https://api.cloudflare.com/client/v4/zones?'.http_build_query(array(
				'name' => $_POST['url'],
				'page' => 1,
				'per_page' => 1
			))
		));
		$json_result = json_decode(curl_exec($ch),true);
		curl_close($ch);
		if(!$json_result){
			echo json_encode(array(
				'step' => $step,
				'errors' => array('Something went wrong connecting to cloudflare!')
			));
			exit;
		}
		if(!$json_result['success']){
			$errors = array();
			foreach($json_result['errors'] as $e){
				$errors[] = $e['message'];
			}
			echo json_encode(array(
				'step' => $step,
				'errors' => $errors
			));
			exit;
		}
		if(sizeof($json_result['result']) < 1){
			echo json_encode(array(
				'step' => $step,
				'errors' => array('Domain not found in cloudflare!')
			));
			exit;
		}
		$zone = $json_result['result'][0]['id'];
		
		$urlroot = isset($_SERVER['HTTP_X_FORWARDED_PROTO'])?$_SERVER['HTTP_X_FORWARDED_PROTO']:(isset($_SERVER['REQUEST_SCHEME'])?$_SERVER['REQUEST_SCHEME']:(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on'?'https':'http'));
		$urlroot .= '://'.$domain.dirname($_SERVER['REQUEST_URI']);
		$cf_files = array();
		foreach($files as $f){
			if(in_array(pathinfo($f,PATHINFO_EXTENSION),array('html','css','js'))){
				$cf_files[] = $urlroot.'/'.$f;
			}
		}
		$ch = curl_init();
		curl_setopt_array($ch,array(
			CURLOPT_VERBOSE => false,
			CURLOPT_FORBID_REUSE => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER => false,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTPHEADER => array(
				'X-Auth-Email: '.$email,
				'X-Auth-Key: '.$key,
				'Content-type: application/json'
			),
			CURLOPT_POSTFIELDS => json_encode(array(
				'files' => $cf_files
			)),
			CURLOPT_CUSTOMREQUEST => 'DELETE',
			CURLOPT_URL => 'https://api.cloudflare.com/client/v4/zones/'.$zone.'/purge_cache'
		));
		$json_result = json_decode(curl_exec($ch),true);
		curl_close($ch);
		if(!$json_result){
			echo json_encode(array(
				'step' => $step,
				'errors' => array('Something went wrong connecting to cloudflare!')
			));
			exit;
		}
		if(!$json_result['success']){
			$errors = array();
			foreach($json_result['errors'] as $e){
				$errors[] = $e['message'];
			}
			echo json_encode(array(
				'step' => $step,
				'errors' => $errors
			));
			exit;
		}
		$s = json_encode(array(
			'use' => true,
			'email' => $_POST['email'],
			'key' => $_POST['key'],
			'url' => $_POST['url']
		));
		$r = $sql->query("SELECT id,type FROM {db_prefix}vars WHERE name='update_cache'");
		$r = $r[0];
		if(isset($r['id'])){ //check if we need to update or add a new
			$sql->query("UPDATE {db_prefix}vars SET value='%s',type=5 WHERE name='update_cache'",$s);
		}else{
			$sql->query("INSERT INTO {db_prefix}vars (name,value,type) VALUES ('update_cache','%s',5)",$s);
		}
		echo json_encode(array(
			'step' => $step+1
		));
	}else{
		die('{"errors":["Unkown updater step"],"step":1}');
	}
}
