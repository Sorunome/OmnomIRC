<?php
class Sql{
	private $mysqliConnection = false;
	private $queryNum = 0;
	private $curDb = NULL;
	private $stmt;
	private function connectSql(){
		global $vars;
		if($this->mysqliConnection !== false){
			return $this->mysqliConnection;
		}
		if($this->curDb===NULL){
			$this->curDb = $vars->get('sql_database');
		}
		$mysqli = new mysqli($vars->get('sql_host'),$vars->get('sql_user'),$vars->get('sql_password'),$this->curDb);
		if ($mysqli->connect_errno){
			die('Could not connect to SQL DB: '.$mysqli->connect_errno.' '.$mysqli->connect_error);
		}
		$mysqli->autocommit(true);
		$this->mysqliConnection = $mysqli;
		return $mysqli;
	}
	private function reportError($type,$a = array(),$stack = 1,$prep = false){
		var_dump($this->stmt->errno);
		var_dump($this->mysqliConnection->errno);
		var_dump($this->mysqliConnection->error);
		return;
		global $json;
		if($prep){
			$mysqli = $this->stmt;
		}else{
			$mysqli = $this->connectSql();
		}
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,$stack+1);
		$json->addError(array_merge(array(
			'type' => 'mysql',
			'file' => $trace[$stack]['file'],
			'line' => $trace[$stack]['line'],
			'method' => $type,
			'errno' => $mysqli->errno,
			'error' => $mysqli->error,
		),$a));
	}
	private function refValues($arr){
		if (strnatcmp(phpversion(),'5.3') >= 0){  //Reference is required for PHP 5.3+
			$refs = array();
			foreach($arr as $key => $value){
				$refs[$key] = &$arr[$key];
			}
			return $refs;
		}
		return $arr;
	}
	public function prepare($query, $stack = 0){
		$mysqli = $this->connectSql();
		if(!$this->stmt = $mysqli->prepare($query)){
			$this->reportError('prepare',array('query' => $query),$stack+1);
			return false;
		}
		return true;
	}
	public function bind_param($params,$stack = 0){
		if(is_array($params)){
			if(empty($params)){
				return true; // nothing to do here!
			}
			$s = '';
			foreach($params as $p){
				switch(gettype($p)){
					case 'boolean':
					case 'integer':
						$s .= 'i';
						break;
					case 'double':
						$s .= 'd';
						break;
					default:
						$s .= 's';
				}
			}
			array_unshift($params,$s);
		}else{
			$params = func_get_args();
		}
		if(!call_user_func_array(array($this->stmt,'bind_param'),$this->refValues($params))){
			$this->reportError('bind_param',array(),$stack+1,true);
			return false;
		}
		return true;
	}
	public function execute($stack = 0){
		$this->stmt->execute();
		return true;
	}
	public function close_prepare(){
		$this->stmt->close();
	}
	public function query($query,$params = array(),$num = false,$stack = 0){
		$this->queryNum++;
		if($this->prepare($query,$stack+1) && $this->bind_param($params,$stack+1) && $this->execute($stack +1)){
			$result = $this->stmt->get_result();
			if($result === false && $this->stmt->errno == 0){ // bug prevention
				$result = true;
			}
			if($result===false){
				$this->reportError('misc',array(),$stack+1);
				$this->close_prepare();
				return array();
			}
			if($result===true){ //nothing returned
				$this->close_prepare();
				return array();
			}
			$res = array();
			$i = 0;
			while($row = $result->fetch_assoc()){
				$res[] = $row;
				if($num!==false && $i===$num){
					$result->free();
					$this->close_prepare();
					return $row;
				}
				if($i++>=1000)
					break;
			}
			if($res === array()){
				$fields = $result->fetch_fields();
				for($i=0;$i<count($fields);$i++)
					$res[$fields[$i]->name] = NULL;
				if($num===false)
					$res = array($res);
			}
			$result->free();
			$this->close_prepare();
			return $res;
		}
		return array();
	}
	public function getQueryNum(){
		return $this->queryNum;
	}
	public function switchDb($db){
		$this->curDb = $db;
		return $this->connectSql()->select_db($db);
	}
	public function insertId(){
		$mysqli = $this->connectSql();
		return $mysqli->insert_id;
	}
}
$sql = new Sql();
?>