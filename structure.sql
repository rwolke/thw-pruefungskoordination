# ************************************************************
# Sequel Pro SQL dump
# Version 4004
#
# http://www.sequelpro.com/
# http://code.google.com/p/sequel-pro/
#
# Host: localhost (MySQL 5.5.32-MariaDB)
# Datenbank: thw
# Erstellungsdauer: 2013-10-04 14:29:12 +0200
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

# Table drop
# ------------------------------------------------------------

DROP TABLE IF EXISTS `job`;
DROP TABLE IF EXISTS `station`;
DROP TABLE IF EXISTS `teilnehmer`;

# Table creation
# ------------------------------------------------------------

CREATE TABLE `station` (
  `stationID` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `krzl` varchar(5) DEFAULT NULL,
  `name` varchar(30) DEFAULT NULL,
  `type` enum('1','2','3','4','5','6','T','M','P') DEFAULT NULL,
  `min` tinyint(3) unsigned DEFAULT NULL,
  `max` tinyint(3) unsigned DEFAULT NULL,
  `dauer` tinyint(3) unsigned DEFAULT NULL,
  `pauseReq` tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (`stationID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `teilnehmer` (
  `teilnehmerID` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `ov` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`teilnehmerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `job` (
  `jobID` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `station` tinyint(3) unsigned NOT NULL,
  `teilnehmer` tinyint(3) unsigned NOT NULL,
  `start` datetime NOT NULL,
  `end` datetime DEFAULT NULL,
  `counts` tinyint(3) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`jobID`),
  UNIQUE KEY `station` (`station`,`teilnehmer`),
  KEY `job_station` (`station`),
  KEY `job_teilnehmer` (`teilnehmer`),
  CONSTRAINT `thw_job_station` FOREIGN KEY (`station`) REFERENCES `station` (`stationID`),
  CONSTRAINT `thw_job_teilnehmer` FOREIGN KEY (`teilnehmer`) REFERENCES `teilnehmer` (`teilnehmerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
