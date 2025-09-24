-- =============================================
-- TORRENT TRACKER TORRENTBG — ПЪЛНА СХЕМА (PHP 8.4 + UTF-8)
-- =============================================

-- =============================================
-- ПАКЕТ #1-2: ОСНОВНИ ТАБЛИЦИ
-- =============================================

-- Потребители
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `rank` TINYINT UNSIGNED NOT NULL DEFAULT 2 COMMENT '1=Guest,2=User,3=Uploader,4=Validator,5=Moderator,6=Owner',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `last_login` DATETIME NULL,
  `language` VARCHAR(5) DEFAULT 'en',
  `style` VARCHAR(10) DEFAULT 'light',
  `unread_notifications` INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Торенти
CREATE TABLE `torrents` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `info_hash` VARCHAR(40) NOT NULL UNIQUE,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `poster` VARCHAR(255) NULL,
  `imdb_link` VARCHAR(255) NULL,
  `youtube_link` VARCHAR(255) NULL,
  `category_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `uploader_id` INT UNSIGNED NOT NULL,
  `size` BIGINT UNSIGNED NOT NULL,
  `files_count` INT UNSIGNED NOT NULL DEFAULT 1,
  `seeders` INT UNSIGNED NOT NULL DEFAULT 0,
  `leechers` INT UNSIGNED NOT NULL DEFAULT 0,
  `completed` INT UNSIGNED NOT NULL DEFAULT 0,
  `uploaded_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FULLTEXT KEY `ft_search` (`name`, `description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ПАКЕТ #3: SHOUTBOX + КОМЕНТАРИ
-- =============================================

-- Shoutbox
CREATE TABLE `shoutbox` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Коментари към торенти
CREATE TABLE `torrent_comments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `torrent_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `comment` TEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_edited` BOOLEAN NOT NULL DEFAULT FALSE,
  FOREIGN KEY (`torrent_id`) REFERENCES `torrents`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ПАКЕТ #4: БЛОКОВЕ + КАТЕГОРИИ + ОНЛАЙН ПОТРЕБИТЕЛИ
-- =============================================

-- Блокове
CREATE TABLE `blocks` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `title` VARCHAR(100) NOT NULL,
  `position` ENUM('left', 'center', 'right') NOT NULL DEFAULT 'center',
  `order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `is_locked` BOOLEAN NOT NULL DEFAULT FALSE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Вмъкваме началните блокове
INSERT INTO `blocks` (`name`, `title`, `position`, `order`, `is_active`, `is_locked`) VALUES
('latest_torrents', 'Последни торенти', 'center', 1, 1, 1),
('shoutbox', 'Шутбокс', 'center', 2, 1, 1),
('user_info', 'Потребителска информация', 'left', 1, 1, 1),
('online_users', 'Онлайн потребители', 'right', 2, 1, 1),
('clock', 'Часовник', 'right', 1, 1, 0);

-- Категории
CREATE TABLE `categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  `icon` VARCHAR(255) NULL,
  `description` VARCHAR(255) NULL,
  `order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Начални категории
INSERT INTO `categories` (`name`, `icon`, `description`, `order`) VALUES
('Филми', 'images/categories/film.png', 'Филми в HD и 4K', 1),
('Сериали', 'images/categories/series.png', 'Сериали с български субтитри', 2),
('Музика', 'images/categories/music.png', 'Албуми, песни, FLAC', 3),
('Игри', 'images/categories/games.png', 'PC и конзолни игри', 4),
('Софтуер', 'images/categories/software.png', 'Програми и приложения', 5);

-- Онлайн потребители
CREATE TABLE `online_users` (
  `user_id` INT UNSIGNED NOT NULL,
  `last_activity` DATETIME NOT NULL,
  `is_bot` BOOLEAN NOT NULL DEFAULT FALSE,
  PRIMARY KEY (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ПАКЕТ #5: АНКЕТИ + РЕЙТИНГИ
-- =============================================

-- Анкети
CREATE TABLE `polls` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `question` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NOT NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Опции на анкетите
CREATE TABLE `poll_options` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `poll_id` INT UNSIGNED NOT NULL,
  `option_text` VARCHAR(255) NOT NULL,
  `votes` INT UNSIGNED NOT NULL DEFAULT 0,
  FOREIGN KEY (`poll_id`) REFERENCES `polls`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Гласове в анкети
CREATE TABLE `poll_votes` (
  `poll_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `option_id` INT UNSIGNED NOT NULL,
  `voted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`poll_id`, `user_id`),
  FOREIGN KEY (`poll_id`) REFERENCES `polls`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`option_id`) REFERENCES `poll_options`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Рейтинги на торенти
CREATE TABLE `torrent_ratings` (
  `torrent_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `rating` TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
  `rated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`torrent_id`, `user_id`),
  FOREIGN KEY (`torrent_id`) REFERENCES `torrents`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ПАКЕТ #6: ФОРУМ + ИЗВЕСТИЯ
-- =============================================

-- Форуми
CREATE TABLE `forums` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `icon` VARCHAR(255) NULL,
  `parent_id` INT UNSIGNED NULL DEFAULT NULL,
  `order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `topics_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `posts_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_post_id` INT UNSIGNED NULL,
  FOREIGN KEY (`parent_id`) REFERENCES `forums`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Теми
CREATE TABLE `forum_topics` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `forum_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `views` INT UNSIGNED NOT NULL DEFAULT 0,
  `replies` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_post_id` INT UNSIGNED NULL,
  `is_locked` BOOLEAN NOT NULL DEFAULT FALSE,
  `is_sticky` BOOLEAN NOT NULL DEFAULT FALSE,
  FOREIGN KEY (`forum_id`) REFERENCES `forums`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Мнения
CREATE TABLE `forum_posts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `topic_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `content` TEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_edited` BOOLEAN NOT NULL DEFAULT FALSE,
  FOREIGN KEY (`topic_id`) REFERENCES `forum_topics`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Известия
CREATE TABLE `notifications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `type` ENUM('forum_reply', 'comment_reply', 'system') NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `url` VARCHAR(255) NOT NULL,
  `is_read` BOOLEAN NOT NULL DEFAULT FALSE,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ПАКЕТ #7: ТРАКЕР (PEERS)
-- =============================================

-- Пиъри (сидъри и лийчъри)
CREATE TABLE `peers` (
  `torrent_id` INT UNSIGNED NOT NULL,
  `peer_id` VARCHAR(40) NOT NULL,
  `ip` VARCHAR(45) NOT NULL,
  `port` INT UNSIGNED NOT NULL,
  `uploaded` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `downloaded` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `left` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `event` ENUM('started', 'completed', 'stopped', 'empty') NOT NULL DEFAULT 'empty',
  `user_id` INT UNSIGNED NOT NULL,
  `last_announce` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_seeder` BOOLEAN NOT NULL DEFAULT FALSE,
  PRIMARY KEY (`torrent_id`, `peer_id`),
  FOREIGN KEY (`torrent_id`) REFERENCES `torrents`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_last_announce` (`last_announce`),
  INDEX `idx_is_seeder` (`is_seeder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ПАКЕТ #8: СИГУРНОСТ + API + CRON
-- =============================================

-- Rate limiting
CREATE TABLE `rate_limits` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ip` VARCHAR(45) NOT NULL,
  `action` VARCHAR(50) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_ip_action` (`ip`, `action`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- DDoS защита
CREATE TABLE `ddos_protection` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ip` VARCHAR(45) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_ip` (`ip`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Блокирани IP адреси
CREATE TABLE `blocked_ips` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ip` VARCHAR(45) NOT NULL UNIQUE,
  `reason` VARCHAR(255) NOT NULL,
  `blocked_until` DATETIME NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Логове на имейли
CREATE TABLE `email_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `sent_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Настройки на потребители
CREATE TABLE `user_settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `receive_emails` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_user_type` (`user_id`, `type`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ПАКЕТ #9: ПРЕВОДИ
-- =============================================

-- Преводи от общността
CREATE TABLE `translations` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(255) NOT NULL,
  `language` VARCHAR(5) NOT NULL,
  `translation` TEXT NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `approved_by` INT UNSIGNED NULL,
  `approved_at` DATETIME NULL,
  UNIQUE KEY `unique_key_lang` (`key`, `language`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_language` (`language`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- IMDb кеш
CREATE TABLE `imdb_cache` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `imdb_id` VARCHAR(20) NOT NULL UNIQUE,
  `data` LONGTEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- YouTube кеш
CREATE TABLE `youtube_cache` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `video_id` VARCHAR(20) NOT NULL UNIQUE,
  `data` LONGTEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ДОБАВЯНЕ НА FOREIGN KEY ЗА last_post_id
-- =============================================

ALTER TABLE `forums` 
ADD FOREIGN KEY (`last_post_id`) REFERENCES `forum_posts`(`id`) ON DELETE SET NULL;

ALTER TABLE `forum_topics` 
ADD FOREIGN KEY (`last_post_id`) REFERENCES `forum_posts`(`id`) ON DELETE SET NULL;

-- =============================================
-- ГОТОВО! 
-- =============================================