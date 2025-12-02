<?php
namespace App;

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        // Проверяваме дали сме в инсталационния процес
        $inInstaller = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/install') !== false;

        $config = require __DIR__ . '/config.php';

        // Ако не е инсталирано и сме в инсталатора — не правим връзка
        if (!$inInstaller && !($config['site']['installed'] ?? false)) {
            throw new \RuntimeException('System not installed. Please run installer first.');
        }

        // Ако е инсталирано — правим връзка
        if ($config['site']['installed'] ?? false) {
            $db = $config['db'];
            $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";
            $this->pdo = new \PDO($dsn, $db['user'], $db['pass'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
        }
        // Ако не е инсталирано и не сме в инсталатора — няма връзка (но това няма да се случи, защото index.php пренасочва към install.php)
    }

    /**
     * Връща PDO връзката директно — за съвместимост със стария код
     * Сега Database::getInstance() връща \PDO|null, а не обект от класа.
     */
    public static function getInstance(): ?\PDO {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->pdo;
    }
}

// Глобален alias: позволява използването на \Database навсякъде без "use App\Database;"
class_alias('App\Database', 'Database');