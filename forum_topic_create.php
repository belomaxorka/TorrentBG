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

$forumId = (int)($_GET['forum_id'] ?? 0);
if (!$forumId) {
    die($lang->get('invalid_forum_id'));
}

// Взимаме форума
$stmt = $pdo->prepare("SELECT * FROM forums WHERE id = ? AND is_active = 1");
$stmt->execute([$forumId]);
$forum = $stmt->fetch();

if (!$forum) {
    die($lang->get('forum_not_found'));
}

$error = '';
$success = false;

if ($_POST['create'] ?? false) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (empty($title)) {
        $error = $lang->get('title_cannot_be_empty');
    } elseif (strlen($title) > 255) {
        $error = $lang->get('title_too_long');
    } elseif (empty($content)) {
        $error = $lang->get('post_cannot_be_empty');
    } elseif (strlen($content) > 5000) {
        $error = $lang->get('post_too_long');
    } else {
        try {
            $pdo->beginTransaction();

            // Създаваме темата
            $stmt = $pdo->prepare("
                INSERT INTO forum_topics (forum_id, user_id, title) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$forumId, $auth->getUser()['id'], $title]);
            $topicId = $pdo->lastInsertId();

            // Създаваме първото мнение
            $stmt = $pdo->prepare("
                INSERT INTO forum_posts (topic_id, user_id, content) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$topicId, $auth->getUser()['id'], $content]);
            $postId = $pdo->lastInsertId();

            // Обновяваме темата
            $pdo->prepare("
                UPDATE forum_topics 
                SET last_post_id = ? 
                WHERE id = ?
            ")->execute([$postId, $topicId]);

            // Обновяваме форума
            $pdo->prepare("
                UPDATE forums 
                SET topics_count = topics_count + 1,
                    posts_count = posts_count + 1,
                    last_post_id = ?
                WHERE id = ?
            ")->execute([$postId, $forumId]);

            $pdo->commit();
            $success = true;
            $_SESSION['success'] = $lang->get('topic_created');

        } catch (Exception $e) {
            $pdo->rollback();
            $error = $lang->get('topic_create_failed');
        }
    }
}

require_once __DIR__ . '/templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center"><?= $lang->get('create_new_topic') ?> - <?= htmlspecialchars($forum['name']) ?></h3>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?= $lang->get('topic_created_successfully') ?><br>
                        <a href="/forum_topic.php?id=<?= $topicId ?>"><?= $lang->get('view_topic') ?></a>
                    </div>
                <?php else: ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label"><?= $lang->get('title') ?> *</label>
                            <input type="text" name="title" class="form-control" required maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= $lang->get('content') ?> *</label>
                            <textarea name="content" id="editor" class="form-control" rows="10" required></textarea>
                            <div class="form-text"><?= $lang->get('bbc_codes_supported') ?></div>
                        </div>
                        <input type="hidden" name="create" value="1">
                        <button type="submit" class="btn btn-success w-100"><?= $lang->get('create_topic') ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- BBC Редактор -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('editor');
    
    function insertBBC(tag, content = null) {
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selectedText = textarea.value.substring(start, end);
        const textToInsert = content ? `[${tag}=${content}]${selectedText}[/${tag}]` : `[${tag}]${selectedText}[/${tag}]`;
        textarea.value = textarea.value.substring(0, start) + textToInsert + textarea.value.substring(end);
        textarea.focus();
    }

    const toolbar = document.createElement('div');
    toolbar.className = 'btn-toolbar mb-3';
    toolbar.innerHTML = `
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertBBC('b')">B</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertBBC('i')">I</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertBBC('u')">U</button>
        </div>
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertBBC('url', prompt('Enter URL:'))">URL</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertBBC('img', prompt('Enter image URL:'))">IMG</button>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#smilesModal">😊</button>
        </div>
    `;
    textarea.parentNode.insertBefore(toolbar, textarea);

    // Усмивки модален прозорец
    const smilesModal = document.createElement('div');
    smilesModal.className = 'modal fade';
    smilesModal.id = 'smilesModal';
    smilesModal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $lang->get('smiles') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="row g-2" id="smilesGrid">
                        <?php
                        $smiles = ['smile', 'wink', 'grin', 'tongue', 'laugh', 'sad', 'angry', 'shock', 'cool', 'blush'];
                        foreach ($smiles as $smile):
                            if (file_exists(__DIR__ . '/images/smiles/' . $smile . '.png')):
                        ?>
                            <div class="col-3 text-center">
                                <img src="/images/smiles/<?= $smile ?>.png" class="img-fluid smile-img" alt="<?= $smile ?>" style="cursor: pointer; max-height: 40px;">
                            </div>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(smilesModal);

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('smile-img')) {
            const smileCode = e.target.alt;
            insertBBC('smile', smileCode);
            bootstrap.Modal.getInstance(document.getElementById('smilesModal')).hide();
        }
    });
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>