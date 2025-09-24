<?php
if (!defined('IN_BLOCK')) die('Direct access not allowed.');

$stmt = $pdo->prepare("
    SELECT p.id, p.question, p.description, p.created_at, u.username as creator
    FROM polls p
    JOIN users u ON p.created_by = u.id
    WHERE p.is_active = 1
    ORDER BY p.created_at DESC
    LIMIT 1
");
$stmt->execute();
$poll = $stmt->fetch();

if (!$poll) {
    echo '<div class="alert alert-info">' . $lang->get('no_active_poll') . '</div>';
    return;
}

// Взимаме опциите
$stmt = $pdo->prepare("SELECT id, option_text, votes FROM poll_options WHERE poll_id = ? ORDER BY id");
$stmt->execute([$poll['id']]);
$options = $stmt->fetchAll();

// Общ брой гласове
$totalVotes = array_sum(array_column($options, 'votes'));

// Проверка дали потребителят е гласувал
$voted = false;
$hasVotedOption = null;
if ($auth->isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT option_id FROM poll_votes WHERE poll_id = ? AND user_id = ?");
    $stmt->execute([$poll['id'], $auth->getUser()['id']]);
    $vote = $stmt->fetch();
    if ($vote) {
        $voted = true;
        $hasVotedOption = $vote['option_id'];
    }
}
?>

<div class="card mb-4">
    <div class="card-header">
        <?= $lang->get('poll') ?>
    </div>
    <div class="card-body">
        <h5 class="card-title"><?= htmlspecialchars($poll['question']) ?></h5>
        <?php if ($poll['description']): ?>
            <p class="text-muted"><?= htmlspecialchars($poll['description']) ?></p>
        <?php endif; ?>
        <p class="small text-muted"><?= $lang->get('created_by') ?> <?= htmlspecialchars($poll['creator']) ?>, <?= $lang->get('total_votes') ?>: <?= $totalVotes ?></p>

        <?php if ($voted): ?>
            <!-- Показваме резултати -->
            <div class="progress-group">
                <?php foreach ($options as $opt): ?>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span><?= htmlspecialchars($opt['option_text']) ?></span>
                            <span><?= $opt['votes'] ?> (<?= $totalVotes ? round(($opt['votes']/$totalVotes)*100) : 0 ?>%)</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-primary" style="width: <?= $totalVotes ? (($opt['votes']/$totalVotes)*100) : 0 ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-3 alert alert-success small">
                <?= $lang->get('you_voted') ?>
            </div>
        <?php else: ?>
            <!-- Показваме форма за гласуване -->
            <form method="POST" action="/poll_vote.php">
                <input type="hidden" name="poll_id" value="<?= $poll['id'] ?>">
                <div class="mb-3">
                    <?php foreach ($options as $opt): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="option_id" id="opt<?= $opt['id'] ?>" value="<?= $opt['id'] ?>" required>
                            <label class="form-check-label" for="opt<?= $opt['id'] ?>">
                                <?= htmlspecialchars($opt['option_text']) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (!$auth->isLoggedIn()): ?>
                    <div class="alert alert-warning small">
                        <?= $lang->get('login_to_vote') ?>
                    </div>
                <?php else: ?>
                    <button type="submit" class="btn btn-primary btn-sm"><?= $lang->get('vote') ?></button>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
</div>