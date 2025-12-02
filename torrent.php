<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Language.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = Database::getInstance();
$auth = new Auth($pdo);
$lang = new Language($_SESSION['lang'] ?? 'en');

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    die($lang->get('invalid_torrent_id'));
}

$stmt = $pdo->prepare("
    SELECT t.*, u.username as uploader_name,
           (SELECT AVG(rating) FROM torrent_ratings WHERE torrent_id = t.id) as avg_rating,
           (SELECT COUNT(*) FROM torrent_ratings WHERE torrent_id = t.id) as rating_count
    FROM torrents t
    JOIN users u ON t.uploader_id = u.id
    WHERE t.id = ?
");
$stmt->execute([$id]);
$torrent = $stmt->fetch();

if (!$torrent) {
    die($lang->get('torrent_not_found'));
}

// === IMDb информация — с реален API ключ от базата ===
$imdbInfo = null;
$imdbId = null;
if (!empty($torrent['imdb_link'])) {
    // Извличаме IMDb ID (tt1234567)
    if (preg_match('/(tt\d+)/', $torrent['imdb_link'], $matches)) {
        $imdbId = $matches[1];
        
        // Вземи API ключа от базата
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE name = 'omdb_api_key'");
        $stmt->execute();
        $omdbApiKey = $stmt->fetchColumn();

        if (!empty($omdbApiKey)) {
            $url = "https://www.omdbapi.com/?i={$imdbId}&apikey=" . urlencode($omdbApiKey) . "&plot=full";
            $response = @file_get_contents($url);
            
            if ($response) {
                $data = json_decode($response, true);
                if ($data && ($data['Response'] ?? '') === 'True') {
                    $imdbInfo = [
                        'title' => $data['Title'] ?? 'N/A',
                        'year' => $data['Year'] ?? 'N/A',
                        'rating' => ($data['imdbRating'] ?? 'N/A') . '/10',
                        'genre' => $data['Genre'] ?? 'N/A',
                        'description' => $data['Plot'] ?? $lang->get('no_description'),
                        'poster' => $data['Poster'] ?? null,
                        'director' => $data['Director'] ?? 'N/A',
                        'actors' => $data['Actors'] ?? 'N/A',
                    ];
                }
            }
        }
    }
}

// === YouTube ===
$youtubeEmbed = '';
if ($torrent['youtube_link']) {
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $torrent['youtube_link'], $matches)) {
        $youtubeEmbed = '<iframe width="560" height="315" src="https://www.youtube.com/embed/' . $matches[1] . '" frameborder="0" allowfullscreen></iframe>';
    }
}

// === Рейтинг ===
$userRating = 0;
if ($auth->isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT rating FROM torrent_ratings WHERE torrent_id = ? AND user_id = ?");
    $stmt->execute([$id, $auth->getUser()['id']]);
    $rating = $stmt->fetch();
    $userRating = $rating ? $rating['rating'] : 0;
}

// === Коментари ===
$stmt = $pdo->prepare("
    SELECT tc.*, u.username, u.rank
    FROM torrent_comments tc
    JOIN users u ON tc.user_id = u.id
    WHERE tc.torrent_id = ?
    ORDER BY tc.created_at DESC
");
$stmt->execute([$id]);
$comments = $stmt->fetchAll();

require_once __DIR__ . '/templates/header.php';
?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h3><?= htmlspecialchars($torrent['name']) ?></h3>
            </div>
            <div class="card-body">
                <?php if ($youtubeEmbed): ?>
                    <div class="mb-4">
                        <?= $youtubeEmbed ?>
                    </div>
                <?php endif; ?>

                <?php if ($imdbInfo): ?>
                    <div class="card mb-4">
                        <div class="card-header"><?= $lang->get('movie_info') ?> (IMDb)</div>
                        <div class="card-body">
                            <?php if (!empty($imdbInfo['poster']) && $imdbInfo['poster'] !== 'N/A'): ?>
                                <div class="text-center mb-3">
                                    <img src="<?= htmlspecialchars($imdbInfo['poster']) ?>" class="img-fluid rounded" style="max-height: 300px; object-fit: cover;">
                                </div>
                            <?php endif; ?>

                            <h5><?= htmlspecialchars($imdbInfo['title']) ?> (<?= htmlspecialchars($imdbInfo['year']) ?>)</h5>
                            <p><strong><?= $lang->get('imdb_rating') ?>:</strong> <?= htmlspecialchars($imdbInfo['rating']) ?></p>
                            <p><strong><?= $lang->get('imdb_genre') ?>:</strong> <?= htmlspecialchars($imdbInfo['genre']) ?></p>
                            <p><strong><?= $lang->get('imdb_director') ?>:</strong> <?= htmlspecialchars($imdbInfo['director']) ?></p>
                            <p><strong><?= $lang->get('imdb_actors') ?>:</strong> <?= htmlspecialchars($imdbInfo['actors']) ?></p>
                            <p><?= nl2br(htmlspecialchars($imdbInfo['description'])) ?></p>
                            <a href="https://www.imdb.com/title/<?= $imdbId ?>/" target="_blank" class="btn btn-sm btn-warning">
                                <i class="bi bi-film"></i> <?= $lang->get('view_on_imdb') ?>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($torrent['poster']): ?>
                    <div class="text-center mb-4">
                        <img src="/<?= $torrent['poster'] ?>" class="img-fluid rounded" alt="<?= htmlspecialchars($torrent['name']) ?>">
                    </div>
                <?php endif; ?>

                <div class="mb-4">
                    <h5><?= $lang->get('description') ?></h5>
                    <div class="bg-light p-3 rounded">
                        <?= parseBBC($torrent['description'] ?? $lang->get('no_description')) ?>
                    </div>
                </div>

                <!-- Рейтинг -->
                <div class="mb-4">
                    <h5><?= $lang->get('rating') ?> (<?= $torrent['rating_count'] ?> <?= $lang->get('votes') ?>)</h5>
                    <div class="rating-system">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star <?= $i <= round($torrent['avg_rating'] ?? 0) ? 'filled' : '' ?>" 
                                  data-rating="<?= $i ?>"
                                  <?= $auth->isLoggedIn() ? 'style="cursor: pointer;"' : '' ?>>
                                ★
                            </span>
                        <?php endfor; ?>
                        <?php if ($auth->isLoggedIn()): ?>
                            <span class="ms-3" id="rating-value"><?= $userRating ? $lang->get('your_rating') . ': ' . $userRating : $lang->get('click_to_rate') ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mb-4">
                    <h5><?= $lang->get('file_info') ?></h5>
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between">
                            <span><?= $lang->get('size') ?>:</span>
                            <strong><?= formatBytes($torrent['size']) ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><?= $lang->get('files') ?>:</span>
                            <strong><?= $torrent['files_count'] ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><?= $lang->get('uploaded_by') ?>:</span>
                            <strong><?= htmlspecialchars($torrent['uploader_name']) ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><?= $lang->get('uploaded_at') ?>:</span>
                            <strong><?= date('Y-m-d H:i', strtotime($torrent['uploaded_at'])) ?></strong>
                        </li>
                    </ul>
                </div>

                <a href="/download.php?id=<?= $torrent['id'] ?>" class="btn btn-success"><?= $lang->get('download_torrent') ?></a>
            </div>
        </div>

        <!-- Коментари -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?= $lang->get('comments') ?> (<?= count($comments) ?>)</h5>
                <?php if ($auth->isLoggedIn()): ?>
                    <p class="mb-0 mt-2 text-muted"><?= $lang->get('add_your_comment') ?></p>
                <?php endif; ?>
            </div>

            <?php if ($auth->isLoggedIn()): ?>
                <div class="card-body border-bottom">
                    <form method="POST" action="/comment_add.php">
                        <input type="hidden" name="torrent_id" value="<?= $torrent['id'] ?>">
                        <div class="mb-3">
                            <textarea name="comment" class="form-control" rows="3" placeholder="<?= $lang->get('write_comment') ?>" required></textarea>
                            <div class="form-text"><?= $lang->get('bbc_codes_supported') ?></div>
                        </div>
                        <button type="submit" class="btn btn-primary"><?= $lang->get('post_comment') ?></button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="card-body">
                <?php if (empty($comments)): ?>
                    <div class="alert alert-info"><?= $lang->get('no_comments_yet') ?></div>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong><?= htmlspecialchars($comment['username']) ?></strong>
                                        <small class="text-muted ms-2"><?= date('Y-m-d H:i', strtotime($comment['created_at'])) ?></small>
                                        <?php if ($comment['is_edited']): ?>
                                            <small class="text-muted">(<?= $lang->get('edited') ?>)</small>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if ($auth->isLoggedIn() && ($auth->getUser()['id'] == $comment['user_id'] || $auth->getRank() >= 5)): ?>
                                            <button class="btn btn-sm btn-outline-secondary edit-comment-btn" data-id="<?= $comment['id'] ?>">✏️</button>
                                            <form method="POST" action="/comment_delete.php" style="display:inline;" onsubmit="return confirm('<?= $lang->get('confirm_delete') ?>')">
                                                <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">🗑️</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="mt-2 comment-content">
                                    <?= parseBBC($comment['comment']) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header"><?= $lang->get('statistics') ?></div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= $lang->get('seeders') ?>:</span>
                        <strong><?= $torrent['seeders'] ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= $lang->get('leechers') ?>:</span>
                        <strong><?= $torrent['leechers'] ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= $lang->get('completed') ?>:</span>
                        <strong><?= $torrent['completed'] ?></strong>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Модален прозорец за редакция на коментар -->
<div class="modal fade" id="editCommentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/comment_edit.php">
                <input type="hidden" name="comment_id" id="edit-comment-id">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $lang->get('edit_comment') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <textarea name="comment" id="edit-comment-text" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $lang->get('cancel') ?></button>
                    <button type="submit" class="btn btn-primary"><?= $lang->get('save') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.star {
    color: #ddd;
    font-size: 1.5em;
    transition: color 0.2s;
}
.star.filled {
    color: #ffc107;
}
.star:hover {
    color: #ff9800;
}
</style>

<script>
// Рейтинг
document.querySelectorAll('.star').forEach(star => {
    star.addEventListener('click', function() {
        const rating = this.getAttribute('data-rating');
        fetch('/rate_torrent.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'torrent_id=<?= $torrent['id'] ?>&rating=' + rating
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error);
            }
        });
    });
});

// Редакция на коментар
document.querySelectorAll('.edit-comment-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const commentText = this.closest('.card').querySelector('.comment-content').textContent;
        document.getElementById('edit-comment-id').value = id;
        document.getElementById('edit-comment-text').value = commentText;
        const modal = new bootstrap.Modal(document.getElementById('editCommentModal'));
        modal.show();
    });
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>