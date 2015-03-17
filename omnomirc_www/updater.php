<?php
error_reporting(0);
$installScriptVersion = '2.9.0.3';
include_once(realpath(dirname(__FILE__)).'/config.php');
// IMPORTANT!!!! sqli object ONLY FOR INSTALLATION SCRIPT
class Sqli{
	private $mysqliConnection;
	private function connectSql(){
		global $config;
		if(isset($this->mysqliConnection)){
			return $this->mysqliConnection;
		}
		$mysqli = new mysqli($config['sql']['server'],$config['sql']['user'],$config['sql']['passwd'],$config['sql']['db']);
		if($mysqli->connect_errno){
			die('{"errors":["ERROR: Couldn\'t connect to SQL. Maybe insufficiant Database priviliges?"],"step":2}');
		}
		if(!$mysqli->set_charset('utf8')){
			die('{"errors":["ERROR: Couldn\'t use UTF-8, please check your SQL settings"],"step":2}');
		}
		$this->mysqliConnection = $mysqli;
		return $mysqli;
	}
	public function query(){
		//ini_set('memory_limit','-1');
		$mysqli = $this->connectSql();
		$params = func_get_args();
		$query = $params[0];
		$args = Array();
		for($i=1;$i<count($params);$i++)
			$args[$i-1] = $mysqli->real_escape_string($params[$i]);
		$result = $mysqli->multi_query(vsprintf($query,$args));
		if($mysqli->errno==1065){ //empty
			return array();
		}
		if($mysqli->errno!=0){
			die('{"errors":["ERROR: Insufficient permissions to execute SQL query, please grant your user all priviliges to the database"],"step":4}');
		}
		if($result===true){ //nothing returned
			return Array();
		}
		$res = Array();
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
function getPage($title,$head,$body){
	global $config;
	return '<!DOCTYPE html>'.
			'<html>'.
			'<head>'.
				'<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'.
				'<link rel="icon" type="image/png" href="omni.png">'.
				'<link rel="stylesheet" type="text/css" href="style.css" />'.
				'<script src="btoa.js"></script>'.
				'<script type="text/javascript" src="jquery-1.11.0.min.js"></script>'.
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
//JSONSTART
//'.json_encode($config).'
//JSONEND
?>';
	if(!file_put_contents(realpath(dirname(__FILE__)).'/config.json.php',$file)){
		die('{"errors":["ERROR: Coulnd\'t write config, please make file config.json.php writeable for PHP"],"step":1}');
	}
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
								}),
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
								"<br>",
								$("<button>").text("submit").click(function(e){
									e.preventDefault();
									getStep(2,{
										"server":$("#sqlServer").val(),
										"db":$("#sqlDb").val(),
										"user":$("#sqlUser").val(),
										"passwd":$("#sqlPasswd").val()
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
							$("#container").append(
								"Almost done! I just need some default information from you.<br>checkLogin file (FULL url) (you\'ll get the signature key at the next screen):",
								$("<input>").attr({
									"type":"text",
									"id":"checkLogin"
								}),
								"<br>Default Channel:",
								$("<input>").attr({
									"type":"text",
									"id":"defaultChan"
								}).val("#"),
								"<br>Default OP Group (you can later on add more via the admin pannel):",
								$("<input>").attr({
									"type":"text",
									"id":"defaultOpGroup"
								}),
								"<br>Default OP User (You can leave this blank, it is a good idea though, as it makes you op even if the op group doens\'t match):",
								$("<input>").attr({
									"type":"text",
									"id":"defaultOp"
								}),
								"<br>",
								$("<button>").text("continue").click(function(e){
									e.preventDefault();
									getStep(7,{
										"checkLogin":$("#checkLogin").val(),
										"chan":$("#defaultChan").val(),
										"group":$("#defaultOpGroup").val(),
										"defaultOp":$("#defaultOp").val()
									});
								})
							);
							break;
						case 8:
							$("#container").append(
								$("<b>").text("Thank you for using OmnomIRC!"),
								"<br>If you are experiencing any issues, please ",
								$("<a>").attr({
									"target":"_blank",
									"href":"https://github.com/Sorunome/OmnomIRC2/issues"
								}).text("report them"),
								"<br>Your personal signature is ",
								$("<b>").text(data.sigKey),
								" (You\'ll have to set it in the checkLogin file)<br>Please make sure that PHP can <u>write</u> to the following files:",
								$("<ul>").append(
									$.map(["config.json.php","config.backup.php","omnomirc_curid","updater.php"],function(file){
										return $("<li>").text(file);
									})
								),
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
			writeConfig();
			echo '{"step":2}';
			break;
		case 2:
			$config['sql']['server'] = $_POST['server'];
			$config['sql']['db'] = $_POST['db'];
			$config['sql']['user'] = $_POST['user'];
			$config['sql']['passwd'] = $_POST['passwd'];
			$sql->query("SELECT 1"); // test query
			// if the query failed we are already dead
			writeConfig();
			echo '{"step":3}';
			break;
		case 3:
			$sqlFile = file_get_contents(realpath(dirname(__FILE__)).'/omnomirc.sql');
			if(!$sqlFile){
				die('{"errors":["SQL file not found, please upload it"],"step":4}');
			}
			$sql->query($sqlFile);
			if(unlink(realpath(dirname(__FILE__)).'/omnomirc.sql')){
				echo '{"step":5}';
			}else{
				echo '{"errors":["Couldn\'t delete file omnomirc.sql, please do so manually"],"step":5}';
			}
			break;
		case 5:
			$sqlFile = file_get_contents(realpath(dirname(__FILE__)).'/irc_vars.sql');
			if(!$sqlFile){
				die('{"errors":["SQL file not found, please upload it"],"step":6}');
			}
			$sql->query($sqlFile);
			if(unlink(realpath(dirname(__FILE__)).'/irc_vars.sql')){
				echo '{"step":7}';
			}else{
				echo '{"errors":["Couldn\'t delete file irc_vars.sql, please do so manually"],"step":7}';
			}
			break;
		case 7:
			$config['networks'][1]['config']['checkLogin'] = $_POST['checkLogin'];
			$config['channels'][0]['networks'][0]['name'] = $_POST['chan'];
			$config['networks'][1]['config']['opGroups'] = Array($_POST['group']);
			if($_POST['defaultOp'] !== ''){
				$sql->query("INSERT INTO `irc_userstuff` (`name`,`globalOp`) VALUES ('%s',1)",strtolower($_POST['defaultOp']));
			}
			
			$config['settings']['hostname'] = $_SERVER['SERVER_NAME'];
			$config['settings']['curidFilePath'] = realpath(dirname(__FILE__)).'/omnomirc_curid';
			$config['security']['sigKey'] = md5(base64_encode(md5(rand(100,9999).'-'.rand(10000,999999))));
			$config['security']['ircPwd'] = md5(base64_encode(md5(rand(100,9999).'-'.rand(10000,999999))));
			writeConfig();
			echo json_encode(Array(
				'step' => 8,
				'sigKey' => $config['security']['sigKey']
			));
			break;
		default:
			die('{"errors":["Unkown installation step"],"step":1}');
	}
}
?>
