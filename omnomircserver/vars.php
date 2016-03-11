<?php
class Vars{
	private $config;
	public function __construct(){
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
		$this->config = json_decode($json,true);
		if(json_last_error()){ //if error back to default config
			$this->config = json_decode('{"sql_host":"localhost","sql_user":"sql_user","sql_database":"sql_database","sql_password":"sql_password"}',true);
		}
	}
	public function set($s,$c,$t = NULL){ //set a global variable
		global $sql;
		$type = NULL;
		if(isset($this->config[$s])){ //we can't set variables that are set over config.json
			return false;
		}
		if($t===NULL){ //no third parameter, we detect the type
			switch(gettype($c)){
				case 'integer':
					$type = 1;
					break;
				case 'double':
					$type = 2;
					break;
				case 'boolean':
					$type = 4;
					break;
				case 'array':
					$c = json_encode($c);
					if(json_last_error()===0){
						$type = 5;
					}
					break;
				case 'object':
					$c = json_encode($c);
					if(json_last_error()===0){
						$type = 3;
					}
					break;
				case 'string':
				default:
					json_decode($c);
					if(json_last_error()){
						$type = 0;
					}else{
						$type = 3;
					}
			}
		}else{
			switch($t){ //user said which type he wants, we try to convert the variable
				case 'integer':
				case 'int':
					$c = (int)$c;
					$type = 1;
					break;
				case 'double':
				case 'float':
					$c = (float)$c;
					$type = 2;
					break;
				case 'boolean':
				case 'bool':
					$c = (bool)$c;
					$type = 4;
					break;
				case 'string':
				case 'str':
					$c = (string)$c;
					$type = 0;
					break;
				case 'json':
				case 'object':
					if(gettype($c)=='string'){
						json_decode($c);
						if(json_last_error()){
							return false;
						}
					}else{
						$c = json_encode($c);
						if(json_last_error()){
							return false;
						}
					}
					$type = 3;
					break;
				case 'array': //array is actually JSON, only with enabling the array option when parsing
					if(gettype($c)=='string'){
						json_decode($c);
						if(json_last_error()){
							return false;
						}
					}else{
						$c = json_encode($c);
						if(json_last_error()){
							return false;
						}
					}
					$type = 5;
					break;
			}
		}
		if($type===NULL){ //if we couldn't set a type return false
			return false;
		}
		$r = $sql->query("SELECT id,type FROM vars WHERE name=?",[$s],0);
		if(isset($r['id'])){ //check if we need to update or add a new
			$sql->query("UPDATE vars SET value=?,type=? WHERE name=?",[$c,$type,$s]);
		}else{
			$sql->query("INSERT INTO vars (name,value,type) VALUES(?,?,?)",[$s,$c,(int)$type]);
		}
		return true;
	}
	public function get($s){
		global $sql;
		if(isset($this->config[$s])){ //it is from config.json
			return $this->config[$s];
		}
		$res = $sql->query("SELECT value,type FROM vars WHERE name=?",[$s],0);
		switch((int)$res['type']){ //convert to types, else return false
			case 0:
				return (string)$res['value'];
			case 1:
				return (int)$res['value'];
			case 2:
				return (float)$res['value'];
			case 3:
				$json = json_decode($res['value']);
				if(json_last_error()){
					return false;
				}
				return $json;
			case 4:
				return (bool)$res['value'];
			case 5:
				$json = json_decode($res['value'],true);
				if(json_last_error()){
					return false;
				}
				return $json;
		}
		return false;
	}
}
$vars = new Vars();
?>