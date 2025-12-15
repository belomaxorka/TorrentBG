<?php
declare(strict_types=1);

class Security {
    private PDO $pdo;
    private string $ip;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    // CSRF protection
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
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM rate_limits 
            WHERE ip = ? AND action = ? AND created_at > NOW() - INTERVAL ? SECOND
        ");
        $stmt->execute([$this->ip, $action, $timeWindow]);
        $attempts = $stmt->fetchColumn();
        
        if ($attempts >= $maxAttempts) {
            return false;
        }
        
        // Record the attempt
        $stmt = $this->pdo->prepare("INSERT INTO rate_limits (ip, action) VALUES (?, ?)");
        $stmt->execute([$this->ip, $action]);
        
        return true;
    }
    
    // XSS filtering
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
    
    // DDoS protection check
    public function checkDDoSProtection(): bool {
        // Check for too many requests from one IP
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM ddos_protection 
            WHERE ip = ? AND created_at > NOW() - INTERVAL 1 MINUTE
        ");
        $stmt->execute([$this->ip]);
        $requests = $stmt->fetchColumn();
        
        if ($requests > 100) { // Maximum 100 requests per minute
            // Block IP for 1 hour
            $stmt = $this->pdo->prepare("INSERT INTO blocked_ips (ip, reason, blocked_until) VALUES (?, 'Too many requests', DATE_ADD(NOW(), INTERVAL 1 HOUR))");
            $stmt->execute([$this->ip]);
            return false;
        }
        
        // Record the request
        $stmt = $this->pdo->prepare("INSERT INTO ddos_protection (ip) VALUES (?)");
        $stmt->execute([$this->ip]);
        
        return true;
    }
    
    // Check if IP is blocked
    public function isIPBlocked(): bool {
        $stmt = $this->pdo->prepare("SELECT id FROM blocked_ips WHERE ip = ? AND blocked_until > NOW()");
        $stmt->execute([$this->ip]);
        return $stmt->fetch() !== false;
    }
}