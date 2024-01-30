-- MySQL dump 10.13  Distrib 8.2.0, for macos13.5 (x86_64)
--
-- Host: chugbot.cluxsdbzncfc.us-east-1.rds.amazonaws.com    Database: camprama_chugbot_db
-- ------------------------------------------------------
-- Server version	8.0.33

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

--
-- Current Database: `camprama_chugbot_db`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `camprama_chugbot_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `camprama_chugbot_db`;

--
-- Table structure for table `admin_data`
--

DROP TABLE IF EXISTS `admin_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_data` (
  `admin_email` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `admin_password` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `admin_email_cc` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `admin_email_from_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `regular_user_token` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'Kayitz',
  `regular_user_token_hint` varchar(512) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT 'Hebrew word for summer',
  `pref_page_instructions` varchar(2048) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT '&lt;h3&gt;How to Make Your Choices:&lt;/h3&gt;&lt;ol&gt;&lt;li&gt;For each time period, choose six Chugim, and drag them from the left column to the right column.  Hover over a Chug name in the left box to see a brief description.  If you have existing preferences, they will be pre-loaded in the right box: you can reorder or remove them as needed.&lt;/li&gt;&lt;li&gt;Use your mouse to drag the right column into order of preference, from top (first choice) to bottom (last choice).&lt;/li&gt;&lt;li&gt;When you have arranged preferences for all your time periods, click &lt;font color=&quot;green&quot;&gt;Submit&lt;/font&gt;.&lt;/li&gt;&lt;/ol&gt;',
  `camp_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'Camp Ramah New England',
  `camp_web` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'www.campramahne.org',
  `pref_count` int NOT NULL DEFAULT '6',
  `send_confirm_email` tinyint(1) NOT NULL DEFAULT '1',
  `chug_term_singular` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'chug',
  `chug_term_plural` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'chugim',
  `block_term_singular` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'block',
  `block_term_plural` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'blocks',
  `enable_camper_importer` tinyint(1) NOT NULL DEFAULT '0',
  `enable_selection_process` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_data`
--

LOCK TABLES `admin_data` WRITE;
/*!40000 ALTER TABLE `admin_data` DISABLE KEYS */;
INSERT INTO `admin_data` VALUES ('dcdaycamp@campramahne.org','$2y$10$FLqC32rJ1igTfkflLuR06e5wpBbQQ/OaHXbxDwuBfh50m//epANNu','dcdaycamp@campramahne.org','Lisa Zelermyer','Kayitz','the Hebrew word for summer','&lt;h3&gt;How to Make Your Choices:&lt;/h3&gt;&lt;ol&gt;&lt;li&gt;For each time period, choose three Chugim, and drag them from the left column to the right column.  Hover over a Chug name in the left box to see a brief description.  If you have existing preferences, they will be pre-loaded in the right box: you can reorder or remove them as needed.&lt;/li&gt;&lt;li&gt;Use your mouse to drag the right column into order of preference, from top (first choice) to bottom (last choice).&lt;/li&gt;&lt;li&gt;When you have arranged preferences for all your time periods, click &lt;font color=&quot;green&quot;&gt;Submit&lt;/font&gt;.&lt;/li&gt;&lt;/ol&gt;','Ramah Day Camp DC','www.campramahne.org/day-camp-washington-dc/',3,1,'chug','chugim','block','blocks',1,0);
/*!40000 ALTER TABLE `admin_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assignments`
--

DROP TABLE IF EXISTS `assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assignments` (
  `edah_id` int NOT NULL,
  `block_id` int NOT NULL,
  `group_id` int NOT NULL,
  `first_choice_ct` float DEFAULT '0',
  `second_choice_ct` float DEFAULT '0',
  `third_choice_ct` float DEFAULT '0',
  `fourth_choice_or_worse_ct` float DEFAULT '0',
  `under_min_list` varchar(1024) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT '',
  `over_max_list` varchar(1024) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT '',
  `ctime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`edah_id`,`block_id`,`group_id`),
  KEY `fk_block_id` (`block_id`),
  KEY `fk_group_id` (`group_id`),
  CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`edah_id`) REFERENCES `edot` (`edah_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `assignments_ibfk_2` FOREIGN KEY (`block_id`) REFERENCES `blocks` (`block_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `assignments_ibfk_3` FOREIGN KEY (`group_id`) REFERENCES `chug_groups` (`group_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assignments`
--

LOCK TABLES `assignments` WRITE;
/*!40000 ALTER TABLE `assignments` DISABLE KEYS */;
INSERT INTO `assignments` VALUES (1,1,1,32,7,1,0,'Softball (-2)','','2016-06-30 03:29:46'),(1,1,2,35,5,0,0,'Ultimate Frisbee (-1)','Dance (+2)','2016-06-30 03:29:46'),(1,1,3,33,7,0,0,'','','2016-06-30 03:29:46'),(1,2,1,40,15,0,0,'Softball (-7)','','2016-07-11 11:36:07'),(1,2,2,44,11,0,0,'Exploring Art with Crayons (-3)','Basketball (+8), Ultimate Frisbee (+3), Dance (+1), Sand Art (+2)','2016-07-11 11:36:07'),(1,2,3,40,15,0,0,'Martial Arts (-3), Jewelry-Making (-3)','Flag Football (+2), Drama (+5), Melty Beads (+2)','2016-07-11 11:36:07'),(1,3,1,26,13,0,0,'Soccer (-1), Softball (-7)','','2016-07-25 11:52:07'),(1,3,2,32,7,0,0,'Exploring Art with Crayons (-3), Sand Art (-3)','Dance (+1)','2016-07-25 11:52:07'),(1,3,3,30,9,0,0,'Martial Arts (-3), Aerobics &amp; Fitness (-3), Jewelry-Making (-3), Melty Beads (-3)','Drama (+2), Candle making (+2)','2016-07-25 11:52:07'),(2,1,1,39,5,0,0,'','','2016-06-27 01:38:31'),(2,1,2,36,8,0,0,'','','2016-06-27 01:38:31'),(2,1,3,34,10,0,0,'','Martial Arts (+2), Drama (+1)','2016-06-27 01:38:32'),(2,2,1,39,11,1,0,'Softball (-7)','Weaving, Sewing &amp; Needle-Point (+1)','2016-07-11 03:31:02'),(2,2,2,38,12,1,0,'Exploring Art with Crayons (-3)','Basketball (+6), Ultimate Frisbee (+1), Dance (+1), Sand Art (+1)','2016-07-11 03:31:03'),(2,2,3,37,10,4,0,'Martial Arts (-3), Jewelry-Making (-3)','Flag Football (+1)','2016-07-11 03:31:03'),(2,3,1,22,9,3,0,'Softball (-7), Tennis Racquet Baseball (-1)','','2016-07-25 11:56:54'),(2,3,2,25,9,0,0,'Exploring Art with Crayons (-3), Sand Art (-3)','','2016-07-25 11:56:54'),(2,3,3,30,4,0,0,'Martial Arts (-3), Aerobics &amp; Fitness (-3), Jewelry-Making (-3), Melty Beads (-3)','Drama (+1)','2016-07-25 11:56:54'),(3,1,1,11,1,0,0,'Soccer (-6), Weaving, Sewing &amp; Needle-Point (-3), Softball (-7)','','2016-06-27 01:43:00'),(3,1,2,12,0,0,0,'Ultimate Frisbee (-4), Dance (-1), Exploring Art with Crayons (-3), Pickleball (-1)','','2016-06-27 01:43:00'),(3,1,3,11,1,0,0,'Flag Football (-2), Aerobics &amp; Fitness (-3), Jewelry-Making (-1)','','2016-06-27 01:43:00'),(3,2,1,13,3,0,0,'Teva (-1), Softball (-7)','','2016-07-11 03:33:29'),(3,2,2,16,0,0,0,'Dance (-2), Exploring Art with Crayons (-3)','','2016-07-11 03:33:29'),(3,2,3,16,0,0,0,'Martial Arts (-3), Jewelry-Making (-3), Melty Beads (-1)','','2016-07-11 03:33:29'),(3,3,1,14,1,0,0,'Soccer (-5), Softball (-7)','','2016-07-25 13:04:07'),(3,3,2,15,0,0,0,'Dance (-2), Exploring Art with Crayons (-3), Sand Art (-3)','','2016-07-25 13:04:07'),(3,3,3,15,0,0,0,'Martial Arts (-3), Jewelry-Making (-3), Melty Beads (-3)','','2016-07-25 13:04:07');
/*!40000 ALTER TABLE `assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `block_instances`
--

DROP TABLE IF EXISTS `block_instances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `block_instances` (
  `block_id` int NOT NULL,
  `session_id` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`block_id`,`session_id`),
  KEY `fk_session_id` (`session_id`),
  CONSTRAINT `block_instances_ibfk_1` FOREIGN KEY (`block_id`) REFERENCES `blocks` (`block_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `block_instances_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `block_instances`
--

LOCK TABLES `block_instances` WRITE;
/*!40000 ALTER TABLE `block_instances` DISABLE KEYS */;
INSERT INTO `block_instances` VALUES (1,5),(2,6),(3,7),(4,8),(1,9),(2,9),(1,10),(3,10),(1,11),(4,11),(1,12),(2,12),(3,12),(1,14),(2,14),(4,14),(1,15),(3,15),(4,15),(1,16),(2,16),(3,16),(4,16),(2,17),(3,17),(2,18),(3,18),(4,18),(3,20),(4,20);
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
  `name` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `visible_to_campers` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`block_id`),
  UNIQUE KEY `uk_blocks` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blocks`
--

LOCK TABLES `blocks` WRITE;
/*!40000 ALTER TABLE `blocks` DISABLE KEYS */;
INSERT INTO `blocks` VALUES (1,'Session A',0),(2,'Session B',1),(3,'Session C',1),(4,'Session D',1);
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bunk_instances`
--

LOCK TABLES `bunk_instances` WRITE;
/*!40000 ALTER TABLE `bunk_instances` DISABLE KEYS */;
INSERT INTO `bunk_instances` VALUES (11,1),(11,2),(11,3),(11,6),(11,8);
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
  `name` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`bunk_id`),
  UNIQUE KEY `uk_bunks` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bunks`
--

LOCK TABLES `bunks` WRITE;
/*!40000 ALTER TABLE `bunks` DISABLE KEYS */;
INSERT INTO `bunks` VALUES (11,'Not Yet Assigned');
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
  `first` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `last` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `needs_first_choice` tinyint(1) DEFAULT '0',
  `inactive` tinyint(1) NOT NULL DEFAULT '0',
  `email2` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`camper_id`),
  KEY `fk_edah_id` (`edah_id`),
  KEY `fk_session_id` (`session_id`),
  KEY `fk_bunk_id` (`bunk_id`),
  CONSTRAINT `campers_ibfk_1` FOREIGN KEY (`edah_id`) REFERENCES `edot` (`edah_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `campers_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `campers_ibfk_3` FOREIGN KEY (`bunk_id`) REFERENCES `bunks` (`bunk_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1361 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campers`
--

LOCK TABLES `campers` WRITE;
/*!40000 ALTER TABLE `campers` DISABLE KEYS */;
/*!40000 ALTER TABLE `campers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `category_tables`
--

DROP TABLE IF EXISTS `category_tables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_tables` (
  `name` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `category_table_id` int NOT NULL AUTO_INCREMENT,
  `delete_ok` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`category_table_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category_tables`
--

LOCK TABLES `category_tables` WRITE;
/*!40000 ALTER TABLE `category_tables` DISABLE KEYS */;
INSERT INTO `category_tables` VALUES ('blocks',1,0),('bunks',2,1),('campers',3,1),('chugim',4,1),('edot',5,1),('chug_groups',6,0),('sessions',7,1);
/*!40000 ALTER TABLE `category_tables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chug_dedup_instances`
--

DROP TABLE IF EXISTS `chug_dedup_instances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chug_dedup_instances` (
  `left_chug_name` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `right_chug_name` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  UNIQUE KEY `uk_chug_dedup_instances` (`left_chug_name`,`right_chug_name`),
  KEY `fk_right_chug_name` (`right_chug_name`),
  CONSTRAINT `chug_dedup_instances_ibfk_1` FOREIGN KEY (`left_chug_name`) REFERENCES `chugim` (`name`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chug_dedup_instances_ibfk_2` FOREIGN KEY (`right_chug_name`) REFERENCES `chugim` (`name`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chug_dedup_instances`
--

LOCK TABLES `chug_dedup_instances` WRITE;
/*!40000 ALTER TABLE `chug_dedup_instances` DISABLE KEYS */;
INSERT INTO `chug_dedup_instances` VALUES ('Cooking','Cooking'),('Dance','Dance'),('Flag Football','Flag Football'),('Glass Painting','Glass Painting'),('Jewelry-Making','Jewelry-Making'),('Martial Arts','Martial Arts'),('Melty Beads','Melty Beads'),('Pickleball','Pickleball'),('Sand Art','Sand Art'),('Soccer','Soccer'),('Softball','Softball'),('Teva','Teva'),('Ultimate Frisbee','Ultimate Frisbee'),('Weaving, Sewing &amp; Needle-Point','Weaving, Sewing &amp; Needle-Point');
/*!40000 ALTER TABLE `chug_dedup_instances` ENABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chug_dedup_instances_v2`
--

LOCK TABLES `chug_dedup_instances_v2` WRITE;
/*!40000 ALTER TABLE `chug_dedup_instances_v2` DISABLE KEYS */;
INSERT INTO `chug_dedup_instances_v2` VALUES (19,19),(17,17),(31,31),(26,26),(37,37),(30,30),(18,18),(5,5),(15,15),(43,43),(32,32),(35,35),(35,75),(74,74),(75,75),(75,35),(62,62),(62,39),(63,63),(63,39),(70,70),(71,71),(41,41),(42,42),(38,38),(69,69),(82,82),(84,84),(21,21),(39,62),(39,63),(39,39),(80,80),(3,3),(3,47),(3,46),(86,86),(81,81),(29,29),(44,44),(14,14),(45,45),(27,27),(28,28),(56,56),(55,55),(87,87),(8,8),(59,59),(36,36),(95,95),(33,33),(103,103),(104,104),(57,57),(149,149),(150,150),(151,151),(152,152),(158,158),(161,161),(162,162),(163,163),(164,164),(165,165),(166,166),(168,168),(169,169),(170,170),(171,171),(172,172),(173,173),(174,174),(177,177),(181,181),(182,182),(183,183),(185,185),(186,186),(187,187),(191,191),(192,192),(175,175),(180,180),(34,34),(142,142),(23,23),(101,101),(85,85),(140,140),(111,111),(160,160),(114,114),(22,22),(143,143),(118,118),(137,137),(108,108),(83,83),(47,47),(47,3),(46,46),(46,3),(167,167),(134,134),(73,73),(102,102),(133,133),(10,10),(64,64),(65,65),(188,188),(113,113),(130,130),(179,179),(94,94),(189,189),(190,190),(54,54),(107,107),(119,119),(132,132),(58,58),(25,25),(176,176),(135,135),(11,11),(110,110),(125,125),(138,138),(131,131),(139,139),(141,141),(156,156),(155,155),(109,109),(145,145),(116,116),(124,124),(123,123),(96,96),(97,97),(136,136),(2,2),(6,6),(128,128),(184,184),(115,115),(126,126),(4,4),(144,144),(117,117),(129,129),(112,112),(122,122),(193,193),(148,148),(153,153),(153,154),(154,154),(154,153),(7,7),(146,146),(157,157),(195,195),(195,147),(195,194),(195,120),(121,120),(121,121),(121,194),(121,147),(194,194),(194,195),(194,121),(194,147),(147,147),(147,195),(147,121),(147,194),(120,120),(120,195),(120,121),(196,196),(127,127),(198,198),(159,159),(178,178);
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
  `name` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`group_id`),
  UNIQUE KEY `uk_groups` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chug_groups`
--

LOCK TABLES `chug_groups` WRITE;
/*!40000 ALTER TABLE `chug_groups` DISABLE KEYS */;
INSERT INTO `chug_groups` VALUES (1,'Chug Aleph'),(2,'Chug Bet'),(4,'Chug Dalet'),(3,'Chug Gimel');
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
) ENGINE=InnoDB AUTO_INCREMENT=395 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chug_instances`
--

LOCK TABLES `chug_instances` WRITE;
/*!40000 ALTER TABLE `chug_instances` DISABLE KEYS */;
INSERT INTO `chug_instances` VALUES (2,1,325),(7,1,324),(10,1,332),(22,1,326),(22,2,327),(25,1,333),(25,2,334),(54,2,344),(82,1,173),(84,1,175),(113,1,329),(113,2,330),(120,1,338),(121,1,335),(121,2,336),(146,1,323),(147,1,339),(148,1,343),(149,1,345),(150,1,346),(151,1,347),(152,1,348),(153,1,349),(154,2,350),(155,2,351),(156,1,352),(157,1,353),(157,2,354),(158,1,355),(159,2,356),(160,2,357),(161,2,358),(162,1,359),(163,1,360),(164,1,361),(165,1,362),(166,1,363),(167,1,364),(168,1,365),(169,1,366),(170,1,367),(171,1,368),(172,1,369),(173,1,370),(174,1,371),(175,1,372),(175,2,390),(176,2,373),(177,2,374),(178,2,375),(179,2,376),(180,2,377),(181,2,378),(182,2,379),(183,2,380),(184,2,381),(185,2,382),(186,2,383),(187,2,384),(188,2,385),(189,2,386),(190,2,387),(191,2,388),(192,2,389),(193,2,392),(194,2,393),(195,2,394);
/*!40000 ALTER TABLE `chug_instances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chugim`
--

DROP TABLE IF EXISTS `chugim`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chugim` (
  `name` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `group_id` int DEFAULT NULL,
  `max_size` int DEFAULT NULL,
  `min_size` int DEFAULT '0',
  `description` varchar(2048) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `chug_id` int NOT NULL AUTO_INCREMENT,
  `department_name` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `rosh_name` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`chug_id`),
  UNIQUE KEY `uk_chugim` (`name`,`group_id`),
  KEY `fk_group` (`group_id`),
  CONSTRAINT `chugim_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `chug_groups` (`group_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=199 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chugim`
--

LOCK TABLES `chugim` WRITE;
/*!40000 ALTER TABLE `chugim` DISABLE KEYS */;
INSERT INTO `chugim` VALUES ('Soccer',1,18,6,'Learn the basic skills and techniques of soccer and the importance of teamwork. We will challenge more experienced players with higher-level drills.',2,NULL,NULL),('Cooking',2,20,3,'Campers will explore cooking with an emphasis on Jewish food and its history and good nutrition. Campers will learn how to make specific dishes as well as build the skills that will enable them to read and follow recipes.',3,NULL,NULL),('Teva',1,15,3,'Campers in this chug will become the stewards of the Ramah Garden and will learn about and tend to all of the plants growing at camp.',4,NULL,NULL),('Weaving, Sewing &amp; Needle-Point',2,10,3,'Get your needle and thread ready to learn to sew! Campers will also use yarn and needlepoint plastic canvases to create patterns and designs.',5,NULL,NULL),('Softball',4,16,7,'Campers will learn the basic skills and techniques of softball! Weâ€™ll work to improve offensive and defensive skills, and weâ€™ll focus on the importance of teamwork on and off the field. More advanced players will be offered high-level drills and coaching. Throughout, we will have a lot of fun playing and enjoying Americaâ€™s pastime!',6,NULL,NULL),('Ultimate Frisbee',1,16,6,'In this chug, campers will learn the rules, skills and strategies of the game. Because of the free-flowing nature of the sport, every player has the opportunity to be a thrower, receiver and defender in every game.',7,NULL,NULL),('Dance',2,15,4,'Hip-hop, modern dance, or classic Israeli folk dance â€” whatever your preference, this is the chug for you! Come sharpen your technique and learn new choreography.',8,NULL,NULL),('Flag Football',3,16,4,'Campers who participate in this chug will learn the basic skills of football, from how to throw a spiral and catch properly to running routes and playing defense. Once past the basics we will learn more advanced aspects of the game, such as offensive and defensive schemes. All of these skills will be incorporated into games and scrimmages in a fun yet competitive atmosphere.',10,NULL,NULL),('Martial Arts',2,15,3,NULL,11,NULL,NULL),('Jewelry-Making',3,12,3,'Come join us in this chug to create beautiful works of art that you can wear! In this chug, we will make rings, bracelets, and earrings using beads, wire, string, and anything else we can get our hands on!',14,NULL,NULL),('Pickleball',3,8,4,'Also called Beach Tennis, this Israeli mix of tennis, badminton, and pingpong is played with a tall net and small wooden paddles. A mix of scrimmaging and skill-building will provide for hours of fun in this exciting chug!',15,NULL,NULL),('Melty Beads',3,8,3,'Call them melty beads or hot beads or perler beads, but whatever you call them, these tiny little beads that you iron to fuse together will entertain chanichim for hours, as well as create beautiful and colorful masterpieces. We will make items into magnets, keychains, and other pieces.',17,NULL,NULL),('Sand Art',3,10,3,'Take a walk through the beach or the desert all while staying at Ramah! Chanichim will use colored sand to create different works of art, such as layered sand in bottles, sand creations on paper using glue, and window decorations using sand, straws, and contact paper!',18,NULL,NULL),('Glass Painting',2,10,2,'In this chug, chanichim will thoughtfully design and paint glass bottles, jars, and vessels using specialty glass paints and fine brushes.',19,NULL,NULL),('Ginun (Gardening)',1,15,4,'Join us in our permaculture garden! Campers in this chug will become the stewards of the Ramah Garden and spend each afternoon learning about and tending to all of the plants growing at camp.',21,NULL,NULL),('Chetz V\'Keshet (Archery)',2,16,4,'Learn the basics of form and sharpen your skills in this fun adventure sport.',22,NULL,NULL),('Aerobics / Fitness',3,10,4,'Stay active with high-energy sports: running, zumba, aerobics, and more!',23,NULL,NULL),('Mada (STEM)',4,16,3,'Join us for some fun experiments!  We will learn to be young scientists by hypothesizing, testing, and observing.',25,NULL,NULL),('Mosaics',2,12,2,NULL,26,NULL,NULL),('Chug Menucha',1,100,-1,'This chug offers a chance to unwind after lunch with structured down time: board games, card games, storytelling, and simple crafts. This chug is offered to all edot simultaneously after lunch, and presents an opportunity for siblings to be together across edot. There is no maximum registration.',27,NULL,NULL),('Chug Menucha',2,100,-1,'This chug offers a chance to unwind after lunch with structured down time: board games, card games, storytelling, and simple crafts. This chug is offered to all edot simultaneously after lunch, and presents an opportunity for siblings to be together across edot. There is no maximum registration.',28,NULL,NULL),('Chug Menucha',3,100,-1,'This chug offers a chance to unwind after lunch with structured down time: board games, card games, storytelling, and simple crafts. This chug is offered to all edot simultaneously after lunch, and presents an opportunity for siblings to be together across edot. There is no maximum registration.',29,NULL,NULL),('Music Makers',1,10,3,'Drum circles, a capella, and making rhythm with unlikely instruments, this &quot;Ramah Band&quot; will use the talents of its campers to make music for the Ramah community.',30,NULL,NULL),('Watercolor Painting',1,10,2,'Learn about stroke, color mixing, and perspective in this classic omanut chug.',31,NULL,NULL),('Badminton',3,12,4,'Using birdies, rackets and a net this chug will practice skills and scrimmaging',32,NULL,NULL),('Collaborative Canvas',2,12,4,'Collaborative group painting that will be displayed around camp.',33,NULL,NULL),('Acrylic Painting',1,10,4,NULL,34,NULL,NULL),('Camp Crafts',2,10,4,'Classic Camp art crafts! Sand Art, Macrame, Wind-chimes, Friendship bracelets, melty beads, and more!',35,NULL,NULL),('Judaica Crafts',3,10,4,'Classic Judaica crafts, Havdallah candles, Shabbat cups, Hamsa decorating and more!',36,NULL,NULL),('Dance &amp; Zumba',3,10,4,'Hip-hop, modern dance, or classic Israeli folk dance â€” whatever your preference, this is the chug for you! Come sharpen your technique and learn new choreography.',37,NULL,NULL),('Under the Sea Crafts',1,12,-1,'Various art materials will be used to create under-the-sea creatures and scenery.',38,NULL,NULL),('Chug Yisrael',1,8,4,'Join our mishlahat for fun Israeli games that connect participants to the culture of and people of Israel.',39,NULL,NULL),('Print Making',2,12,4,'Using ink, carvings, and nature to create prints for cards and other designs!',41,NULL,NULL),('Tae Kwon Do',3,12,4,'Learn the values and basics of this martial art with a black belt instructor.',42,NULL,NULL),('Architecture/Design',1,8,4,'Campers will use materials in their environment to make crafts and explore spatial reasoning, learning about how we create and use space on many levels.',43,NULL,NULL),('Circus Arts',3,10,4,'Learn to be an entertainer! Weâ€™ll explore the art of making balloon animals â€” create your own and learn how balloon shaping works. We may even learn to do a few magic tricks, as well!',44,NULL,NULL),('Sports Medley',3,16,4,'Join us for different sports games everyday! Including our new 9 square in the air game and 9 hole mini golf course!',45,NULL,NULL),('Cooking - Sha\'ar',2,10,-1,'Campers will explore cooking with an emphasis on Jewish food and its history and good nutrition. Campers will learn how to make specific dishes as well as build the skills that will enable them to read and follow recipes.',46,NULL,NULL),('Cooking - Sha\'ar',1,10,4,'Campers will explore cooking with an emphasis on Jewish food and its history and good nutrition. Campers will learn how to make specific dishes as well as build the skills that will enable them to read and follow recipes.',47,NULL,NULL),('Kickball',4,16,4,'Kickball combines elements of baseball and soccer using an inflated bouncy ball.',54,NULL,NULL),('Yarn Art',1,12,4,'Come join us for some looming, weaving and more fun creating masterpieces with yarn!!',55,NULL,NULL),('Sculpture',2,12,4,'Have fun creating sculptures and designs with different kinds of materials!',56,NULL,NULL),('Mixed Media Art',3,12,4,'Have fun in using different materials to create wonderful masterpieces!',57,NULL,NULL),('Mada (STEM)',1,10,2,'Join us for some fun experiments, we are going to be using our imagination to create experiences and learn to be young scientists!!',58,NULL,NULL),('Hero Kids',1,6,2,'Hero Kids is a fantasy roleplaying game, each player assumes the role of a unique hero. The heroes must work together to accomplish their goal or objective.',59,NULL,NULL),('Chug Yisrael - Sha\'ar',2,10,4,'Join our mishlahat for fun Israeli games that connect participants to the culture of and people of Israel.',62,NULL,NULL),('Chug Yisrael - Sha\'ar',3,10,4,'Join our mishlahat for fun Israeli games that connect participants to the culture of and people of Israel.',63,NULL,NULL),('Flag Football - Sha\'ar',1,10,4,'Campers who participate in this chug will learn the basic skills of football, from how to throw a spiral and catch properly to running routes and playing defense. Once past the basics we will learn more advanced aspects of the game, such as offensive and defensive schemes. All of these skills will be incorporated into games and scrimmages in a fun yet competitive atmosphere.',64,NULL,NULL),('Flag Football - Sha\'ar',3,10,4,'Campers who participate in this chug will learn the basic skills of football, from how to throw a spiral and catch properly to running routes and playing defense. Once past the basics we will learn more advanced aspects of the game, such as offensive and defensive schemes. All of these skills will be incorporated into games and scrimmages in a fun yet competitive atmosphere.',65,NULL,NULL),('Yoga',2,18,4,'A calm time to move your body and clear your mind!',69,NULL,NULL),('Net Sports',3,20,4,'We will play a mix of volleyball, badminton, neucomb and other sports that use a net!',70,NULL,NULL),('Papier Mache',1,12,4,'Join us in creating amazing papier mache designs!',71,NULL,NULL),('Drama - Sha\'ar',2,10,4,'This summer in chug drama weâ€™ll play improv games, build characters, and work on acting in comedy and drama. Weâ€™ll learn about what makes us laugh and what moves us, and what makes a particular actor more or less believable. And mainly weâ€™ll have lots and lots of fun!',73,NULL,NULL),('Camp Crafts - Sha\'ar',1,10,4,'Classic Camp art crafts! Sand Art, Macrame, Wind-chimes, Friendship bracelets, melty beads, and more!',74,NULL,NULL),('Camp Crafts - Sha\'ar',2,10,4,'Classic Camp art crafts! Sand Art, Macrame, Wind-chimes, Friendship bracelets, melty beads, and more!',75,NULL,NULL),('Color Explosion',1,12,4,'Join us to make various mixed media projects that involve experimenting with colors.',80,NULL,NULL),('Rain Forest Crafts',2,12,4,'By using a variety of materials, we will create rain forest creatures and fauna.',81,NULL,NULL),('Color Explosion - Sha\'ar',1,10,-1,'Join us to make various mixed media projects that involve experimenting with colors.',82,NULL,NULL),('Color Explosion - Sha\'ar',2,10000,-1,'Join us to make various mixed media projects that involve experimenting with colors.',83,NULL,NULL),('Basketball - Sha\'ar',1,10000,-1,'Work on your jump shot, learn dribbling technique, and offensive and defensive strategy. Scrimmage each week.',84,NULL,NULL),('Basketball - Sha\'ar',2,10000,-1,'Work on your jump shot, learn dribbling technique, and offensive and defensive strategy. Scrimmage each week.',85,NULL,NULL),('Herbalism',2,15,4,'A mixture of activities with a creative, mindful and ecological twist. From getting creative with art and cooking to being mindful with herbalism and meditation.  Activities may include making jam, infusing oils, making nature mobiles and more!',86,NULL,NULL),('Impressionists',3,12,-1,'We will be inspired by famous artists to make our own special artwork.',87,NULL,NULL),('Israeli Music',3,12,-1,'We will be learning about different beats, how to create music using our bodies and everyday things and have fun experimenting with music and Israeli songs.',94,NULL,NULL),('Ancient Cultures',1,12,-1,'Learn and create art from Ancient Egypt, Greece, Rome and other Ancient times.',95,NULL,NULL),('Sha\'ar Aerobics',2,15,-1,'Join us for some Israeli exercises. We will practice counting in Hebrew and moving parts of our body.',96,NULL,NULL),('Sha\'ar Aerobics',3,15,-1,'Join us for some Israeli exercises. We will practice counting in Hebrew and moving parts of our body.',97,NULL,NULL),('Ball Games - Sha\'ar',3,15,-1,'We will play different games that use a ball.',101,NULL,NULL),('Drama - Sha\'ar',3,15,-1,'This summer in chug drama weâ€™ll play improv games, build characters, and work on acting in comedy and drama. Weâ€™ll learn about what makes us laugh and what moves us, and what makes a particular actor more or less believable. And mainly weâ€™ll have lots and lots of fun!',102,NULL,NULL),('String theory',1,12,-1,'The art of tying knots into decorative pieces of jewelry. MacramÃ© and friendship bracelets!',103,NULL,NULL),('Beach crafts',2,12,-1,'Sand art, shell art',104,NULL,NULL),('Lawn Games',1,15,2,'Lawn games include 9 Square, Mini Golf, building with giant foam blocks (Kachol Gadol), cornhole, and more!',107,NULL,NULL),('Color Exploration',1,15,2,'Artists will spend this week playing with mixing and creating new colors with various media such as paint and tissue papers.  Weâ€™ll look at how dark and light affect tones.  Weâ€™ll also create â€œstained glassâ€ based on Marc Chagallâ€™s work.',108,NULL,NULL),('Rhythm â€˜Nâ€™ Ruach',2,15,2,'Rhythm â€˜Nâ€™ Ruach connects children to Judaism through upbeat, high energy music and movement; through participation in music with real instruments, such as, strings, percussion, shakers, tambourines and singing.',109,NULL,NULL),('Mishakei Ivrit (Hebrew Games)',1,15,2,'Led by our Israeli counselors, campers will learn and play common Israeli childhood games.  This chug will be facilitated in Hebrew. *Priority will be given to Sha\'ar Campers)',110,NULL,NULL),('Board Games',1,15,2,NULL,111,NULL,NULL),('Weaving',2,15,2,'Campers will use a variety of techniques to design some fun creations with yarn, threads, and more.',112,NULL,NULL),('Har Ramah (Climbing Tower)',2,15,2,'Challenge yourself to climb Ramah Day Camp\'s climbing tower! Learn the basics of climbing safety and strategy to reach the top.  (Har Ramah has beginner, intermediate, and advanced elements for all levels of climbing.)',113,NULL,NULL),('Candle Making',3,15,2,NULL,114,NULL,NULL),('Sports Around the World',3,15,2,'Sports Around the World will give campers an opportunity to learn about sports such as cricket, rugby and field hockey',115,NULL,NULL),('Rikud (Dance)',2,15,2,'This chug will focus on learning both modern and traditional Israeli dances.',116,NULL,NULL),('Theater Arts',3,15,2,'Campers will have fun creating and acting out stories, playing drama games, and learning improv.',117,NULL,NULL),('Clay Making and Printing Presses',4,15,2,'Clay Club and Printing Presses description: Artists will use clay to create pinch pots and other fun, usable crafts. Alternatively, artists will have the opportunity to make and share prints using ink and rollers on special paper and/or create sponge stamps to share with their friends.',118,NULL,NULL),('Legos',3,15,2,NULL,119,NULL,NULL),('Bishul Im Ivrit (Cooking with Hebrew)',4,11,2,'Kids will have fun in the kitchen exploring new recipes and developing hands-on cooking skills - with lots of Hebrew mixed in! This chug will be facilitated mostly in Hebrew. Priority will be given to campers in the Sha\'ar Hebrew immersion program.',120,NULL,NULL),('Bishul (Cooking)',4,22,2,'Kids will have fun in the kitchen exploring new recipes and developing hands-on cooking skills. (Some days, Bishul &amp; Bishul Ba\'Hutz - Indoor &amp; Outdoor Cooking - will be combined.)',121,NULL,NULL),('Fun with Nets',4,14,2,'This chug features our two newest net games - Spike Ball and Tri-Crosse.  Never played before?  This chug will be perfect for you!',122,NULL,NULL),('Self Portraits and Pastels',1,15,2,'Israeli Artists Hanoch Piven and Shirley Markham: Self Portraits and Pastels',123,NULL,NULL),('Rikud V\'hitamlut Ivri (Hebrew Dance/Movement)',1,15,2,'Campers will learn dances and movement exercises in Hebrew',124,NULL,NULL),('Painting and 3D Sculptures',2,15,2,'Israeli Artists Ken Goldman and Yaacov Agam: Painting and 3D sculptures',125,NULL,NULL),('Street Hockey',2,15,2,NULL,126,NULL,NULL),('3D Israel Map',3,15,2,'Create a 3D, walkable map of Israel&#039;s geography and famous sights.',127,'another  department','another name'),('Speedball',3,15,2,'Speedball is an exciting mix of soccer, flag football, and ultimate frisbee. You can score by throwing, kicking, or heading the ball into the goal.',128,NULL,NULL),('Theatre',1,15,2,'We will explore different aspects of theatre through fun improv games and learning lifelong lessons in flexibility and quick thinking',129,NULL,NULL),('Israeli Jewelry',4,15,2,'Create beautiful, wearable jewelry',130,NULL,NULL),('Paper Crafts',1,20,2,'Campers in paper crafts will have fun quilling, oragami, and other paper crafts.',131,NULL,NULL),('Legos &amp; Board Games',2,15,2,NULL,132,NULL,NULL),('Fabric Fun!',2,20,2,'Campers will have a choice of a make-your-own-willow, weaving, or needlepoint projects.',133,NULL,NULL),('Cricket',2,20,2,NULL,134,NULL,NULL),('Marc Chagall &amp; Stained Glass Art',3,20,2,'Campers will learn about artist Marc Chagall and design stained glass art of their own!',135,NULL,NULL),('Silly Games',3,20,2,'Have a blast playing silly games, including Backwards Baseball and Kickball, Capture the &quot;Fake\'on&quot; and Tennis Bagel.',136,NULL,NULL),('Collaborative Canvas',4,20,2,'Together, campers will paint a mural that will be on display at Ramah.',137,NULL,NULL),('Painting Party',4,20,2,'Using a variety of different paints, methods, and materials, campers will have a party creating new art.',138,NULL,NULL),('Pickleball',4,12,2,'Pickleball is a racket-paddle sport that combines elements of several other racket sports. Players use solid paddles to hit a ball over a net.',139,NULL,NULL),('Beach Week (Omanut/Art)',1,20,2,'We will have fun with projects using shells, sand art, candles, and corks.',140,NULL,NULL),('Quidditch &amp; Cricket',1,20,2,'Played on a noodle, quidditch is a variation of the game made popular through Harry Potter. Cricket is a bat-and-ball game played between two teams with a wicket at each end.',141,NULL,NULL),('Adventures in Art',2,20,2,'Some of our favorite activities from this summer will be back, including jewelry making, painting, and camp crafts.',142,NULL,NULL),('Choose Your Own Omanut Adventure',2,20,2,'Some of our favorite activities from this summer will be back, including jewelry making, painting, and camp crafts.',143,NULL,NULL),('Theater',2,15,2,'We will explore different aspects of theater through fun improv games and learning lifelong lessons in flexibility and quick thinking.',144,NULL,NULL),('Rikud (Dance)',1,15,2,NULL,145,NULL,NULL),('Kadorsal B\'Ivrit (Basketball in Hebrew)',3,14,4,'Work on your jump shot, learn dribbling technique, and offensive and defensive strategy. This chug will be facilitated mostly in Hebrew.  Priority is given to campers in the Sha\'ar Hebrew immersion program.',146,NULL,NULL),('Bishul BaHutz (Outdoor Cooking)',4,8,3,'Learn resourcefulness and responsibility at the medurah (campfire) with this fun and practical outdoor cooking chug. (Some days, Bishul &amp; Bishul BaHutz - Indoor &amp; Outdoor Cooking - will be combined.)',147,NULL,NULL),('Music &amp; Theater',3,14,4,'Together we\'ll sing, make music, and learn new music.  We\'ll also play improv games, build characters, and work on acting in comedy and drama. And mainly we\'ll have lots and lots of fun!',148,NULL,NULL),('Beach Jewelry',1,18,4,'Create sea inspired jewelry using materials such as pebbles, sea glass, shells, and more!',149,NULL,NULL),('Beach Jewelry',2,18,4,'Create sea inspired jewelry using materials such as pebbles, sea glass, shells, and more!',150,NULL,NULL),('Beach Jewelry',3,18,4,'Create sea inspired jewelry using materials such as pebbles, sea glass, shells, and more!',151,NULL,NULL),('Beach Jewelry',4,18,4,'Create sea inspired jewelry using materials such as pebbles, sea glass, shells, and more!',152,NULL,NULL),('Tarbut Yisrael B\'Ivrit (Israeli Culture in Hebrew)',2,15,4,'Spend extra time with several of our shlichim (Israeli counselors) who will share their love and expertise on Israeli foods, music, art and much more! This chug will be facilitated mostly in Hebrew.  Priority will be given to campers in the Sha\'ar Hebrew immersion program.',153,NULL,NULL),('Tarbut Yisrael (Israeli Culture)',2,15,4,'Spend extra time with several of our shlichim (Israeli counselors) who will share their love and expertise on Israeli foods, music, art and much more!',154,NULL,NULL),('Ra-Ra Rikud B\'Ivrit (in Hebrew) - Israeli Dance Pa',1,16,4,'Break out your best dance moves to some popular Israeli music. This chug will be facilitaed b\'Ivirt (in Hebrew).  Priority will go to campers in the Sha\'ar Hebrew immersion program.',155,NULL,NULL),('Ra-Ra Rikud - Dance Mix',1,20,4,'Break out your best dance moves to your favorite popular songs with Israeli dance mixed in.',156,NULL,NULL),('Going Gaga for Gaga &amp; other Lawn Games',2,16,2,'Come play in our new gaga pit! (Gaga is a dodgeball type game played in an octagonal-shaped pit.) Lawn games include Tesha Bashamayim (9 Square), mini-golf, Kachol Gadol (building with giant foam blue blocks), cornhole, and more!',157,NULL,NULL),('Net Games (Pickleball, Tricross, Spikeball)',4,16,4,'In this chug, campers will have an opportunity to play a variety of sports including tricross, pickleball, spikeball, volleyball, and newcomb!',158,NULL,NULL),('Wiffle Ball',3,16,5,'A scaled back variation of baseball, wiffle ball is played using a perforated light-weight plastic ball and a long hollow plastic bat.  Take us out to the ball game!',159,'sports','test head counselor name'),('Brain Games (Chess, Checkers, Rubix &amp; more)',3,15,4,'Engage in fun workouts for your brain! We will play chess and checkers, learn rubix strategies, and  more!',160,NULL,NULL),('Tennis Baseball',1,16,4,'It\'s baseball...with a tennis racquet! Check out this awesome hybrid sport featured only at camp!',161,NULL,NULL),('Ginun - Grow a Garden',3,16,4,'Join us in our community permaculture garden! Campers will become the stewards of our community garden and spend their afternoons planting, weeding, and paving a path.',162,NULL,NULL),('Beachy Keen Wearables and Souvenirs',1,18,5,'Create your own beach souvenirs! Make sea life magnets, suncatchers, decorate sun visors, and more!',163,NULL,NULL),('Beachy Keen Wearables and Souvenirs',2,18,5,'Create your own beach souvenirs! Make sea life magnets, suncatchers, decorate sun visors, and more!',164,NULL,NULL),('Beachy Keen Wearables and Souvenirs',3,18,5,'Create your own beach souvenirs! Make sea life magnets, suncatchers, decorate sun visors, and more!',165,NULL,NULL),('Beachy Keen Wearables and Souvenirs',4,18,5,'Create your own beach souvenirs! Make sea life magnets, suncatchers, decorate sun visors, and more!',166,NULL,NULL),('Crafting in Color',1,18,5,'Create with colorful materials to make red white and blue pins, color diffusing flowers, pinwheels, bead fusion, and more!',167,NULL,NULL),('Crafting in Color',3,18,5,'Create with colorful materials to make red white and blue pins, color diffusing flowers, pinwheels, bead fusion, and more!',168,NULL,NULL),('Crafting in Color',2,18,5,'Create with colorful materials to make red white and blue pins, color diffusing flowers, pinwheels, bead fusion, and more!',169,NULL,NULL),('Crafting in Color',4,18,5,'Create with colorful materials to make red white and blue pins, color diffusing flowers, pinwheels, bead fusion, and more!',170,NULL,NULL),('Picture Yourself at the Beach',1,18,5,'Envision yourself at the beach! Create beach picture frames, water color beach scenes, shaving cream print greeting cards and more!',171,NULL,NULL),('Picture Yourself at the Beach',2,18,5,'Envision yourself at the beach! Create beach picture frames, water color beach scenes, shaving cream print greeting cards and more!',172,NULL,NULL),('Picture Yourself at the Beach',3,18,5,'Envision yourself at the beach! Create beach picture frames, water color beach scenes, shaving cream print greeting cards and more!',173,NULL,NULL),('Picture Yourself at the Beach',4,18,5,'Envision yourself at the beach! Create beach picture frames, water color beach scenes, shaving cream print greeting cards and more!',174,NULL,NULL),('Cardboard Engineering (Makerspace)',1,12,4,'With a little imagination, some simple tools, and engineering principles, a corrugated/cardboard box can become just about anything you want it to be. Whether young or all grown up, making things out of cardboard requires us to imagine, plan, build, adjust and improve our creation. (Campers will be using only age-appropriate tools in this chug.)',175,NULL,NULL),('Make Some Noise - Musical Instrument Making',2,18,4,'In this chug, campers will create their own musical instruments using art and household supplies. Perhaps we\'ll start our own camp band with our newly-made banjos, guitars, drums, maracas, and shakers!',176,NULL,NULL),('Make Some Noise - Musical Instrument Making',4,18,4,'In this chug, campers will create their own musical instruments using art and household supplies. Perhaps we\'ll start our own camp band with our newly-made banjos, guitars, drums, maracas, and shakers!',177,NULL,NULL),('Volleyball and Nukem',3,15,5,'Work in small teams to learn and execute the fundamentals of net games.',178,'sports','another test name'),('Israeli Mosaics',1,18,4,'Using colorful mosaic tiles, campers will design candle holders, trivets, and other creations.',179,NULL,NULL),('Israeli Jewelry (B\'Ivrit)',1,18,4,'Create beautiful, wearable jewelry with an Israeli flair. This chug will be facilitated in Hebrew.  Priority will be given to campers in the Sha\'ar Hebrew Immersion Program.',180,NULL,NULL),('Israeli Jewelry (B\'Ivrit)',2,18,4,'Create beautiful, wearable jewelry with an Israeli flair. This chug will be facilitated in Hebrew.  Priority will be given to campers in the Sha\'ar Hebrew Immersion Program.',181,NULL,NULL),('Israeli Jewelry (B\'Ivrit)',3,18,4,'Create beautiful, wearable jewelry with an Israeli flair. This chug will be facilitated in Hebrew.  Priority will be given to campers in the Sha\'ar Hebrew Immersion Program.',182,NULL,NULL),('Painting &amp; Pastels - Create a Scene',4,18,4,'Learn to paint and create beautiful scenes and images from nature.',183,NULL,NULL),('Sports Around the World',1,15,5,'Sports Around the World will give campers an opportunity to learn about sports such as cricket, rugby and quidditch.',184,NULL,NULL),('Play with Clay',1,18,4,'Design and create with fimo and clay.',185,NULL,NULL),('Play with Clay',2,18,4,'Design and create with fimo and clay.',186,NULL,NULL),('Play with Clay',3,18,4,'Design and create with fimo and clay.',187,NULL,NULL),('Gifts of the Gan (Garden)',3,15,5,'Use the resources we\'ve grown in the garden to create! We will pickle vegetables, make tea, and create flower art arrangements.',188,NULL,NULL),('Judaic Crafts',2,18,5,'Weave challah baskets and design and create hamsa wall hangings.  Each project will take a couple days to complete.',189,NULL,NULL),('Judaic Crafts',4,18,5,'Weave challah baskets and design and create hamsa wall hangings.  Each project will take a couple days to complete.',190,NULL,NULL),('Challah Days',3,18,5,'Design and create your own wooden challah board.  This is a week long project.',191,NULL,NULL),('Challah Days',4,18,4,'Design and create your own wooden challah board.  This is a week long project.',192,NULL,NULL),('Israeli Mosaics',3,18,4,'Using colorful mosaic tiles, campers will design candle holders, trivets, and other creations.',193,NULL,NULL),('Bishul Ba-Hutz (Outdoor Cooking)',4,10,3,'Learn resourcefulness and responsibility at the medurah (campfire) with this fun and practical outdoor cooking chug. (Some days, Bishul &amp; Bishul Ba\'Hutz - Indoor &amp; Outdoor Cooking - will be combined.)',194,NULL,NULL),('Bishul/Cooking',4,22,4,'Kids will have fun in the kitchen exploring new recipes and developing hands-on cooking skills. (Some days, Bishul &amp; Bishul Ba\'Hutz - Indoor &amp; Outdoor Cooking - will be combined.)',195,NULL,NULL),('test chug',1,10000,-1,NULL,196,'another dept name',NULL),('test chug1111',1,10,1,NULL,198,'testing another dept.','Brian roth');
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
  `name` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `rosh_name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT '',
  `rosh_phone` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT '',
  `comments` varchar(512) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT '',
  `sort_order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`edah_id`),
  UNIQUE KEY `uk_edot` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edot`
--

LOCK TABLES `edot` WRITE;
/*!40000 ALTER TABLE `edot` DISABLE KEYS */;
INSERT INTO `edot` VALUES (1,'Shorashim (entering kindergarten after camp)','Jessica',NULL,NULL,1),(2,'Garinim (entering 2nd grade after camp)',NULL,NULL,NULL,3),(3,'Nitzanim (entering 4th-6th grades after camp)',NULL,NULL,NULL,4),(6,'Anafim (entering 1st grade after camp)',NULL,NULL,NULL,2),(8,'Etzim (entering 3rd grade after camp)',NULL,NULL,NULL,3);
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edot_for_block`
--

LOCK TABLES `edot_for_block` WRITE;
/*!40000 ALTER TABLE `edot_for_block` DISABLE KEYS */;
INSERT INTO `edot_for_block` VALUES (1,1),(2,1),(3,1),(4,1),(1,2),(2,2),(3,2),(4,2),(1,3),(2,3),(3,3),(4,3),(1,6),(2,6),(3,6),(4,6),(1,8),(2,8),(3,8),(4,8);
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edot_for_chug`
--

LOCK TABLES `edot_for_chug` WRITE;
/*!40000 ALTER TABLE `edot_for_chug` DISABLE KEYS */;
INSERT INTO `edot_for_chug` VALUES (2,1),(3,1),(4,1),(5,1),(6,1),(8,1),(10,1),(14,1),(17,1),(18,1),(19,1),(22,1),(23,1),(25,1),(26,1),(29,1),(30,1),(31,1),(32,1),(33,1),(34,1),(35,1),(36,1),(37,1),(38,1),(39,1),(41,1),(42,1),(43,1),(44,1),(45,1),(54,1),(55,1),(56,1),(57,1),(58,1),(59,1),(69,1),(70,1),(71,1),(80,1),(81,1),(86,1),(95,1),(103,1),(104,1),(108,1),(109,1),(110,1),(112,1),(113,1),(114,1),(115,1),(118,1),(119,1),(120,1),(122,1),(123,1),(124,1),(125,1),(126,1),(127,1),(128,1),(130,1),(131,1),(132,1),(133,1),(134,1),(135,1),(136,1),(138,1),(139,1),(140,1),(142,1),(145,1),(146,1),(149,1),(153,1),(154,1),(155,1),(156,1),(158,1),(159,1),(160,1),(162,1),(164,1),(168,1),(174,1),(175,1),(177,1),(180,1),(184,1),(187,1),(188,1),(189,1),(194,1),(195,1),(198,1),(2,2),(3,2),(4,2),(5,2),(6,2),(8,2),(10,2),(14,2),(17,2),(18,2),(19,2),(22,2),(23,2),(25,2),(26,2),(27,2),(30,2),(31,2),(32,2),(33,2),(34,2),(35,2),(36,2),(37,2),(38,2),(39,2),(41,2),(42,2),(43,2),(44,2),(45,2),(54,2),(55,2),(56,2),(57,2),(58,2),(59,2),(69,2),(70,2),(71,2),(80,2),(81,2),(86,2),(95,2),(103,2),(104,2),(108,2),(109,2),(110,2),(112,2),(113,2),(114,2),(115,2),(117,2),(118,2),(120,2),(121,2),(122,2),(123,2),(124,2),(125,2),(126,2),(127,2),(128,2),(129,2),(130,2),(131,2),(132,2),(133,2),(134,2),(135,2),(136,2),(138,2),(139,2),(140,2),(143,2),(146,2),(147,2),(148,2),(150,2),(153,2),(154,2),(155,2),(156,2),(157,2),(158,2),(159,2),(160,2),(161,2),(162,2),(163,2),(170,2),(173,2),(175,2),(178,2),(181,2),(184,2),(185,2),(188,2),(192,2),(193,2),(194,2),(198,2),(2,3),(3,3),(5,3),(6,3),(7,3),(10,3),(11,3),(14,3),(17,3),(18,3),(19,3),(22,3),(23,3),(25,3),(26,3),(28,3),(30,3),(31,3),(32,3),(33,3),(34,3),(35,3),(36,3),(37,3),(38,3),(39,3),(41,3),(42,3),(43,3),(44,3),(45,3),(54,3),(55,3),(56,3),(57,3),(59,3),(69,3),(70,3),(71,3),(80,3),(81,3),(86,3),(95,3),(103,3),(104,3),(107,3),(108,3),(110,3),(111,3),(112,3),(113,3),(114,3),(115,3),(117,3),(118,3),(120,3),(122,3),(123,3),(124,3),(125,3),(126,3),(127,3),(128,3),(130,3),(131,3),(133,3),(134,3),(135,3),(136,3),(137,3),(139,3),(140,3),(141,3),(143,3),(144,3),(147,3),(148,3),(152,3),(153,3),(154,3),(155,3),(156,3),(157,3),(158,3),(159,3),(160,3),(161,3),(162,3),(165,3),(169,3),(171,3),(175,3),(178,3),(179,3),(183,3),(184,3),(186,3),(188,3),(191,3),(194,3),(195,3),(198,3),(2,6),(3,6),(6,6),(7,6),(8,6),(10,6),(11,6),(14,6),(22,6),(23,6),(25,6),(29,6),(33,6),(36,6),(39,6),(44,6),(45,6),(54,6),(55,6),(56,6),(57,6),(59,6),(80,6),(81,6),(86,6),(95,6),(103,6),(104,6),(107,6),(108,6),(110,6),(111,6),(112,6),(113,6),(114,6),(115,6),(116,6),(118,6),(119,6),(120,6),(121,6),(122,6),(123,6),(124,6),(125,6),(126,6),(127,6),(128,6),(130,6),(131,6),(133,6),(134,6),(135,6),(136,6),(138,6),(139,6),(140,6),(141,6),(142,6),(146,6),(147,6),(148,6),(151,6),(153,6),(154,6),(155,6),(156,6),(157,6),(158,6),(159,6),(160,6),(161,6),(162,6),(166,6),(167,6),(172,6),(175,6),(176,6),(178,6),(182,6),(184,6),(185,6),(188,6),(190,6),(194,6),(198,6),(2,8),(6,8),(7,8),(10,8),(11,8),(22,8),(25,8),(54,8),(107,8),(108,8),(110,8),(111,8),(112,8),(113,8),(114,8),(115,8),(117,8),(118,8),(120,8),(122,8),(123,8),(124,8),(125,8),(126,8),(127,8),(128,8),(130,8),(131,8),(133,8),(134,8),(135,8),(136,8),(137,8),(139,8),(140,8),(141,8),(143,8),(144,8),(147,8),(148,8),(152,8),(153,8),(154,8),(155,8),(156,8),(157,8),(158,8),(159,8),(160,8),(161,8),(162,8),(165,8),(169,8),(171,8),(175,8),(178,8),(179,8),(183,8),(184,8),(186,8),(188,8),(191,8),(194,8),(195,8),(198,8);
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edot_for_group`
--

LOCK TABLES `edot_for_group` WRITE;
/*!40000 ALTER TABLE `edot_for_group` DISABLE KEYS */;
INSERT INTO `edot_for_group` VALUES (1,1),(2,1),(3,1),(4,1),(1,2),(2,2),(3,2),(4,2),(1,3),(2,3),(3,3),(4,3),(1,6),(2,6),(3,6),(4,6),(1,8),(2,8),(3,8),(4,8);
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
) ENGINE=InnoDB AUTO_INCREMENT=35081 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `matches`
--

LOCK TABLES `matches` WRITE;
/*!40000 ALTER TABLE `matches` DISABLE KEYS */;
/*!40000 ALTER TABLE `matches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_codes`
--

DROP TABLE IF EXISTS `password_reset_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_codes` (
  `code` varchar(512) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `expires` datetime NOT NULL,
  `code_id` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`code_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_codes`
--

LOCK TABLES `password_reset_codes` WRITE;
/*!40000 ALTER TABLE `password_reset_codes` DISABLE KEYS */;
INSERT INTO `password_reset_codes` VALUES ('cd4b8b81d1ab2ec75b5ceb80e853bf552df232fc43cabf9cec847ca1074f5c2b','2023-06-13 18:39:44',9);
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
) ENGINE=InnoDB AUTO_INCREMENT=8481 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `preferences`
--

LOCK TABLES `preferences` WRITE;
/*!40000 ALTER TABLE `preferences` DISABLE KEYS */;
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
  `name` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`session_id`),
  UNIQUE KEY `uk_sessions` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES (16,'Full Summer'),(5,'Session A'),(6,'Session B'),(7,'Session C'),(8,'Session D'),(9,'Sessions AB'),(12,'Sessions ABC'),(14,'Sessions ABD'),(10,'Sessions AC'),(15,'Sessions ACD'),(11,'Sessions AD'),(17,'Sessions BC'),(18,'Sessions BCD'),(20,'Sessions CD');
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'camprama_chugbot_db'
--

--
-- Dumping routines for database 'camprama_chugbot_db'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-01-29 18:46:49
