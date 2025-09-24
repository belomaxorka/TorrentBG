<?php
declare(strict_types=1);

class TranslationManager {
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        
        // Създаваме таблицата за преводи (ако не съществува)
        try {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `translations` (
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
            ");
        } catch (Exception $e) {
            // Игнорираме грешки при създаване на таблица
        }
    }
    
    // Предлагане на превод
    public function suggestTranslation(string $key, string $language, string $translation, int $userId): bool {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO translations (`key`, `language`, `translation`, `user_id`, `status`) 
                VALUES (?, ?, ?, ?, 'pending')
                ON DUPLICATE KEY UPDATE 
                    `translation` = VALUES(`translation`),
                    `user_id` = VALUES(`user_id`),
                    `status` = 'pending',
                    `updated_at` = NOW()
            ");
            return $stmt->execute([$key, $language, $translation, $userId]);
        } catch (Exception $e) {
            error_log("Translation suggestion failed: " . $e->getMessage());
            return false;
        }
    }
    
    // Одобрение на превод
    public function approveTranslation(int $translationId, int $approverId): bool {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE translations 
                SET status = 'approved', approved_by = ?, approved_at = NOW() 
                WHERE id = ?
            ");
            return $stmt->execute([$approverId, $translationId]);
        } catch (Exception $e) {
            error_log("Translation approval failed: " . $e->getMessage());
            return false;
        }
    }
    
    // Отхвърляне на превод
    public function rejectTranslation(int $translationId, int $approverId): bool {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE translations 
                SET status = 'rejected', approved_by = ?, approved_at = NOW() 
                WHERE id = ?
            ");
            return $stmt->execute([$approverId, $translationId]);
        } catch (Exception $e) {
            error_log("Translation rejection failed: " . $e->getMessage());
            return false;
        }
    }
    
    // Вземане на одобрен превод
    public function getApprovedTranslation(string $key, string $language): ?string {
        try {
            $stmt = $this->pdo->prepare("
                SELECT translation 
                FROM translations 
                WHERE `key` = ? AND `language` = ? AND status = 'approved'
            ");
            $stmt->execute([$key, $language]);
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Get approved translation failed: " . $e->getMessage());
            return null;
        }
    }
    
    // Вземане на всички предложени преводи (за администратор)
    public function getPendingTranslations(int $limit = 50, int $offset = 0): array {
        try {
            $stmt = $this->pdo->prepare("
                SELECT t.*, u.username as suggested_by, a.username as approved_by_name
                FROM translations t
                JOIN users u ON t.user_id = u.id
                LEFT JOIN users a ON t.approved_by = a.id
                WHERE t.status = 'pending'
                ORDER BY t.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get pending translations failed: " . $e->getMessage());
            return [];
        }
    }
    
    // Вземане на статистики за преводи
    public function getTranslationStats(): array {
        try {
            $stats = [
                'total' => 0,
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
                'languages' => []
            ];
            
            // Общ брой
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM translations");
            $stats['total'] = $stmt->fetchColumn();
            
            // Брой по статус
            $stmt = $this->pdo->query("SELECT status, COUNT(*) as count FROM translations GROUP BY status");
            foreach ($stmt->fetchAll() as $row) {
                $stats[$row['status']] = $row['count'];
            }
            
            // Брой по езици
            $stmt = $this->pdo->query("SELECT language, COUNT(*) as count FROM translations GROUP BY language ORDER BY count DESC");
            $stats['languages'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            return $stats;
        } catch (Exception $e) {
            error_log("Get translation stats failed: " . $e->getMessage());
            return ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'languages' => []];
        }
    }
}