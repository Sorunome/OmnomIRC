SET time_zone = "+00:00";


DROP TABLE IF EXISTS `irc_lines`;
CREATE TABLE IF NOT EXISTS `irc_lines` (
  `line_number` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name1` varchar(45) NOT NULL,
  `name2` varchar(45) DEFAULT NULL,
  `message` varchar(1024) DEFAULT NULL,
  `type` varchar(45) NOT NULL,
  `channel` varchar(45) NOT NULL,
  `time` varchar(45) NOT NULL,
  `Online` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`line_number`),
  KEY `time` (`time`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

DROP TABLE IF EXISTS `irc_lines_old`;
CREATE TABLE IF NOT EXISTS `irc_lines_old` (
  `line_number` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name1` varchar(45) NOT NULL,
  `name2` varchar(45) DEFAULT NULL,
  `message` varchar(1024) DEFAULT NULL,
  `type` varchar(45) NOT NULL,
  `channel` varchar(45) NOT NULL,
  `time` varchar(45) NOT NULL,
  `Online` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`line_number`),
  KEY `time` (`time`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

DROP TABLE IF EXISTS `irc_outgoing_messages`;
CREATE TABLE IF NOT EXISTS `irc_outgoing_messages` (
  `prikey` int(11) NOT NULL AUTO_INCREMENT,
  `message` varchar(1024) NOT NULL,
  `nick` varchar(45) NOT NULL,
  `channel` varchar(45) NOT NULL,
  `action` tinyint(1) NOT NULL DEFAULT '0',
  `fromSource` int(11) NOT NULL DEFAULT '0',
  `type` varchar(45) NOT NULL DEFAULT 'msg',
  PRIMARY KEY (`prikey`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

DROP TABLE IF EXISTS `irc_permissions`;
CREATE TABLE IF NOT EXISTS `irc_permissions` (
  `generic_autoincrementing_prikey` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(120) DEFAULT NULL,
  `channel` varchar(120) NOT NULL,
  `modes` varchar(120) NOT NULL,
  `isChanModes` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`generic_autoincrementing_prikey`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ;

DROP TABLE IF EXISTS `irc_topics`;
CREATE TABLE IF NOT EXISTS `irc_topics` (
  `channum` int(11) NOT NULL AUTO_INCREMENT,
  `chan` varchar(45) NOT NULL,
  `topic` varchar(1024) NOT NULL,
  PRIMARY KEY (`channum`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

DROP TABLE IF EXISTS `irc_users`;
CREATE TABLE IF NOT EXISTS `irc_users` (
  `usernum` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(45) NOT NULL,
  `channel` varchar(45) NOT NULL,
  `online` int(50) NOT NULL DEFAULT '0',
  `time` int(11) DEFAULT NULL,
  `lastMsg` int(11) DEFAULT NULL,
  `isOnline` tinyint(2) NOT NULL DEFAULT '1',
  PRIMARY KEY (`usernum`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

DROP TABLE IF EXISTS `irc_userstuff`;
CREATE TABLE IF NOT EXISTS `irc_userstuff` (
  `usernum` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `ignores` varchar(1024) NOT NULL,
  `ops` varchar(1024) NOT NULL,
  `bans` varchar(1024) NOT NULL,
  `kicks` varchar(1024) NOT NULL,
  `globalOp` int(10) NOT NULL,
  `globalBan` int(10) NOT NULL,
  PRIMARY KEY (`usernum`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;



DROP EVENT IF EXISTS `Clean up Userstuff`;
CREATE EVENT `Clean up Userstuff` ON SCHEDULE EVERY 1 DAY STARTS '2013-10-31 00:00:00' ON COMPLETION NOT PRESERVE ENABLE COMMENT 'Clean up the db' DO DELETE FROM irc_userstuff
	WHERE (ignores = '' OR ignores IS NULL)
	AND (ops = '' OR ops IS NULL)
	AND (bans = '' OR bans IS NULL)
	AND (kicks = '' OR kicks IS NULL)
	AND globalOp = '0'
	AND globalBan = '0';

DROP EVENT IF EXISTS `Clean up Userslist`;
CREATE EVENT `Clean up Userslist` ON SCHEDULE EVERY 1 DAY STARTS '2013-10-31 00:00:00' ON COMPLETION NOT PRESERVE ENABLE COMMENT 'Clean up the Userlist' DO DELETE FROM irc_users
	WHERE (lastMsg = '' OR lastMsg IS NULL)
	AND isOnline='0';

DELIMITER $$
DROP EVENT IF EXISTS `Flush Logs Nightly`$$
CREATE EVENT `Flush Logs Nightly` ON SCHEDULE EVERY 1 DAY STARTS '2013-10-31 00:00:00' ON COMPLETION NOT PRESERVE ENABLE COMMENT 'Flushes the logs into the archive table' DO BEGIN
	SET @time := (select max(time) from irc_lines);
	INSERT INTO irc_lines_old (name1,name2,message,type,channel,time,Online)
	SELECT name1,name2,message,type,channel,time,Online
	FROM irc_lines;
	DELETE FROM irc_lines WHERE time < @time;
END$$
DELIMITER ;