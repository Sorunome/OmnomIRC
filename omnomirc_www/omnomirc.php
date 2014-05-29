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
class Json{
	private $json;
	private $warnings;
	private $errors;
	public function clear(){
		$this->warnings = Array();
		$this->errors = Array();
		$this->json = Array();
	}
	public function __construct(){
		$this->clear();
	}
	public function addWarning($s){
		$this->warnings[] = $s;
	}
	public function addError($s){
		$this->errors[] = $s;
	}
	public function add($key,$value){
		$this->json[$key] = $value;
	}
	public function get(){
		$this->json['warnings'] = $this->warnings;
		$this->json['errors'] = $this->errors;
		return json_encode($this->json);
	}
	public function hasErrors(){
		return sizeof($this->errors) > 0;
	}
	public function hasWarnings(){
		return sizeof($this->warnings) > 0;
	}
}
$json = new Json();
function errorHandler($errno,$errstr,$errfile,$errline){
	global $json;
	switch($errno){
		case E_USER_WARNING:
		case E_USER_NOTICE:
			$json->addWarning(Array('type' => 'php','number' => $errno,'message'=>$errstr,'file' => $errfile,'line' => $errline));
			break;
		//case E_USER_ERROR: // no need, already caught by default.
		default:
			$json->addError(Array('type' => 'php','number' => $errno,'message'=>$errstr,'file' => $errfile,'line' => $errline));
	}
}
set_error_handler('errorHandler',E_ALL);
header('Last-Modified: Thu, 01-Jan-1970 00:00:01 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0',false);
header('Pragma: no-cache');
header('Content-Type: text/json');
date_default_timezone_set('UTC'); 
include_once(realpath(dirname(__FILE__)).'/config.php');
function base64_url_encode($input){
	return strtr(base64_encode($input),'+/=','-_,');
}
function base64_url_decode($input){
	return base64_decode(strtr($input,'-_,','+/=')); 
}
class Sqli{
	private $mysqliConnection;
	private function connectSql(){
		global $config,$json;
		if(isset($this->mysqliConnection)){
			return $this->mysqliConnection;
		}
		$mysqli = new mysqli($config['sql']['server'],$config['sql']['user'],$config['sql']['passwd'],$config['sql']['db']);
		if($mysqli->connect_errno){
			die('Could not connect to SQL DB: '.$mysqli->connect_errno.' '.$mysqli->connect_error);
		}
		if(!$mysqli->set_charset('utf8')){
			$json->addError(Array('type' => 'mysql','message' => 'Couldn\'t use utf8'));
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
		$result = $mysqli->query(vsprintf($query,$args));
		if($mysqli->errno==1065){ //empty
			return array();
		}
		if($mysqli->errno!=0){
			die($mysqli->error.' Query: '.vsprintf($query,$args));
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
class Secure{
	public function sign($s){
		global $config;
		return mcrypt_encrypt(MCRYPT_RIJNDAEL_256,$config['security']['sigKey'],$s,MCRYPT_MODE_ECB);
	}
}
$security = new Secure();
class Users{
	public function notifyJoin($nick,$channel){
		global $sql;
		if($nick){
			$sql->query("INSERT INTO `irc_lines` (name1,type,channel,time,online) VALUES('%s','join','%s','%s',1)",$nick,$channel,time());
		}
	}
	public function notifyPart($nick,$channel){
		global $sql;
		if($nick){
			$sql->query("INSERT INTO `irc_lines` (name1,type,channel,time,online) VALUES('%s','part','%s','%s',1)",$nick,$channel,time());
		}
	}
	public function clean(){
		global $sql;
		$result = $sql->query("SELECT `username`,`channel` FROM `irc_users` WHERE `time` < %s  AND `online`='1' AND `isOnline`='1'",strtotime('-1 minute'));
		$sql->query("UPDATE `irc_users` SET `isOnline`='0' WHERE `time` < %s  AND `online`='1' AND `isOnline`='1'",strtotime('-1 minute'));
		foreach($result as $row){
			$this->notifyPart($row['username'],$row['channel']);
		}
	}
}
$users = new Users();
class You{
	public $nick;
	private $sig;
	private $id;
	private $loggedIn;
	private $globalOps;
	private $ops;
	private $infoStuff;
	public $chan;
	public function __construct($n = false){
		global $security,$defaultChan,$json,$ADMINPAGE;
		if($n!==false){
			$this->nick = $n;
		}elseif(isset($_GET['nick'])){
			$this->nick = base64_url_decode($_GET['nick']);
		}else{
			$json->addError('Nick not set');
			$this->nick = '0';
		}
		if(isset($_GET['signature'])){
			$this->sig = base64_url_decode($_GET['signature']);
		}else{
			$json->addError('Signature not set');
			$this->sig = '';
		}
		if(isset($_GET['id'])){
			$this->id = (int)$_GET['id'];
		}else{
			$json->addWarning('ID not set, some features may be unavailable');
			$this->id = 0;
		}
		if(isset($_GET['channel'])){
			$this->chan = base64_url_decode($_GET['channel']);
			$this->chan = ($this->chan==''?'0':$this->chan);
			if($this->chan[0]!="*" and $this->chan[0]!="#" and $this->chan[0]!="@" and $this->chan[0]!="&"){
				$json->addError('Invalid channel');
				$this->chan = '0';
			}
		}else{
			if($ADMINPAGE!==true){
				$json->addWarning('Didn\'t set a channel, defaulting to '.$defaultChan);
			}
			$this->chan = $defaultChan;
		}
		$this->globalOps = NULL;
		$this->ops = NULL;
		$this->infoStuff = NULL;
		$this->loggedIn = ($this->sig == base64_url_encode($security->sign($this->nick)));
		if(!$this->loggedIn){
			$json->addWarning('Not logged in');
		}
	}
	public function update(){
		global $sql,$users;
		if($this->chan[0]=='*' || $this->chan=='0'){
			return;
		}
		$result = $sql->query("SELECT usernum,time,isOnline FROM `irc_users` WHERE `username` = '%s' AND `channel` = '%s' AND `online` = 1",$this->nick,$this->chan);
		if($result[0]['usernum']!==NULL){ //Update  
			$sql->query("UPDATE `irc_users` SET `time`='%s',`isOnline`='1' WHERE `usernum` = %d",time(),(int)$result[0]['usernum']);
			if((int)$result[0]['isOnline'] == 0){
				$users->notifyJoin($this->nick,$this->chan);
			}
		}else{
			$sql->query("INSERT INTO `irc_users` (`username`,`channel`,`time`,`online`) VALUES('%s','%s','%s',1)",$this->nick,$this->chan,time());
			$users->notifyJoin($this->nick,$this->chan);
		}
		$users->clean();
	}
	public function info(){
		global $sql;
		if($this->infoStuff !== NULL){
			return $this->infoStuff;
		}
		$temp = $sql->query("SELECT * FROM `irc_userstuff` WHERE name='%s'",strtolower($this->nick));
		$userSql = $temp[0];
		if($userSql['name']===NULL){
			$sql->query("INSERT INTO `irc_userstuff` (name) VALUES('%s')",strtolower($this->nick));
			$temp = $sql->query("SELECT * FROM `irc_userstuff` WHERE name='%s'",strtolower($this->nick));
			$userSql = $temp[0];
		}
		$this->infoStuff = $userSql;
		return $userSql;
	}
	public function isGlobalOp(){
		global $config;
		if(!$config['info']['installed']){
			return true;
		}
		if($this->globalOps !== NULL){
			return $this->globalOps;
		}
		if(!$this->loggedIn){
			$this->globalOps = false;
			return false;
		}
		$userSql = $this->info();
		if($userSql['globalOp']==1){
			$this->globalOps = true;
			return true;
		}
		$returnPosition = json_decode(trim(file_get_contents($config['settings']['checkLoginUrl'].'?op&u='.$this->id.'&nick='.base64_url_encode($this->nick))));
		if(in_array($returnPosition->group,$config['opGroups'])){
			$this->globalOps = true;
			return true;
		}
		$this->globalOps = false;
		return false;
	}
	public function isOp(){
		global $config;
		if($this->ops !== NULL){
			return $this->ops;
		}
		if($this->isGlobalOp()){
			$this->ops = true;
			return true;
		}
		$userSql = $this->info($nick);
		if(strpos($userSql['ops'],$this->chan."\n")!==false){
			$this->ops = true;
			return true;
		}
		$this->ops = false;
		return false;
	}
	public function isBanned(){
		$userSql = $this->info();
		if(strpos($userSql['bans'],$this->chan."\n")!==false || $userSql['globalBan']=='1'){
			return true;
		}
		return false;
	}
}
$you = new You();
?>