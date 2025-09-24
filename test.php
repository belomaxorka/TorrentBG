<?php
echo "1. Проверка дали файлът съществува: ";
if (file_exists('includes/StyleManager.php')) {
    echo "✅ ДА<br>";
} else {
    echo "❌ НЕ<br>";
    exit;
}

echo "2. Опитваме да го заредим: ";
require_once 'includes/StyleManager.php';
echo "✅ УСПЕШНО!<br>";

$style = new StyleManager();
echo "3. Текущ стил: " . htmlspecialchars($style->getCurrent());