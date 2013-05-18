-- phpMyAdmin SQL Dump
-- version 3.3.2deb1ubuntu1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Oct 20, 2012 at 10:21 AM
-- Server version: 5.1.63
-- PHP Version: 5.3.2-1ubuntu4.18

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `netham`
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `irc_lines`
--


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
  `fromSource` INT(11) NOT NULL DEFAULT '0',
  `type` varchar(45) NOT NULL DEFAULT 'msg',

  PRIMARY KEY (`prikey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `irc_outgoing_messages`
--


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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `irc_permissions`
--


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
  PRIMARY KEY (`usernum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `irc_users`
--



-- ---------------------------------------------
-- Sorunome edit START
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `irc_topics` (
  `channum` int(11) NOT NULL AUTO_INCREMENT,
  `chan` varchar(45) NOT NULL,
  `topic` varchar(1024) NOT NULL,
  PRIMARY KEY (`channum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
-- Sorunome edit END
