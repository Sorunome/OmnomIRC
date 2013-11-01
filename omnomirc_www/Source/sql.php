<?PHP
	/*
	function connectSQL()
	{
		global $sql_user,$sql_password,$sql_server,$sql_db;
		$sqlConnection = new mysqli($sql_server,$sql_user,$sql_password,$sql_db); //This is a connection to a local SQL server.
		if ($mysqli->connect_errno) 
			die("Failed to connect to MySQL: (".$mysqli->connect_errno.") ".$mysqli->connect_error);
		return $sqlConnection;
	}
	
	function sql_query()
	{
		$sqlConnection = connectSQL();
		$params = func_get_args();
		$query = $params[0];
		$args = Array();
		for ($i=1;$i<count($params);$i++)
			$args[$i-1] = mysql_real_escape_string($params[$i],$sqlConnection);
		$result = $mysqli->query(vsprintf($query,$args),$sqlConnection);
		if (!$result) 
			die("Query failed: (".$mysqli->errno.") ".$mysqli->error." Query: ".vsprintf($query,$args));
		return $result;
	}
	*/
	function connectSQL(){
		global $sql_user,$sql_password,$sql_server,$sql_db;
		$sqlConnection = mysql_connect($sql_server,$sql_user,$sql_password); //This is a connection to a local SQL server.
		if (!$sqlConnection) 
			die('Could not connect to SQL DB: '.mysql_error());
		if (!mysql_select_db($sql_db,$sqlConnection))
			die('Could not select DB: '.mysql_error());
		return $sqlConnection;
	}
	
	function sql_query(){
		$sqlConnection = connectSQL();
		$params = func_get_args();
		$query = $params[0];
		$args = Array();
		for ($i=1;$i<count($params);$i++)
			$args[$i-1] = mysql_real_escape_string($params[$i],$sqlConnection);
		$result = mysql_query(vsprintf($query,$args),$sqlConnection);
		if (!$result) 
			die(mysql_error().'Query: '.vsprintf($query,$args));
		return $result;
	}
?>