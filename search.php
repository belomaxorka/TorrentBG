<?php
$q = $_GET['q'] ?? '';
header("Location: /torrents.php?q=" . urlencode(trim($q)));
exit;
?>