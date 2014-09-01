<?php
/* This is a automatically generated config-file by OmnomIRC, please use the admin pannel to edit it! */

function getConfig(){
	$cfg = explode("\n",file_get_contents(realpath(dirname(__FILE__))."/config.php"));
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
			$json .= "\n".$line;
		}
	}
	$json = implode("\n",explode("\n//",$json));
	return json_decode($json,true);
}
$config = getConfig();
//JSONSTART
//{"info":{"version":"2.8.0","installed":false},"sql":{"server":"localhost","db":"omnomirc","user":"omnomirc","passwd":""},"security":{"sigKey":"","ircPwd":""},"settings":{"hostname":"omnomirc","defaultNetwork":1,"curidFilePath":"/usr/share/nginx/html/oirc/omnomirc_curid"},"channels":[{"id":0,"alias":"main chan","enabled":true,"networks":[{"id":1,"name":"#omnimaga","hidden":false,"order":1}]}],"opGroups":["true"],"networks":[{"enabled":true,"id":0,"type":0,"normal":"<b>NICK</b>","userlist":"!NICK","irc":{"color":-2,"prefix":"(!)"},"name":"Server","config":false},{"enabled":true,"id":1,"type":1,"normal":"<a target=\"_top\" href=\"#NICKENCODE\">NICK</a>","userlist":"<a target=\"_top\" href=\"NICKENCODE\"><img src=\"omni.png\">NICK</a>","irc":{"color":12,"prefix":"(O)"},"name":"OmnomIRC","config":{"checkLogin":"link to checkLogin file","externalStyleSheet":"","defaults":"TTF-TTFFFFTT3FTFF"}}]}
//JSONEND
if(isset($_GET["js"])){
	include_once(realpath(dirname(__FILE__))."/omnomirc.php");
	header("Content-type: text/json");
	$channels = Array();
	foreach($config["channels"] as $chan){
		if($chan["enabled"]){
			foreach($chan["networks"] as $cn){
				if($cn["id"] == $you->getNetwork()){
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
	$cl .= "?sid=".urlencode(htmlspecialchars(str_replace(";","%^%",hash_hmac("sha512",$_SERVER["REMOTE_ADDR"],$config["security"]["sigKey"].$ts.$you->getNetwork())."|".$ts)));
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
?>
