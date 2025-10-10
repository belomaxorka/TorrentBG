<?php
// test-path.php

// 1. Провери текущата работна директория
echo "Текуща директория: " . __DIR__ . "<br>";

// 2. Провери дали папката 'torrents' съществува
if (is_dir(__DIR__ . '/torrents')) {
    echo "✅ Папката 'torrents' съществува.<br>";
} else {
    echo "❌ Папката 'torrents' НЕ съществува!<br>";
}

// 3. Провери конкретен файл (замени с реалния хеш!)
$hash = '46ce761ca4c53b59cd0907bb02705e07739cb084';
$filePath = __DIR__ . '/torrents/' . $hash;

echo "Проверка на файл: " . htmlspecialchars($filePath) . "<br>";

if (file_exists($filePath)) {
    echo "✅ ФАЙЛЪТ СЪЩЕСТВУВА!<br>";
    echo "Размер: " . filesize($filePath) . " байта<br>";
} else {
    echo "❌ ФАЙЛЪТ НЕ СЪЩЕСТВУВА!<br>";
    
    // Покажи какво има в папката
    if ($handle = opendir(__DIR__ . '/torrents')) {
        echo "Файлове в torrents/:<br><ul>";
        while (false !== ($entry = readdir($handle))) {
            if ($entry !== '.' && $entry !== '..') {
                echo "<li>" . htmlspecialchars($entry) . "</li>";
            }
        }
        echo "</ul>";
        closedir($handle);
    }
}