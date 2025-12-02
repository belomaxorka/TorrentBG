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

// ✅ Вземи заявката за търсене
$searchQuery = trim($_GET['q'] ?? '');
$categoryIds = $_GET['categories'] ?? [];
$filterTitle = isset($_GET['filter_title']) ? 1 : 0;
$filterSubs = isset($_GET['filter_subs']) ? 1 : 0;
$filterAudio = isset($_GET['filter_audio']) ? 1 : 0;
$filterAuthor = isset($_GET['filter_author']) ? 1 : 0;

// ✅ Извлечи всички активни категории
$stmt = $pdo->query("SELECT id, name, icon FROM categories WHERE is_active = 1 ORDER BY `order`");
$categories = $stmt->fetchAll();

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

$where = "WHERE 1=1";
$params = [];

if (!empty($searchQuery)) {
    $where .= " AND (t.name LIKE ? OR t.description LIKE ? OR u.username LIKE ?)";
    $searchTerm = '%' . str_replace(' ', '%', $searchQuery) . '%';
    $params = [$searchTerm, $searchTerm, $searchTerm];
}

if (!empty($categoryIds)) {
    $placeholders = str_repeat('?,', count($categoryIds) - 1) . '?';
    $where .= " AND t.category_id IN ($placeholders)";
    $params = array_merge($params, $categoryIds);
}

if ($filterTitle) {
    $where .= " AND t.name LIKE ?";
    $params[] = "%$searchQuery%";
}
if ($filterSubs) {
    $where .= " AND t.description LIKE ?";
    $params[] = "%[SUBS]%";
}
if ($filterAudio) {
    $where .= " AND t.description LIKE ?";
    $params[] = "%[AUDIO]%";
}
if ($filterAuthor) {
    $where .= " AND u.username LIKE ?";
    $params[] = "%$searchQuery%";
}

$sql .= " $where ORDER BY t.uploaded_at DESC";

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

/* 📁 СТИЛ ЗА КАТЕГОРИИ — 6 НА КОЛОНА, ВЕРТИКАЛНО */
.category-grid-container {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.category-column {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 200px;
}

.category-checkbox {
    display: block;
    padding: 0.25rem 0.5rem;
    cursor: pointer;
}

.category-checkbox input[type="checkbox"] {
    margin-right: 0.5rem;
}

.category-checkbox:hover {
    background: #e9ecef;
    border-radius: 4px;
}

/* 🎯 Филтри + търсачка в една линия */
.filter-bar {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.filter-bar .filter-options {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin: 0;
}

.filter-bar .filter-options label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-size: 0.9rem;
}

.filter-bar .search-box {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    margin-left: auto;
}

.filter-bar .search-box .form-control {
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
    height: auto;
}

.filter-bar .search-box .btn {
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
    height: auto;
}

/* Мобилен вид */
@media (max-width: 767px) {
    .filter-bar {
        flex-direction: column;
        align-items: stretch;
    }
    .filter-bar .search-box {
        margin-left: 0;
        width: 100%;
        justify-content: flex-end;
    }
    .category-grid-container {
        flex-direction: column;
    }
    .category-column {
        min-width: auto;
    }
}
</style>

<div class="container">
    <h2 class="mb-4"><?= $lang->get('torrents') ?></h2>

    <!-- 🔍 ФОРМА ЗА ТЪРСЕНЕ И ФИЛТРИ -->
    <form method="GET" class="mb-4">
        <!-- 📁 КАТЕГОРИИ: 6 на колона, вертикално подреждане с PHP -->
        <div class="category-grid-container">
            <?php
            $columns = [];
            $colIndex = 0;
            $MAX_PER_COLUMN = 6;

            foreach ($categories as $cat) {
                if (!isset($columns[$colIndex])) {
                    $columns[$colIndex] = [];
                }
                $columns[$colIndex][] = $cat;
                if (count($columns[$colIndex]) >= $MAX_PER_COLUMN) {
                    $colIndex++;
                }
            }

            foreach ($columns as $column): ?>
                <div class="category-column">
                    <?php foreach ($column as $cat): ?>
                        <div class="category-checkbox">
                            <label>
                                <input type="checkbox" name="categories[]" value="<?= $cat['id'] ?>"
                                    <?= in_array($cat['id'], $categoryIds) ? 'checked' : '' ?>>
                                <?php if ($cat['icon']): ?>
                                    <img src="/<?= htmlspecialchars($cat['icon']) ?>" width="16" height="16" class="me-1">
                                <?php endif; ?>
                                <?= htmlspecialchars($cat['name']) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- 🎯 ФИЛТРИ + ТЪРСАЧКА В ЕДНА ЛИНИЯ -->
        <div class="filter-bar">
            <div class="filter-options">
                <label>
                    <input type="radio" name="filter_title" value="1" <?= $filterTitle ? 'checked' : '' ?>>
                    <?= $lang->get('title') ?>
                </label>
                <label>
                    <input type="radio" name="filter_subs" value="1" <?= $filterSubs ? 'checked' : '' ?>>
                    <?= $lang->get('subs') ?>
                </label>
                <label>
                    <input type="radio" name="filter_audio" value="1" <?= $filterAudio ? 'checked' : '' ?>>
                    <?= $lang->get('audio') ?>
                </label>
                <label>
                    <input type="radio" name="filter_author" value="1" <?= $filterAuthor ? 'checked' : '' ?>>
                    <?= $lang->get('shared_by_author') ?>
                </label>
            </div>

            <div class="search-box">
                <span class="input-group-text bg-light">🔍</span>
                <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="<?= $lang->get('search_placeholder') ?>">
                <button type="submit" class="btn btn-primary"><?= $lang->get('search_button') ?></button>
            </div>
        </div>
    </form>

    <!-- 📋 РЕЗУЛТАТИ -->
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
</div>

<!-- 🎯 Глобален tooltip контейнер (в края на body) -->
<div id="global-tooltip" class="torrent-tooltip"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const links = document.querySelectorAll('.torrent-name-link');
    let hoverTimeout = null;
    const tooltip = document.getElementById('global-tooltip');
    const currentLang = '<?= $_SESSION['lang'] ?? 'en' ?>';

    links.forEach(link => {
        link.addEventListener('mouseenter', () => {
            const torrentId = link.dataset.torrentId;

            hoverTimeout = setTimeout(() => {
                if (tooltip.dataset.currentId !== torrentId) {
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