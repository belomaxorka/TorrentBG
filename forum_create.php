<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Language.php';

$pdo = Database::getInstance();
$auth = new Auth($pdo);
$lang = new Language($_SESSION['lang'] ?? 'en');

if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Взимаме всички активни форуми
$stmt = $pdo->prepare("
    SELECT f.*, 
           (SELECT COUNT(*) FROM forums WHERE parent_id = f.id) as subforums_count
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

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center"><?= $lang->get('select_forum_for_new_topic') ?></h3>
            </div>
            <div class="card-body">
                <?php if (empty($forums)): ?>
                    <div class="alert alert-info"><?= $lang->get('no_forums_available') ?></div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($forums as $forum): ?>
                            <a href="/forum_topic_create.php?forum_id=<?= $forum['id'] ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1"><?= htmlspecialchars($forum['name']) ?></h5>
                                    <?php if ($forum['subforums_count'] > 0): ?>
                                        <small><?= $forum['subforums_count'] ?> <?= $lang->get('subforums') ?></small>
                                    <?php endif; ?>
                                </div>
                                <?php if ($forum['description']): ?>
                                    <p class="mb-1"><?= htmlspecialchars($forum['description']) ?></p>
                                <?php endif; ?>
                            </a>
                            
                            <?php foreach ($forum['subforums'] as $subforum): ?>
                                <a href="/forum_topic_create.php?forum_id=<?= $subforum['id'] ?>" class="list-group-item list-group-item-action ps-5">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?= htmlspecialchars($subforum['name']) ?></h6>
                                    </div>
                                    <?php if ($subforum['description']): ?>
                                        <p class="mb-1"><?= htmlspecialchars($subforum['description']) ?></p>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>