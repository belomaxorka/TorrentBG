<?php
declare(strict_types=1);

/**
 * Центральный класс приложения (Singleton)
 * Управляет всеми компонентами системы
 */
class Application
{
    private static ?Application $instance = null;
    
    private PDO $database;
    private Auth $auth;
    private Language $language;
    private StyleManager $styleManager;
    private Config $config;
    
    private bool $initialized = false;
    
    private function __construct()
    {
        // Приватный конструктор для Singleton
    }
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->initialize();
        }
        return self::$instance;
    }
    
    /**
     * Инициализация всех компонентов приложения
     */
    private function initialize(): void
    {
        if ($this->initialized) {
            return;
        }
        
        // Загружаем конфигурацию
        $this->config = new Config();
        
        // Инициализируем БД
        $this->database = Database::getInstance();
        
        // Инициализируем аутентификацию
        $this->auth = new Auth($this->database);
        
        // Инициализируем язык
        $langCode = $_SESSION['lang'] ?? $this->config->get('site.default_lang', 'en');
        $this->language = new Language($langCode);
        
        // Инициализируем менеджер стилей
        $this->styleManager = new StyleManager();
        
        // Выполняем одноразовую инициализацию системы
        $this->runOneTimeSetup();
        
        $this->initialized = true;
    }
    
    /**
     * Одноразовая настройка системы (создание директорий, проверка БД)
     */
    private function runOneTimeSetup(): void
    {
        // Создаем необходимые директории
        $this->ensureDirectoriesExist();
        
        // Создаем необходимые таблицы если их нет
        $this->ensureDatabaseTablesExist();
    }
    
    /**
     * Создание необходимых директорий
     */
    private function ensureDirectoriesExist(): void
    {
        $requiredDirs = [
            'torrents',
            'subtitles',
            'images/posters',
            'images/categories',
            'images/forums',
            'images/smiles',
            'logs',
            'backups',
            'uploads',
        ];
        
        foreach ($requiredDirs as $dir) {
            $path = ROOT_PATH . $dir;
            if (!is_dir($path)) {
                @mkdir($path, 0755, true);
            }
        }
    }
    
    /**
     * Создание необходимых таблиц в БД
     */
    private function ensureDatabaseTablesExist(): void
    {
        try {
            // Создаем таблицу peers если не существует
            $this->database->exec("CREATE TABLE IF NOT EXISTS `peers` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `torrent_id` INT NOT NULL,
                `peer_id` VARCHAR(40) NOT NULL,
                `ip` VARCHAR(45) NOT NULL,
                `port` INT NOT NULL,
                `seeder` TINYINT(1) NOT NULL DEFAULT 0,
                `uploaded` BIGINT UNSIGNED NOT NULL DEFAULT 0,
                `downloaded` BIGINT UNSIGNED NOT NULL DEFAULT 0,
                `left` BIGINT UNSIGNED NOT NULL DEFAULT 0,
                `last_announce` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `unique_peer` (`torrent_id`, `peer_id`),
                KEY `torrent_id` (`torrent_id`),
                KEY `seeder` (`seeder`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        } catch (PDOException $e) {
            // Таблица уже существует, игнорируем ошибку
            error_log("Database setup: " . $e->getMessage());
        }
    }
    
    /**
     * Получение статистики сайта
     */
    public function getStatistics(): array
    {
        static $stats = null;
        
        if ($stats !== null) {
            return $stats;
        }
        
        $stats = [
            'users' => 0,
            'torrents' => 0,
            'seeders' => 0,
            'leechers' => 0,
            'total_peers' => 0,
            'seeder_percentage' => 0,
        ];
        
        try {
            // Количество пользователей
            $stmt = $this->database->query("SELECT COUNT(*) FROM users");
            $stats['users'] = (int)$stmt->fetchColumn();
            
            // Количество торрентов
            $stmt = $this->database->query("SELECT COUNT(*) FROM torrents");
            $stats['torrents'] = (int)$stmt->fetchColumn();
            
            // Проверяем наличие таблицы peers и колонки seeder
            $stmt = $this->database->query("SHOW TABLES LIKE 'peers'");
            if ($stmt->rowCount() > 0) {
                // Очищаем старых пиров
                $this->database->exec("DELETE FROM peers WHERE last_announce < NOW() - INTERVAL 30 MINUTE");
                
                // Считаем сидеров
                $stmt = $this->database->query("SELECT COUNT(*) FROM peers WHERE seeder = 1");
                $stats['seeders'] = (int)$stmt->fetchColumn();
                
                // Считаем личеров
                $stmt = $this->database->query("SELECT COUNT(*) FROM peers WHERE seeder = 0");
                $stats['leechers'] = (int)$stmt->fetchColumn();
                
                $stats['total_peers'] = $stats['seeders'] + $stats['leechers'];
                $stats['seeder_percentage'] = $stats['total_peers'] > 0 
                    ? round(($stats['seeders'] / $stats['total_peers']) * 100, 1) 
                    : 0;
            }
        } catch (PDOException $e) {
            error_log("Statistics error: " . $e->getMessage());
        }
        
        return $stats;
    }
    
    // Геттеры для компонентов
    
    public function getDatabase(): PDO
    {
        return $this->database;
    }
    
    public function getAuth(): Auth
    {
        return $this->auth;
    }
    
    public function getLanguage(): Language
    {
        return $this->language;
    }
    
    public function getStyleManager(): StyleManager
    {
        return $this->styleManager;
    }
    
    public function getConfig(): Config
    {
        return $this->config;
    }
    
    // Запрет клонирования и десериализации (Singleton)
    private function __clone() {}
    public function __wakeup() {}
}

