<?php
namespace App;

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        // Check if we are in the installation process
        $inInstaller = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/install') !== false;

        $config = require __DIR__ . '/config.php';

        // If not installed and we are in the installer — don't connect
        if (!$inInstaller && !($config['site']['installed'] ?? false)) {
            throw new \RuntimeException('System not installed. Please run installer first.');
        }

        // If installed — connect to database
        if ($config['site']['installed'] ?? false) {
            $db = $config['db'];
            $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";
            $this->pdo = new \PDO($dsn, $db['user'], $db['pass'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
        }
        // If not installed and not in installer — no connection (but this won't happen because index.php redirects to install.php)
    }

    /**
     * Returns PDO connection directly — for compatibility with old code
     * Now Database::getInstance() returns \PDO|null, not a class object.
     */
    public static function getInstance(): ?\PDO {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->pdo;
    }
}

// Global alias: allows using \Database everywhere without "use App\Database;"
class_alias('App\Database', 'Database');