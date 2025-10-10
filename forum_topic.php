<?php
require_once __DIR__ . '/includes/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    die($lang->get('invalid_topic_id'));
}

// Взимаме темата
$stmt = $pdo->prepare("
    SELECT ft.*, f.name as forum_name, f.id as forum_id, u.username as author_name
    FROM forum_topics ft
    JOIN forums f ON ft.forum_id = f.id
    JOIN users u ON ft.user_id = u.id
    WHERE ft.id = ?
");
$stmt->execute([$id]);
$topic = $stmt->fetch();

if (!$topic) {
    die($lang->get('topic_not_found'));
}

// Увеличаваме брояча на прегледи
$pdo->prepare("UPDATE forum_topics SET views = views + 1 WHERE id = ?")->execute([$id]);

// Взимаме мненията
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT fp.*, u.username, u.rank
    FROM forum_posts fp
    JOIN users u ON fp.user_id = u.id
    WHERE fp.topic_id = ?
    ORDER BY fp.created_at ASC
    LIMIT ? OFFSET ?
");
$stmt->execute([$id, $perPage, $offset]);
$posts = $stmt->fetchAll();

// Общ брой мнения
$stmt = $pdo->prepare("SELECT COUNT(*) FROM forum_posts WHERE topic_id = ?");
$stmt->execute([$id]);
$totalPosts = $stmt->fetchColumn();
$totalPages = ceil($totalPosts / $perPage);

require_once __DIR__ . '/templates/header.php';
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/forum.php"><?= $lang->get('forum') ?></a></li>
        <li class="breadcrumb-item"><a href="/forum_view.php?id=<?= $topic['forum_id'] ?>"><?= htmlspecialchars($topic['forum_name']) ?></a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($topic['title']) ?></li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= htmlspecialchars($topic['title']) ?></h2>
    <?php if ($auth->isLoggedIn() && !$topic['is_locked']): ?>
        <a href="#reply" class="btn btn-primary"><?= $lang->get('add_reply') ?></a>
    <?php endif; ?>
</div>

<?php if ($topic['is_locked']): ?>
    <div class="alert alert-warning"><?= $lang->get('topic_locked') ?></div>
<?php endif; ?>

<?php if (empty($posts)): ?>
    <div class="alert alert-info"><?= $lang->get('no_posts_yet') ?></div>
<?php else: ?>
    <?php foreach ($posts as $post): ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <strong><?= htmlspecialchars($post['username']) ?></strong>
                    <small class="text-muted ms-2"><?= date('Y-m-d H:i', strtotime($post['created_at'])) ?></small>
                    <?php if ($post['is_edited']): ?>
                        <small class="text-muted">(<?= $lang->get('edited') ?>)</small>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if ($auth->isLoggedIn() && ($auth->getUser()['id'] == $post['user_id'] || $auth->getRank() >= 5)): ?>
                        <button class="btn btn-sm btn-outline-secondary edit-post-btn" data-id="<?= $post['id'] ?>">✏️</button>
                        <?php if ($post['id'] != $posts[0]['id']): // Не позволяваме изтриване на първото мнение ?>
                            <form method="POST" action="/forum_post_delete.php" style="display:inline;" onsubmit="return confirm('<?= $lang->get('confirm_delete') ?>')">
                                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                <input type="hidden" name="topic_id" value="<?= $topic['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">🗑️</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="post-content">
                    <?= parseBBC($post['content']) ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Пагинация -->
    <?php if ($totalPages > 1): ?>
        <nav>
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?id=<?= $topic['id'] ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php if ($auth->isLoggedIn() && !$topic['is_locked']): ?>
    <div class="card" id="reply">
        <div class="card-header">
            <h4><?= $lang->get('add_reply') ?></h4>
        </div>
        <div class="card-body">
            <form method="POST" action="/forum_post_add.php">
                <input type="hidden" name="topic_id" value="<?= $topic['id'] ?>">
                <div class="mb-3">
                    <textarea name="content" id="editor" class="form-control" rows="8" required></textarea>
                    <div class="form-text"><?= $lang->get('bbc_codes_supported') ?></div>
                </div>
                <button type="submit" class="btn btn-primary"><?= $lang->get('post_reply') ?></button>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- Модален прозорец за редакция на мнение -->
<div class="modal fade" id="editPostModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/forum_post_edit.php">
                <input type="hidden" name="post_id" id="edit-post-id">
                <input type="hidden" name="topic_id" value="<?= $topic['id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $lang->get('edit_post') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <textarea name="content" id="edit-post-content" class="form-control" rows="8" required></textarea>
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

<!-- BBC Редактор -->
<script>
// BBC редактор - бутони за форматиране
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('editor');
    
    // Функция за вмъкване на BBC код
    function insertBBC(tag, content = null) {
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selectedText = textarea.value.substring(start, end);
        const textToInsert = content ? `[${tag}=${content}]${selectedText}[/${tag}]` : `[${tag}]${selectedText}[/${tag}]`;
        textarea.value = textarea.value.substring(0, start) + textToInsert + textarea.value.substring(end);
        textarea.focus();
    }

    // Създаваме панел с бутони
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

    // Редакция на мнение
    document.querySelectorAll('.edit-post-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const content = this.closest('.card').querySelector('.post-content').textContent;
            document.getElementById('edit-post-id').value = id;
            document.getElementById('edit-post-content').value = content;
            const modal = new bootstrap.Modal(document.getElementById('editPostModal'));
            modal.show();
        });
    });

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
                        $smileCodes = ['smile', 'wink', 'grin', 'tongue', 'laugh', 'sad', 'angry', 'shock', 'cool', 'blush'];
                        foreach ($smileCodes as $smileCode):
                            // Проверка първо за .gif, после за .png
                            $smileFile = null;
                            if (file_exists('/images/smiles/' . $smileCode . '.gif')) {
                                $smileFile = $smileCode . '.gif';
                            } elseif (file_exists('/images/smiles/' . $smileCode . '.png')) {
                                $smileFile = $smileCode . '.png';
                            }
                            
                            if ($smileFile):
                        ?>
                            <div class="col-3 text-center">
                                <img src="images/smiles/<?= $smileFile ?>" class="img-fluid smile-img" alt="<?= $smileCode ?>" style="cursor: pointer; max-height: 40px;">
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

    // Усмивки функционалност
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('smile-img')) {
            const smileSrc = e.target.src;
            const smileFilename = smileSrc.split('/').pop(); // smile.gif или smile.png
            const smileCode = smileFilename.replace(/\.(gif|png)$/, ''); // само 'smile'
            
            insertBBC('smile', smileCode);
            bootstrap.Modal.getInstance(document.getElementById('smilesModal')).hide();
        }
    });
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>