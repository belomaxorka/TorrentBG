<?php
declare(strict_types=1);

class ExternalAPI {
    private PDO $pdo;
    private array $config;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->config = require __DIR__ . '/../config.php';
    }
    
    // IMDb API integration
    public function getIMDbInfo(string $imdbId): ?array {
        try {
            // Check cache
            $stmt = $this->pdo->prepare("
                SELECT data, created_at 
                FROM imdb_cache 
                WHERE imdb_id = ? AND created_at > NOW() - INTERVAL 7 DAY
            ");
            $stmt->execute([$imdbId]);
            $cached = $stmt->fetch();
            
            if ($cached) {
                return json_decode($cached['data'], true);
            }
            
            // If not in cache, fetch from API
            $apiKey = $this->config['imdb']['api_key'] ?? '';
            if (empty($apiKey)) {
                return null;
            }
            
            $url = "https://imdb-api.com/en/API/Title/{$apiKey}/{$imdbId}";
            $response = $this->makeRequest($url);
            
            if ($response && isset($response['title'])) {
                // Save to cache
                $stmt = $this->pdo->prepare("
                    INSERT INTO imdb_cache (imdb_id, data) 
                    VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE data = VALUES(data), created_at = NOW()
                ");
                $stmt->execute([$imdbId, json_encode($response)]);
                
                return $response;
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("IMDb API error: " . $e->getMessage());
            return null;
        }
    }
    
    // YouTube API integration
    public function getYouTubeInfo(string $videoId): ?array {
        try {
            // Check cache
            $stmt = $this->pdo->prepare("
                SELECT data, created_at 
                FROM youtube_cache 
                WHERE video_id = ? AND created_at > NOW() - INTERVAL 7 DAY
            ");
            $stmt->execute([$videoId]);
            $cached = $stmt->fetch();
            
            if ($cached) {
                return json_decode($cached['data'], true);
            }
            
            // If not in cache, fetch from API
            $apiKey = $this->config['youtube']['api_key'] ?? '';
            if (empty($apiKey)) {
                return null;
            }
            
            $url = "https://www.googleapis.com/youtube/v3/videos?part=snippet,statistics&id={$videoId}&key={$apiKey}";
            $response = $this->makeRequest($url);
            
            if ($response && isset($response['items'][0])) {
                $video = $response['items'][0];
                
                $result = [
                    'title' => $video['snippet']['title'] ?? '',
                    'description' => $video['snippet']['description'] ?? '',
                    'thumbnail' => $video['snippet']['thumbnails']['high']['url'] ?? '',
                    'views' => $video['statistics']['viewCount'] ?? 0,
                    'likes' => $video['statistics']['likeCount'] ?? 0,
                    'duration' => $video['contentDetails']['duration'] ?? '',
                    'published_at' => $video['snippet']['publishedAt'] ?? ''
                ];
                
                // Save to cache
                $stmt = $this->pdo->prepare("
                    INSERT INTO youtube_cache (video_id, data) 
                    VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE data = VALUES(data), created_at = NOW()
                ");
                $stmt->execute([$videoId, json_encode($result)]);
                
                return $result;
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("YouTube API error: " . $e->getMessage());
            return null;
        }
    }
    
    // Extract IMDb ID from URL
    public function extractIMDbID(string $url): ?string {
        if (preg_match('/imdb\.com\/title\/(tt\d+)/i', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }
    
    // Extract YouTube video ID from URL
    public function extractYouTubeID(string $url): ?string {
        $pattern = '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/';
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
        return null;
    }
    
    private function makeRequest(string $url): ?array {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'TorrentTracker/1.0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            return is_array($data) ? $data : null;
        }
        
        return null;
    }
}

// Create cache tables (if not exist)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `imdb_cache` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `imdb_id` VARCHAR(20) NOT NULL UNIQUE,
          `data` LONGTEXT NOT NULL,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          INDEX `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `youtube_cache` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `video_id` VARCHAR(20) NOT NULL UNIQUE,
          `data` LONGTEXT NOT NULL,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          INDEX `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
} catch (Exception $e) {
    // Ignore table creation errors
}