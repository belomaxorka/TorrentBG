-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 10, 2025 at 06:06 AM
-- Server version: 9.1.0
-- PHP Version: 8.4.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `markoni`
--

-- --------------------------------------------------------

--
-- Table structure for table `blocked_ips`
--

DROP TABLE IF EXISTS `blocked_ips`;
CREATE TABLE IF NOT EXISTS `blocked_ips` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` enum('left','center','right') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'center',
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
(21, 'Анкета', 'poll', 'right', 2, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `icon`, `description`, `order`, `is_active`) VALUES
(6, 'Movies Xvid', 'images/categories/category_68cd832480b94.png', 'Only Xvid', 1, 1),
(7, 'Movies/HD', 'images/categories/category_68e760668ceaf.png', '', 2, 1);

-- --------------------------------------------------------

--
-- Table structure for table `ddos_protection`
--

DROP TABLE IF EXISTS `ddos_protection`;
CREATE TABLE IF NOT EXISTS `ddos_protection` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_id` int UNSIGNED DEFAULT NULL,
  `order` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `topics_count` int UNSIGNED NOT NULL DEFAULT '0',
  `posts_count` int UNSIGNED NOT NULL DEFAULT '0',
  `last_post_id` int UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `last_post_id` (`last_post_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `forums`
--

INSERT INTO `forums` (`id`, `name`, `description`, `icon`, `parent_id`, `order`, `is_active`, `topics_count`, `posts_count`, `last_post_id`) VALUES
(1, 'General Forum', '', NULL, NULL, 0, 1, 1, 2, 2),
(2, 'Movies', 'Only for movies', NULL, NULL, 1, 1, 0, 0, NULL),
(3, 'Games', 'Only for games', NULL, NULL, 2, 1, 0, 0, NULL),
(4, 'Music', 'Only for Music', NULL, NULL, 3, 1, 0, 0, NULL),
(5, 'Programs', 'Only for Programs', NULL, NULL, 4, 1, 0, 0, NULL),
(6, 'Suggestions and complaints', '', NULL, 1, 2, 1, 0, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `forum_posts`
--

DROP TABLE IF EXISTS `forum_posts`;
CREATE TABLE IF NOT EXISTS `forum_posts` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `topic_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_edited` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `topic_id` (`topic_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `forum_posts`
--

INSERT INTO `forum_posts` (`id`, `topic_id`, `user_id`, `content`, `created_at`, `updated_at`, `is_edited`) VALUES
(1, 1, 1, 'Test', '2025-09-19 16:10:08', '2025-09-19 16:10:08', 0),
(2, 1, 1, 'Добьр тест', '2025-09-19 16:23:34', '2025-09-19 16:23:34', 0);

-- --------------------------------------------------------

--
-- Table structure for table `forum_topics`
--

DROP TABLE IF EXISTS `forum_topics`;
CREATE TABLE IF NOT EXISTS `forum_topics` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `forum_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `forum_topics`
--

INSERT INTO `forum_topics` (`id`, `forum_id`, `user_id`, `title`, `created_at`, `updated_at`, `views`, `replies`, `last_post_id`, `is_locked`, `is_sticky`) VALUES
(1, 1, 1, 'Test', '2025-09-19 16:10:08', '2025-09-25 05:58:55', 8, 1, 2, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `imdb_cache`
--

DROP TABLE IF EXISTS `imdb_cache`;
CREATE TABLE IF NOT EXISTS `imdb_cache` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `imdb_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `type` enum('forum_reply','comment_reply','system') COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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
(1, '2025-10-09 23:16:59', 0);

-- --------------------------------------------------------

--
-- Table structure for table `peers`
--

DROP TABLE IF EXISTS `peers`;
CREATE TABLE IF NOT EXISTS `peers` (
  `torrent_id` int UNSIGNED NOT NULL,
  `peer_id` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `port` int UNSIGNED NOT NULL,
  `seeder` tinyint(1) NOT NULL DEFAULT '0',
  `uploaded` bigint UNSIGNED NOT NULL DEFAULT '0',
  `downloaded` bigint UNSIGNED NOT NULL DEFAULT '0',
  `left` bigint UNSIGNED NOT NULL DEFAULT '0',
  `event` enum('started','completed','stopped','empty') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'empty',
  `user_id` int UNSIGNED NOT NULL,
  `last_announce` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_seeder` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`torrent_id`,`peer_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_last_announce` (`last_announce`),
  KEY `idx_is_seeder` (`is_seeder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `polls`
--

DROP TABLE IF EXISTS `polls`;
CREATE TABLE IF NOT EXISTS `polls` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `question` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `polls`
--

INSERT INTO `polls` (`id`, `question`, `description`, `is_active`, `created_at`, `created_by`) VALUES
(1, 'Харесвате ли саита', '', 1, '2025-09-19 20:36:06', 1);

-- --------------------------------------------------------

--
-- Table structure for table `poll_options`
--

DROP TABLE IF EXISTS `poll_options`;
CREATE TABLE IF NOT EXISTS `poll_options` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `poll_id` int UNSIGNED NOT NULL,
  `option_text` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `votes` int UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `poll_id` (`poll_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `poll_options`
--

INSERT INTO `poll_options` (`id`, `poll_id`, `option_text`, `votes`) VALUES
(1, 1, 'Да много', 0),
(2, 1, 'Още може', 0),
(3, 1, 'Бива', 0),
(4, 1, 'Не ми харесва', 0);

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
  KEY `user_id` (`user_id`),
  KEY `option_id` (`option_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ranks_permissions`
--

DROP TABLE IF EXISTS `ranks_permissions`;
CREATE TABLE IF NOT EXISTS `ranks_permissions` (
  `rank_id` tinyint UNSIGNED NOT NULL,
  `permission_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_action` (`ip`,`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shoutbox`
--

DROP TABLE IF EXISTS `shoutbox`;
CREATE TABLE IF NOT EXISTS `shoutbox` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shoutbox`
--

INSERT INTO `shoutbox` (`id`, `user_id`, `message`, `created_at`) VALUES
(31, 1, 'Привет', '2025-10-09 20:40:17'),
(32, 1, '[smile=wink]', '2025-10-09 20:40:31');

-- --------------------------------------------------------

--
-- Table structure for table `torrents`
--

DROP TABLE IF EXISTS `torrents`;
CREATE TABLE IF NOT EXISTS `torrents` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `info_hash` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `poster` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `imdb_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `youtube_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `torrents`
--

INSERT INTO `torrents` (`id`, `info_hash`, `name`, `description`, `poster`, `imdb_link`, `youtube_link`, `category_id`, `uploader_id`, `size`, `files_count`, `seeders`, `leechers`, `completed`, `uploaded_at`, `updated_at`) VALUES
(1, '46ce761ca4c53b59cd0907bb02705e07739cb084', 'The.Naked.Gun.2025.1080p.WEB.DDP5.1.Atmos.H.265-WAR', '## Режисьор : Акива Шейфър\r\n\r\n## В ролите : Лиъм Нийсън, Памела Андерсън, Пол Уолтър Хаузър, Кевин Дюранд, Дани Хюстън, Лайза Коши, СиСиЕйч Паундър, Еди Ю, Майкъл Бийзли, Моузес Джоунс\r\n\r\n## Държава : САЩ, Канада\r\n\r\n## Година : 2025\r\n\r\n## Времетраене : 85 минути\r\n\r\n## Резюме : Запознайте се с Франк Дребин-младши – сина на легендарния Франк Дребин, изигран с безстрашен артистичен апломб от иконичния Лиъм Нийсън. Дребин-младши уверено върви в стъпките на баща си – непоколебим в правотата на закона и тотално неадекватен в прилагането му, всекидневно доказващ, че най-опасното оръжие е един полицай с добро намерение и никакъв усет. Когато опасна престъпна конспирация заплашва града, той е принуден да се изправи срещу най-големия си кошмар. С таланта си да превръща рутинни разследвания в пълни катастрофи, Дребин-младши се впуска в рисковани преследвания, взривоопасни стълкновения и куп недоразумения. Ще успее ли да спаси положението, да изчисти името си и да не разруши половината град, докато го прави? Вероятно не. Но ще е забавно да го гледаме как се мъчи.', 'images/posters/68d0e4727f111.jpg', 'https://www.imdb.com/title/tt3402138/', 'https://youtu.be/uLguU7WLreA?si=gqAGHWLd5VAT6ii0', 6, 1, 3064004315, 3, 0, 0, 4, '2025-09-22 08:53:54', '2025-10-09 10:46:20'),
(2, '', 'Chief.of.War.S01E09.The.Black.Desert.1080p.WEB.DDP5.1.Atmos.H.265-WAR', '## Режисьор : Джейсън Момоа\r\n\r\n## В ролите : Джейсън Момоа, Лусиан Бюканан, Те Ао о Хинепехинга, Те Кохе Тухака, Брандън Фин, Сиуа Икалео, Майней Кинимака, Моузес Гудс, Джеймс Удом, Бенджамин Хойджес, Роймата Фокс, Чарли Бръмбли, Темуера Морисън, Клиф Къртис\r\n\r\n## IMDB : Линк към IMDB\r\n\r\n## TVMaze : Линк към TVMaze\r\n\r\n## Държава : САЩ\r\n\r\n## Година : 2025\r\n\r\n## Времетраене : 60 минути\r\n\r\n## Резюме : Сюжетът е вдъхновен от реални събития и се върти около легендарен хавайски воин, който иска да обедини различните местни племена на хавайските острови в края на XVIII в., когато пристигат европейските колонизатори и се опитват да ги поставят под собствен контрол. ', 'images/posters/68d128f7ca87b.jpg', 'https://www.imdb.com/title/tt19381692/', 'https://youtu.be/5qY0Zh61H3w?si=iAixMD7_6FfVYDZ3', 6, 1, 2141834780, 3, 0, 0, 0, '2025-09-22 13:46:15', '2025-09-22 13:46:15'),
(3, 'F', 'Prisoner.Of.War.2025.1080p.WEBRip.x265..AAC-YTS-TP', ' Prisoner of War (2025), Военнопленный [2025] // Военнопленник (2025)\r\n\r\nДържава: Филипини, САЩ\r\n\r\nГодина: 2025\r\n\r\nЖанр: екшън, трилър, военен, бойни изкуства\r\n\r\nРежисьори: Луис Мандилор\r\n\r\nВреметраене: 1 ч 52 мин 51 сек\r\n\r\nhttps://www.imdb.com/title/tt33057137/\r\n\r\n\r\nВ ролите: Скот Адкинс, Питър Шинкода, Майкъл Копон, Доналд Сероне, Майкъл Рене Уолтън, Гари Кернс II, Габи Гарсия, Масанори Мимото, Шейн Косуги, Ацуки Кашио, Кансуке Йокой, Пи Бернардо, Сол Еугенио, Коджи Хиронака, Андрей Касушкин и др.\r\n\r\n.:Резюме:.\r\n\r\n\r\nДжеймс Райт (Скот Адкинс), командир на британска ескадрила, е изпратен на последна мисия в подкрепа на съюзническите сили, сражаващи се с японците по време на ВСВ - битката при Батаан. Заловен, той попада в японски лагер за военнопленници. Там подполковник Ито открива, че Райт е бил традиционно обучен в Токио и многократно го принуждава да участва в битки с най-добрите му воини. Но уменията в боя, непоколебимата воля и решителност на Райт дават на другите затворници нова надежда за оцеляване.\r\n\r\n.:Субтитри:.\r\n\r\nанглийски - пълни, български - по-късно\r\n\r\n.:Aудио:.\r\n\r\nанглийски, филипински - оригинал', 'images/posters/68d4b1dc734b6.jpg', 'https://www.imdb.com/title/tt33057137/', 'https://youtu.be/mKFL8CMoCVk?si=O1FHkcspfA1s7w00', 6, 1, 2025838283, 15, 0, 0, 0, '2025-09-25 06:07:08', '2025-09-25 06:07:08'),
(4, '/<*', 'Afterburn / Изпепеляване (2025)', '## Режисьор : Джей Джей Пери\r\n\r\n## В ролите : Самюъл Л. Джаксън, Дейв Батиста, Олга Куриленко, Киристофър Хивю, Даниел Бернхард, Идън Епстейн, Джордж Сомнър, Фил Цимерман\r\n\r\n## Държава : САЩ\r\n\r\n## Година : 2025\r\n\r\n## Времетраене : 105 минути\r\n\r\n## Резюме : След като мощно слънчево изригване унищожава източното полукълбо на Земята, един смел търсач на съкровища се впуска в приключения из Европа, за да открие легендарната „Мона Лиза“. Скоро обаче става ясно, че светът се нуждае много повече от герой, отколкото от една картина...', 'images/posters/68e75de927349.jpg', 'https://www.imdb.com/title/tt1210027/', 'https://youtu.be/-44nQPX34D0?si=FpHGj0EgT3Y9DH3o', 6, 1, 6980073215, 4, 0, 0, 0, '2025-10-09 10:02:01', '2025-10-09 10:02:01'),
(5, 'c65e3d9a599b046e4e113e4eeb665aa528962dfd', 'The Lost Bus / Изгубеният автобус (2025)', '## Режисьор : Пол Грийнграс\r\n\r\n## В ролите : Матю Макконъхи, Америка Ферера, Юл Васкес, Ашли Аткинсън, Кимбърли Флорес, Ливай Макконъхи, Кей Макконъхи, Джон Месина, Кейт Уортън, Дани Маккарти, Спенсър Уотсън\r\n\r\n## Държава : САЩ\r\n\r\n## Година : 2025\r\n\r\n## Времетраене : 129 минути\r\n\r\n## Резюме : Режисьорът Пол Грийнграс пресъздава вдъхновено от реални събития и изпълнено с напрежение пътуване през един от най-смъртоносните горски пожари в Америка, в което своенравния шофьор на училищен автобус (Матю Макконъхи) и една всеотдайна учителка (Америка Ферера) се борят, за да спасят 22 деца от ужасяващия ад.', 'images/posters/68e76903c6b80.jpg', 'https://www.imdb.com/title/tt21103218/', 'https://youtu.be/XSDHjkuwaic?si=j5Mve6eo2J8QJh03', 7, 1, 4581963826, 3, 0, 0, 12, '2025-10-09 10:49:23', '2025-10-09 19:51:17');

-- --------------------------------------------------------

--
-- Table structure for table `torrent_comments`
--

DROP TABLE IF EXISTS `torrent_comments`;
CREATE TABLE IF NOT EXISTS `torrent_comments` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `torrent_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_edited` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `torrent_id` (`torrent_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `torrent_comments`
--

INSERT INTO `torrent_comments` (`id`, `torrent_id`, `user_id`, `comment`, `created_at`, `updated_at`, `is_edited`) VALUES
(1, 1, 1, 'Супер', '2025-09-22 09:08:20', '2025-09-22 09:08:20', 0),
(3, 5, 1, 'Филма бива', '2025-10-09 21:06:44', '2025-10-09 21:06:44', 0),
(4, 5, 1, 'Супер', '2025-10-09 21:26:43', '2025-10-09 21:26:43', 0);

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
) ;

--
-- Dumping data for table `torrent_ratings`
--

INSERT INTO `torrent_ratings` (`torrent_id`, `user_id`, `rating`, `rated_at`) VALUES
(1, 1, 5, '2025-09-22 09:08:49'),
(2, 1, 5, '2025-09-23 11:02:15'),
(3, 1, 5, '2025-09-25 06:07:29'),
(4, 1, 5, '2025-10-09 10:02:13'),
(5, 1, 5, '2025-10-09 12:29:40');

-- --------------------------------------------------------

--
-- Table structure for table `translations`
--

DROP TABLE IF EXISTS `translations`;
CREATE TABLE IF NOT EXISTS `translations` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `language` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `translation` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
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
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rank` tinyint UNSIGNED NOT NULL DEFAULT '2' COMMENT '1=Guest,2=User,3=Uploader,4=Validator,5=Moderator,6=Owner',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL,
  `language` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT 'en',
  `style` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'light',
  `unread_notifications` int UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `rank`, `created_at`, `last_login`, `language`, `style`, `unread_notifications`) VALUES
(1, 'crowni', 'crowni@mail.bg', '$argon2id$v=19$m=65536,t=4,p=1$aENGOTlIdDROQ2xHYXpZcQ$Z12RPeaYTBGjl81QrrO5359pyTmR3a/WYbTd4k5v5DU', 6, '2025-09-19 11:31:54', '2025-10-09 13:50:06', 'bg', 'light', 0),
(2, 'kosmos', 'kosmos@abv.bg', '$2y$12$DvC/G2gp8XK/u2YlJJAP0OjMFlKEvFJA853NQ0OXiMLHcsiM.Vw2S', 2, '2025-10-09 10:18:41', '2025-10-09 13:20:19', 'bg', 'light', 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_settings`
--

DROP TABLE IF EXISTS `user_settings`;
CREATE TABLE IF NOT EXISTS `user_settings` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `video_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
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
-- Constraints for table `peers`
--
ALTER TABLE `peers`
  ADD CONSTRAINT `peers_ibfk_1` FOREIGN KEY (`torrent_id`) REFERENCES `torrents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `peers_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `torrent_ratings`
--
ALTER TABLE `torrent_ratings`
  ADD CONSTRAINT `torrent_ratings_ibfk_1` FOREIGN KEY (`torrent_id`) REFERENCES `torrents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `torrent_ratings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
