<?php
require_once __DIR__ . '/includes/bootstrap.php';

// ✅ Вземи заявката за търсене
$searchQuery = trim($_GET['q'] ?? '');

// ✅ Подготви заявката
$sql = "
    SELECT 
        t.id, 
        t.name, 
        t.size, 
        t.seeders, 
        t.leechers, 
        t.uploaded_at,
        c.name as category_name,
        c.icon as category_icon,
        u.username as uploader_name,
        u.id as uploader_id
    FROM torrents t
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN users u ON t.uploader_id = u.id
";

$params = [];
if (!empty($searchQuery)) {
    $sql .= " WHERE t.name LIKE ? OR t.description LIKE ?";
    $searchTerm = '%' . str_replace(' ', '%', $searchQuery) . '%';
    $params = [$searchTerm, $searchTerm];
}

$sql .= " ORDER BY t.uploaded_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$torrents = $stmt->fetchAll();

require_once __DIR__ . '/templates/header.php';
?>

<style>
/* ✅ Стил за иконите на категориите */
.category-icon {
    width: 90px;
    height: 50px;
    object-fit: contain;
    vertical-align: middle;
    margin-right: 8px;
}

/* ✅ Tooltip стилове */
.torrent-tooltip {
    position: absolute;
    background: #2c2c2c;
    color: white;
    padding: 12px;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
    z-index: 10000;
    width: 240px;
    font-size: 13px;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.25s ease, visibility 0.25s ease;
    pointer-events: none;
}

.torrent-tooltip img {
    width: 100%;
    border-radius: 4px;
    margin-bottom: 8px;
    display: block;
}

.torrent-tooltip .placeholder {
    width: 100%;
    height: 120px;
    background: #444;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #aaa;
    border-radius: 4px;
    margin-bottom: 8px;
}

.torrent-tooltip .stats {
    line-height: 1.5;
}

.torrent-tooltip .seeds { color: #4caf50; }
.torrent-tooltip .leechers { color: #f44336; }
.torrent-tooltip .size { color: #2196f3; }

/* Стил за името на торента — предотвратява разместване */
.torrent-name-link {
    cursor: help;
    text-decoration: underline;
    color: #007bff;
    display: inline-block;
    max-width: 100%;
    word-break: break-word;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <?php if (!empty($searchQuery)): ?>
        <h2><?= $lang->get('search_results_for') ?> "<?= htmlspecialchars($searchQuery) ?>"</h2>
    <?php else: ?>
        <h2><?= $lang->get('torrents') ?></h2>
    <?php endif; ?>
    
    <?php if ($auth->isLoggedIn()): ?>
        <a href="/upload.php" class="btn btn-primary"><?= $lang->get('upload_torrent') ?></a>
    <?php endif; ?>
</div>

<?php if (empty($torrents)): ?>
    <div class="alert alert-info">
        <?php if (!empty($searchQuery)): ?>
            <?= $lang->get('no_results') ?>
        <?php else: ?>
            <?= $lang->get('no_torrents_yet') ?>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?= $lang->get('category') ?></th>
                    <th style="width: 35%; min-width: 200px;"><?= $lang->get('name') ?></th>
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
                        <td>
                            <a href="/torrent.php?id=<?= $t['id'] ?>" 
                               class="torrent-name-link"
                               data-torrent-id="<?= $t['id'] ?>">
                                <?= htmlspecialchars($t['name']) ?>
                            </a>
                        </td>
                        <!-- 📦 Размер -->
                        <td><?= formatBytes($t['size']) ?></td>
                        <!-- 🌱 Сийдъри -->
                        <td><?= $t['seeders'] ?></td>
                        <!-- 🐜 Лийчъри -->
                        <td><?= $t['leechers'] ?></td>
                        <!-- 👤 Качил -->
                        <td>
                            <?php if (!empty($t['uploader_name'])): ?>
                                <a href="/profile.php?id=<?= $t['uploader_id'] ?>" class="text-decoration-none">
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

<!-- 🎯 Глобален tooltip контейнер (в края на body) -->
<div id="global-tooltip" class="torrent-tooltip"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const links = document.querySelectorAll('.torrent-name-link');
    let hoverTimeout = null;
    const tooltip = document.getElementById('global-tooltip');
    // 🔤 Подаваме текущия език към AJAX заявката
    const currentLang = '<?= $_SESSION['lang'] ?? 'en' ?>';

    links.forEach(link => {
        link.addEventListener('mouseenter', () => {
            const torrentId = link.dataset.torrentId;

            hoverTimeout = setTimeout(() => {
                if (tooltip.dataset.currentId !== torrentId) {
                    // 💬 Включваме lang параметър в заявката
                    fetch(`/ajax/torrent_tooltip.php?id=${torrentId}&lang=${currentLang}`)
                        .then(response => response.text())
                        .then(html => {
                            tooltip.innerHTML = html;
                            tooltip.dataset.currentId = torrentId;
                            positionTooltip(tooltip, link);
                            tooltip.style.opacity = '1';
                            tooltip.style.visibility = 'visible';
                        })
                        .catch(err => {
                            console.error('Грешка при зареждане на tooltip:', err);
                        });
                } else {
                    positionTooltip(tooltip, link);
                    tooltip.style.opacity = '1';
                    tooltip.style.visibility = 'visible';
                }
            }, 400);
        });

        link.addEventListener('mouseleave', () => {
            clearTimeout(hoverTimeout);
            tooltip.style.opacity = '0';
            tooltip.style.visibility = 'hidden';
        });
    });

    function positionTooltip(tooltip, link) {
        const rect = link.getBoundingClientRect();
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;

        let top = rect.top + scrollTop - tooltip.offsetHeight - 10;
        if (top < 0) {
            top = rect.bottom + scrollTop + 10;
        }

        tooltip.style.top = top + 'px';
        tooltip.style.left = (rect.left + scrollLeft) + 'px';
    }
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>