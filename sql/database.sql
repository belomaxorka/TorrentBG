-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 22, 2025 at 10:46 AM
-- Server version: 9.1.0
-- PHP Version: 8.4.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------

--
-- Table structure for table `blocked_ips`
--

DROP TABLE IF EXISTS `blocked_ips`;
CREATE TABLE IF NOT EXISTS `blocked_ips` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `blocked_until` datetime NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blocks`
--

DROP TABLE IF EXISTS `blocks`;
CREATE TABLE IF NOT EXISTS `blocks` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` enum('left','center','right') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'center',
  `order` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_locked` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blocks`
--

INSERT INTO `blocks` (`id`, `name`, `title`, `position`, `order`, `is_active`, `is_locked`) VALUES
(11, 'latest_torrents', 'Последни торенти', 'center', 2, 1, 0),
(12, 'shoutbox', 'Чат', 'center', 1, 1, 0),
(13, 'user_info', 'Потребителска информация', 'left', 1, 1, 0),
(14, 'online_users', 'Онлайн потребители', 'right', 2, 1, 0),
(15, 'clock', 'Часовник', 'right', 2, 1, 0),
(21, 'Анкета', 'poll', 'left', 3, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `icon`, `description`, `order`, `is_active`) VALUES
(6, 'Movies Xvid', 'images/categories/category_68cd832480b94.png', 'Only Xvid', 1, 1),
(7, 'Movies/HD', 'images/categories/category_68e760668ceaf.png', '', 2, 1),
(8, 'Movies/DVD', 'images/categories/category_68e8fbd538e9c.png', '', 3, 1),
(9, 'Bluray', 'images/categories/category_68e901755abfc.png', '', 4, 1),
(10, 'x264', 'images/categories/category_68e901a0c3f15.png', '', 5, 1),
(11, 'XXX', 'images/categories/category_68e901e5ea2ea.png', '', 6, 1),
(12, 'Games PC', 'images/categories/category_68e90255e0189.png', '', 7, 1),
(13, 'Games Xbox', 'images/categories/category_68e92d401aa4e.png', '', 8, 1),
(14, 'Games PS', 'images/categories/category_68e92d863a7b6.png', '', 9, 1),
(15, 'Games Linux', 'images/categories/category_68e92e16cbed5.png', '', 10, 1),
(16, 'Games Console', 'images/categories/category_68e92e6c75d70.png', '', 11, 1),
(17, 'Games Sport', 'images/categories/category_68e92f0ed58c0.png', '', 12, 1);

-- --------------------------------------------------------

--
-- Table structure for table `ddos_protection`
--

DROP TABLE IF EXISTS `ddos_protection`;
CREATE TABLE IF NOT EXISTS `ddos_protection` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip` (`ip`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

DROP TABLE IF EXISTS `email_logs`;
CREATE TABLE IF NOT EXISTS `email_logs` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sent_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `forums`
--

DROP TABLE IF EXISTS `forums`;
CREATE TABLE IF NOT EXISTS `forums` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_id` int UNSIGNED DEFAULT NULL,
  `order` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `topics_count` int UNSIGNED NOT NULL DEFAULT '0',
  `posts_count` int UNSIGNED NOT NULL DEFAULT '0',
  `last_post_id` int UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `last_post_id` (`last_post_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `forums`
--

INSERT INTO `forums` (`id`, `name`, `description`, `icon`, `parent_id`, `order`, `is_active`, `topics_count`, `posts_count`, `last_post_id`) VALUES
(7, 'General Forum', '', 'images/forums/forum_68f8aa249b7bd.png', NULL, 1, 1, 1, 1, 6),
(8, 'Movies', '', 'images/forums/forum_68f8b3223ee71.png', NULL, 2, 1, 0, 0, NULL),
(9, 'Games', '', 'images/forums/forum_68f8b37c40cdd.png', NULL, 3, 1, 0, 0, NULL),
(10, 'Music', '', 'images/forums/forum_68f8b3ed8dec5.png', NULL, 4, 1, 0, 0, NULL),
(11, 'Programs', '', 'images/forums/forum_68f8b43262b66.png', NULL, 5, 1, 0, 0, NULL),
(12, 'Suggestions and Complaints', '', 'images/forums/forum_68f8b4c0ec4c7.png', NULL, 6, 1, 0, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `forum_posts`
--

DROP TABLE IF EXISTS `forum_posts`;
CREATE TABLE IF NOT EXISTS `forum_posts` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `topic_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_edited` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `topic_id` (`topic_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `forum_posts`
--

INSERT INTO `forum_posts` (`id`, `topic_id`, `user_id`, `content`, `created_at`, `updated_at`, `is_edited`) VALUES
(6, 5, 1, '[b]TEST[/b]\r\n\r\n[b][color=red]This is only test[/color][/b]', '2025-10-22 12:57:25', '2025-10-22 12:57:25', 0);

-- --------------------------------------------------------

--
-- Table structure for table `forum_topics`
--

DROP TABLE IF EXISTS `forum_topics`;
CREATE TABLE IF NOT EXISTS `forum_topics` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `forum_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `views` int UNSIGNED NOT NULL DEFAULT '0',
  `replies` int UNSIGNED NOT NULL DEFAULT '0',
  `last_post_id` int UNSIGNED DEFAULT NULL,
  `is_locked` tinyint(1) NOT NULL DEFAULT '0',
  `is_sticky` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `forum_id` (`forum_id`),
  KEY `user_id` (`user_id`),
  KEY `last_post_id` (`last_post_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `forum_topics`
--

INSERT INTO `forum_topics` (`id`, `forum_id`, `user_id`, `title`, `created_at`, `updated_at`, `views`, `replies`, `last_post_id`, `is_locked`, `is_sticky`) VALUES
(5, 7, 1, 'Test', '2025-10-22 12:57:25', '2025-10-22 13:09:17', 2, 0, 6, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `imdb_cache`
--

DROP TABLE IF EXISTS `imdb_cache`;
CREATE TABLE IF NOT EXISTS `imdb_cache` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `imdb_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `imdb_id` (`imdb_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `type` enum('forum_reply','comment_reply','system') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `online_users`
--

DROP TABLE IF EXISTS `online_users`;
CREATE TABLE IF NOT EXISTS `online_users` (
  `user_id` int UNSIGNED NOT NULL,
  `last_activity` datetime NOT NULL,
  `is_bot` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `online_users`
--

INSERT INTO `online_users` (`user_id`, `last_activity`, `is_bot`) VALUES
(1, '2025-10-22 13:43:34', 0);

-- --------------------------------------------------------

--
-- Table structure for table `peers`
--

DROP TABLE IF EXISTS `peers`;
CREATE TABLE IF NOT EXISTS `peers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `torrent_id` int DEFAULT NULL,
  `info_hash` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `peer_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip` varchar(39) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `port` int NOT NULL,
  `seeder` tinyint(1) NOT NULL DEFAULT '0',
  `uploaded` bigint UNSIGNED NOT NULL DEFAULT '0',
  `downloaded` bigint UNSIGNED NOT NULL DEFAULT '0',
  `left` bigint UNSIGNED NOT NULL DEFAULT '0',
  `last_announce` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_peer` (`info_hash`,`peer_id`),
  KEY `idx_torrent_id` (`torrent_id`),
  KEY `idx_info_hash` (`info_hash`),
  KEY `idx_last_announce` (`last_announce`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `polls`
--

DROP TABLE IF EXISTS `polls`;
CREATE TABLE IF NOT EXISTS `polls` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `question` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `polls`
--

INSERT INTO `polls` (`id`, `question`, `description`, `is_active`, `created_at`, `created_by`) VALUES
(2, 'Харесвате ли сайта?', '', 1, '2025-10-20 21:58:18', 1);

-- --------------------------------------------------------

--
-- Table structure for table `poll_options`
--

DROP TABLE IF EXISTS `poll_options`;
CREATE TABLE IF NOT EXISTS `poll_options` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `poll_id` int UNSIGNED NOT NULL,
  `option_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `votes` int UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `poll_id` (`poll_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `poll_options`
--

INSERT INTO `poll_options` (`id`, `poll_id`, `option_text`, `votes`) VALUES
(9, 2, 'Да много', 1),
(10, 2, 'Бива', 0),
(11, 2, 'Може и по добре', 0),
(12, 2, 'Не ми харесва', 0);

-- --------------------------------------------------------

--
-- Table structure for table `poll_votes`
--

DROP TABLE IF EXISTS `poll_votes`;
CREATE TABLE IF NOT EXISTS `poll_votes` (
  `poll_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `option_id` int UNSIGNED NOT NULL,
  `voted_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`poll_id`,`user_id`),
  UNIQUE KEY `unique_vote` (`poll_id`,`user_id`),
  KEY `user_id` (`user_id`),
  KEY `option_id` (`option_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `poll_votes`
--

INSERT INTO `poll_votes` (`poll_id`, `user_id`, `option_id`, `voted_at`) VALUES
(2, 1, 9, '2025-10-20 21:58:26');

-- --------------------------------------------------------

--
-- Table structure for table `ranks_permissions`
--

DROP TABLE IF EXISTS `ranks_permissions`;
CREATE TABLE IF NOT EXISTS `ranks_permissions` (
  `rank_id` tinyint UNSIGNED NOT NULL,
  `permission_key` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT '0',
  `can_edit` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rank_id`,`permission_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ranks_permissions`
--

INSERT INTO `ranks_permissions` (`rank_id`, `permission_key`, `can_view`, `can_edit`) VALUES
(1, 'categories', 0, 0),
(1, 'news', 1, 0),
(1, 'reports', 0, 0),
(1, 'statistics', 0, 0),
(1, 'torrents', 1, 0),
(1, 'users', 0, 0),
(2, 'categories', 1, 0),
(2, 'news', 1, 0),
(2, 'reports', 1, 0),
(2, 'statistics', 1, 0),
(2, 'torrents', 1, 0),
(2, 'users', 1, 0),
(3, 'categories', 1, 0),
(3, 'news', 1, 0),
(3, 'reports', 1, 0),
(3, 'statistics', 1, 0),
(3, 'torrents', 1, 1),
(3, 'users', 1, 0),
(4, 'categories', 1, 0),
(4, 'news', 1, 0),
(4, 'reports', 1, 1),
(4, 'statistics', 1, 0),
(4, 'torrents', 1, 0),
(4, 'users', 1, 0),
(5, 'categories', 1, 1),
(5, 'news', 1, 1),
(5, 'reports', 1, 1),
(5, 'statistics', 1, 1),
(5, 'torrents', 1, 1),
(5, 'users', 1, 1),
(6, 'categories', 0, 0),
(6, 'news', 0, 0),
(6, 'reports', 0, 0),
(6, 'statistics', 0, 0),
(6, 'torrents', 0, 0),
(6, 'users', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `rate_limits`
--

DROP TABLE IF EXISTS `rate_limits`;
CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_action` (`ip`,`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `value` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `name`, `value`, `created_at`, `updated_at`) VALUES
(1, 'omdb_api_key', '81dabb21', '2025-10-10 07:04:43', '2025-10-10 07:29:26'),
(2, 'site_name', 'TorrentBG', '2025-10-10 09:32:13', '2025-10-10 09:37:13'),
(3, 'site_url', 'http://localhost', '2025-10-10 09:32:13', '2025-10-22 08:00:24'),
(4, 'tracker_announce', 'http://localhost:8080/announce', '2025-10-10 09:32:13', '2025-10-20 08:11:06'),
(5, 'site_email', 'crowni@mail.com', '2025-10-10 09:32:13', '2025-10-22 08:00:24'),
(6, 'tracker_mode', 'open', '2025-10-20 06:31:56', '2025-10-20 11:07:44'),
(7, 'default_lang', 'en', '2025-10-22 08:00:24', '2025-10-22 08:00:24');

-- --------------------------------------------------------

--
-- Table structure for table `shoutbox`
--

DROP TABLE IF EXISTS `shoutbox`;
CREATE TABLE IF NOT EXISTS `shoutbox` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shoutbox`
--

INSERT INTO `shoutbox` (`id`, `user_id`, `message`, `created_at`) VALUES
(38, 1, 'Hi all !Welcome to the TorrentBG  [smile=cool]', '2025-10-22 12:23:41');

-- --------------------------------------------------------

--
-- Table structure for table `torrents`
--

DROP TABLE IF EXISTS `torrents`;
CREATE TABLE IF NOT EXISTS `torrents` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `info_hash` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `poster` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `imdb_link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `youtube_link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category_id` int UNSIGNED NOT NULL DEFAULT '1',
  `uploader_id` int UNSIGNED NOT NULL,
  `size` bigint UNSIGNED NOT NULL,
  `files_count` int UNSIGNED NOT NULL DEFAULT '1',
  `seeders` int UNSIGNED NOT NULL DEFAULT '0',
  `leechers` int UNSIGNED NOT NULL DEFAULT '0',
  `completed` int UNSIGNED NOT NULL DEFAULT '0',
  `uploaded_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `info_hash` (`info_hash`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `torrents`
--

INSERT INTO `torrents` (`id`, `info_hash`, `name`, `description`, `poster`, `imdb_link`, `youtube_link`, `category_id`, `uploader_id`, `size`, `files_count`, `seeders`, `leechers`, `completed`, `uploaded_at`, `updated_at`) VALUES
(1, '46ce761ca4c53b59cd0907bb02705e07739cb084', 'The.Naked.Gun.2025.1080p.WEB.DDP5.1.Atmos.H.265-WAR', '## Режисьор : Акива Шейфър\r\n\r\n## В ролите : Лиъм Нийсън, Памела Андерсън, Пол Уолтър Хаузър, Кевин Дюранд, Дани Хюстън, Лайза Коши, СиСиЕйч Паундър, Еди Ю, Майкъл Бийзли, Моузес Джоунс\r\n\r\n## Държава : САЩ, Канада\r\n\r\n## Година : 2025\r\n\r\n## Времетраене : 85 минути\r\n\r\n## Резюме : Запознайте се с Франк Дребин-младши – сина на легендарния Франк Дребин, изигран с безстрашен артистичен апломб от иконичния Лиъм Нийсън. Дребин-младши уверено върви в стъпките на баща си – непоколебим в правотата на закона и тотално неадекватен в прилагането му, всекидневно доказващ, че най-опасното оръжие е един полицай с добро намерение и никакъв усет. Когато опасна престъпна конспирация заплашва града, той е принуден да се изправи срещу най-големия си кошмар. С таланта си да превръща рутинни разследвания в пълни катастрофи, Дребин-младши се впуска в рисковани преследвания, взривоопасни стълкновения и куп недоразумения. Ще успее ли да спаси положението, да изчисти името си и да не разруши половината град, докато го прави? Вероятно не. Но ще е забавно да го гледаме как се мъчи.', 'images/posters/68d0e4727f111.jpg', 'https://www.imdb.com/title/tt3402138/', 'https://youtu.be/uLguU7WLreA?si=gqAGHWLd5VAT6ii0', 6, 1, 3064004315, 3, 0, 0, 4, '2025-09-22 08:53:54', '2025-10-09 10:46:20'),
(2, '', 'Chief.of.War.S01E09.The.Black.Desert.1080p.WEB.DDP5.1.Atmos.H.265-WAR', '## Режисьор : Джейсън Момоа\r\n\r\n## В ролите : Джейсън Момоа, Лусиан Бюканан, Те Ао о Хинепехинга, Те Кохе Тухака, Брандън Фин, Сиуа Икалео, Майней Кинимака, Моузес Гудс, Джеймс Удом, Бенджамин Хойджес, Роймата Фокс, Чарли Бръмбли, Темуера Морисън, Клиф Къртис\r\n\r\n## IMDB : Линк към IMDB\r\n\r\n## TVMaze : Линк към TVMaze\r\n\r\n## Държава : САЩ\r\n\r\n## Година : 2025\r\n\r\n## Времетраене : 60 минути\r\n\r\n## Резюме : Сюжетът е вдъхновен от реални събития и се върти около легендарен хавайски воин, който иска да обедини различните местни племена на хавайските острови в края на XVIII в., когато пристигат европейските колонизатори и се опитват да ги поставят под собствен контрол. ', 'images/posters/68d128f7ca87b.jpg', 'https://www.imdb.com/title/tt19381692/', 'https://youtu.be/5qY0Zh61H3w?si=iAixMD7_6FfVYDZ3', 6, 1, 2141834780, 3, 0, 0, 0, '2025-09-22 13:46:15', '2025-09-22 13:46:15'),
(3, 'F', 'Prisoner.Of.War.2025.1080p.WEBRip.x265..AAC-YTS-TP', ' Prisoner of War (2025), Военнопленный [2025] // Военнопленник (2025)\r\n\r\nДържава: Филипини, САЩ\r\n\r\nГодина: 2025\r\n\r\nЖанр: екшън, трилър, военен, бойни изкуства\r\n\r\nРежисьори: Луис Мандилор\r\n\r\nВреметраене: 1 ч 52 мин 51 сек\r\n\r\nhttps://www.imdb.com/title/tt33057137/\r\n\r\n\r\nВ ролите: Скот Адкинс, Питър Шинкода, Майкъл Копон, Доналд Сероне, Майкъл Рене Уолтън, Гари Кернс II, Габи Гарсия, Масанори Мимото, Шейн Косуги, Ацуки Кашио, Кансуке Йокой, Пи Бернардо, Сол Еугенио, Коджи Хиронака, Андрей Касушкин и др.\r\n\r\n.:Резюме:.\r\n\r\n\r\nДжеймс Райт (Скот Адкинс), командир на британска ескадрила, е изпратен на последна мисия в подкрепа на съюзническите сили, сражаващи се с японците по време на ВСВ - битката при Батаан. Заловен, той попада в японски лагер за военнопленници. Там подполковник Ито открива, че Райт е бил традиционно обучен в Токио и многократно го принуждава да участва в битки с най-добрите му воини. Но уменията в боя, непоколебимата воля и решителност на Райт дават на другите затворници нова надежда за оцеляване.\r\n\r\n.:Субтитри:.\r\n\r\nанглийски - пълни, български - по-късно\r\n\r\n.:Aудио:.\r\n\r\nанглийски, филипински - оригинал', 'images/posters/68d4b1dc734b6.jpg', 'https://www.imdb.com/title/tt33057137/', 'https://youtu.be/mKFL8CMoCVk?si=O1FHkcspfA1s7w00', 6, 1, 2025838283, 15, 0, 0, 0, '2025-09-25 06:07:08', '2025-09-25 06:07:08'),
(4, '/<*', 'Afterburn / Изпепеляване (2025)', '## Режисьор : Джей Джей Пери\r\n\r\n## В ролите : Самюъл Л. Джаксън, Дейв Батиста, Олга Куриленко, Киристофър Хивю, Даниел Бернхард, Идън Епстейн, Джордж Сомнър, Фил Цимерман\r\n\r\n## Държава : САЩ\r\n\r\n## Година : 2025\r\n\r\n## Времетраене : 105 минути\r\n\r\n## Резюме : След като мощно слънчево изригване унищожава източното полукълбо на Земята, един смел търсач на съкровища се впуска в приключения из Европа, за да открие легендарната „Мона Лиза“. Скоро обаче става ясно, че светът се нуждае много повече от герой, отколкото от една картина...', 'images/posters/68e75de927349.jpg', 'https://www.imdb.com/title/tt1210027/', 'https://youtu.be/-44nQPX34D0?si=FpHGj0EgT3Y9DH3o', 6, 1, 6980073215, 4, 0, 0, 0, '2025-10-09 10:02:01', '2025-10-09 10:02:01'),
(5, 'c65e3d9a599b046e4e113e4eeb665aa528962dfd', 'The Lost Bus / Изгубеният автобус (2025)', '## Режисьор : Пол Грийнграс\r\n\r\n## В ролите : Матю Макконъхи, Америка Ферера, Юл Васкес, Ашли Аткинсън, Кимбърли Флорес, Ливай Макконъхи, Кей Макконъхи, Джон Месина, Кейт Уортън, Дани Маккарти, Спенсър Уотсън\r\n\r\n## Държава : САЩ\r\n\r\n## Година : 2025\r\n\r\n## Времетраене : 129 минути\r\n\r\n## Резюме : Режисьорът Пол Грийнграс пресъздава вдъхновено от реални събития и изпълнено с напрежение пътуване през един от най-смъртоносните горски пожари в Америка, в което своенравния шофьор на училищен автобус (Матю Макконъхи) и една всеотдайна учителка (Америка Ферера) се борят, за да спасят 22 деца от ужасяващия ад.', 'images/posters/68e76903c6b80.jpg', 'https://www.imdb.com/title/tt21103218/', 'https://youtu.be/XSDHjkuwaic?si=j5Mve6eo2J8QJh03', 7, 1, 4581963826, 3, 0, 0, 13, '2025-10-09 10:49:23', '2025-10-10 10:32:38'),
(6, '6e895cb85e001780a871783de19b3cef63c89a1c', 'Primitive War / Примитивна война (2025)', '\r\n## Резюме : Режисьорът Люк Спарк представя „ПРИМИТИВНА ВОЙНА“, базирана на романа „ПРИМИТИВНАТА ВОЙНА“ от Итън Петъс. Виетнам. 1968 г. Разузнавателна единица, известна като „Отряд лешояди“, е изпратена в изолирана долина в джунглата, за да разкрие съдбата на изчезнал взвод „Зелени барети“. Скоро те откриват, че не са сами. Екшън военен филм, който изправя закалените в битки войници срещу най-големите хищници, ходили някога по земята...', 'images/posters/68e8d755967c4.jpg', 'https://www.imdb.com/title/tt18312380', 'https://youtu.be/ixXxdWY6d68?si=c-WB7CiuYzWJhUFn', 7, 1, 4393980745, 3, 0, 0, 0, '2025-10-10 12:52:21', '2025-10-10 12:52:21'),
(7, 'fc2fa3d9d32d68ba46721a188205a38b3348df04', 'Bad Man (2025)', '## Резюме : В Колт Лейк, Тенеси, ченгето Сам Еванс (Джони Симънс) се опитва да се справи с разпространението на метамфетамин. Скоро пристига агента под прикритие, Боби Гейнс (Шон Уилям СКот) и въпреки подозренията към него е приветстван като герой. Еванс минава на заден план, съмненията около Гейнс обаче започват да стават притеснителни и цялата история заплашва да взриви привидното спокойствие в градчето.', 'images/posters/68f5cdef1bcbb.jpg', 'https://www.imdb.com/title/tt30057173/', 'https://youtu.be/YANpXDvWF6I?si=pm6UbhYTnqNpQSq-', 7, 1, 1747751589, 1, 0, 0, 1, '2025-10-20 08:51:43', '2025-10-20 14:35:34'),
(8, 'eec77bb7e411efced37495f65676222b184369ba', 'Mission: Impossible - The Final Reckoning / Мисията невъзможна: Възмездиe (2025)', '## Резюме : Животът ни е сумата от нашите избори! Супершпионинът Итън Хънт влиза в решителна схватка с енигматичния изкуствен интелект Същността, а залогът е самото бъдеще! Всеки избор, всяка мисия – всичко води дотук!След съприкосновението му със Същността, Итън Хънт е принуден да мине в отстъпление и да преосмисли действията си. Той вече плати твърде висока цена за своите решения до момента, а бъдещето се очертава дори още по-мрачно и пълната му капитулация звучи като единственото логично нещо. Но колкото и разумно да изглежда на пръв поглед, оттеглянето му ще доведе до много по-тежки последици за света и Итън ще трябва да жертва всичко, дори собствения си живот, за да надделее в тази битка.', 'images/posters/68f5def1278e3.jpg', 'https://www.imdb.com/title/tt9603208/', 'https://youtu.be/fsQgc9pCyDU?si=8MPXbo_XTh_aVnnG', 7, 1, 41370114277, 8, 0, 0, 2, '2025-10-20 10:04:17', '2025-10-20 14:36:24'),
(9, 'f6c23755e2c17424dad1c3d0a7374f8a991953e3', 'Look Away / Тъмно огледало', 'Look Away / Тъмно огледало\r\n\r\n[color=blue]Държава: [/color]САЩ, Канада\r\nГодина: 2018\r\nЖанр: Ужаси, Трилър, Фентъзи, Драма\r\nРежисьор: Асаф Бернщайн\r\nВреметраене: 99 минути\r\nВ ролите: Мира Сорвино, Джейсън Айзъкс, Индиа Айсли, Харисън Гилбертсън, Пенелопи Митчъл, Адам Хъртиг, Кристън Харис, Конър Петърсън и др.\r\n\r\n.:Резюме:.\r\n18-годишната гимназистка Мария не е любимка нито на родителите си, нито на връстниците си. Като една истинска бъдеща бездомница, тя доверява всичките си тайни и преживявания на отражението си в огледалото, злобна близначка, която поддържа, поощрява и крие всичките и тайни. Веднъж тя сменя мястото си със своята двойничка от огледалото, а тя започва да сбъдва най-страшните и необуздани нейни желания. ', 'images/posters/68f5f7f000923.jpg', '', 'https://youtu.be/wuLJHROphdM?si=oVMq5DqJMKPEOwiL', 7, 1, 2773544225, 1, 0, 0, 0, '2025-10-20 11:50:56', '2025-10-20 11:50:56'),
(10, '236adeb77729cc14781288602b767b24f3aebada', 'Hunter Hunter / Ловeцът и хищникът (2020)', '[color=red]##[/color] [color=blue]Резюме :[/color] Джоузеф живее с жена си и дъщеря си в отдалечена хижа дълбоко в канадската пустош. Семейството се издържа като продава кожите на уловените от тях диви животни. Напълно откъснати от обществото, сами сред природата, през деня бащата учи дъщеря си как се поставят капани и разчитат следи от животни, а вечер майката се занимава с образованието на детето, което се състои в четене на книга. Един ден спокойствието им е нарушено от появата на огромен вълк, който разкъсва уловените в капаните им животни и застрашава дори собствената им сигурност. Джоузеф тръгва на лов за да убие натрапника…', 'images/posters/68f60002c735f.jpg', 'https://www.imdb.com/title/tt2226162/', 'https://youtu.be/lB-7oVKUBQk?si=O3RD8oc8Eycq4HOK', 7, 1, 3277923094, 3, 0, 0, 3, '2025-10-20 12:25:22', '2025-10-20 14:32:20'),
(12, '31d05ee186ddbef27b583a33b76ba048a8f51d22', 'The Roses / Войната на семейство Роуз (2025)', '[color=red]##[/color] [color=blue]Резюме :[/color] Животът изглежда като песен за перфектната двойка Айви и Тео Роуз: успешни кариери, хармоничен брак, страхотни деца. Но под привидната фасада на идеалния им живот назрява буря – докато кариерата на Тео се срива, собствените амбиции на Айви набират все по-голяма скорост и между двамата пламва неукротима буря от ожесточено съревнование и дълго потискано негодувание. Скоро нещата излизат извън контрол и изпепеляващата им омраза един към друг заплашва да погълне всичко.', 'images/posters/68f8a433b0d05.jpg', 'https://www.imdb.com/title/tt31973693/', 'https://youtu.be/XkgMaS5gbaA?si=crnxB7H3xmUwMVfw', 7, 1, 3777052663, 3, 0, 0, 0, '2025-10-22 12:30:27', '2025-10-22 12:30:27');

-- --------------------------------------------------------

--
-- Table structure for table `torrent_comments`
--

DROP TABLE IF EXISTS `torrent_comments`;
CREATE TABLE IF NOT EXISTS `torrent_comments` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `torrent_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_edited` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `torrent_id` (`torrent_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `torrent_comments`
--

INSERT INTO `torrent_comments` (`id`, `torrent_id`, `user_id`, `comment`, `created_at`, `updated_at`, `is_edited`) VALUES
(1, 1, 1, 'Супер', '2025-09-22 09:08:20', '2025-09-22 09:08:20', 0),
(3, 5, 1, 'Филма бива', '2025-10-09 21:06:44', '2025-10-09 21:06:44', 0),
(4, 5, 1, 'Супер', '2025-10-09 21:26:43', '2025-10-09 21:26:43', 0),
(5, 6, 1, 'Интересен филм заслужава си !', '2025-10-11 08:27:12', '2025-10-11 08:27:12', 0),
(6, 6, 1, '[b]Тест[/b]', '2025-10-11 08:30:03', '2025-10-11 08:30:03', 0),
(7, 8, 1, 'Бива', '2025-10-20 10:44:33', '2025-10-20 10:44:33', 0),
(8, 10, 1, 'бива', '2025-10-20 12:40:28', '2025-10-20 12:40:28', 0);

-- --------------------------------------------------------

--
-- Table structure for table `torrent_ratings`
--

DROP TABLE IF EXISTS `torrent_ratings`;
CREATE TABLE IF NOT EXISTS `torrent_ratings` (
  `torrent_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `rating` tinyint UNSIGNED NOT NULL,
  `rated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`torrent_id`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `torrent_ratings`
--

INSERT INTO `torrent_ratings` (`torrent_id`, `user_id`, `rating`, `rated_at`) VALUES
(1, 1, 5, '2025-09-22 09:08:49'),
(2, 1, 5, '2025-09-23 11:02:15'),
(3, 1, 5, '2025-09-25 06:07:29'),
(4, 1, 5, '2025-10-09 10:02:13'),
(5, 1, 5, '2025-10-09 12:29:40'),
(6, 1, 5, '2025-10-10 12:52:38'),
(7, 1, 5, '2025-10-20 08:52:15'),
(8, 1, 5, '2025-10-20 10:42:34'),
(11, 1, 5, '2025-10-20 15:39:32'),
(12, 1, 5, '2025-10-22 12:30:46');

-- --------------------------------------------------------

--
-- Table structure for table `translations`
--

DROP TABLE IF EXISTS `translations`;
CREATE TABLE IF NOT EXISTS `translations` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `language` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `translation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `status` enum('pending','approved','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `approved_by` int UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_key_lang` (`key`,`language`),
  KEY `user_id` (`user_id`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_language` (`language`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `rank` tinyint UNSIGNED NOT NULL DEFAULT '2' COMMENT '1=Guest,2=User,3=Uploader,4=Validator,5=Moderator,6=Owner',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL,
  `language` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'en',
  `style` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'light',
  `unread_notifications` int UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `rank`, `created_at`, `last_login`, `language`, `style`, `unread_notifications`) VALUES
(1, 'crowni', 'crowni@mail.bg', '$argon2id$v=19$m=65536,t=4,p=1$aENGOTlIdDROQ2xHYXpZcQ$Z12RPeaYTBGjl81QrrO5359pyTmR3a/WYbTd4k5v5DU', 6, '2025-09-19 11:31:54', '2025-10-22 12:52:21', 'bg', 'light', 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_settings`
--

DROP TABLE IF EXISTS `user_settings`;
CREATE TABLE IF NOT EXISTS `user_settings` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `receive_emails` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_type` (`user_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `youtube_cache`
--

DROP TABLE IF EXISTS `youtube_cache`;
CREATE TABLE IF NOT EXISTS `youtube_cache` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `video_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `video_id` (`video_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `torrents`
--
ALTER TABLE `torrents` ADD FULLTEXT KEY `ft_search` (`name`,`description`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD CONSTRAINT `email_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `forums`
--
ALTER TABLE `forums`
  ADD CONSTRAINT `forums_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `forums` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `forums_ibfk_2` FOREIGN KEY (`last_post_id`) REFERENCES `forum_posts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `forum_posts`
--
ALTER TABLE `forum_posts`
  ADD CONSTRAINT `forum_posts_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `forum_topics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `forum_posts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `forum_topics`
--
ALTER TABLE `forum_topics`
  ADD CONSTRAINT `forum_topics_ibfk_1` FOREIGN KEY (`forum_id`) REFERENCES `forums` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `forum_topics_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `forum_topics_ibfk_3` FOREIGN KEY (`last_post_id`) REFERENCES `forum_posts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `online_users`
--
ALTER TABLE `online_users`
  ADD CONSTRAINT `online_users_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `polls`
--
ALTER TABLE `polls`
  ADD CONSTRAINT `polls_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `poll_options`
--
ALTER TABLE `poll_options`
  ADD CONSTRAINT `poll_options_ibfk_1` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `poll_votes`
--
ALTER TABLE `poll_votes`
  ADD CONSTRAINT `poll_votes_ibfk_1` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `poll_votes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `poll_votes_ibfk_3` FOREIGN KEY (`option_id`) REFERENCES `poll_options` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shoutbox`
--
ALTER TABLE `shoutbox`
  ADD CONSTRAINT `shoutbox_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `torrent_comments`
--
ALTER TABLE `torrent_comments`
  ADD CONSTRAINT `torrent_comments_ibfk_1` FOREIGN KEY (`torrent_id`) REFERENCES `torrents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `torrent_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `translations`
--
ALTER TABLE `translations`
  ADD CONSTRAINT `translations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `translations_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
