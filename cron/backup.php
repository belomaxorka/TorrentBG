<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/Database.php';

$config = require __DIR__ . '/config.php';

$dbHost = $config['db']['host'];
$dbUser = $config['db']['user'];
$dbPass = $config['db']['pass'];
$dbName = $config['db']['name'];

// Генерираме име на файла
$backupFile = __DIR__ . "/backups/backup_" . date('Y-m-d_H-i-s') . ".sql.gz";

// Команда за mysqldump
$command = "mysqldump --host=$dbHost --user=$dbUser --password=$dbPass $dbName | gzip > $backupFile 2>&1";

// Изпълняваме командата
exec($command, $output, $returnCode);

if ($returnCode === 0) {
    // Записваме в лог
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Backup created successfully: $backupFile\n";
    file_put_contents(__DIR__ . '/logs/backup.log', $logMessage, FILE_APPEND);
    
    echo "Backup created successfully: $backupFile\n";
    
    // Изтриваме бекъпи по-стари от 30 дни
    $files = glob(__DIR__ . '/backups/backup_*.sql.gz');
    foreach ($files as $file) {
        if (filemtime($file) < time() - 30 * 24 * 60 * 60) {
            unlink($file);
        }
    }
} else {
    $error = implode("\n", $output);
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Backup failed: $error\n";
    file_put_contents(__DIR__ . '/logs/backup.log', $logMessage, FILE_APPEND);
    
    echo "Backup failed: $error\n";
}