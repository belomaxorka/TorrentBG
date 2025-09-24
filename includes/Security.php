<?php
declare(strict_types=1);

class Security {
    private PDO $pdo;
    private string $ip;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    // CSRF защита
    public function generateCSRFToken(): string {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public function validateCSRFToken(string $token): bool {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    // Rate limiting
    public function checkRateLimit(string $action, int $maxAttempts = 5, int $timeWindow = 60): bool {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM rate_limits 
            WHERE ip = ? AND action = ? AND created_at > NOW() - INTERVAL ? SECOND
        ");
        $stmt->execute([$this->ip, $action, $timeWindow]);
        $attempts = $stmt->fetchColumn();
        
        if ($attempts >= $maxAttempts) {
            return false;
        }
        
        // Записваме опита
        $stmt = $pdo->prepare("INSERT INTO rate_limits (ip, action) VALUES (?, ?)");
        $stmt->execute([$this->ip, $action]);
        
        return true;
    }
    
    // XSS филтриране
    public static function sanitizeInput(string $input): string {
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    
    public static function sanitizeArray(array $data): array {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $result[$key] = self::sanitizeInput($value);
            } elseif (is_array($value)) {
                $result[$key] = self::sanitizeArray($value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }
    
    // Проверка за DDoS
    public function checkDDoSProtection(): bool {
        // Проверка за твърде много заявки от един IP
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM ddos_protection 
            WHERE ip = ? AND created_at > NOW() - INTERVAL 1 MINUTE
        ");
        $stmt->execute([$this->ip]);
        $requests = $stmt->fetchColumn();
        
        if ($requests > 100) { // Максимум 100 заявки на минута
            // Блокираме IP за 1 час
            $stmt = $pdo->prepare("INSERT INTO blocked_ips (ip, reason, blocked_until) VALUES (?, 'Too many requests', DATE_ADD(NOW(), INTERVAL 1 HOUR))");
            $stmt->execute([$this->ip]);
            return false;
        }
        
        // Записваме заявката
        $stmt = $pdo->prepare("INSERT INTO ddos_protection (ip) VALUES (?)");
        $stmt->execute([$this->ip]);
        
        return true;
    }
    
    // Проверка дали IP е блокиран
    public function isIPBlocked(): bool {
        $stmt = $pdo->prepare("SELECT id FROM blocked_ips WHERE ip = ? AND blocked_until > NOW()");
        $stmt->execute([$this->ip]);
        return $stmt->fetch() !== false;
    }
}

// Създаваме таблиците за сигурност (ако не съществуват)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `rate_limits` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `ip` VARCHAR(45) NOT NULL,
          `action` VARCHAR(50) NOT NULL,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          INDEX `idx_ip_action` (`ip`, `action`),
          INDEX `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `ddos_protection` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `ip` VARCHAR(45) NOT NULL,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          INDEX `idx_ip` (`ip`),
          INDEX `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `blocked_ips` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `ip` VARCHAR(45) NOT NULL UNIQUE,
          `reason` VARCHAR(255) NOT NULL,
          `blocked_until` DATETIME NOT NULL,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
} catch (Exception $e) {
    // Игнорираме грешки при създаване на таблици
}