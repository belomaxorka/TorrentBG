<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Language.php';

$pdo = Database::getInstance();
$auth = new Auth($pdo);
$lang = new Language($_SESSION['lang'] ?? 'en');

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    die($lang->get('invalid_forum_id'));
}

// Взимаме форума
$stmt = $pdo->prepare("SELECT * FROM forums WHERE id = ? AND is_active = 1");
$stmt->execute([$id]);
$forum = $stmt->fetch();

if (!$forum) {
    die($lang->get('forum_not_found'));
}

// Взимаме темите
$stmt = $pdo->prepare("
    SELECT ft.*, u.username as author_name,
           (SELECT username FROM users u2 JOIN forum_posts fp ON u2.id = fp.user_id WHERE fp.id = ft.last_post_id) as last_poster
    FROM forum_topics ft
    JOIN users u ON ft.user_id = u.id
    WHERE ft.forum_id = ?
    ORDER BY ft.is_sticky DESC, ft.updated_at DESC
");
$stmt->execute([$id]);
$topics = $stmt->fetchAll();

require_once __DIR__ . '/templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= htmlspecialchars($forum['name']) ?></h2>
    <?php if ($auth->isLoggedIn()): ?>
        <a href="/forum_topic_create.php?forum_id=<?= $forum['id'] ?>" class="btn btn-primary"><?= $lang->get('create_new_topic') ?></a>
    <?php endif; ?>
</div>

<?php if ($forum['description']): ?>
    <div class="alert alert-info"><?= htmlspecialchars($forum['description']) ?></div>
<?php endif; ?>

<?php if (empty($topics)): ?>
    <div class="alert alert-info"><?= $lang->get('no_topics_yet') ?></div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th><?= $lang->get('topic') ?></th>
                    <th><?= $lang->get('author') ?></th>
                    <th><?= $lang->get('replies') ?></th>
                    <th><?= $lang->get('views') ?></th>
                    <th><?= $lang->get('last_post') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topics as $topic): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <?php if ($topic['is_sticky']): ?>
                                    <span class="badge bg-warning me-2">📌</span>
                                <?php endif; ?>
                                <?php if ($topic['is_locked']): ?>
                                    <span class="badge bg-secondary me-2">🔒</span>
                                <?php endif; ?>
                                <a href="/forum_topic.php?id=<?= $topic['id'] ?>">
                                    <?= htmlspecialchars($topic['title']) ?>
                                </a>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($topic['author_name']) ?></td>
                        <td><?= $topic['replies'] ?></td>
                        <td><?= $topic['views'] ?></td>
                        <td>
                            <?php if ($topic['last_post_id']): ?>
                                <small>
                                    <?= date('Y-m-d H:i', strtotime($topic['updated_at'])) ?><br>
                                    <?= $lang->get('by') ?> <?= htmlspecialchars($topic['last_poster']) ?>
                                </small>
                            <?php else: ?>
                                <small class="text-muted"><?= $lang->get('no_posts_yet') ?></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/templates/footer.php'; ?>