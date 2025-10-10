<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Взимаме основните форуми (без родител)
$stmt = $pdo->prepare("
    SELECT f.*, 
           (SELECT COUNT(*) FROM forums WHERE parent_id = f.id) as subforums_count,
           (SELECT username FROM users u JOIN forum_posts fp ON u.id = fp.user_id WHERE fp.id = f.last_post_id) as last_poster,
           (SELECT created_at FROM forum_posts WHERE id = f.last_post_id) as last_post_time
    FROM forums f
    WHERE f.parent_id IS NULL AND f.is_active = 1
    ORDER BY f.order
");
$stmt->execute();
$forums = $stmt->fetchAll();

// За всеки форум взимаме подфорумите
foreach ($forums as &$forum) {
    $stmt = $pdo->prepare("
        SELECT * FROM forums 
        WHERE parent_id = ? AND is_active = 1
        ORDER BY `order`
    ");
    $stmt->execute([$forum['id']]);
    $forum['subforums'] = $stmt->fetchAll();
}
unset($forum);

require_once __DIR__ . '/templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= $lang->get('forum') ?></h2>
    <?php if ($auth->isLoggedIn()): ?>
        <a href="/forum_create.php" class="btn btn-primary"><?= $lang->get('create_new_topic') ?></a>
    <?php endif; ?>
</div>

<?php if (empty($forums)): ?>
    <div class="alert alert-info"><?= $lang->get('no_forums_yet') ?></div>
<?php else: ?>
    <?php foreach ($forums as $forum): ?>
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <?php if ($forum['icon']): ?>
                        <img src="/<?= $forum['icon'] ?>" width="30" height="30" class="me-3">
                    <?php endif; ?>
                    <h3 class="mb-0"><?= htmlspecialchars($forum['name']) ?></h3>
                </div>
                <?php if ($forum['description']): ?>
                    <p class="mb-0 mt-2 text-muted"><?= htmlspecialchars($forum['description']) ?></p>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th><?= $lang->get('forum') ?></th>
                                <th><?= $lang->get('topics') ?></th>
                                <th><?= $lang->get('posts') ?></th>
                                <th><?= $lang->get('last_post') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Основен форум -->
                            <tr>
                                <td>
                                    <a href="/forum_view.php?id=<?= $forum['id'] ?>">
                                        <strong><?= htmlspecialchars($forum['name']) ?></strong>
                                    </a>
                                    <?php if ($forum['description']): ?>
                                        <div class="small text-muted"><?= htmlspecialchars($forum['description']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= $forum['topics_count'] ?></td>
                                <td><?= $forum['posts_count'] ?></td>
                                <td>
                                    <?php if ($forum['last_post_id']): ?>
                                        <small>
                                            <?= date('Y-m-d H:i', strtotime($forum['last_post_time'])) ?><br>
                                            <?= $lang->get('by') ?> <?= htmlspecialchars($forum['last_poster']) ?>
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted"><?= $lang->get('no_posts_yet') ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            
                            <!-- Подфоруми -->
                            <?php foreach ($forum['subforums'] as $subforum): ?>
                                <tr>
                                    <td style="padding-left: 50px;">
                                        <a href="/forum_view.php?id=<?= $subforum['id'] ?>">
                                            <?= htmlspecialchars($subforum['name']) ?>
                                        </a>
                                        <?php if ($subforum['description']): ?>
                                            <div class="small text-muted"><?= htmlspecialchars($subforum['description']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $subforum['topics_count'] ?></td>
                                    <td><?= $subforum['posts_count'] ?></td>
                                    <td>
                                        <?php if ($subforum['last_post_id']): ?>
                                            <small>
                                                <?= date('Y-m-d H:i', strtotime($subforum['last_post_time'])) ?><br>
                                                <?= $lang->get('by') ?> <?= htmlspecialchars($subforum['last_poster']) ?>
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
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/templates/footer.php'; ?>