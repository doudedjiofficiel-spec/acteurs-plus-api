
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
DROP TABLE IF EXISTS `actor_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `actor_profiles` (
  `user_id` bigint(20) unsigned NOT NULL,
  `stage_name` varchar(150) DEFAULT '',
  `role` varchar(150) DEFAULT '',
  `about` text DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `nationality` varchar(100) DEFAULT '',
  `height` varchar(20) DEFAULT '',
  `weight` varchar(20) DEFAULT '',
  `eyes_color` varchar(40) DEFAULT '',
  `hair_color` varchar(40) DEFAULT '',
  `morphology` varchar(60) DEFAULT '',
  `accents` varchar(255) DEFAULT '',
  `dialects` varchar(255) DEFAULT '',
  `location` varchar(150) DEFAULT '',
  `country` varchar(100) DEFAULT '',
  `city` varchar(100) DEFAULT '',
  `mobility_type` varchar(40) DEFAULT 'local',
  `intervention_zone` varchar(255) DEFAULT '',
  `showreel_type` varchar(40) DEFAULT 'youtube',
  `showreel_link` varchar(500) DEFAULT '',
  `cv_url` varchar(500) DEFAULT '',
  `professional_organization` varchar(150) DEFAULT '',
  `currency` varchar(10) DEFAULT 'FCFA',
  `represented_by_agency` tinyint(1) NOT NULL DEFAULT 0,
  `languages_legacy` text DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_actor_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `agency_roster`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agency_roster` (
  `user_id` bigint(20) unsigned NOT NULL,
  `contract_type` varchar(40) NOT NULL DEFAULT 'non-exclusif',
  `representation_start` date DEFAULT NULL,
  `representation_end` date DEFAULT NULL,
  `roster_status` varchar(20) NOT NULL DEFAULT 'actif',
  `agency_notes` text DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_roster_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `applications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kind` varchar(20) NOT NULL,
  `target_id` bigint(20) unsigned NOT NULL,
  `target_title` varchar(200) DEFAULT '',
  `candidate_uid` bigint(20) unsigned NOT NULL,
  `candidate_name` varchar(150) DEFAULT '',
  `candidate_role` varchar(150) DEFAULT '',
  `candidate_image` mediumtext DEFAULT NULL,
  `owner_uid` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'recue',
  `note` text DEFAULT NULL,
  `to_audition` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_app_candidate` (`candidate_uid`),
  KEY `idx_app_owner` (`owner_uid`),
  KEY `idx_app_target` (`kind`,`target_id`),
  CONSTRAINT `fk_app_candidate` FOREIGN KEY (`candidate_uid`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `artistic_team_slots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `artistic_team_slots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `team_id` bigint(20) unsigned NOT NULL,
  `role_category` varchar(80) DEFAULT '',
  `role_name` varchar(150) DEFAULT '',
  `assigned_actor_id` bigint(20) unsigned DEFAULT NULL,
  `contact_status` varchar(40) DEFAULT 'Non contacte',
  PRIMARY KEY (`id`),
  KEY `idx_slot_team` (`team_id`),
  KEY `fk_slot_actor` (`assigned_actor_id`),
  CONSTRAINT `fk_slot_actor` FOREIGN KEY (`assigned_actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_slot_team` FOREIGN KEY (`team_id`) REFERENCES `artistic_teams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `artistic_teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `artistic_teams` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `project_type` varchar(60) DEFAULT '',
  `project_description` text DEFAULT NULL,
  `status` varchar(40) DEFAULT '',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_artteam_creator` (`created_by`),
  CONSTRAINT `fk_artteam_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `availability_calendar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `availability_calendar` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` varchar(40) DEFAULT '',
  `note` varchar(255) DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `idx_avail_user` (`user_id`),
  CONSTRAINT `fk_avail_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `awards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `awards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(200) DEFAULT '',
  `year` varchar(10) DEFAULT '',
  `project` varchar(200) DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `idx_awards_user` (`user_id`),
  CONSTRAINT `fk_awards_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `castings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `castings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `role` varchar(150) DEFAULT '',
  `description` text DEFAULT NULL,
  `location` varchar(150) DEFAULT '',
  `deadline` datetime DEFAULT NULL,
  `close_date_legacy` varchar(60) DEFAULT NULL,
  `project_type` varchar(60) DEFAULT '',
  `status` varchar(20) DEFAULT 'ouvert',
  `image` mediumtext DEFAULT NULL,
  `company` varchar(150) DEFAULT '',
  `role_age` varchar(60) DEFAULT '',
  `role_physique` varchar(255) DEFAULT '',
  `role_psychologie` text DEFAULT NULL,
  `role_arc` text DEFAULT NULL,
  `criteria_required` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`criteria_required`)),
  `criteria_preferred` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`criteria_preferred`)),
  `budget_mode` varchar(20) DEFAULT '',
  `budget_amount` varchar(60) DEFAULT '',
  `shoot_dates` varchar(150) DEFAULT '',
  `duration` varchar(60) DEFAULT '',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_castings_creator` (`created_by`),
  KEY `idx_castings_status` (`status`),
  CONSTRAINT `fk_castings_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `certifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `certifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `name` varchar(200) NOT NULL,
  `year` varchar(10) DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `idx_certs_user` (`user_id`),
  CONSTRAINT `fk_certs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conversation_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversation_participants` (
  `conversation_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `last_read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`conversation_id`,`user_id`),
  KEY `idx_cp_user` (`user_id`),
  CONSTRAINT `fk_cp_conv` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `crew_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `crew_members` (
  `crew_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`crew_id`,`user_id`),
  KEY `idx_crewmember_user` (`user_id`),
  CONSTRAINT `fk_crewmember_crew` FOREIGN KEY (`crew_id`) REFERENCES `crews` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_crewmember_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `crews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `crews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `project_description` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_crew_creator` (`created_by`),
  CONSTRAINT `fk_crew_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `filmography`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `filmography` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `project_title` varchar(200) DEFAULT '',
  `project_type` varchar(60) DEFAULT '',
  `role` varchar(150) DEFAULT '',
  `production_name` varchar(200) DEFAULT '',
  `director` varchar(200) DEFAULT '',
  `year` varchar(10) DEFAULT '',
  `description` text DEFAULT NULL,
  `project_link` varchar(500) DEFAULT '',
  `link_type` varchar(40) DEFAULT '',
  `poster_image` mediumtext DEFAULT NULL,
  `media` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`media`)),
  PRIMARY KEY (`id`),
  KEY `idx_film_user` (`user_id`),
  CONSTRAINT `fk_film_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `follows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `follows` (
  `follower_uid` bigint(20) unsigned NOT NULL,
  `followed_uid` bigint(20) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`follower_uid`,`followed_uid`),
  KEY `idx_follows_followed` (`followed_uid`),
  CONSTRAINT `fk_follows_followed` FOREIGN KEY (`followed_uid`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_follows_follower` FOREIGN KEY (`follower_uid`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `formations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `formations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `school` varchar(200) DEFAULT '',
  `degree` varchar(200) DEFAULT '',
  `year` varchar(10) DEFAULT '',
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_formations_user` (`user_id`),
  CONSTRAINT `fk_formations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `gallery_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gallery_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` varchar(20) DEFAULT 'photo',
  `url` mediumtext DEFAULT NULL,
  `title` varchar(200) DEFAULT '',
  `description` text DEFAULT NULL,
  `category` varchar(120) DEFAULT '',
  `visibility` varchar(20) DEFAULT 'public',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_gallery_user` (`user_id`),
  CONSTRAINT `fk_gallery_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `magazines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `magazines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `number` varchar(40) DEFAULT '',
  `date` date DEFAULT NULL,
  `cover` varchar(500) DEFAULT '',
  `pdf_url` varchar(500) DEFAULT '',
  `description` text DEFAULT NULL,
  `pages` int(10) unsigned DEFAULT 0,
  `status` varchar(20) DEFAULT 'draft',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mastered_equipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mastered_equipment` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `value` varchar(150) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mastered_user` (`user_id`),
  CONSTRAINT `fk_mastered_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `sender_uid` bigint(20) unsigned NOT NULL,
  `text` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_msg_conv` (`conversation_id`),
  KEY `fk_msg_sender` (`sender_uid`),
  CONSTRAINT `fk_msg_conv` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_msg_sender` FOREIGN KEY (`sender_uid`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `missions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `missions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `departments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`departments`)),
  `roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`roles`)),
  `location` varchar(150) DEFAULT '',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `deadline` datetime DEFAULT NULL,
  `project_type` varchar(60) DEFAULT '',
  `image` mediumtext DEFAULT NULL,
  `profile_wanted` text DEFAULT NULL,
  `criteria_required` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`criteria_required`)),
  `criteria_preferred` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`criteria_preferred`)),
  `budget` varchar(60) DEFAULT '',
  `budget_mode` varchar(20) DEFAULT '',
  `budget_visibility` varchar(20) DEFAULT '',
  `duration` varchar(60) DEFAULT '',
  `status` varchar(20) DEFAULT 'ouverte',
  `company` varchar(150) DEFAULT '',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_missions_creator` (`created_by`),
  KEY `idx_missions_status` (`status`),
  CONSTRAINT `fk_missions_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(60) DEFAULT '',
  `text` varchar(500) NOT NULL,
  `color` varchar(20) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `token` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_pr_token` (`token`),
  KEY `idx_pr_user` (`user_id`),
  CONSTRAINT `fk_pr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `profile_languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `profile_languages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `name` varchar(80) NOT NULL,
  `level` varchar(40) DEFAULT 'Courant',
  PRIMARY KEY (`id`),
  KEY `idx_lang_user` (`user_id`),
  CONSTRAINT `fk_lang_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rate_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rate_limits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `action` varchar(32) NOT NULL,
  `rl_key` varchar(190) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rl` (`action`,`rl_key`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recruiter_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recruiter_profiles` (
  `user_id` bigint(20) unsigned NOT NULL,
  `recruiter_type` varchar(60) DEFAULT '',
  `location` varchar(150) DEFAULT '',
  `phone` varchar(40) DEFAULT '',
  `website` varchar(255) DEFAULT '',
  `about` text DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_recruiter_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `target_type` varchar(40) DEFAULT '',
  `target_id` varchar(80) DEFAULT '',
  `target_label` varchar(200) DEFAULT '',
  `reporter_uid` bigint(20) unsigned DEFAULT NULL,
  `reason` varchar(200) DEFAULT '',
  `note` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'open',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_reports_reporter` (`reporter_uid`),
  CONSTRAINT `fk_reports_reporter` FOREIGN KEY (`reporter_uid`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `author` varchar(150) DEFAULT '',
  `author_uid` bigint(20) unsigned DEFAULT NULL,
  `rating` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `comment` text DEFAULT NULL,
  `recommend` tinyint(1) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_review_author` (`user_id`,`author_uid`),
  KEY `idx_reviews_user` (`user_id`),
  CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sanctions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sanctions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `target_uid` bigint(20) unsigned NOT NULL,
  `action` varchar(20) NOT NULL,
  `reason` text DEFAULT NULL,
  `until` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sanctions_target` (`target_uid`),
  CONSTRAINT `fk_sanctions_target` FOREIGN KEY (`target_uid`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `secondary_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `secondary_roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `value` varchar(150) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_secroles_user` (`user_id`),
  CONSTRAINT `fk_secroles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `token` char(64) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sessions_token` (`token`),
  KEY `idx_sessions_user` (`user_id`),
  CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=115 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `skills` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `level` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_skills_user` (`user_id`),
  CONSTRAINT `fk_skills_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `software_skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `software_skills` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `level` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_software_user` (`user_id`),
  CONSTRAINT `fk_software_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `technician_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `technician_profiles` (
  `user_id` bigint(20) unsigned NOT NULL,
  `role` varchar(150) DEFAULT '',
  `about` text DEFAULT NULL,
  `location` varchar(150) DEFAULT '',
  `crew_ready` tinyint(1) NOT NULL DEFAULT 0,
  `availability_status` varchar(40) DEFAULT '',
  `availability_start_date` date DEFAULT NULL,
  `availability_end_date` date DEFAULT NULL,
  `availability_note` varchar(255) DEFAULT '',
  `daily_rate` varchar(40) DEFAULT '',
  `daily_rate_visibility` tinyint(1) NOT NULL DEFAULT 0,
  `equipment` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`equipment`)),
  `mobility` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`mobility`)),
  `profession` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`profession`)),
  `software_legacy` text DEFAULT NULL,
  `hardware_legacy` text DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_tech_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `user_type` varchar(20) NOT NULL DEFAULT 'talent',
  `gender` enum('H','F') DEFAULT NULL,
  `image` mediumtext DEFAULT NULL,
  `cover_image` mediumtext DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `account_status` enum('active','restricted','banned') NOT NULL DEFAULT 'active',
  `score` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `credibility_level` varchar(20) DEFAULT NULL,
  `rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `reviews_count` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_type` (`user_type`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

