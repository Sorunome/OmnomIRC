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
//{"info":{"version":"2.7.0","installed":true},"sql":{"server":"localhost","db":"omnomirc","user":"omnomirc","passwd":""},"security":{"sigKey":"","calcKey":"","cookie":"PHPSESSID"},"settings":{"hostname":"omnomirc","checkLoginUrl":"http://omnomirc/checkLogin-smf.php","curidFilePath":"/usr/share/nginx/html/oirc/omnomirc_curid","externalStyleSheet":"","defaults":"TTF-TTFFFFTT3FTFF"},"channels":[{"id":0,"alias":"main chan","enabled":true,"networks":[{"id":1,"name":"#omnimaga","hidden":false,"order":1},{"id":3,"name":"#omnimaga","hidden":false,"order":1}]}],"exChans":[],"opGroups":["true"],"networks":[{"normal":"<b>NICK</b>","userlist":"!NICK","name":"Server"},{"normal":"<a target=\"_top\" href=\"#NICKENCODE\">NICK</a>","userlist":"<a target=\"_top\" href=\"NICKENCODE\"><img src=\"omni.png\">NICK</a>","name":"OmnomIRC"},{"normal":"<span style=\"color:#8A5D22\">(C)</span> NICK","userlist":"<span style=\"color:#8A5D22\">(C)</span> NICK","name":"Calcnet"},{"normal":"NICK","userlist":"#NICK","name":"IRC"}],"irc":{"main":{"servers":[{"server":"irc server","port":6667,"nickserv":"nickserv password","network":3}],"nick":"OmnomIRC"},"topic":{"servers":[{"server":"irc server","port":6667,"nickserv":"nickserv password","network":3}],"nick":"TopicBot"},"password":""},"gcn":{"enabled":false,"server":"myurl.com","port":4295}}
//JSONEND
//$defaultChan = $config["channels"][0]["chan"];
if(isset($_GET["js"])){
	include_once(realpath(dirname(__FILE__))."/omnomirc.php");
	header("Content-type: text/json");
	$channels = Array();
	foreach($config["channels"] as $chan){
		if($chan["enabled"]){
			foreach($chan["networks"] as $cn){
				if($cn["id"] == 1){
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
	echo json_encode(Array(
		"hostname" => $config["settings"]["hostname"],
		"channels" => $channels,
		"smileys" => $vars->get("smileys"),
		"networks" => $config["networks"],
		"checkLoginUrl" => (isset($_COOKIE[$config["security"]["cookie"]])?$config["settings"]["checkLoginUrl"]."?sid=".urlencode(htmlspecialchars(str_replace(";","%^%",$_COOKIE[$config["security"]["cookie"]]))):$config["settings"]["checkLoginUrl"]."?sid=THEGAME"),
		"defaults" => $config["settings"]["defaults"]
	));
}
?>