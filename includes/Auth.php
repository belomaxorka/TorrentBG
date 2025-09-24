<?php
declare(strict_types=1);

class Auth {
    private PDO $pdo;
    private ?array $user = null;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        
        // Проверка дали сесията вече е стартирана
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->loadUser();
    }

    private function loadUser(): void {
        if (isset($_SESSION['user_id'])) {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $this->user = $stmt->fetch();
        }
    }

    public function login(string $username, string $password): bool {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['rank'] = $user['rank'];
            $_SESSION['lang'] = $user['language'] ?? 'en';
            $_SESSION['style'] = $user['style'] ?? 'light';

            // Обновяваме last_login
            $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

            $this->user = $user;
            return true;
        }

        return false;
    }

    public function logout(): void {
        session_destroy();
        $this->user = null;
    }

    public function isLoggedIn(): bool {
        return $this->user !== null;
    }

    public function getUser(): ?array {
        return $this->user;
    }

    public function getRank(): int {
        return $this->user['rank'] ?? 1; // 1 = Guest
    }

    public function hasPermission(string $permission): bool {
        // Тук ще добавим по-късно проверка по рангове
        $rankPermissions = [
            1 => [], // Guest
            2 => ['view_torrents', 'view_forum'], // User
            3 => ['view_torrents', 'view_forum', 'upload_torrent'], // Uploader
            4 => ['view_torrents', 'view_forum', 'upload_torrent', 'edit_own_torrent'], // Validator
            5 => ['view_torrents', 'view_forum', 'upload_torrent', 'edit_own_torrent', 'edit_any_torrent', 'delete_own_torrent', 'moderate_forum'], // Moderator
            6 => ['*'], // Owner — всичко
        ];

        $permissions = $rankPermissions[$this->getRank()] ?? [];
        return in_array($permission, $permissions) || in_array('*', $permissions);
    }
}