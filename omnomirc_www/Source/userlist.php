<?php
	function UpdateUser($nick,$channel,$online){
		global $sql;
		if($channel[0]=='*')
			return;
		$result = $sql->query("SELECT time FROM `irc_users` WHERE `username` = '%s' AND `channel` = '%s' AND `online` = 1",$nick,$channel);
		if(sizeof($result)){ //Update  
			$sql->query("UPDATE `irc_users` SET `time`='%s',`isOnline`='1' WHERE `username` = '%s' AND `channel` = '%s' AND `online` = 1",time(),$nick,$channel);
			$row = $result[0];
			if ($row['time'] < strtotime('-1 minute')) //First time they joined in a minute.
				notifyJoin($nick,$channel);
		}else{ //Insert
			$sql->query("INSERT INTO `irc_users` (`username`,`channel`,`time`,`online`) VALUES('%s','%s','%s',1)",$nick,$channel,time());
			notifyJoin($nick,$channel);
		}
	}
	
	function getOnlineUsers($channel){
		global $sql;
		$userlist = Array();
		if ($channel[0]=='*')
			return $userlist; //PMs have no userlist. 
		$result = $sql->query("SELECT * FROM `irc_users` WHERE `channel` = '%s' AND `time` > %s AND `online` = 1 AND `isOnline`='1'",$channel,strtotime('-1 minute')); //Get all users in the last minute(update.php timeout is 30 seconds).
		foreach($result as $row)
			$userlist[] = $row['username'];
		return $userlist;
	}
	
	function notifyJoin($nick,$channel){
		global $sql;
		$sql->query("INSERT INTO `irc_lines` (name1,type,channel,time,online) VALUES('%s','join','%s','%s',1)",$nick,$channel,time());
	}
	function notifyPart($nick,$channel){
		global $sql;
		$sql->query("INSERT INTO `irc_lines` (name1,type,channel,time,online) VALUES('%s','part','%s','%s',1)",$nick,$channel,time());
	}
	function CleanOfflineUsers(){
		global $sql;
		$result = $sql->query("SELECT `username`,`channel` FROM `irc_users` WHERE `time` < %s  AND `online`='1' AND `isOnline`='1'",strtotime('-1 minute'));
		$sql->query("UPDATE `irc_users` SET `isOnline`='0' WHERE `time` < %s  AND `online`='1' AND `isOnline`='1'",strtotime('-1 minute'));
		foreach($result as $row){
			notifyPart($row['username'],$row['channel']);
		}
	}
	
	function getUserstuffQuery($nick){
		global $sql;
		$temp = $sql->query("SELECT * FROM `irc_userstuff` WHERE name='%s'",strtolower($nick));
		if(isset($temp[0]))
			$userSql = $temp[0];
		else
			$userSql = array('name' => NULL);
		if($userSql['name']==NULL){
			$sql->query("INSERT INTO `irc_userstuff` (name) VALUES('%s')",strtolower($nick));
			$temp = $sql->query("SELECT * FROM `irc_userstuff` WHERE name='%s'",strtolower($nick));
			$userSql = $temp[0];
		}
		return $userSql;
	}
	
	function isGlobalOp($nick,$sig,$id){
		global $opGroups,$checkLoginUrl;
		if(!checkSignature($nick,$sig))
			return false;
		$userSql = getUserstuffQuery($nick);
		if ($userSql['globalOp']==1)
			return true;
		$returnPosition = trim(file_get_contents($checkLoginUrl.'?op&u='.$id.'&nick='.base64_url_encode($nick)));
		//$returnPosition = substr($returnPosition,3,strlen($returnPosition));
		if (in_array($returnPosition,$opGroups))
			return true;
		return false;
	}
	
	function isOp($nick,$sig,$id,$chan){
		global $opGroups,$checkLoginUrl;
		if(!checkSignature($nick,$sig))
			return false;
		$returnPosition = trim(file_get_contents($checkLoginUrl.'?op&u='.$id.'&nick='.base64_url_encode($nick)));
		//$returnPosition = substr($returnPosition,3,strlen($returnPosition));
		if (in_array($returnPosition,$opGroups))
			return true;
		$userSql = getUserstuffQuery($nick);
		if (strpos($userSql['ops'],$chan."\n")!==false)
			return true;
		if ($userSql['globalOp']==1)
			return true;
		return false;
	}
?>