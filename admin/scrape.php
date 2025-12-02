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
                $seeders = $pdo->prepare("SELECT COUNT(*) FROM peers WHERE torrent_id = ? AND seeder = 1 AND last_announce >= NOW() - INTERVAL 30 MINUTE");
                $seeders->execute([$torrent['id']]);
                $seederCount = $seeders->fetchColumn();

                $leechers = $pdo->prepare("SELECT COUNT(*) FROM peers WHERE torrent_id = ? AND seeder = 0 AND last_announce >= NOW() - INTERVAL 30 MINUTE");
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
$activeSeeders = $pdo->query("SELECT COUNT(*) FROM peers WHERE seeder = 1 AND last_announce >= NOW() - INTERVAL 30 MINUTE")->fetchColumn();
$activeLeechers = $pdo->query("SELECT COUNT(*) FROM peers WHERE seeder = 0 AND last_announce >= NOW() - INTERVAL 30 MINUTE")->fetchColumn();
$totalPeers = $activeSeeders + $activeLeechers;

require_once __DIR__ . '/../templates/header.php';
?>

<style>
/* Тъмна навигация */
.navbar {
    background-color: #212529 !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.navbar-brand,
.navbar-nav .nav-link {
    color: #dee2e6 !important;
}

.navbar-nav .nav-link:hover,
.navbar-nav .nav-link.active {
    color: #ffffff !important;
    background-color: #495057;
}

/* Заглавие */
.admin-panel-title {
    background-color: #0d6efd;
    color: white;
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    border-radius: 4px;
    font-weight: bold;
    font-size: 1.3rem;
}

/* Карти */
.admin-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    margin-bottom: 1rem;
    overflow: hidden;
}

/* Цветни заглавия */
.card-header.bg-primary { background-color: #0d6efd !important; }
.card-header.bg-success { background-color: #28a745 !important; }
.card-header.bg-info { background-color: #0dcaf0 !important; }
.card-header.bg-warning { background-color: #ffc107 !important; color: #212529 !important; }
.card-header.bg-danger { background-color: #dc3545 !important; }
.card-header.bg-secondary { background-color: #6c757d !important; }
.card-header.bg-dark { background-color: #212529 !important; }

/* Пурпурен цвят */
.bg-purple {
    background-color: #6f42c1 !important;
}
.btn-purple {
    background-color: #6f42c1;
    color: white;
    border-color: #6f42c1;
}
.btn-purple:hover {
    background-color: #5a35a3;
    border-color: #543196;
}
</style>

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
                                            <?php if ($peer['seeder']): ?>
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