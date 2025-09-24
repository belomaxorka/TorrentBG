<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Language.php';

$pdo = Database::getInstance();
$auth = new Auth($pdo);
$lang = new Language($_SESSION['lang'] ?? 'en');

if ($auth->getRank() < 5) { // Moderator+
    die($lang->get('no_permission'));
}

$message = '';

// Обработка на форми
if ($_POST['action'] ?? false) {
    if ($_POST['action'] === 'delete_torrent') {
        $torrentId = (int)$_POST['torrent_id'];
        $stmt = $pdo->prepare("DELETE FROM torrents WHERE id = ?");
        if ($stmt->execute([$torrentId])) {
            // Изтриваме и .torrent файла
            $stmt = $pdo->prepare("SELECT info_hash FROM torrents WHERE id = ?");
            $stmt->execute([$torrentId]);
            $infoHash = $stmt->fetchColumn();
            if ($infoHash) {
                $torrentFile = __DIR__ . "/../torrents/{$infoHash}.torrent";
                if (file_exists($torrentFile)) {
                    unlink($torrentFile);
                }
            }
            $message = '<div class="alert alert-success">' . $lang->get('torrent_deleted') . '</div>';
        } else {
            $message = '<div class="alert alert-danger">' . $lang->get('torrent_delete_failed') . '</div>';
        }
    }
    
    if ($_POST['action'] === 'edit_torrent') {
        $torrentId = (int)$_POST['torrent_id'];
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $categoryId = (int)$_POST['category_id'];
        
        if (empty($name)) {
            $message = '<div class="alert alert-danger">' . $lang->get('torrent_name_required') . '</div>';
        } else {
            $stmt = $pdo->prepare("UPDATE torrents SET name = ?, description = ?, category_id = ? WHERE id = ?");
            if ($stmt->execute([$name, $description, $categoryId, $torrentId])) {
                $message = '<div class="alert alert-success">' . $lang->get('torrent_updated') . '</div>';
            } else {
                $message = '<div class="alert alert-danger">' . $lang->get('torrent_update_failed') . '</div>';
            }
        }
    }
}

// Извличаме категориите
$stmt = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY `order`");
$categories = $stmt->fetchAll();

// Взимаме торентите
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT t.*, c.name as category_name, u.username as uploader_name
    FROM torrents t
    JOIN categories c ON t.category_id = c.id
    JOIN users u ON t.uploader_id = u.id
    ORDER BY t.uploaded_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$perPage, $offset]);
$torrents = $stmt->fetchAll();

// Общ брой
$stmt = $pdo->query("SELECT COUNT(*) FROM torrents");
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $perPage);

require_once __DIR__ . '/../templates/header.php';
?>

<div class="container-fluid">
    <h2><?= $lang->get('manage_torrents') ?></h2>
    <?= $message ?>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><?= $lang->get('torrents_list') ?></h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th><?= $lang->get('name') ?></th>
                                    <th><?= $lang->get('category') ?></th>
                                    <th><?= $lang->get('uploader') ?></th>
                                    <th><?= $lang->get('size') ?></th>
                                    <th><?= $lang->get('seeders') ?></th>
                                    <th><?= $lang->get('leechers') ?></th>
                                    <th><?= $lang->get('uploaded_at') ?></th>
                                    <th><?= $lang->get('actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($torrents as $torrent): ?>
                                    <tr>
                                        <td>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="edit_torrent">
                                                <input type="hidden" name="torrent_id" value="<?= $torrent['id'] ?>">
                                                <input type="text" name="name" value="<?= htmlspecialchars($torrent['name']) ?>" class="form-control form-control-sm" style="width: 200px;" onchange="this.form.submit()">
                                            </form>
                                        </td>
                                        <td>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="edit_torrent">
                                                <input type="hidden" name="torrent_id" value="<?= $torrent['id'] ?>">
                                                <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                                    <?php foreach ($categories as $cat): ?>
                                                        <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $torrent['category_id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($cat['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </form>
                                        </td>
                                        <td><?= htmlspecialchars($torrent['uploader_name']) ?></td>
                                        <td><?= formatBytes($torrent['size']) ?></td>
                                        <td><?= $torrent['seeders'] ?></td>
                                        <td><?= $torrent['leechers'] ?></td>
                                        <td><?= date('Y-m-d H:i', strtotime($torrent['uploaded_at'])) ?></td>
                                        <td>
                                            <a href="/torrent.php?id=<?= $torrent['id'] ?>" class="btn btn-sm btn-outline-primary" target="_blank">👁️</a>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('<?= $lang->get('confirm_delete_torrent') ?>')">
                                                <input type="hidden" name="action" value="delete_torrent">
                                                <input type="hidden" name="torrent_id" value="<?= $torrent['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">🗑️</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Пагинация -->
                    <?php if ($totalPages > 1): ?>
                        <nav>
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>