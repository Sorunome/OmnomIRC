<?php
namespace oirc\install;
error_reporting(E_ALL);
$installScriptVersion = '2.12';

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

// IMPORTANT!!!! sqli object ONLY FOR INSTALLATION SCRIPT
class Sql{
	private function connectSql(){
		global $config;
		$mysqli = new \mysqli($config['sql']['server'],$config['sql']['user'],$config['sql']['passwd'],$config['sql']['db']);
		if($mysqli->connect_errno){
			die('{"errors":["ERROR: Couldn\'t connect to SQL. Maybe insufficiant Database priviliges?"],"step":2}');
		}
		if(!$mysqli->set_charset('utf8')){
			die('{"errors":["ERROR: Couldn\'t use UTF-8, please check your SQL settings"],"step":2}');
		}
		return $mysqli;
	}
	public function query($query,$args = array(),$die = true){
		global $config;
		//ini_set('memory_limit','-1');
		$mysqli = $this->connectSql();
		
		$query = str_replace('{db_prefix}',$config['sql']['prefix'],$query);
		foreach($args as &$arg){
			$arg = $mysqli->real_escape_string($arg);
		}
		
		$mysqli->multi_query(vsprintf($query,$args));
		$result = $mysqli->store_result();
		if($mysqli->errno==1065){ //empty
			$result->free();
			return array();
		}
		if($mysqli->errno!=0){
			if($result){
				$result->free();
			}
			if($die){
				die(json_encode(array(
					'step' => 2,
					'errors' => array(
						'ERROR: Insufficient permissions to execute SQL query',
						$mysqli->error
					)
				)));
			}
			return false;
		}
		if($result===true || $result===false){ //nothing returned
			return array();
		}
		$res = array();
		$i = 0;
		while($row = $result->fetch_assoc()){
			$res[] = $row;
			if($i++>=1000){
				break;
			}
		}
		if($res === array()){
			$fields = $result->fetch_fields();
			for($i=0;$i<count($fields);$i++)
				$res[$fields[$i]->name] = NULL;
			$res = array($res);
		}
		$result->free();
		return $res;
	}
}
$sql = new Sql();
function base64_url_encode($input) {
	return strtr(base64_encode($input),'+/=','-_,');
}
function getPage($title,$head,$body){
	global $config;
	return '<!DOCTYPE html>'.
			'<html>'.
			'<head>'.
				'<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'.
				'<link rel="icon" type="image/png" href="omni.png">'.
				'<link rel="stylesheet" type="text/css" href="style.css" />'.
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
function writeConfig(){
	global $config;
	$file = '<?php
/* This is a automatically generated config-file by OmnomIRC, please use the admin pannel to edit it! */
header("Location:index.php");
exit;
?>
'.json_encode($config);
	if(!file_put_contents(realpath(dirname(__FILE__)).'/config.json.php',$file)){
		die('{"errors":["ERROR: Coulnd\'t write config, please make file config.json.php writeable for PHP"],"step":1}');
	}
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
if(!isset($_GET['server'])){
	echo getPage('OmnomIRC Installer Version '.$installScriptVersion,'
	<script type="text/javascript">
		(function($){
			var doStep = function(step,data){
					$("#container").empty();
					if(data.errors!==undefined){
						$.map(data.errors,function(err){
							$("#container").append(
								$("<span>").addClass("highlight").text(err),
								"<br>"
							);
						});
					}
					switch(step){
						case 1:
							$("#container").append(
								$("<button>").text("Reload").click(function(e){
									e.preventDefault();
									location.reload();
								})
							);
							break;
						case 2:
							$("#container").append(
								$("<h3>").text("SQL Settings"),
								"Server:",
								$("<input>").attr({
									"type":"text",
									"id":"sqlServer"
								}).val("localhost"),
								"<br>Database:",
								$("<input>").attr({
									"type":"text",
									"id":"sqlDb"
								}),
								"<br>Username:",
								$("<input>").attr({
									"type":"text",
									"id":"sqlUser"
								}),
								"<br>Password:",
								$("<input>").attr({
									"type":"password",
									"id":"sqlPasswd"
								}),
								"<br>Prefix:",
								$("<input>").attr({
									"type":"text",
									"id":"sqlPrefix"
								}).val("oirc_"),
								"<br><br>",
								"If you were sent back here and your previous credentials worked just hit submit while leaving everythin blank<br>",
								$("<button>").text("submit").click(function(e){
									e.preventDefault();
									getStep(2,{
										"server":$("#sqlServer").val(),
										"db":$("#sqlDb").val(),
										"user":$("#sqlUser").val(),
										"passwd":$("#sqlPasswd").val(),
										"prefix":$("#sqlPrefix").val()
									});
								})
							);
							break;
						case 3:
							$("#container").append("Populating database structure...");
							getStep(3,{});
							break;
						case 4:
							$("#container").append(
								"Change your permissions and then retry<br>",
								$("<button>").text("retry").click(function(e){
									e.preventDefault();
									getStep(3,{});
								})
							);
							break;
						case 5:
							if(data.errors!==undefined){
								$("#container").append(
									"Continue to populate the tables<br>",
									$("<button>").text("continue").click(function(e){
										e.preventDefault();
										getStep(5,{});
									})
								);
							}else{
								$("#container").append("Populating the tables...");
								getStep(5,{});
							}
							break;
						case 6:
							$("#container").append(
								"Change your permissions and then retry<br>",
								$("<button>").text("retry").click(function(e){
									e.preventDefault();
									getStep(5,{});
								})
							);
							break;
						case 7:
							var originalUrl = "",
								paths = {
									"":"",
									smf:"/checkLogin/",
									abxd:"/plugins/OmnomIRC/checkLogin/",
									phpbb3:"/ext/omnimaga/OmnomIRC/checkLogin/"
								};
							$("#container").append(
								"Forum URL (leave empty if you were sent back here and it was already set):",
								$("<input>").attr({
									"type":"text",
									"id":"forumUrl"
								}).focus(function(){
									this.value = originalUrl;
								}).keyup(function(){
									originalUrl = this.value;
								}).blur(function(){
									this.value = originalUrl + paths[$("#hook").val()];
								}),
								"<br>Forum type: ",
								$("<select>").attr("id","hook").append(
									$("<option>").val("").text("-please select-"),
									$("<option>").val("smf").text("Simple Machine Forums (SMF)"),
									$("<option>").val("abxd").text("ABXD Boards"),
									$("<option>").val("phpbb3").text("phpBB 3")
								).change(function(e){
									$("#forumUrl").val(originalUrl+paths[this.value]);
								}),
								"<br>",
								$("<button>").text("continue").click(function(e){
									e.preventDefault();
									getStep(7,{
										"checkLogin":$("#forumUrl").val(),
										"hook":$("#hook").val()
									});
								})
							);
							break;
						case 8:
							$("#container").append(
								"Almost done! I just need some default information from you.<br",
								"<br>Default Channel:",
								$("<input>").attr({
									"type":"text",
									"id":"defaultChan"
								}).val("#"),
								"<br>Default OP User (You can leave this blank, it is a good idea though, as it makes you op even if the op group doens\'t match):",
								$("<input>").attr({
									"type":"text",
									"id":"defaultOp"
								}),
								"<br>",
								$("<button>").text("continue").click(function(e){
									e.preventDefault();
									getStep(8,{
										"chan":$("#defaultChan").val(),
										"group":$("#defaultOpGroup").val(),
										"defaultOp":$("#defaultOp").val()
									});
								})
							);
							break;
						case 9:
							$("#container").append(
								$("<b>").text("Thank you for using OmnomIRC!"),
								"<br>If you are experiencing any issues, please ",
								$("<a>").attr({
									"target":"_blank",
									"href":"https://github.com/Sorunome/OmnomIRC2/issues"
								}).text("report them"),
								"<br><br>",
								"After you made that sure, hit the install button to finish the installation. Do <b><u>NOT</u></b> remove this file.<br>",
								$("<a>").attr("href","admin.php?finishUpdate").append(
									$("<button>").text("Install")
								)
							);
							break;
						default:
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
	<h2>OmnomIRC Installer Version '.$installScriptVersion.'</h2>
	<div id="container">Loading...</div>
	');
}else{
	header('Content-Type: text/json');
	if($config['info']['installed']){
		die('{"errors":["ERROR: OmnomIRC is already installed."],"step":1}');
	}else if($config['info']['version'] !== $installScriptVersion){
		die('{"errors":["ERROR: Config version and installer version don\'t match"],"step":1}');
	}
	switch($_GET['step']){
		case 1:
			$errors = array();
			if(!is_writable(realpath(dirname(__FILE__)))){
				$errors[] = "ERROR: Please make sure that the omnomirc directory is writable.";
			}
			foreach(scandir(realpath(dirname(__FILE__))) as $f){
				if($f != '.' && $f != '..') {
					if(!is_writable(realpath(dirname(__FILE__)).'/'.$f)){
						$errors[] = 'ERROR: '.$f.' isn\'t writable!';
					}
				}
			}
			if(sizeof($errors) > 0){
				die(json_encode(array(
					'step' => 1,
					'errors' => $errors
				)));
			}
			writeConfig();
			echo '{"step":2}';
			break;
		case 2:
			if(!empty($_POST['user'])){
				$config['sql']['server'] = $_POST['server'];
				$config['sql']['db'] = $_POST['db'];
				$config['sql']['user'] = $_POST['user'];
				$config['sql']['passwd'] = $_POST['passwd'];
				$config['sql']['prefix'] = $_POST['prefix'];
			}
			$sql->query("SELECT 1"); // test query
			// if the query failed we are already dead
			writeConfig();
			echo '{"step":3}';
			break;
		case 3:
			$sqlFile = @file_get_contents(realpath(dirname(__FILE__)).'/omnomirc.sql');
			if($sql->query("SELECT `line_number` FROM {db_prefix}lines",array(),false) === false){ // else we already populated
				if(!$sqlFile){
					die('{"errors":["SQL file not found, please upload it"],"step":4}');
				}
				$sql->query($sqlFile);
			}
			if(@unlink(realpath(dirname(__FILE__)).'/omnomirc.sql') || !$sqlFile){
				echo '{"step":5}';
			}else{
				echo '{"errors":["Couldn\'t delete file omnomirc.sql, please do so manually"],"step":5}';
			}
			break;
		case 5:
			$sqlFile = @file_get_contents(realpath(dirname(__FILE__)).'/irc_vars.sql');
			$res = $sql->query("SELECT `id` FROM {db_prefix}vars WHERE `name`='hotlinks'",array(),false);
			if($res===false || sizeof($res) == 0 || $res[0]['id'] === NULL){
				if(!$sqlFile){
					die('{"errors":["SQL file not found, please upload it"],"step":6}');
				}
				$sql->query($sqlFile);
			}
			if(@unlink(realpath(dirname(__FILE__)).'/irc_vars.sql') || !$sqlFile){
				echo '{"step":7}';
			}else{
				echo '{"errors":["Couldn\'t delete file irc_vars.sql, please do so manually"],"step":7}';
			}
			break;
		case 7:
			$config['security']['sigKey'] = generateRandomString(40);
			$config['security']['ircPwd'] = generateRandomString(40);
			writeConfig(); // just absolutly make sure that this is writable else it will be a PITA for the user
			if(!empty($_POST['checkLogin'])){
				$config['networks'][1]['config']['checkLogin'] = $_POST['checkLogin'];
				$res = json_decode(@file_get_contents($config['networks'][1]['config']['checkLogin'].'?server=&action=get'),true);
				if($res===NULL || (isset($res['auth']) && !$res['auth']) || !isset($res['hook'])){
					die('{"errors":["Couldn\'t connect to forum!"],"step":7}');
				}
				$https = @$_SERVER['HTTPS'] == 'on';
				$u = 'http'.($https?'s':'').'://'.(isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:$_SERVER['SERVER_NAME']);
				if(($https && $_SERVER['SERVER_PORT'] != '443') || (!$https && $_SERVER['SERVER_PORT'] != '80')){
					$u .= ':'.$_SERVER['server_port'];
				}
				$p = dirname(isset($_SERVER['DOCUMENT_URI'])?$_SERVER['DOCUMENT_URI']:$_SERVER['SCRIPT_NAME']);
				$u .= $p.'/';
				$urls = array(
					'action=set&var=sigKey&val='.base64_url_encode($config['security']['sigKey']),
					'action=set&var=network&val=1',
					'action=set&var=oircUrl&val='.base64_url_encode($u)
				);
				if(!empty($_POST['hook'])){
					if(in_array($_POST['hook'],$res['hooks'])){
						$urls[] = 'action=set&var=hook&val='.base64_url_encode($_POST['hook']);
					}else{
						die('{"errors":["Don\'t know what to do with forum hooks!"],"step":7}');
					}
				}elseif(!in_array($res['hook'],$res['hooks'])){
					if(count($res['hooks']) == 0){
						die('{"errors":["Forum doesn\'t have any hooks!"],"step":7}');
					}else{
						die('{"errors":["Forum hook stuff is invalid!"],"step":7}');
					}
				}
				$urls[] = 'action=set&var=installed&val=true';
				foreach($urls as $url){
					$res = json_decode(file_get_contents($config['networks'][1]['config']['checkLogin'].'?server=&'.$url),true);
					if($res===NULL || (isset($res['auth']) && !$res['auth']) || !$res['success']){
						die('{"errors":["Forum couldn\'t create files!"],"step":7}');
					}
				}
			}
			if(empty($config['networks'][1]['config']['checkLogin'])){
				echo '{"errors":["No URL specified, please do so!"],"step":7}';
			}
			
			$u = parse_url($config['networks'][1]['config']['checkLogin']);
			$config['networks'][1]['config']['spLinks'] = array($u['host']);
			writeConfig();
			echo '{"step":8}';
			break;
		case 8:
			$config['channels'][0]['alias'] = $_POST['chan'];
			$config['channels'][0]['networks'][0]['name'] = $_POST['chan'];
			
			if($_POST['defaultOp'] !== ''){
				$res = $sql->query("SELECT `usernum` FROM {db_prefix}userstuff WHERE `usernum`=%d",array($_POST['defaultOp']));
				if(count($res) > 0){
					$sql->query("UPDATE {db_prefix}userstuff SET `name`='%s' WHERE `usernum`=%d",array($_POST['defaultOp'],$res[0]['usernum']));
				}else{
					$sql->query("INSERT INTO `{db_prefix}userstuff` (`name`,`network`,`globalOp`) VALUES ('%s',1,1)",array($_POST['defaultOp']));
				}
			}
			
			$config['settings']['hostname'] = isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:$_SERVER['SERVER_NAME'];
			if(class_exists('Memcached') || class_exists('Memcache')){
				if(($s = @socket_create(AF_INET,SOCK_STREAM,SOL_TCP)) && @socket_connect($s,'localhost',11211)){
					socket_close($s);
					$config['cache']['type'] = 1;
					$config['cache']['host'] = 'localhost';
					$config['cache']['port'] = 11211;
				}
			}
			writeConfig();
			echo json_encode(array(
				'step' => 9
			));
			break;
		default:
			die('{"errors":["Unkown installation step"],"step":1}');
	}
}
