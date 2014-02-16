<?php
class sqli{
	private $mysqliConnection;
	private function connectSql(){
		global $config;
		if(isset($this->mysqliConnection)){
			return $this->mysqliConnection;
		}
		$mysqli = new mysqli($config['sql']['server'],$config['sql']['user'],$config['sql']['passwd'],$config['sql']['db']);
		if ($mysqli->connect_errno) 
			die('Could not connect to SQL DB: '.$mysqli->connect_errno.' '.$mysqli->connect_error);
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
		if($mysqli->errno==1065) //empty
			return array();
		if($mysqli->errno!=0) 
			die($mysqli->error.' Query: '.vsprintf($query,$args));
		if($result===true) //nothing returned
			return array();
		$res = array();
		$i = 0;
		while($row = $result->fetch_assoc()) {
			$res[] = $row;
			if($i++>=150)
				break;
		}
		if($res === []){
			$fields = $result->fetch_fields();
			for($i=0;$i<count($fields);$i++)
				$res[$fields[$i]->name] = NULL;
			$res = array($res);
		}
		$result->free();
		return $res;
	}
}
$sql = new sqli;
?>