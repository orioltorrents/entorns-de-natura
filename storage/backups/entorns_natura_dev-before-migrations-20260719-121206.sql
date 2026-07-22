-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: entorns_natura_dev
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `entorns_natura_dev`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `entorns_natura_dev` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `entorns_natura_dev`;

--
-- Table structure for table `academic_years`
--

DROP TABLE IF EXISTS `academic_years`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_years` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `start_year` year(4) NOT NULL,
  `end_year` year(4) NOT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_academic_years_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_years`
--

LOCK TABLES `academic_years` WRITE;
/*!40000 ALTER TABLE `academic_years` DISABLE KEYS */;
INSERT INTO `academic_years` VALUES (1,'2025-2026',2025,2026,1,'2026-06-29 10:12:18');
/*!40000 ALTER TABLE `academic_years` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `class_members`
--

DROP TABLE IF EXISTS `class_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `class_members` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `class_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_class_members` (`class_id`,`user_id`),
  KEY `fk_class_members_user` (`user_id`),
  CONSTRAINT `fk_class_members_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_class_members_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `class_members`
--

LOCK TABLES `class_members` WRITE;
/*!40000 ALTER TABLE `class_members` DISABLE KEYS */;
INSERT INTO `class_members` VALUES (1,1,1,'2026-06-29 10:29:41'),(2,2,2,'2026-06-29 10:29:59');
/*!40000 ALTER TABLE `class_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `class_teachers`
--

DROP TABLE IF EXISTS `class_teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `class_teachers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `class_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_class_teachers` (`class_id`,`user_id`),
  KEY `fk_class_teachers_user` (`user_id`),
  CONSTRAINT `fk_class_teachers_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_class_teachers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `class_teachers`
--

LOCK TABLES `class_teachers` WRITE;
/*!40000 ALTER TABLE `class_teachers` DISABLE KEYS */;
INSERT INTO `class_teachers` VALUES (1,1,5,'2026-06-29 10:30:24'),(2,2,5,'2026-06-29 10:30:24'),(3,1,4,'2026-06-29 10:30:24'),(4,2,4,'2026-06-29 10:30:24'),(5,1,3,'2026-06-29 10:30:24'),(6,2,3,'2026-06-29 10:30:24');
/*!40000 ALTER TABLE `class_teachers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `classes`
--

DROP TABLE IF EXISTS `classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `classes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `academic_year_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_classes_year_name` (`academic_year_id`,`name`),
  CONSTRAINT `fk_classes_academic_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `classes`
--

LOCK TABLES `classes` WRITE;
/*!40000 ALTER TABLE `classes` DISABLE KEYS */;
INSERT INTO `classes` VALUES (1,1,'4ESOA','4ESOA','2026-06-29 10:12:18'),(2,1,'4ESOB','4ESOB','2026-06-29 10:12:18');
/*!40000 ALTER TABLE `classes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `languages`
--

DROP TABLE IF EXISTS `languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `languages` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_languages_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `languages`
--

LOCK TABLES `languages` WRITE;
/*!40000 ALTER TABLE `languages` DISABLE KEYS */;
INSERT INTO `languages` VALUES (1,'ca','Català',1,'2026-06-29 10:12:18'),(2,'es','Castellano',0,'2026-06-29 10:12:18'),(3,'en','English',0,'2026-06-29 10:12:18');
/*!40000 ALTER TABLE `languages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_groups`
--

DROP TABLE IF EXISTS `project_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `academic_year_id` int(11) DEFAULT NULL,
  `starts_at` date DEFAULT NULL,
  `ends_at` date DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_project_class_year` (`project_id`,`class_id`,`academic_year_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_groups`
--

LOCK TABLES `project_groups` WRITE;
/*!40000 ALTER TABLE `project_groups` DISABLE KEYS */;
INSERT INTO `project_groups` VALUES (1,3,2,1,NULL,NULL,'active','2026-06-29 10:43:16','2026-06-29 10:43:16'),(2,5,2,1,NULL,NULL,'pendent','2026-06-29 10:43:16','2026-06-29 12:21:53'),(3,2,1,1,NULL,NULL,'active','2026-06-29 10:43:16','2026-06-29 10:43:16'),(4,4,1,1,NULL,NULL,'pendent','2026-06-29 10:43:16','2026-06-29 12:22:00'),(5,1,1,1,NULL,NULL,'realitzat','2026-06-29 10:43:16','2026-06-29 12:16:54'),(6,1,2,1,NULL,NULL,'realitzat','2026-06-29 10:43:16','2026-06-29 12:17:16');
/*!40000 ALTER TABLE `project_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_translations`
--

DROP TABLE IF EXISTS `project_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_translations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(10) unsigned NOT NULL,
  `language_id` smallint(5) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_project_translations` (`project_id`,`language_id`),
  KEY `fk_project_translations_language` (`language_id`),
  CONSTRAINT `fk_project_translations_language` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_project_translations_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_translations`
--

LOCK TABLES `project_translations` WRITE;
/*!40000 ALTER TABLE `project_translations` DISABLE KEYS */;
INSERT INTO `project_translations` VALUES (1,1,1,'Projecte Rius','Projecte educatiu sobre la gestió i recuperació dels rius.','2026-06-29 10:12:18'),(2,1,2,'Proyecto Ríos','Proyecto educativo sobre la gestión y recuperación de los ríos.','2026-06-29 10:12:18'),(3,1,3,'Rivers Project','Educational project about river management and recovery.','2026-06-29 10:12:18'),(4,2,1,'MAT Penedès','Programa d’educació ambiental al Penedès.','2026-06-29 10:12:18'),(5,2,2,'MAT Penedès','Programa de educación ambiental en el Penedès.','2026-06-29 10:12:18'),(6,2,3,'MAT Penedès','Environmental education programme in the Penedès area.','2026-06-29 10:12:18'),(7,3,1,'Agroparc','Projecte sobre agricultura, sostenibilitat i territori.','2026-06-29 10:12:18'),(8,3,2,'Agroparc','Proyecto sobre agricultura, sostenibilidad y territorio.','2026-06-29 10:12:18'),(9,3,3,'Agroparc','Project about agriculture, sustainability and territory.','2026-06-29 10:12:18'),(10,4,1,'Projecte Orenetes','Projecte de conservació i observació de les orenetes.','2026-06-29 10:12:18'),(11,4,2,'Proyecto Orenetes','Proyecto de conservación y observación de las golondrinas.','2026-06-29 10:12:18'),(12,4,3,'Swallow Project','Conservation and observation project for swallows.','2026-06-29 10:12:18'),(13,5,1,'Liquencity','Projecte de ciència ciutadana centrat en líquens.','2026-06-29 10:12:18'),(14,5,2,'Liquencity','Proyecto de ciencia ciudadana centrado en líquenes.','2026-06-29 10:12:18'),(15,5,3,'Liquencity','Citizen science project focused on lichens.','2026-06-29 10:12:18'),(16,6,1,'Vespa velutina','Projecte de seguiment de la vespa velutina.','2026-06-29 10:12:18'),(17,6,2,'Vespa velutina','Proyecto de seguimiento de la avispa velutina.','2026-06-29 10:12:18'),(18,6,3,'Asian hornet','Monitoring project for the Asian hornet.','2026-06-29 10:12:18');
/*!40000 ALTER TABLE `project_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `display_order` int(10) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_projects_slug` (`slug`),
  KEY `idx_projects_display_order` (`display_order`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
INSERT INTO `projects` VALUES (1,'projecte-rius','Projecte Rius',10,1,'2026-06-29 10:12:18'),(2,'mat-penedes','MAT Penedès',20,1,'2026-06-29 10:12:18'),(3,'agroparc','Agroparc',30,1,'2026-06-29 10:12:18'),(4,'projecte-orenetes','Projecte Orenetes',40,1,'2026-06-29 10:12:18'),(5,'liquencity','Liquencity',50,1,'2026-06-29 10:12:18'),(6,'vespa-velutina','Vespa velutina',60,1,'2026-06-29 10:12:18');
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'student','Alumne','2026-06-29 09:49:47'),(2,'teacher','Professor','2026-06-29 09:49:47'),(3,'coordinator','Coordinador','2026-06-29 09:49:47'),(4,'admin','Administrador','2026-06-29 09:49:47');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'current_academic_year','2025-2026','2026-06-29 10:12:18','2026-06-29 10:12:18');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_visits`
--

DROP TABLE IF EXISTS `site_visits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_visits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` varchar(100) DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `visited_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `path` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `country_code` varchar(10) DEFAULT NULL,
  `region` varchar(255) DEFAULT NULL,
  `device_type` varchar(50) DEFAULT NULL,
  `os_family` varchar(50) DEFAULT NULL,
  `browser` varchar(100) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_site_visits_visited_at` (`visited_at`),
  KEY `idx_site_visits_user_id` (`user_id`),
  KEY `idx_site_visits_session_id` (`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_visits`
--

LOCK TABLES `site_visits` WRITE;
/*!40000 ALTER TABLE `site_visits` DISABLE KEYS */;
INSERT INTO `site_visits` VALUES (1,'c33fa579a6222bab173b9e6ebdf3449b',3,'2026-07-19 10:07:13','/ca/projectes','::1',NULL,NULL,'desktop','windows','chrome','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36'),(2,'c33fa579a6222bab173b9e6ebdf3449b',3,'2026-07-19 10:07:17','/admin/projectes','::1',NULL,NULL,'desktop','windows','chrome','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36'),(3,'c33fa579a6222bab173b9e6ebdf3449b',3,'2026-07-19 10:07:17','/admin','::1',NULL,NULL,'desktop','windows','chrome','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36'),(4,'c33fa579a6222bab173b9e6ebdf3449b',3,'2026-07-19 10:07:18','/login','::1',NULL,NULL,'desktop','windows','chrome','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36'),(5,'c33fa579a6222bab173b9e6ebdf3449b',3,'2026-07-19 10:07:18','/admin','::1',NULL,NULL,'desktop','windows','chrome','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36'),(6,'c33fa579a6222bab173b9e6ebdf3449b',3,'2026-07-19 10:07:19','/admin','::1',NULL,NULL,'desktop','windows','chrome','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36'),(7,'c33fa579a6222bab173b9e6ebdf3449b',3,'2026-07-19 10:07:22','/admin','::1',NULL,NULL,'desktop','windows','chrome','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36'),(8,'c33fa579a6222bab173b9e6ebdf3449b',3,'2026-07-19 10:07:27','/entorns-de-natura','::1',NULL,NULL,'desktop','windows','chrome','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36');
/*!40000 ALTER TABLE `site_visits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_role` (`user_id`,`role_id`),
  KEY `fk_user_roles_role` (`role_id`),
  CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_roles`
--

LOCK TABLES `user_roles` WRITE;
/*!40000 ALTER TABLE `user_roles` DISABLE KEYS */;
INSERT INTO `user_roles` VALUES (1,1,1,'2026-06-29 09:55:17'),(2,2,1,'2026-06-29 09:55:17'),(4,3,4,'2026-06-29 09:57:18'),(5,3,3,'2026-06-29 10:00:01'),(6,3,2,'2026-06-29 10:00:01'),(8,4,3,'2026-06-29 10:02:39'),(9,4,2,'2026-06-29 10:02:39'),(11,5,2,'2026-06-29 10:02:47');
/*!40000 ALTER TABLE `user_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `surname` varchar(150) DEFAULT NULL,
  `email` varchar(190) NOT NULL,
  `google_id` varchar(190) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `google_id` (`google_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Aiman','Torrents','aiman@intermunicipal.cat',NULL,'$2y$10$s1nD1E.bkJ1ucqdLrHmxC.3FgetwyfJ3ujEVQryiLo6rs/kWCQbQK',NULL,1,'2026-06-29 13:52:34','2026-06-29 09:54:20','2026-06-29 11:52:34'),(2,'Sílvia','López','silvia@intermunicipal.cat',NULL,'$2y$10$s1nD1E.bkJ1ucqdLrHmxC.3FgetwyfJ3ujEVQryiLo6rs/kWCQbQK',NULL,1,'2026-06-29 14:22:33','2026-06-29 09:54:20','2026-06-29 12:22:33'),(3,'Oriol','Torrents','otorrents@intermunicipal.cat',NULL,'$2y$10$s1nD1E.bkJ1ucqdLrHmxC.3FgetwyfJ3ujEVQryiLo6rs/kWCQbQK',NULL,1,'2026-07-19 11:57:54','2026-06-29 09:56:58','2026-07-19 09:57:54'),(4,'Oriol','Rovira','orovira@intermunicipal.cat',NULL,'$2y$10$s1nD1E.bkJ1ucqdLrHmxC.3FgetwyfJ3ujEVQryiLo6rs/kWCQbQK',NULL,1,'2026-06-29 13:58:01','2026-06-29 10:02:22','2026-06-29 11:58:01'),(5,'Àlex','Martí','amarti@intermunicipal.cat',NULL,'$2y$10$s1nD1E.bkJ1ucqdLrHmxC.3FgetwyfJ3ujEVQryiLo6rs/kWCQbQK',NULL,1,'2026-06-29 13:58:11','2026-06-29 10:02:22','2026-06-29 11:58:11');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-19 12:12:06
