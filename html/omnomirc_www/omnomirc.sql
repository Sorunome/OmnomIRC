SET time_zone = "+00:00";


DROP TABLE IF EXISTS `{db_prefix}lines`;
CREATE TABLE IF NOT EXISTS `{db_prefix}lines` (
  `line_number` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name1` varchar(45) NOT NULL,
  `name2` varchar(45) DEFAULT NULL,
  `message` varchar(1024) DEFAULT NULL,
  `type` varchar(45) NOT NULL,
  `channel` int(10) NOT NULL,
  `time` varchar(45) NOT NULL,
  `Online` int(10) NOT NULL DEFAULT '0',
  `uid` int(10) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`line_number`),
  KEY `time` (`time`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

DROP TABLE IF EXISTS `{db_prefix}lines_old`;
CREATE TABLE IF NOT EXISTS `{db_prefix}lines_old` (
  `line_number` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name1` varchar(45) NOT NULL,
  `name2` varchar(45) DEFAULT NULL,
  `message` varchar(1024) DEFAULT NULL,
  `type` varchar(45) NOT NULL,
  `channel` int(10) NOT NULL,
  `time` varchar(45) NOT NULL,
  `Online` int(10) NOT NULL DEFAULT '0',
  `uid` int(10) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`line_number`),
  KEY `time` (`time`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

DROP TABLE IF EXISTS `{db_prefix}permissions`;
CREATE TABLE IF NOT EXISTS `{db_prefix}permissions` (
  `generic_autoincrementing_prikey` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(120) DEFAULT NULL,
  `channel` varchar(120) NOT NULL,
  `modes` varchar(120) NOT NULL,
  `isChanModes` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`generic_autoincrementing_prikey`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;

DROP TABLE IF EXISTS `{db_prefix}channels`;
CREATE TABLE IF NOT EXISTS `{db_prefix}channels` (
  `channum` int(11) NOT NULL AUTO_INCREMENT,
  `chan` varchar(45) NOT NULL,
  `topic` varchar(1024) NOT NULL DEFAULT '',
  `ops` TEXT NOT NULL DEFAULT '',
  `bans` TEXT NOT NULL DEFAULT '',
  `modes` TEXT NOT NULL DEFAULT '',
  PRIMARY KEY (`channum`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

DROP TABLE IF EXISTS `{db_prefix}users`;
CREATE TABLE IF NOT EXISTS `{db_prefix}users` (
  `usernum` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(45) NOT NULL,
  `channel` varchar(45) NOT NULL,
  `online` int(50) NOT NULL DEFAULT '0',
  `time` int(11) DEFAULT NULL,
  `lastMsg` int(11) DEFAULT NULL,
  `isOnline` tinyint(2) NOT NULL DEFAULT '1',
  `uid` INT NOT NULL DEFAULT '-1',
  PRIMARY KEY (`usernum`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

DROP TABLE IF EXISTS `{db_prefix}userstuff`;
CREATE TABLE IF NOT EXISTS `{db_prefix}userstuff` (
  `usernum` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `network` int(11) NOT NULL,
  `ignores` varchar(1024) NOT NULL,
  `kicks` varchar(1024) NOT NULL,
  `globalOp` int(10) NOT NULL,
  `globalBan` int(10) NOT NULL,
  `uid` int(11) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`usernum`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

DROP TABLE IF EXISTS `{db_prefix}vars`;
CREATE TABLE IF NOT EXISTS `{db_prefix}vars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `value` text NOT NULL,
  `type` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;


ALTER TABLE `{db_prefix}lines`
 ADD KEY `name1` (`name1`), ADD KEY `name2` (`name2`), ADD KEY `channel` (`channel`), ADD KEY `type` (`type`);

ALTER TABLE `{db_prefix}lines_old`
 ADD KEY `name1` (`name1`), ADD KEY `name2` (`name2`), ADD KEY `channel` (`channel`);

ALTER TABLE `{db_prefix}users`
 ADD KEY `channel` (`channel`), ADD KEY `isOnline` (`isOnline`), ADD KEY `username` (`username`), ADD KEY `online` (`online`), UNIQUE `unique_trigger` ( `username` , `channel` , `online` ) COMMENT '';

ALTER TABLE `{db_prefix}userstuff` ADD UNIQUE (`uid` ,`network`) COMMENT '';

ALTER TABLE `{db_prefix}channels` ADD UNIQUE (`chan`) COMMENT '';


DROP EVENT IF EXISTS `Clean up Userstuff`;
CREATE EVENT `Clean up Userstuff` ON SCHEDULE EVERY 1 DAY ON COMPLETION NOT PRESERVE ENABLE COMMENT 'Clean up the db' DO DELETE FROM {db_prefix}userstuff
	WHERE (ignores = '' OR ignores IS NULL)
	AND (ops = '' OR ops IS NULL)
	AND (bans = '' OR bans IS NULL)
	AND (kicks = '' OR kicks IS NULL)
	AND globalOp = '0'
	AND globalBan = '0';

DROP EVENT IF EXISTS `Clean up Userslist`;
CREATE EVENT `Clean up Userslist` ON SCHEDULE EVERY 1 DAY ON COMPLETION NOT PRESERVE ENABLE COMMENT 'Clean up the Userlist' DO DELETE FROM {db_prefix}users
	WHERE (lastMsg = '' OR lastMsg IS NULL)
	AND isOnline='0';

DELIMITER $$
DROP EVENT IF EXISTS `Flush Logs Nightly`$$
CREATE EVENT `Flush Logs Nightly` ON SCHEDULE EVERY 1 DAY  ON COMPLETION NOT PRESERVE ENABLE COMMENT 'Flushes the logs into the archive table' DO BEGIN
	SET @time := (select max(`time`) from `{db_prefix}lines`);
	INSERT INTO `{db_prefix}lines_old` (`name1`,`name2`,`message`,`type`,`channel`,`time`,`Online`,`uid`)
	SELECT `name1`,`name2`,`message`,`type`,`channel`,`time`,`Online`,`uid`
	FROM `{db_prefix}lines` WHERE `time` < @time ORDER BY `line_number` ASC;
	DELETE FROM `{db_prefix}lines` WHERE `time` < @time;
END$$
DELIMITER ;
SET GLOBAL event_scheduler = "ON";

DELIMITER $$
DROP FUNCTION IF EXISTS `{db_prefix}getchanid`$$
CREATE FUNCTION `{db_prefix}getchanid`(`chan_in` VARCHAR(45)) RETURNS INT(11) NOT DETERMINISTIC NO SQL SQL SECURITY DEFINER BEGIN
	DECLARE chan_id INT DEFAULT -1;
	SELECT `channum` INTO chan_id FROM `{db_prefix}channels` WHERE `chan`=LOWER(chan_in);
	IF chan_id = -1 THEN
		INSERT INTO `{db_prefix}channels` (`chan`) VALUES (LOWER(chan_in));
		return LAST_INSERT_ID();
	END IF;
	return chan_id;
END$$
DELIMITER ;
