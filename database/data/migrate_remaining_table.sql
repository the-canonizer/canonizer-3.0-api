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

DROP TABLE IF EXISTS `change_agree_logs`;
--
-- Create Table change_agree_logs
--

CREATE TABLE `change_agree_logs`
(
    `id`           int(11) NOT NULL AUTO_INCREMENT,
    `change_id`    int(11)     DEFAULT NULL,
    `topic_num`    int(11) NOT NULL,
    `camp_num`     int(11)     DEFAULT NULL,
    `nick_name_id` int(11) NOT NULL,
    `change_for`   varchar(50) DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 151
  DEFAULT CHARSET = latin1;

DROP TABLE IF EXISTS `ether_address`;
CREATE TABLE `ether_address`
(
    `id`      int(11)      NOT NULL AUTO_INCREMENT,
    `user_id` int(11)      NOT NULL,
    `name`    varchar(255) NOT NULL,
    `address` text         NOT NULL,
    `balance` varchar(255) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 5
  DEFAULT CHARSET = latin1;

DROP TABLE IF EXISTS `failed_jobs`;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs`
(
    `id`         bigint(20) unsigned                 NOT NULL AUTO_INCREMENT,
    `connection` text COLLATE utf8mb4_unicode_ci     NOT NULL,
    `queue`      text COLLATE utf8mb4_unicode_ci     NOT NULL,
    `payload`    longtext COLLATE utf8mb4_unicode_ci NOT NULL,
    `exception`  longtext COLLATE utf8mb4_unicode_ci NOT NULL,
    `failed_at`  timestamp                           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs`
(
    `id`           bigint(20) unsigned                     NOT NULL AUTO_INCREMENT,
    `queue`        varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `payload`      longtext COLLATE utf8mb4_unicode_ci     NOT NULL,
    `attempts`     tinyint(3) unsigned                     NOT NULL,
    `reserved_at`  int(10) unsigned                        DEFAULT NULL,
    `available_at` int(10) unsigned                        NOT NULL,
    `created_at`   int(10) unsigned                        NOT NULL,
    `job_clazz`    varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `model_clazz`  varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `model_id`     varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `jobs_queue_reserved_at_index` (`queue`(191), `reserved_at`),
    KEY `jobs_job_clazz_index` (`job_clazz`(191)),
    KEY `jobs_model_clazz_index` (`model_clazz`(191)),
    KEY `jobs_model_id_index` (`model_id`(191))
) ENGINE = InnoDB
  AUTO_INCREMENT = 9469
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `namespace_requests`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `namespace_requests`
(
    `id`         int(10) unsigned                        NOT NULL AUTO_INCREMENT,
    `user_id`    int(11)                                 NOT NULL,
    `topic_num`  int(11)                                 NOT NULL,
    `name`       varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `created_at` timestamp                               NULL     DEFAULT NULL,
    `updated_at` timestamp                               NULL     DEFAULT NULL,
    `status`     int(11)                                 NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

--
-- Table structure for table `open_social_link`
--

DROP TABLE IF EXISTS `open_social_link`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `open_social_link`
(
    `id`               bigint(12) unsigned NOT NULL,
    `cid`              bigint(12) unsigned NOT NULL,
    `os_container_id`  varchar(255)        NOT NULL,
    `os_user_id_token` varchar(255)        NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `post_old`
--

DROP TABLE IF EXISTS `post_old`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_old`
(
    `post_num`    bigint(12) unsigned NOT NULL DEFAULT '0',
    `thread_num`  bigint(12) unsigned NOT NULL DEFAULT '0',
    `topic_num`   bigint(12) unsigned NOT NULL DEFAULT '0',
    `camp_num`    bigint(12) unsigned NOT NULL DEFAULT '0',
    `nick_id`     bigint(12) unsigned NOT NULL DEFAULT '0',
    `message`     mediumtext          NOT NULL,
    `submit_time` bigint(12) unsigned NOT NULL DEFAULT '0',
    `post_id`     bigint(12) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`post_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `processed_jobs`
--

DROP TABLE IF EXISTS `processed_jobs`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `processed_jobs`
(
    `id`         int(10) unsigned NOT NULL AUTO_INCREMENT,
    `payload`    longtext COLLATE utf8mb4_unicode_ci,
    `status`     varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `code`       int(11)                                 DEFAULT NULL,
    `response`   longtext COLLATE utf8mb4_unicode_ci,
    `created_at` timestamp        NULL                   DEFAULT NULL,
    `updated_at` timestamp        NULL                   DEFAULT NULL,
    `topic_num`  int(11)                                 DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 2472
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `reset_passwords`
--

DROP TABLE IF EXISTS `reset_passwords`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reset_passwords`
(
    `id`         char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
    `user_id`    bigint(20) unsigned                 NOT NULL,
    `created_at` timestamp                           NULL DEFAULT NULL,
    `updated_at` timestamp                           NULL DEFAULT NULL,
    `reset_at`   datetime                                 DEFAULT NULL,
    `expires_at` datetime                                 DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `reset_passwords_user_id_index` (`user_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `shares_algo_data`
--

DROP TABLE IF EXISTS `shares_algo_data`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shares_algo_data`
(
    `id`           int(11)      NOT NULL AUTO_INCREMENT,
    `nick_name_id` int(11)      NOT NULL,
    `as_of_date`   date         NOT NULL,
    `share_value`  varchar(255) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 244
  DEFAULT CHARSET = latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
--
-- Table structure for table `support_instance`
--

DROP TABLE IF EXISTS `support_instance`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_instance`
(
    `id`               int(10) unsigned                        NOT NULL AUTO_INCREMENT,
    `topic_support_id` int(11)                                 NOT NULL,
    `camp_num`         int(11)                                 NOT NULL,
    `support_order`    int(11)                                 NOT NULL,
    `submit_time`      varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `status`           varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    PRIMARY KEY (`id`),
    KEY `support_instance_topic_support_id_index` (`topic_support_id`),
    KEY `support_instance_camp_num_index` (`camp_num`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 1890
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `templates`
--

DROP TABLE IF EXISTS `templates`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `templates`
(
    `id`         int(11)                         NOT NULL AUTO_INCREMENT,
    `name`       text COLLATE utf8mb4_unicode_ci NOT NULL,
    `subject`    text COLLATE utf8mb4_unicode_ci NOT NULL,
    `body`       text COLLATE utf8mb4_unicode_ci NOT NULL,
    `status`     int(11)                         NOT NULL DEFAULT '0',
    `created_at` timestamp                       NULL     DEFAULT NULL,
    `updated_at` timestamp                       NULL     DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `id` (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `thread_old`
--

DROP TABLE IF EXISTS `thread_old`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `thread_old`
(
    `thread_num` bigint(12) unsigned NOT NULL DEFAULT '0',
    `topic_num`  bigint(12) unsigned NOT NULL DEFAULT '0',
    `camp_num`   bigint(12) unsigned NOT NULL DEFAULT '0',
    `subject`    mediumtext          NOT NULL,
    `views`      int(10) unsigned             DEFAULT '0',
    `thread_id`  bigint(12) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`thread_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `wp_commentmeta`
--

DROP TABLE IF EXISTS `wp_commentmeta`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_commentmeta`
(
    `meta_id`    bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `comment_id` bigint(20) unsigned NOT NULL                DEFAULT '0',
    `meta_key`   varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
    `meta_value` longtext COLLATE utf8mb4_unicode_520_ci,
    PRIMARY KEY (`meta_id`),
    KEY `comment_id` (`comment_id`),
    KEY `meta_key` (`meta_key`(191))
) ENGINE = InnoDB
  AUTO_INCREMENT = 1835
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wp_comments`
--

DROP TABLE IF EXISTS `wp_comments`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_comments`
(
    `comment_ID`           bigint(20) unsigned                         NOT NULL AUTO_INCREMENT,
    `comment_post_ID`      bigint(20) unsigned                         NOT NULL DEFAULT '0',
    `comment_author`       tinytext COLLATE utf8mb4_unicode_520_ci     NOT NULL,
    `comment_author_email` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
    `comment_author_url`   varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
    `comment_author_IP`    varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
    `comment_date`         datetime                                    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `comment_date_gmt`     datetime                                    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `comment_content`      text COLLATE utf8mb4_unicode_520_ci         NOT NULL,
    `comment_karma`        int(11)                                     NOT NULL DEFAULT '0',
    `comment_approved`     varchar(20) COLLATE utf8mb4_unicode_520_ci  NOT NULL DEFAULT '1',
    `comment_agent`        varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
    `comment_type`         varchar(20) COLLATE utf8mb4_unicode_520_ci  NOT NULL DEFAULT '',
    `comment_parent`       bigint(20) unsigned                         NOT NULL DEFAULT '0',
    `user_id`              bigint(20) unsigned                         NOT NULL DEFAULT '0',
    PRIMARY KEY (`comment_ID`),
    KEY `comment_post_ID` (`comment_post_ID`),
    KEY `comment_approved_date_gmt` (`comment_approved`, `comment_date_gmt`),
    KEY `comment_date_gmt` (`comment_date_gmt`),
    KEY `comment_parent` (`comment_parent`),
    KEY `comment_author_email` (`comment_author_email`(10))
) ENGINE = InnoDB
  AUTO_INCREMENT = 663
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wp_links`
--

DROP TABLE IF EXISTS `wp_links`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_links`
(
    `link_id`          bigint(20) unsigned                         NOT NULL AUTO_INCREMENT,
    `link_url`         varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
    `link_name`        varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
    `link_image`       varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
    `link_target`      varchar(25) COLLATE utf8mb4_unicode_520_ci  NOT NULL DEFAULT '',
    `link_description` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
    `link_visible`     varchar(20) COLLATE utf8mb4_unicode_520_ci  NOT NULL DEFAULT 'Y',
    `link_owner`       bigint(20) unsigned                         NOT NULL DEFAULT '1',
    `link_rating`      int(11)                                     NOT NULL DEFAULT '0',
    `link_updated`     datetime                                    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `link_rel`         varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
    `link_notes`       mediumtext COLLATE utf8mb4_unicode_520_ci   NOT NULL,
    `link_rss`         varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
    PRIMARY KEY (`link_id`),
    KEY `link_visible` (`link_visible`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wp_options`
--

DROP TABLE IF EXISTS `wp_options`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_options`
(
    `option_id`    bigint(20) unsigned                         NOT NULL AUTO_INCREMENT,
    `option_name`  varchar(191) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
    `option_value` longtext COLLATE utf8mb4_unicode_520_ci     NOT NULL,
    `autoload`     varchar(20) COLLATE utf8mb4_unicode_520_ci  NOT NULL DEFAULT 'yes',
    PRIMARY KEY (`option_id`),
    UNIQUE KEY `option_name` (`option_name`),
    KEY `autoload` (`autoload`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 176084
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wp_postmeta`
--

DROP TABLE IF EXISTS `wp_postmeta`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_postmeta`
(
    `meta_id`    bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `post_id`    bigint(20) unsigned NOT NULL                DEFAULT '0',
    `meta_key`   varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
    `meta_value` longtext COLLATE utf8mb4_unicode_520_ci,
    PRIMARY KEY (`meta_id`),
    KEY `post_id` (`post_id`),
    KEY `meta_key` (`meta_key`(191))
) ENGINE = InnoDB
  AUTO_INCREMENT = 7551
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wp_posts`
--

DROP TABLE IF EXISTS `wp_posts`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_posts`
(
    `ID`                    bigint(20) unsigned                         NOT NULL AUTO_INCREMENT,
    `post_author`           bigint(20) unsigned                         NOT NULL DEFAULT '0',
    `post_date`             datetime                                    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `post_date_gmt`         datetime                                    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `post_content`          longtext COLLATE utf8mb4_unicode_520_ci     NOT NULL,
    `post_title`            text COLLATE utf8mb4_unicode_520_ci         NOT NULL,
    `post_excerpt`          text COLLATE utf8mb4_unicode_520_ci         NOT NULL,
    `post_status`           varchar(20) COLLATE utf8mb4_unicode_520_ci  NOT NULL DEFAULT 'publish',
    `comment_status`        varchar(20) COLLATE utf8mb4_unicode_520_ci  NOT NULL DEFAULT 'open',
    `ping_status`           varchar(20) COLLATE utf8mb4_unicode_520_ci  NOT NULL DEFAULT 'open',
    `post_password`         varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
    `post_name`             varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
    `to_ping`               text COLLATE utf8mb4_unicode_520_ci         NOT NULL,
    `pinged`                text COLLATE utf8mb4_unicode_520_ci         NOT NULL,
    `post_modified`         datetime                                    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `post_modified_gmt`     datetime                                    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `post_content_filtered` longtext COLLATE utf8mb4_unicode_520_ci     NOT NULL,
    `post_parent`           bigint(20) unsigned                         NOT NULL DEFAULT '0',
    `guid`                  varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
    `menu_order`            int(11)                                     NOT NULL DEFAULT '0',
    `post_type`             varchar(20) COLLATE utf8mb4_unicode_520_ci  NOT NULL DEFAULT 'post',
    `post_mime_type`        varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
    `comment_count`         bigint(20)                                  NOT NULL DEFAULT '0',
    PRIMARY KEY (`ID`),
    KEY `post_name` (`post_name`(191)),
    KEY `type_status_date` (`post_type`, `post_status`, `post_date`, `ID`),
    KEY `post_parent` (`post_parent`),
    KEY `post_author` (`post_author`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 7108
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wp_term_relationships`
--

DROP TABLE IF EXISTS `wp_term_relationships`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_term_relationships`
(
    `object_id`        bigint(20) unsigned NOT NULL DEFAULT '0',
    `term_taxonomy_id` bigint(20) unsigned NOT NULL DEFAULT '0',
    `term_order`       int(11)             NOT NULL DEFAULT '0',
    PRIMARY KEY (`object_id`, `term_taxonomy_id`),
    KEY `term_taxonomy_id` (`term_taxonomy_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wp_term_taxonomy`
--

DROP TABLE IF EXISTS `wp_term_taxonomy`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_term_taxonomy`
(
    `term_taxonomy_id` bigint(20) unsigned                        NOT NULL AUTO_INCREMENT,
    `term_id`          bigint(20) unsigned                        NOT NULL DEFAULT '0',
    `taxonomy`         varchar(32) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
    `description`      longtext COLLATE utf8mb4_unicode_520_ci    NOT NULL,
    `parent`           bigint(20) unsigned                        NOT NULL DEFAULT '0',
    `count`            bigint(20)                                 NOT NULL DEFAULT '0',
    PRIMARY KEY (`term_taxonomy_id`),
    UNIQUE KEY `term_id_taxonomy` (`term_id`, `taxonomy`),
    KEY `taxonomy` (`taxonomy`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 61
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wp_termmeta`
--

DROP TABLE IF EXISTS `wp_termmeta`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_termmeta`
(
    `meta_id`    bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `term_id`    bigint(20) unsigned NOT NULL                DEFAULT '0',
    `meta_key`   varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
    `meta_value` longtext COLLATE utf8mb4_unicode_520_ci,
    PRIMARY KEY (`meta_id`),
    KEY `term_id` (`term_id`),
    KEY `meta_key` (`meta_key`(191))
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wp_terms`
--

DROP TABLE IF EXISTS `wp_terms`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_terms`
(
    `term_id`    bigint(20) unsigned                         NOT NULL AUTO_INCREMENT,
    `name`       varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
    `slug`       varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
    `term_group` bigint(10)                                  NOT NULL DEFAULT '0',
    PRIMARY KEY (`term_id`),
    KEY `slug` (`slug`(191)),
    KEY `name` (`name`(191))
) ENGINE = InnoDB
  AUTO_INCREMENT = 61
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wp_usermeta`
--

DROP TABLE IF EXISTS `wp_usermeta`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_usermeta`
(
    `umeta_id`   bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `user_id`    bigint(20) unsigned NOT NULL                DEFAULT '0',
    `meta_key`   varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
    `meta_value` longtext COLLATE utf8mb4_unicode_520_ci,
    PRIMARY KEY (`umeta_id`),
    KEY `user_id` (`user_id`),
    KEY `meta_key` (`meta_key`(191))
) ENGINE = InnoDB
  AUTO_INCREMENT = 123
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wp_users`
--

DROP TABLE IF EXISTS `wp_users`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_users`
(
    `ID`                  bigint(20) unsigned                         NOT NULL AUTO_INCREMENT,
    `user_login`          varchar(60) COLLATE utf8mb4_unicode_520_ci  NOT NULL DEFAULT '',
    `user_pass`           varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
    `user_nicename`       varchar(50) COLLATE utf8mb4_unicode_520_ci  NOT NULL DEFAULT '',
    `user_email`          varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
    `user_url`            varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
    `user_registered`     datetime                                    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `user_activation_key` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
    `user_status`         int(11)                                     NOT NULL DEFAULT '0',
    `display_name`        varchar(250) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
    PRIMARY KEY (`ID`),
    KEY `user_login_key` (`user_login`),
    KEY `user_nicename` (`user_nicename`),
    KEY `user_email` (`user_email`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 7
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE = @OLD_TIME_ZONE */;



