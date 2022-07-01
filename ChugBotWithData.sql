-- MySQL dump 10.13  Distrib 8.0.29, for Linux (x86_64)
--
-- Host: localhost    Database: camprama_chugbot_db
-- ------------------------------------------------------
-- Server version	8.0.29-0ubuntu0.20.04.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

# Create the database
CREATE DATABASE IF NOT EXISTS camprama_chugbot_db COLLATE utf8_unicode_ci;

# Create a user for the chugbot program (if it does not already exist), and
# grant the access it needs.
CREATE USER IF NOT EXISTS 'root'@'localhost' IDENTIFIED BY '$2y$10$P1hpl8Hj2fdZnE3hokjeK.SyRFacwhqtS0I8Wn0NKOTUhFZmoMRva';
GRANT CREATE,INSERT,SELECT,UPDATE,DELETE,LOCK TABLES ON camprama_chugbot_db.* TO 'root'@'localhost';

# Switch to the new database, in preparation for creating tables.
USE camprama_chugbot_db;

--
-- Table structure for table `admin_data`
--

DROP TABLE IF EXISTS `admin_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_data` (
  `admin_email` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `admin_password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `admin_email_cc` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `admin_email_from_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `send_confirm_email` tinyint(1) NOT NULL DEFAULT '1',
  `pref_count` int NOT NULL DEFAULT '6',
  `regular_user_token` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'kayitz',
  `regular_user_token_hint` varchar(512) COLLATE utf8_unicode_ci DEFAULT 'Hebrew word for summer',
  `pref_page_instructions` varchar(2048) COLLATE utf8_unicode_ci DEFAULT '&lt;h3&gt;How to Make Your Choices:&lt;/h3&gt;&lt;ol&gt;&lt;li&gt;For each time period, choose six Chugim, and drag them from the left column to the right column.  Hover over a Chug name in the left box to see a brief description.  If you have existing preferences, they will be pre-loaded in the right box: you can reorder or remove them as needed.&lt;/li&gt;&lt;li&gt;Use your mouse to drag the right column into order of preference, from top (first choice) to bottom (last choice).&lt;/li&gt;&lt;li&gt;When you have arranged preferences for all your time periods, click &lt;font color=&quot;green&quot;&gt;Submit&lt;/font&gt;.&lt;/li&gt;&lt;/ol&gt;',
  `camp_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Camp Ramah New England',
  `camp_web` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'www.campramahne.org'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_data`
--

LOCK TABLES `admin_data` WRITE;
/*!40000 ALTER TABLE `admin_data` DISABLE KEYS */;
INSERT INTO `admin_data` VALUES ('dlobron@gmail.com','$2y$10$iiybvL07fs/HKOgoKJN8MOIvqGDORDyOYbeApqiXd9hOJg1eB4rni',NULL,NULL,1,6,'kayitz','Hebrew word for summer','&lt;h3&gt;How to Make Your Choices:&lt;/h3&gt;&lt;ol&gt;&lt;li&gt;For each time period, choose six Chugim, and drag them from the left column to the right column.  Hover over a Chug name in the left box to see a brief description.  If you have existing preferences, they will be pre-loaded in the right box: you can reorder or remove them as needed.&lt;/li&gt;&lt;li&gt;Use your mouse to drag the right column into order of preference, from top (first choice) to bottom (last choice).&lt;/li&gt;&lt;li&gt;When you have arranged preferences for all your time periods, click &lt;font color=&quot;green&quot;&gt;Submit&lt;/font&gt;.&lt;/li&gt;&lt;/ol&gt;','Camp Ramah New England','www.campramahne.org');
/*!40000 ALTER TABLE `admin_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `block_instances`
--

DROP TABLE IF EXISTS `block_instances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `block_instances` (
  `block_id` int NOT NULL,
  `session_id` int NOT NULL,
  PRIMARY KEY (`block_id`,`session_id`),
  KEY `fk_session_id` (`session_id`),
  CONSTRAINT `block_instances_ibfk_1` FOREIGN KEY (`block_id`) REFERENCES `blocks` (`block_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `block_instances_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `block_instances`
--

LOCK TABLES `block_instances` WRITE;
/*!40000 ALTER TABLE `block_instances` DISABLE KEYS */;
INSERT INTO `block_instances` VALUES (1,1);
/*!40000 ALTER TABLE `block_instances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blocks`
--

DROP TABLE IF EXISTS `blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blocks` (
  `block_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `visible_to_campers` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`block_id`),
  UNIQUE KEY `uk_blocks` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blocks`
--

LOCK TABLES `blocks` WRITE;
/*!40000 ALTER TABLE `blocks` DISABLE KEYS */;
INSERT INTO `blocks` VALUES (1,'Weeks 1+2',1);
/*!40000 ALTER TABLE `blocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bunk_instances`
--

DROP TABLE IF EXISTS `bunk_instances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bunk_instances` (
  `bunk_id` int NOT NULL,
  `edah_id` int NOT NULL,
  PRIMARY KEY (`bunk_id`,`edah_id`),
  KEY `fk_edah_id` (`edah_id`),
  CONSTRAINT `bunk_instances_ibfk_1` FOREIGN KEY (`bunk_id`) REFERENCES `bunks` (`bunk_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `bunk_instances_ibfk_2` FOREIGN KEY (`edah_id`) REFERENCES `edot` (`edah_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bunk_instances`
--

LOCK TABLES `bunk_instances` WRITE;
/*!40000 ALTER TABLE `bunk_instances` DISABLE KEYS */;
/*!40000 ALTER TABLE `bunk_instances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bunks`
--

DROP TABLE IF EXISTS `bunks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bunks` (
  `bunk_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`bunk_id`),
  UNIQUE KEY `uk_bunks` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bunks`
--

LOCK TABLES `bunks` WRITE;
/*!40000 ALTER TABLE `bunks` DISABLE KEYS */;
/*!40000 ALTER TABLE `bunks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campers`
--

DROP TABLE IF EXISTS `campers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `campers` (
  `camper_id` int NOT NULL AUTO_INCREMENT,
  `edah_id` int DEFAULT NULL,
  `session_id` int DEFAULT NULL,
  `bunk_id` int DEFAULT NULL,
  `first` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `last` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `needs_first_choice` tinyint(1) DEFAULT '0',
  `inactive` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`camper_id`),
  KEY `fk_edah_id` (`edah_id`),
  KEY `fk_session_id` (`session_id`),
  KEY `fk_bunk_id` (`bunk_id`),
  CONSTRAINT `campers_ibfk_1` FOREIGN KEY (`edah_id`) REFERENCES `edot` (`edah_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `campers_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `campers_ibfk_3` FOREIGN KEY (`bunk_id`) REFERENCES `bunks` (`bunk_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campers`
--

LOCK TABLES `campers` WRITE;
/*!40000 ALTER TABLE `campers` DISABLE KEYS */;
INSERT INTO `campers` VALUES (1,1,1,NULL,'Igor','Stravinsky','dlobron@gmail.com',0,0),(2,1,1,NULL,'Johannes','Brahms','dlobron@gmail.com',0,0);
/*!40000 ALTER TABLE `campers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `category_tables`
--

DROP TABLE IF EXISTS `category_tables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_tables` (
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `category_table_id` int NOT NULL AUTO_INCREMENT,
  `delete_ok` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`category_table_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category_tables`
--

LOCK TABLES `category_tables` WRITE;
/*!40000 ALTER TABLE `category_tables` DISABLE KEYS */;
INSERT INTO `category_tables` VALUES ('blocks',1,0),('bunks',2,0),('campers',3,1),('chugim',4,1),('edot',5,0),('chug_groups',6,0),('sessions',7,0);
/*!40000 ALTER TABLE `category_tables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chug_dedup_instances_v2`
--

DROP TABLE IF EXISTS `chug_dedup_instances_v2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chug_dedup_instances_v2` (
  `left_chug_id` int NOT NULL,
  `right_chug_id` int NOT NULL,
  KEY `fk_left_chug_id` (`left_chug_id`),
  KEY `fk_right_chug_id` (`right_chug_id`),
  CONSTRAINT `chug_dedup_instances_v2_ibfk_1` FOREIGN KEY (`left_chug_id`) REFERENCES `chugim` (`chug_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chug_dedup_instances_v2_ibfk_2` FOREIGN KEY (`right_chug_id`) REFERENCES `chugim` (`chug_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chug_dedup_instances_v2`
--

LOCK TABLES `chug_dedup_instances_v2` WRITE;
/*!40000 ALTER TABLE `chug_dedup_instances_v2` DISABLE KEYS */;
INSERT INTO `chug_dedup_instances_v2` VALUES (1,1),(2,2),(3,3),(4,4),(5,5),(6,6),(7,7);
/*!40000 ALTER TABLE `chug_dedup_instances_v2` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chug_groups`
--

DROP TABLE IF EXISTS `chug_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chug_groups` (
  `group_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`group_id`),
  UNIQUE KEY `uk_groups` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chug_groups`
--

LOCK TABLES `chug_groups` WRITE;
/*!40000 ALTER TABLE `chug_groups` DISABLE KEYS */;
INSERT INTO `chug_groups` VALUES (1,'Aleph');
/*!40000 ALTER TABLE `chug_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chug_instances`
--

DROP TABLE IF EXISTS `chug_instances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chug_instances` (
  `chug_id` int NOT NULL,
  `block_id` int NOT NULL,
  `chug_instance_id` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`chug_instance_id`),
  UNIQUE KEY `uk_chug_instances` (`chug_id`,`block_id`),
  KEY `fk_block_id` (`block_id`),
  CONSTRAINT `chug_instances_ibfk_1` FOREIGN KEY (`chug_id`) REFERENCES `chugim` (`chug_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chug_instances_ibfk_2` FOREIGN KEY (`block_id`) REFERENCES `blocks` (`block_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chug_instances`
--

LOCK TABLES `chug_instances` WRITE;
/*!40000 ALTER TABLE `chug_instances` DISABLE KEYS */;
INSERT INTO `chug_instances` VALUES (1,1,1),(2,1,2),(3,1,3),(4,1,4),(5,1,5),(6,1,6),(7,1,7);
/*!40000 ALTER TABLE `chug_instances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chugim`
--

DROP TABLE IF EXISTS `chugim`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chugim` (
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `group_id` int DEFAULT NULL,
  `max_size` int DEFAULT NULL,
  `min_size` int DEFAULT '0',
  `description` varchar(2048) COLLATE utf8_unicode_ci DEFAULT NULL,
  `chug_id` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`chug_id`),
  UNIQUE KEY `uk_chugim` (`name`,`group_id`),
  KEY `fk_group` (`group_id`),
  CONSTRAINT `chugim_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `chug_groups` (`group_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chugim`
--

LOCK TABLES `chugim` WRITE;
/*!40000 ALTER TABLE `chugim` DISABLE KEYS */;
INSERT INTO `chugim` VALUES ('Counterpoint',1,10000,-1,'Lots of voices in one piece',1),('String Quartet',1,10000,-1,'4 instruments never sounded so good!',2),('Symphony',1,10000,-1,'Bringing out the big guns!',3),('Solfege',1,10000,-1,'Do, re, mi....',4),('Clarinet',1,10000,-1,'B flat is the best key',5),('Trumpet',1,10000,-1,'Super loud!',6),('Chorus',1,10000,-1,'Freude!',7);
/*!40000 ALTER TABLE `chugim` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edot`
--

DROP TABLE IF EXISTS `edot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edot` (
  `edah_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `rosh_name` varchar(100) COLLATE utf8_unicode_ci DEFAULT '',
  `rosh_phone` varchar(20) COLLATE utf8_unicode_ci DEFAULT '',
  `comments` varchar(512) COLLATE utf8_unicode_ci DEFAULT '',
  `sort_order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`edah_id`),
  UNIQUE KEY `uk_edot` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edot`
--

LOCK TABLES `edot` WRITE;
/*!40000 ALTER TABLE `edot` DISABLE KEYS */;
INSERT INTO `edot` VALUES (1,'Kochavim','Ali-O Lobron','617-555-1212','Cool person',0);
/*!40000 ALTER TABLE `edot` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edot_for_block`
--

DROP TABLE IF EXISTS `edot_for_block`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edot_for_block` (
  `block_id` int NOT NULL,
  `edah_id` int NOT NULL,
  PRIMARY KEY (`block_id`,`edah_id`),
  KEY `fk_edah_id` (`edah_id`),
  CONSTRAINT `edot_for_block_ibfk_1` FOREIGN KEY (`block_id`) REFERENCES `blocks` (`block_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `edot_for_block_ibfk_2` FOREIGN KEY (`edah_id`) REFERENCES `edot` (`edah_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edot_for_block`
--

LOCK TABLES `edot_for_block` WRITE;
/*!40000 ALTER TABLE `edot_for_block` DISABLE KEYS */;
INSERT INTO `edot_for_block` VALUES (1,1);
/*!40000 ALTER TABLE `edot_for_block` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edot_for_chug`
--

DROP TABLE IF EXISTS `edot_for_chug`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edot_for_chug` (
  `chug_id` int NOT NULL,
  `edah_id` int NOT NULL,
  PRIMARY KEY (`chug_id`,`edah_id`),
  KEY `fk_edah_id` (`edah_id`),
  CONSTRAINT `edot_for_chug_ibfk_1` FOREIGN KEY (`chug_id`) REFERENCES `chugim` (`chug_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `edot_for_chug_ibfk_2` FOREIGN KEY (`edah_id`) REFERENCES `edot` (`edah_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edot_for_chug`
--

LOCK TABLES `edot_for_chug` WRITE;
/*!40000 ALTER TABLE `edot_for_chug` DISABLE KEYS */;
INSERT INTO `edot_for_chug` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(6,1),(7,1);
/*!40000 ALTER TABLE `edot_for_chug` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edot_for_group`
--

DROP TABLE IF EXISTS `edot_for_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edot_for_group` (
  `group_id` int NOT NULL,
  `edah_id` int NOT NULL,
  PRIMARY KEY (`group_id`,`edah_id`),
  KEY `fk_edah_id` (`edah_id`),
  CONSTRAINT `edot_for_group_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `chug_groups` (`group_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `edot_for_group_ibfk_2` FOREIGN KEY (`edah_id`) REFERENCES `edot` (`edah_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edot_for_group`
--

LOCK TABLES `edot_for_group` WRITE;
/*!40000 ALTER TABLE `edot_for_group` DISABLE KEYS */;
INSERT INTO `edot_for_group` VALUES (1,1);
/*!40000 ALTER TABLE `edot_for_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `matches`
--

DROP TABLE IF EXISTS `matches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `matches` (
  `camper_id` int NOT NULL,
  `chug_instance_id` int NOT NULL,
  `match_id` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`match_id`),
  UNIQUE KEY `uk_matches` (`camper_id`,`chug_instance_id`),
  KEY `fk_chug_instance_id` (`chug_instance_id`),
  CONSTRAINT `matches_ibfk_1` FOREIGN KEY (`camper_id`) REFERENCES `campers` (`camper_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `matches_ibfk_2` FOREIGN KEY (`chug_instance_id`) REFERENCES `chug_instances` (`chug_instance_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `matches`
--

LOCK TABLES `matches` WRITE;
/*!40000 ALTER TABLE `matches` DISABLE KEYS */;
INSERT INTO `matches` VALUES (1,1,1);
/*!40000 ALTER TABLE `matches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_codes`
--

DROP TABLE IF EXISTS `password_reset_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_codes` (
  `code` varchar(512) COLLATE utf8_unicode_ci NOT NULL,
  `expires` datetime NOT NULL,
  `code_id` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`code_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_codes`
--

LOCK TABLES `password_reset_codes` WRITE;
/*!40000 ALTER TABLE `password_reset_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `preferences`
--

DROP TABLE IF EXISTS `preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `preferences` (
  `camper_id` int NOT NULL,
  `group_id` int NOT NULL,
  `block_id` int NOT NULL,
  `first_choice_id` int DEFAULT NULL,
  `second_choice_id` int DEFAULT NULL,
  `third_choice_id` int DEFAULT NULL,
  `fourth_choice_id` int DEFAULT NULL,
  `fifth_choice_id` int DEFAULT NULL,
  `sixth_choice_id` int DEFAULT NULL,
  `preference_id` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`preference_id`),
  UNIQUE KEY `camper_id` (`camper_id`,`group_id`,`block_id`),
  KEY `fk_group_id` (`group_id`),
  KEY `fk_block_id` (`block_id`),
  KEY `fk_first_choice_id` (`first_choice_id`),
  KEY `fk_second_choice_id` (`second_choice_id`),
  KEY `fk_third_choice_id` (`third_choice_id`),
  KEY `fk_fourth_choice_id` (`fourth_choice_id`),
  KEY `fk_fifth_choice_id` (`fifth_choice_id`),
  KEY `fk_sixth_choice_id` (`sixth_choice_id`),
  CONSTRAINT `preferences_ibfk_1` FOREIGN KEY (`camper_id`) REFERENCES `campers` (`camper_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `preferences_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `chug_groups` (`group_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `preferences_ibfk_3` FOREIGN KEY (`block_id`) REFERENCES `blocks` (`block_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `preferences_ibfk_4` FOREIGN KEY (`first_choice_id`) REFERENCES `chugim` (`chug_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `preferences_ibfk_5` FOREIGN KEY (`second_choice_id`) REFERENCES `chugim` (`chug_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `preferences_ibfk_6` FOREIGN KEY (`third_choice_id`) REFERENCES `chugim` (`chug_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `preferences_ibfk_7` FOREIGN KEY (`fourth_choice_id`) REFERENCES `chugim` (`chug_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `preferences_ibfk_8` FOREIGN KEY (`fifth_choice_id`) REFERENCES `chugim` (`chug_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `preferences_ibfk_9` FOREIGN KEY (`sixth_choice_id`) REFERENCES `chugim` (`chug_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `preferences`
--

LOCK TABLES `preferences` WRITE;
/*!40000 ALTER TABLE `preferences` DISABLE KEYS */;
INSERT INTO `preferences` VALUES (1,1,1,1,7,5,6,3,2,1),(2,1,1,3,1,2,4,7,5,2);
/*!40000 ALTER TABLE `preferences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `session_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`session_id`),
  UNIQUE KEY `uk_sessions` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES (1,'First Session');
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-06-30 16:55:20
