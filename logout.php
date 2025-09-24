<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';

$pdo = Database::getInstance();
$auth = new Auth($pdo);
$auth->logout();

header("Location: index.php");
exit;