<?PHP
	include_once("Source/cachefix.php");
	include_once("Source/sql.php");
	
	function UpdateUser($nick,$channel,$online)
	{
		if($channel[0]=="*")
			return; //PMs have no userlist.
		$result = sql_query("SELECT * FROM `irc_users` WHERE `username` = '%s' AND `channel` = '%s' AND `online` = 1",$nick,$channel);
		if (mysql_num_rows($result))//Update
		{
			sql_query("UPDATE `irc_users` SET `time`='%s' WHERE `username` = '%s' AND `channel` = '%s' AND `online` = 1",time(),$nick,$channel);
			$row = mysql_fetch_array($result);
			if ($row['time'] < strtotime('-3 minute')) //First time they joined in a minute.
				notifyJoin($nick,$channel);
		}
		elseif(isset($username)) //Insert
		{
			$tempSql = mysql_fetch_array(sql_query("SELECT * FROM irc_users WHERE username='%s' AND channel='%s' AND online='1'",$username,$channel));
			if (isset($tempSql['username']) && $tempSql["username"]==NULL){
				sql_query("INSERT INTO `irc_users` (`username`,`channel`,`time`,`online`) VALUES('%s','%s','%s',1)",$nick,$channel,time());
				notifyJoin($nick,$channel);
			}
		}
	}
	
	function getOnlineUsers($channel)
	{
		$userlist = Array();
		if ($channel[0]=="*")
			return $userlist; //PMs have no userlist.
		$result = sql_query("SELECT * FROM `irc_users` WHERE `channel` = '%s' AND `time` > %s AND `online` = 1",$channel,strtotime('-3 minute')); //Get all users in the last minute(update.php timeout is 30 seconds).
		while($row = mysql_fetch_array($result))
			$userlist[] = $row['username'];
		return $userlist;
	}
	
	function notifyJoin($nick,$channel)
	{
		sql_query("INSERT INTO `irc_lines` (name1,type,channel,time,online) VALUES('%s','join','%s','%s',1)",$nick,$channel,time());
	}
	function notifyPart($nick,$channel)
	{
		sql_query("INSERT INTO `irc_lines` (name1,type,channel,time,online) VALUES('%s','part','%s','%s',1)",$nick,$channel,time());
	}
	function CleanOfflineUsers()
	{
		$result = sql_query("SELECT * FROM `irc_users` WHERE `time` < %s  AND online='1'",strtotime('-3 minute'));
		sql_query("DELETE FROM `irc_users` WHERE `time` < %s  AND online='1'",strtotime('-3 minute'));
		while ($row = mysql_fetch_array($result))
			notifyPart($row['username'],$row['channel']);
	}
?>