-- phpMyAdmin SQL Dump
-- version 4.0.5deb1.precise~ppa.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 01, 2013 at 03:15 PM
-- Server version: 5.5.34-0ubuntu0.12.04.1-log
-- PHP Version: 5.3.10-1ubuntu3.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `omnomirc`
--

-- --------------------------------------------------------

--
-- Table structure for table `irc_lines`
--

CREATE TABLE IF NOT EXISTS `irc_lines` (
  `line_number` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name1` varchar(45) NOT NULL,
  `name2` varchar(45) DEFAULT NULL,
  `message` varchar(1024) DEFAULT NULL,
  `type` varchar(45) NOT NULL,
  `channel` varchar(45) NOT NULL,
  `time` varchar(45) NOT NULL,
  `Online` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`line_number`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1158542 ;

-- --------------------------------------------------------

--
-- Table structure for table `irc_outgoing_messages`
--

CREATE TABLE IF NOT EXISTS `irc_outgoing_messages` (
  `prikey` int(11) NOT NULL AUTO_INCREMENT,
  `message` varchar(1024) NOT NULL,
  `nick` varchar(45) NOT NULL,
  `channel` varchar(45) NOT NULL,
  `action` tinyint(1) NOT NULL DEFAULT '0',
  `fromSource` int(11) NOT NULL DEFAULT '0',
  `type` varchar(45) NOT NULL DEFAULT 'msg',
  PRIMARY KEY (`prikey`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=345471 ;

-- --------------------------------------------------------

--
-- Table structure for table `irc_permissions`
--

CREATE TABLE IF NOT EXISTS `irc_permissions` (
  `generic_autoincrementing_prikey` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(120) DEFAULT NULL,
  `channel` varchar(120) NOT NULL,
  `modes` varchar(120) NOT NULL,
  `isChanModes` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`generic_autoincrementing_prikey`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `irc_topics`
--

CREATE TABLE IF NOT EXISTS `irc_topics` (
  `channum` int(11) NOT NULL AUTO_INCREMENT,
  `chan` varchar(45) NOT NULL,
  `topic` varchar(1024) NOT NULL,
  PRIMARY KEY (`channum`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=17 ;

-- --------------------------------------------------------

--
-- Table structure for table `irc_users`
--

CREATE TABLE IF NOT EXISTS `irc_users` (
  `usernum` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(45) NOT NULL,
  `channel` varchar(45) NOT NULL,
  `online` int(50) NOT NULL DEFAULT '0',
  `time` int(11) DEFAULT NULL,
  `lastMsg` int(11) DEFAULT NULL,
  PRIMARY KEY (`usernum`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=214364 ;

-- --------------------------------------------------------

--
-- Table structure for table `irc_userstuff`
--

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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=829 ;

DELIMITER $$
--
-- Events
--
CREATE EVENT `Clean up Userstuff` ON SCHEDULE EVERY 1 DAY STARTS '2013-10-31 00:00:00' ON COMPLETION NOT PRESERVE ENABLE COMMENT 'Clean up the db' DO DELETE FROM irc_userstuff
	WHERE (ignores = '' OR ignores IS NULL)
	AND (ops = '' OR ops IS NULL)
	AND (bans = '' OR bans IS NULL)
	AND (kicks = '' OR kicks IS NULL)
	AND globalOp = '0'
	AND globalBan = '0'$$

CREATE EVENT `Clean up Userslist` ON SCHEDULE EVERY 1 DAY STARTS '2013-10-31 00:00:00' ON COMPLETION NOT PRESERVE ENABLE COMMENT 'Clean up the Userlist' DO DELETE FROM irc_users
	WHERE (lastMsg = '' OR lastMsg IS NULL)
	AND isOnline='0'$$
DELIMITER ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
