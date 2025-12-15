<?php
declare(strict_types=1);

class BlockManager {
    private PDO $pdo;
    private array $blocks = [];

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->loadBlocks();
    }

    private function loadBlocks(): void {
        $stmt = $this->pdo->query("
            SELECT * FROM blocks 
            WHERE is_active = 1 
            ORDER BY position, `order`
        ");
        $this->blocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBlocksByPosition(string $position): array {
        return array_filter($this->blocks, fn($b) => $b['position'] === $position);
    }

    public function renderBlock(string $name, PDO $pdo, Auth $auth, Language $lang): void {
        $filePath = __DIR__ . "/../blocks/{$name}.php";
        if (file_exists($filePath)) {
            if (!defined('IN_BLOCK')) {
                define('IN_BLOCK', true);
            }
            // Pass variables to block scope
            extract(compact('pdo', 'auth', 'lang'), EXTR_REFS);
            require $filePath;
        }
    }

    // For admin panel — all blocks
    public function getAllBlocks(): array {
        $stmt = $this->pdo->query("SELECT * FROM blocks ORDER BY position, `order`");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateBlock(int $id, array $data): bool {
        $stmt = $this->pdo->prepare("
            UPDATE blocks SET 
                title = ?, position = ?, `order` = ?, is_active = ?, is_locked = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['title'],
            $data['position'],
            $data['order'],
            $data['is_active'],
            $data['is_locked'],
            $id
        ]);
    }

    public function addBlock(array $data): bool {
        $stmt = $this->pdo->prepare("
            INSERT INTO blocks (name, title, position, `order`, is_active, is_locked)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['name'],
            $data['title'],
            $data['position'],
            $data['order'],
            $data['is_active'],
            $data['is_locked']
        ]);
    }

    public function deleteBlock(int $id): bool {
        // Check if locked
        $stmt = $this->pdo->prepare("SELECT is_locked FROM blocks WHERE id = ?");
        $stmt->execute([$id]);
        $locked = $stmt->fetchColumn();
        if ($locked) return false;

        $stmt = $this->pdo->prepare("DELETE FROM blocks WHERE id = ?");
        return $stmt->execute([$id]);
    }
}