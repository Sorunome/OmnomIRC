<?php
class sql{
	private $mysqliConnection;
	private function connectSql(){
		global $sql_user,$sql_password,$sql_server,$sql_db;
		if(isset($this->mysqliConnection)){
			return $this->mysqliConnection;
		}
		$mysqli = new mysqli($sql_server,$sql_user,$sql_password,$sql_db);
		if ($mysqli->connect_errno) 
			die('Could not connect to SQL DB: '.$mysqli->connect_errno.' '.$mysqli->connect_error);
		$this->mysqliConnection = $mysqli;
		return $mysqli;
	}
	public function query(){
		$mysqli = $this->connectSql();
		$params = func_get_args();
		$query = $params[0];
		$args = Array();
		for($i=1;$i<count($params);$i++)
			$args[$i-1] = $mysqli->real_escape_string($params[$i]);
		$result = $mysqli->query(vsprintf($query,$args));
		if($mysqli->errno==1065) //empty
			return [];
		if($mysqli->errno!=0) 
			die($mysqli->error.' Query: '.vsprintf($query,$args));
		if($result===true) //nothing returned
			return [];
		$res = $result->fetch_all(MYSQLI_BOTH);
		$result->free();
		return $res;
	}
}
$sql = new sql;
?>