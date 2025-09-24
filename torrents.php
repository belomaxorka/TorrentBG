<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Language.php';

$pdo = Database::getInstance();
$auth = new Auth($pdo);
$lang = new Language($_SESSION['lang'] ?? 'en');

// ✅ Функция за форматиране на размер
function formatBytes($bytes, $precision = 2) {
    if ($bytes === 0) return '0 Б';
    $units = ['Б', 'КБ', 'МБ', 'ГБ', 'ТБ'];
    $step = 1024;
    $i = 0;
    while ($bytes >= $step && $i < count($units) - 1) {
        $bytes /= $step;
        $i++;
    }
    return round($bytes, $precision) . ' ' . $units[$i];
}

// ✅ Променена заявка — добавен JOIN с categories И users
$stmt = $pdo->prepare("
    SELECT 
        t.id, 
        t.name, 
        t.size, 
        t.seeders, 
        t.leechers, 
        t.uploaded_at,
        c.name as category_name,
        c.icon as category_icon,
        u.username as uploader_name
    FROM torrents t
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN users u ON t.uploader_id = u.id
    ORDER BY t.uploaded_at DESC
");
$stmt->execute();
$torrents = $stmt->fetchAll();

require_once __DIR__ . '/templates/header.php';
?>

<style>
/* ✅ Стил за иконите на категориите — увеличени на 32x32 */
.category-icon {
    width: 32px;
    height: 32px;
    object-fit: contain; /* запазва пропорциите */
    vertical-align: middle;
    margin-right: 8px; /* замества me-2 */
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= $lang->get('torrents') ?></h2>
    <?php if ($auth->isLoggedIn()): ?>
        <a href="/upload.php" class="btn btn-primary"><?= $lang->get('upload_torrent') ?></a>
    <?php endif; ?>
</div>

<?php if (empty($torrents)): ?>
    <div class="alert alert-info"><?= $lang->get('no_torrents_yet') ?></div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?= $lang->get('category') ?></th>
                    <th><?= $lang->get('name') ?></th>
                    <th><?= $lang->get('size') ?></th>
                    <th><?= $lang->get('seeders') ?></th>
                    <th><?= $lang->get('leechers') ?></th>
                    <th><?= $lang->get('uploader') ?></th>
                    <th><?= $lang->get('uploaded') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($torrents as $t): ?>
                    <tr>
                        <!-- 🖼️ Категория с икона -->
                        <td>
                            <?php if (!empty($t['category_icon'])): ?>
                                <img src="/<?= htmlspecialchars($t['category_icon']) ?>" class="category-icon" alt="<?= htmlspecialchars($t['category_name'] ?? '') ?>">
                            <?php endif; ?>
                            <?= htmlspecialchars($t['category_name'] ?? $lang->get('uncategorized')) ?>
                        </td>
                        <!-- 🔗 Име на торрента -->
                        <td><a href="/torrent.php?id=<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></a></td>
                        <!-- 📦 Размер -->
                        <td><?= formatBytes($t['size']) ?></td>
                        <!-- 🌱 Сийдъри -->
                        <td><?= $t['seeders'] ?></td>
                        <!-- 🐜 Лийчъри -->
                        <td><?= $t['leechers'] ?></td>
                        <!-- 👤 Качил -->
                        <td>
                            <?php if (!empty($t['uploader_name'])): ?>
                                <a href="/profile.php?id=<?= $t['id'] ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($t['uploader_name']) ?>
                                </a>
                            <?php else: ?>
                                <?= $lang->get('unknown') ?>
                            <?php endif; ?>
                        </td>
                        <!-- ⏱️ Качен на -->
                        <td><?= date('Y-m-d H:i', strtotime($t['uploaded_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/templates/footer.php'; ?>