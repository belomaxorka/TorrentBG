<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Language.php';

$pdo = Database::getInstance();
$auth = new Auth($pdo);
$lang = new Language($_SESSION['lang'] ?? 'en');

if ($auth->getRank() < 6) { // Only Owner
    die($lang->get('no_permission'));
}

$message = '';

// Обработка на форми
if ($_POST['action'] ?? false) {
    if ($_POST['action'] === 'add') {
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $parentId = (int)$_POST['parent_id'];
        $order = (int)$_POST['order'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name)) {
            $message = '<div class="alert alert-danger">' . $lang->get('fill_all_fields') . '</div>';
        } else {
            $icon = null;
            if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['image/jpeg', 'image/png', 'image/webp'];
                if (in_array($_FILES['icon']['type'], $allowed)) {
                    $ext = pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION);
                    $iconName = 'forum_' . uniqid() . '.' . $ext;
                    $iconPath = 'images/forums/' . $iconName;
                    if (move_uploaded_file($_FILES['icon']['tmp_name'], __DIR__ . '/../' . $iconPath)) {
                        $icon = $iconPath;
                    }
                }
            }

            $parentId = $parentId > 0 ? $parentId : null;

            $stmt = $pdo->prepare("
                INSERT INTO forums (name, description, icon, parent_id, `order`, is_active) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            if ($stmt->execute([$name, $description, $icon, $parentId, $order, $is_active])) {
                $message = '<div class="alert alert-success">' . $lang->get('forum_added') . '</div>';
            } else {
                $message = '<div class="alert alert-danger">' . $lang->get('forum_add_failed') . '</div>';
            }
        }
    }

    if ($_POST['action'] === 'edit') {
        $id = (int)$_POST['id'];
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $parentId = (int)$_POST['parent_id'];
        $order = (int)$_POST['order'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name)) {
            $message = '<div class="alert alert-danger">' . $lang->get('fill_all_fields') . '</div>';
        } else {
            $icon = null;
            if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['image/jpeg', 'image/png', 'image/webp'];
                if (in_array($_FILES['icon']['type'], $allowed)) {
                    $ext = pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION);
                    $iconName = 'forum_' . uniqid() . '.' . $ext;
                    $iconPath = 'images/forums/' . $iconName;
                    if (move_uploaded_file($_FILES['icon']['tmp_name'], __DIR__ . '/../' . $iconPath)) {
                        $icon = $iconPath;
                    }
                }
            }

            $parentId = $parentId > 0 ? $parentId : null;

            $sql = "UPDATE forums SET name = ?, description = ?, `order` = ?, is_active = ?, parent_id = ?";
            $params = [$name, $description, $order, $is_active, $parentId];
            if ($icon) {
                $sql .= ", icon = ?";
                $params[] = $icon;
            }
            $sql .= " WHERE id = ?";
            $params[] = $id;

            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($params)) {
                $message = '<div class="alert alert-success">' . $lang->get('forum_updated') . '</div>';
            } else {
                $message = '<div class="alert alert-danger">' . $lang->get('forum_update_failed') . '</div>';
            }
        }
    }

    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        try {
            $pdo->beginTransaction();
            
            // Изтриваме всички теми и мнения в този форум
            $stmt = $pdo->prepare("SELECT id FROM forum_topics WHERE forum_id = ?");
            $stmt->execute([$id]);
            $topicIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($topicIds as $topicId) {
                $pdo->prepare("DELETE FROM forum_posts WHERE topic_id = ?")->execute([$topicId]);
            }
            
            $pdo->prepare("DELETE FROM forum_topics WHERE forum_id = ?")->execute([$id]);
            
            // Изтриваме форума
            $pdo->prepare("DELETE FROM forums WHERE id = ?")->execute([$id]);
            
            $pdo->commit();
            $message = '<div class="alert alert-success">' . $lang->get('forum_deleted') . '</div>';
        } catch (Exception $e) {
            $pdo->rollback();
            $message = '<div class="alert alert-danger">' . $lang->get('forum_delete_failed') . '</div>';
        }
    }
}

// Взимаме всички форуми
$stmt = $pdo->query("
    SELECT f.*, 
           (SELECT name FROM forums WHERE id = f.parent_id) as parent_name,
           (SELECT COUNT(*) FROM forums WHERE parent_id = f.id) as subforums_count,
           (SELECT COUNT(*) FROM forum_topics WHERE forum_id = f.id) as topics_count
    FROM forums f
    ORDER BY f.parent_id IS NULL DESC, f.parent_id, f.order
");
$forums = $stmt->fetchAll();

require_once __DIR__ . '/../templates/header.php';
?>

<style>
/* Тъмна навигация */
.navbar {
    background-color: #212529 !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.navbar-brand,
.navbar-nav .nav-link {
    color: #dee2e6 !important;
}

.navbar-nav .nav-link:hover,
.navbar-nav .nav-link.active {
    color: #ffffff !important;
    background-color: #495057;
}

/* Заглавие */
.admin-panel-title {
    background-color: #0d6efd;
    color: white;
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    border-radius: 4px;
    font-weight: bold;
    font-size: 1.3rem;
}

/* Карти */
.admin-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    margin-bottom: 1rem;
    overflow: hidden;
}

/* Цветни заглавия */
.card-header.bg-primary { background-color: #0d6efd !important; }
.card-header.bg-success { background-color: #28a745 !important; }
.card-header.bg-info { background-color: #0dcaf0 !important; }
.card-header.bg-warning { background-color: #ffc107 !important; color: #212529 !important; }
.card-header.bg-danger { background-color: #dc3545 !important; }
.card-header.bg-secondary { background-color: #6c757d !important; }
.card-header.bg-dark { background-color: #212529 !important; }

/* Пурпурен цвят */
.bg-purple {
    background-color: #6f42c1 !important;
}
.btn-purple {
    background-color: #6f42c1;
    color: white;
    border-color: #6f42c1;
}
.btn-purple:hover {
    background-color: #5a35a3;
    border-color: #543196;
}
</style>

<div class="container-fluid">
    <h2><?= $lang->get('manage_forums') ?></h2>
    <?= $message ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><?= $lang->get('add_new_forum') ?></div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label"><?= $lang->get('name') ?> *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= $lang->get('description') ?></label>
                            <textarea name="description" class="form-control"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= $lang->get('icon') ?> (optional)</label>
                            <input type="file" name="icon" class="form-control" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= $lang->get('parent_forum') ?> (for subforums)</label>
                            <select name="parent_id" class="form-select">
                                <option value="0"><?= $lang->get('none') ?> (main forum)</option>
                                <?php foreach ($forums as $f): ?>
                                    <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= $lang->get('order') ?></label>
                            <input type="number" name="order" class="form-control" value="0">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" checked>
                            <label class="form-check-label"><?= $lang->get('is_active') ?></label>
                        </div>
                        <button type="submit" class="btn btn-success"><?= $lang->get('add_forum') ?></button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><?= $lang->get('existing_forums') ?></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th><?= $lang->get('name') ?></th>
                                    <th><?= $lang->get('parent') ?></th>
                                    <th><?= $lang->get('topics') ?></th>
                                    <th><?= $lang->get('actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($forums as $forum): ?>
                                    <tr>
                                        <td>
                                            <?php if ($forum['icon']): ?>
                                                <img src="/<?= $forum['icon'] ?>" width="20" height="20" class="me-2">
                                            <?php endif; ?>
                                            <?= htmlspecialchars($forum['name']) ?>
                                        </td>
                                        <td><?= $forum['parent_name'] ?? $lang->get('none') ?></td>
                                        <td><?= $forum['topics_count'] ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary edit-btn" data-id="<?= $forum['id'] ?>" data-bs-toggle="modal" data-bs-target="#editModal">✏️</button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('<?= $lang->get('confirm_delete_forum') ?>')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $forum['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">🗑️</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модален прозорец за редакция -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit-id">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $lang->get('edit_forum') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?= $lang->get('name') ?> *</label>
                        <input type="text" name="name" id="edit-name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= $lang->get('description') ?></label>
                        <textarea name="description" id="edit-description" class="form-control"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= $lang->get('icon') ?> (optional)</label>
                        <input type="file" name="icon" class="form-control" accept="image/*">
                        <div id="current-icon" class="mt-2"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= $lang->get('parent_forum') ?></label>
                        <select name="parent_id" id="edit-parent_id" class="form-select">
                            <option value="0"><?= $lang->get('none') ?> (main forum)</option>
                            <?php foreach ($forums as $f): ?>
                                <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= $lang->get('order') ?></label>
                        <input type="number" name="order" id="edit-order" class="form-control">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_active" id="edit-is_active" class="form-check-input">
                        <label class="form-check-label"><?= $lang->get('is_active') ?></label>
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

<script>
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        // Тук ще трябва да извлечем данните чрез AJAX или да ги вградим в HTML
        // За простота ще ги оставим празни — в реална употреба ще ги попълним
        document.getElementById('edit-id').value = id;
    });
});
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>