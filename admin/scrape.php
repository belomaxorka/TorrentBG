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

if ($_POST['action'] ?? false) {
    if ($_POST['action'] === 'scrape_all') {
        try {
            // Взимаме всички торенти
            $stmt = $pdo->query("SELECT id, info_hash FROM torrents");
            $torrents = $stmt->fetchAll();
            
            $updated = 0;
            foreach ($torrents as $torrent) {
                // Изчистваме старите пиъри
                $pdo->prepare("DELETE FROM peers WHERE torrent_id = ? AND last_announce < NOW() - INTERVAL 30 MINUTE")->execute([$torrent['id']]);
                
                // Преброяваме активните сидъри и лийчъри
                $seeders = $pdo->prepare("SELECT COUNT(*) FROM peers WHERE torrent_id = ? AND is_seeder = 1 AND last_announce >= NOW() - INTERVAL 30 MINUTE");
                $seeders->execute([$torrent['id']]);
                $seederCount = $seeders->fetchColumn();

                $leechers = $pdo->prepare("SELECT COUNT(*) FROM peers WHERE torrent_id = ? AND is_seeder = 0 AND last_announce >= NOW() - INTERVAL 30 MINUTE");
                $leechers->execute([$torrent['id']]);
                $leecherCount = $leechers->fetchColumn();
                
                // Обновяваме статистиките
                $pdo->prepare("
                    UPDATE torrents 
                    SET seeders = ?, leechers = ? 
                    WHERE id = ?
                ")->execute([$seederCount, $leecherCount, $torrent['id']]);
                
                $updated++;
            }
            
            $message = '<div class="alert alert-success">' . sprintf($lang->get('scraped_torrents_successfully'), $updated) . '</div>';
        } catch (Exception $e) {
            $message = '<div class="alert alert-danger">' . $lang->get('scrape_failed') . ': ' . $e->getMessage() . '</div>';
        }
    }
}

// Статистики
$totalTorrents = $pdo->query("SELECT COUNT(*) FROM torrents")->fetchColumn();
$activeSeeders = $pdo->query("SELECT COUNT(*) FROM peers WHERE is_seeder = 1 AND last_announce >= NOW() - INTERVAL 30 MINUTE")->fetchColumn();
$activeLeechers = $pdo->query("SELECT COUNT(*) FROM peers WHERE is_seeder = 0 AND last_announce >= NOW() - INTERVAL 30 MINUTE")->fetchColumn();
$totalPeers = $activeSeeders + $activeLeechers;

require_once __DIR__ . '/../templates/header.php';
?>

<div class="container-fluid">
    <h2><?= $lang->get('tracker_statistics') ?></h2>
    <?= $message ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><?= $lang->get('scrape_control') ?></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="scrape_all">
                        <p><?= $lang->get('scrape_all_torrents_description') ?></p>
                        <button type="submit" class="btn btn-warning" onclick="return confirm('<?= $lang->get('confirm_scrape_all') ?>')">
                            <?= $lang->get('scrape_all_torrents') ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><?= $lang->get('current_statistics') ?></div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span><?= $lang->get('total_torrents') ?>:</span>
                            <strong><?= $totalTorrents ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><?= $lang->get('active_seeders') ?>:</span>
                            <strong><?= $activeSeeders ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><?= $lang->get('active_leechers') ?>:</span>
                            <strong><?= $activeLeechers ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><?= $lang->get('total_peers') ?>:</span>
                            <strong><?= $totalPeers ?></strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><?= $lang->get('recent_peers') ?></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th><?= $lang->get('torrent') ?></th>
                                    <th><?= $lang->get('peer_id') ?></th>
                                    <th><?= $lang->get('ip') ?></th>
                                    <th><?= $lang->get('port') ?></th>
                                    <th><?= $lang->get('type') ?></th>
                                    <th><?= $lang->get('last_announce') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->prepare("
                                    SELECT p.*, t.name as torrent_name
                                    FROM peers p
                                    JOIN torrents t ON p.torrent_id = t.id
                                    ORDER BY p.last_announce DESC
                                    LIMIT 20
                                ");
                                $stmt->execute();
                                $peers = $stmt->fetchAll();
                                
                                foreach ($peers as $peer):
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars(substr($peer['torrent_name'], 0, 30)) ?>...</td>
                                        <td><?= htmlspecialchars(substr($peer['peer_id'], 0, 10)) ?>...</td>
                                        <td><?= htmlspecialchars($peer['ip']) ?></td>
                                        <td><?= $peer['port'] ?></td>
                                        <td>
                                            <?php if ($peer['is_seeder']): ?>
                                                <span class="badge bg-success"><?= $lang->get('seeder') ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-warning"><?= $lang->get('leecher') ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('Y-m-d H:i:s', strtotime($peer['last_announce'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>