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
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST">

                        <!-- Поле за заглавие -->
                        <div class="mb-3">
                            <label class="form-label"><?= $lang->get('title') ?> *</label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required maxlength="255">
                        </div>

                        <!-- BBC Редактор (копиран от upload.php) -->
                        <div class="mb-3">
                            <label class="form-label"><?= $lang->get('content') ?> *</label>

                            <!-- BBC инструменти -->
                            <div class="bb-editor-toolbar mb-2 p-2 border rounded bg-light d-flex flex-wrap gap-1">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertBBCode('[b]', '[/b]')" title="<?= $lang->get('bold') ?>"><?= $lang->get('bold') ?></button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertBBCode('[i]', '[/i]')" title="<?= $lang->get('italic') ?>"><?= $lang->get('italic') ?></button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertBBCode('[u]', '[/u]')" title="<?= $lang->get('underline') ?>"><?= $lang->get('underline') ?></button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertBBCode('[s]', '[/s]')" title="<?= $lang->get('strikethrough') ?>"><?= $lang->get('strikethrough') ?></button>
                                </div>

                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertBBCode('[quote]', '[/quote]')" title="<?= $lang->get('quote') ?>"><?= $lang->get('quote') ?></button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertBBCode('[img]', '[/img]')" title="<?= $lang->get('img') ?>"><?= $lang->get('img') ?></button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertBBCode('[url]', '[/url]')" title="<?= $lang->get('url') ?>"><?= $lang->get('url') ?></button>
                                </div>

                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertBBCode('[code]', '[/code]')" title="<?= $lang->get('code') ?>"><?= $lang->get('code') ?></button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertBBCode('[list]', '[/list]')" title="<?= $lang->get('list') ?>"><?= $lang->get('list') ?></button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertBBCode('[*]', '')" title="<?= $lang->get('list_item') ?>">•</button>
                                </div>

                                <!-- Падащи менюта -->
                                <div class="btn-group btn-group-sm">
                                    <select class="form-select form-select-sm" onchange="applyFontFace(this.value); this.selectedIndex = 0;" title="<?= $lang->get('font') ?>">
                                        <option value=""><?= $lang->get('font') ?>...</option>
                                        <option value="Arial">Arial</option>
                                        <option value="Courier">Courier</option>
                                        <option value="Georgia">Georgia</option>
                                        <option value="Tahoma">Tahoma</option>
                                        <option value="Times New Roman">Times New Roman</option>
                                        <option value="Verdana">Verdana</option>
                                        <option value="Comic Sans MS">Comic Sans MS</option>
                                    </select>
                                </div>

                                <div class="btn-group btn-group-sm">
                                    <select class="form-select form-select-sm" onchange="applyFontColor(this.value); this.selectedIndex = 0;" title="<?= $lang->get('color') ?>">
                                        <option value=""><?= $lang->get('color') ?>...</option>
                                        <option value="red"><?= $lang->get('red') ?></option>
                                        <option value="green"><?= $lang->get('green') ?></option>
                                        <option value="blue"><?= $lang->get('blue') ?></option>
                                        <option value="orange"><?= $lang->get('orange') ?></option>
                                        <option value="purple"><?= $lang->get('purple') ?></option>
                                        <option value="brown"><?= $lang->get('brown') ?></option>
                                        <option value="black"><?= $lang->get('black') ?></option>
                                        <option value="gray"><?= $lang->get('gray') ?></option>
                                        <option value="navy"><?= $lang->get('navy') ?></option>
                                        <option value="maroon"><?= $lang->get('maroon') ?></option>
                                    </select>
                                </div>

                                <div class="btn-group btn-group-sm">
                                    <select class="form-select form-select-sm" onchange="applyFontSize(this.value); this.selectedIndex = 0;" title="<?= $lang->get('size') ?>">
                                        <option value=""><?= $lang->get('size') ?>...</option>
                                        <option value="xx-small"><?= $lang->get('xx_small') ?></option>
                                        <option value="x-small"><?= $lang->get('x_small') ?></option>
                                        <option value="small"><?= $lang->get('small') ?></option>
                                        <option value="medium"><?= $lang->get('medium') ?></option>
                                        <option value="large"><?= $lang->get('large') ?></option>
                                        <option value="x-large"><?= $lang->get('x_large') ?></option>
                                        <option value="xx-large"><?= $lang->get('xx_large') ?></option>
                                    </select>
                                </div>

                                <div class="btn-group btn-group-sm">
                                    <select class="form-select form-select-sm" onchange="applyTextAlign(this.value); this.selectedIndex = 0;" title="<?= $lang->get('align') ?>">
                                        <option value=""><?= $lang->get('align') ?>...</option>
                                        <option value="left"><?= $lang->get('left') ?></option>
                                        <option value="center"><?= $lang->get('center') ?></option>
                                        <option value="right"><?= $lang->get('right') ?></option>
                                        <option value="justify"><?= $lang->get('justify') ?></option>
                                    </select>
                                </div>

                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertBBCode('[sup]', '[/sup]')" title="<?= $lang->get('superscript') ?>"><?= $lang->get('sup') ?></button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertBBCode('[sub]', '[/sub]')" title="<?= $lang->get('subscript') ?>"><?= $lang->get('sub') ?></button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertBBCode('[spoiler]', '[/spoiler]')" title="<?= $lang->get('spoiler') ?>"><?= $lang->get('spoiler') ?></button>
                                </div>

                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertBBCode('[acronym]', '[/acronym]')" title="<?= $lang->get('acronym') ?>"><?= $lang->get('acronym') ?></button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertBBCode('[pre]', '[/pre]')" title="<?= $lang->get('pre') ?>"><?= $lang->get('pre') ?></button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertBBCode('[box]', '[/box]')" title="<?= $lang->get('box') ?>"><?= $lang->get('box') ?></button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertBBCode('[info]', '[/info]')" title="<?= $lang->get('info') ?>"><?= $lang->get('info') ?></button>
                                </div>

                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#smilesModal" title="<?= $lang->get('smiles') ?>">😊</button>
                                </div>
                            </div>

                            <textarea name="content" id="description" class="form-control" rows="8" required><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
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

<!-- Усмивки модален прозорец -->
<div class="modal fade" id="smilesModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= $lang->get('smiles') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= $lang->get('close') ?>"></button>
            </div>
            <div class="modal-body p-3">
                <div class="row g-2" id="smilesGrid">
                    <?php
                    $smiles = ['smile', 'wink', 'grin', 'tongue', 'laugh', 'sad', 'angry', 'shock', 'cool', 'blush'];
                    foreach ($smiles as $smile):
                        if (file_exists(__DIR__ . '/images/smiles/' . $smile . '.gif')):
                    ?>
                        <div class="col-3 text-center">
                            <img src="/images/smiles/<?= $smile ?>.gif" class="img-fluid smile-img" alt="<?= $smile ?>" style="cursor: pointer; max-height: 40px;" title="<?= $lang->get($smile) ?>">
                        </div>
                    <?php
                        endif;
                    endforeach;
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript за BBC редактора (същият като в upload.php) -->
<script>
function insertBBCode(openTag, closeTag) {
    const textarea = document.getElementById('description');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = textarea.value.substring(start, end);

    let replacement = openTag + selectedText + closeTag;

    if (selectedText === '') {
        replacement = openTag + closeTag;
        textarea.value = textarea.value.slice(0, start) + replacement + textarea.value.slice(end);
        textarea.selectionStart = start + openTag.length;
        textarea.selectionEnd = start + openTag.length;
    } else {
        textarea.value = textarea.value.slice(0, start) + replacement + textarea.value.slice(end);
        textarea.selectionStart = start + openTag.length;
        textarea.selectionEnd = start + openTag.length + selectedText.length;
    }

    textarea.focus();
}

function applyFontFace(font) {
    if (font) insertBBCode('[font=' + font + ']', '[/font]');
}

function applyFontColor(color) {
    if (color) insertBBCode('[color=' + color + ']', '[/color]');
}

function applyFontSize(size) {
    if (size) insertBBCode('[size=' + size + ']', '[/size]');
}

function applyTextAlign(align) {
    if (align) insertBBCode('[align=' + align + ']', '[/align]');
}

// Поддръжка за усмивки (запазваме функционалността)
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('smile-img')) {
        const smileCode = e.target.alt;
        insertBBCode('[smile=' + smileCode + ']', '[/smile]');
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getInstance(document.getElementById('smilesModal'))?.hide();
        }
    }
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>