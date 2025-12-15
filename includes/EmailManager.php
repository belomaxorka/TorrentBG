<?php
declare(strict_types=1);

class EmailManager {
    private array $config;
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->config = require __DIR__ . '/../config.php';
    }
    
    public function sendNotificationEmail(int $userId, string $subject, string $message, string $type = 'notification'): bool {
        try {
            // Get user's email
            $stmt = $this->pdo->prepare("SELECT email, language FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return false;
            }
            
            // Check if user wants to receive this type of notification
            $stmt = $this->pdo->prepare("SELECT receive_emails FROM user_settings WHERE user_id = ? AND type = ?");
            $stmt->execute([$userId, $type]);
            $setting = $stmt->fetch();
            
            if ($setting && !$setting['receive_emails']) {
                return true; // Don't send, but not an error
            }
            
            // Subject
            $emailSubject = "[Torrent Tracker] " . $subject;
            
            // Email body
            $emailBody = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; background-color: #f8f9fa; }
                    .footer { padding: 20px; text-align: center; font-size: 0.8em; color: #666; }
                    .button { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Torrent Tracker</h1>
                    </div>
                    <div class='content'>
                        <h2>" . htmlspecialchars($subject) . "</h2>
                        <p>" . nl2br(htmlspecialchars($message)) . "</p>
                        <p><a href='" . $this->getBaseUrl() . "/notifications.php' class='button'>View Notifications</a></p>
                    </div>
                    <div class='footer'>
                        <p>This is an automated message. Please do not reply to this email.</p>
                        <p><a href='" . $this->getBaseUrl() . "/profile.php'>Manage your notification settings</a></p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            // Headers
            $headers = [
                'From: ' . ($this->config['email']['from'] ?? 'noreply@torrenttracker.com'),
                'Reply-To: ' . ($this->config['email']['reply_to'] ?? 'noreply@torrenttracker.com'),
                'X-Mailer: PHP/' . phpversion(),
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8'
            ];
            
            // Send email
            $result = mail($user['email'], $emailSubject, $emailBody, implode("\r\n", $headers));
            
            if ($result) {
                // Log email
                $this->logEmail($userId, $subject, $type);
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Email sending failed for user $userId: " . $e->getMessage());
            return false;
        }
    }
    
    private function getBaseUrl(): string {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }
    
    private function logEmail(int $userId, string $subject, string $type): void {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO email_logs (user_id, subject, type, sent_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$userId, $subject, $type]);
        } catch (Exception $e) {
            // Ignore logging errors
        }
    }
    
    // Bulk email sending
    public function sendBulkEmail(array $userIds, string $subject, string $message, string $type = 'bulk'): int {
        $sentCount = 0;
        
        foreach ($userIds as $userId) {
            if ($this->sendNotificationEmail($userId, $subject, $message, $type)) {
                $sentCount++;
            }
            
            // Pause to avoid overloading the server
            usleep(100000); // 0.1 second
        }
        
        return $sentCount;
    }
}

// Create email logging table (if not exists)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `email_logs` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `user_id` INT UNSIGNED NOT NULL,
          `subject` VARCHAR(255) NOT NULL,
          `type` VARCHAR(50) NOT NULL,
          `sent_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
          INDEX `idx_user_id` (`user_id`),
          INDEX `idx_sent_at` (`sent_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `user_settings` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `user_id` INT UNSIGNED NOT NULL,
          `type` VARCHAR(50) NOT NULL,
          `receive_emails` BOOLEAN NOT NULL DEFAULT TRUE,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          UNIQUE KEY `unique_user_type` (`user_id`, `type`),
          FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
} catch (Exception $e) {
    // Ignore table creation errors
}