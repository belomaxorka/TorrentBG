<?php
require_once __DIR__ . '/includes/bootstrap.php';
$auth->logout();

header("Location: index.php");
exit;