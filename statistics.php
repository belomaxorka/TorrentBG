<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Основни статистики
$totalTorrents = $pdo->query("SELECT COUNT(*) FROM torrents")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalSeeders = $pdo->query("SELECT COUNT(*) FROM peers WHERE is_seeder = 1 AND last_announce >= NOW() - INTERVAL 30 MINUTE")->fetchColumn();
$totalLeechers = $pdo->query("SELECT COUNT(*) FROM peers WHERE is_seeder = 0 AND last_announce >= NOW() - INTERVAL 30 MINUTE")->fetchColumn();
$totalPeers = $totalSeeders + $totalLeechers;

// Торенти по категории
$stmt = $pdo->prepare("
    SELECT c.name, COUNT(t.id) as count
    FROM categories c
    LEFT JOIN torrents t ON c.id = t.category_id
    GROUP BY c.id, c.name
    ORDER BY count DESC
");
$stmt->execute();
$categoryStats = $stmt->fetchAll();

// Активни потребители (последните 24 часа)
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM users 
    WHERE last_login >= NOW() - INTERVAL 24 HOUR
");
$stmt->execute();
$activeUsers24h = $stmt->fetchColumn();

// Последни качени торенти
$stmt = $pdo->prepare("
    SELECT t.name, t.uploaded_at, u.username
    FROM torrents t
    JOIN users u ON t.uploader_id = u.id
    ORDER BY t.uploaded_at DESC
    LIMIT 10
");
$stmt->execute();
$recentTorrents = $stmt->fetchAll();

// Топ 10 торенти по сидъри
$stmt = $pdo->prepare("
    SELECT name, seeders, leechers
    FROM torrents
    ORDER BY seeders DESC
    LIMIT 10
");
$stmt->execute();
$topTorrents = $stmt->fetchAll();

require_once __DIR__ . '/templates/header.php';
?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h3><?= $lang->get('site_statistics') ?></h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card bg-primary text-white mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><?= $lang->get('total_torrents') ?></h5>
                                <p class="card-text display-4"><?= $totalTorrents ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-success text-white mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><?= $lang->get('total_users') ?></h5>
                                <p class="card-text display-4"><?= $totalUsers ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-info text-white mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><?= $lang->get('active_seeders') ?></h5>
                                <p class="card-text display-4"><?= $totalSeeders ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-warning text-dark mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><?= $lang->get('active_leechers') ?></h5>
                                <p class="card-text display-4"><?= $totalLeechers ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-danger text-white mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><?= $lang->get('total_peers') ?></h5>
                                <p class="card-text display-4"><?= $totalPeers ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h4><?= $lang->get('top_10_torrents_by_seeders') ?></h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th><?= $lang->get('name') ?></th>
                                <th><?= $lang->get('seeders') ?></th>
                                <th><?= $lang->get('leechers') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topTorrents as $torrent): ?>
                                <tr>
                                    <td><?= htmlspecialchars(substr($torrent['name'], 0, 50)) ?>...</td>
                                    <td><?= $torrent['seeders'] ?></td>
                                    <td><?= $torrent['leechers'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4><?= $lang->get('recent_torrents') ?></h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th><?= $lang->get('name') ?></th>
                                <th><?= $lang->get('uploader') ?></th>
                                <th><?= $lang->get('uploaded_at') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTorrents as $torrent): ?>
                                <tr>
                                    <td><?= htmlspecialchars(substr($torrent['name'], 0, 50)) ?>...</td>
                                    <td><?= htmlspecialchars($torrent['username']) ?></td>
                                    <td><?= date('Y-m-d H:i', strtotime($torrent['uploaded_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h4><?= $lang->get('torrents_by_category') ?></h4>
            </div>
            <div class="card-body">
                <canvas id="categoryChart" height="300"></canvas>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4><?= $lang->get('active_users_24h') ?></h4>
            </div>
            <div class="card-body text-center">
                <h2 class="display-4"><?= $activeUsers24h ?></h2>
                <p class="text-muted"><?= $lang->get('users_active_last_24_hours') ?></p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_column($categoryStats, 'name')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($categoryStats, 'count')) ?>,
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#C9CBCF', '#7CFC00', '#FF1493', '#00BFFF'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>