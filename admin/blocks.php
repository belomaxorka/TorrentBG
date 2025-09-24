<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Language.php';
require_once __DIR__ . '/../includes/BlockManager.php';

$pdo = Database::getInstance();
$auth = new Auth($pdo);
$lang = new Language($_SESSION['lang'] ?? 'en');
$blockManager = new BlockManager($pdo);

if ($auth->getRank() < 6) { // Only Owner
    die($lang->get('no_permission'));
}

$message = '';

// Обработка на форми
if ($_POST['action'] ?? false) {
    if ($_POST['action'] === 'update') {
        $id = (int)$_POST['id'];
        $title = $_POST['title'] ?? '';
        $position = $_POST['position'] ?? 'center';
        $order = (int)$_POST['order'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_locked = isset($_POST['is_locked']) ? 1 : 0;

        if (empty($title)) {
            $message = '<div class="alert alert-danger">' . $lang->get('fill_all_fields') . '</div>';
        } else {
            $stmt = $pdo->prepare("
                UPDATE blocks SET 
                title = ?, position = ?, `order` = ?, is_active = ?, is_locked = ?
                WHERE id = ?
            ");
            if ($stmt->execute([$title, $position, $order, $is_active, $is_locked, $id])) {
                $message = '<div class="alert alert-success">' . $lang->get('block_updated') . '</div>';
            } else {
                $message = '<div class="alert alert-danger">' . $lang->get('block_update_failed') . '</div>';
            }
        }
    }

    if ($_POST['action'] === 'add') {
        $name = $_POST['name'] ?? '';
        $title = $_POST['title'] ?? '';
        $position = $_POST['position'] ?? 'center';
        $order = (int)$_POST['order'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_locked = isset($_POST['is_locked']) ? 1 : 0;

        if (empty($name) || empty($title)) {
            $message = '<div class="alert alert-danger">' . $lang->get('fill_all_fields') . '</div>';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO blocks (name, title, position, `order`, is_active, is_locked) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            if ($stmt->execute([$name, $title, $position, $order, $is_active, $is_locked])) {
                $message = '<div class="alert alert-success">' . $lang->get('block_added') . '</div>';
            } else {
                $message = '<div class="alert alert-danger">' . $lang->get('block_add_failed') . '</div>';
            }
        }
    }

    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM blocks WHERE id = ? AND is_locked = 0");
            $stmt->execute([$id]);
            if ($stmt->rowCount() > 0) {
                $message = '<div class="alert alert-success">' . $lang->get('block_deleted') . '</div>';
            } else {
                $message = '<div class="alert alert-danger">' . $lang->get('block_delete_failed_or_locked') . '</div>';
            }
        } catch (Exception $e) {
            $message = '<div class="alert alert-danger">' . $lang->get('block_delete_failed_or_locked') . '</div>';
        }
    }
}

$blocks = $blockManager->getAllBlocks();

require_once __DIR__ . '/../templates/header.php';
?>

<div class="container-fluid">
    <h2><?= $lang->get('manage_blocks') ?></h2>
    <?= $message ?>

    <div class="row">
        <!-- Форма за добавяне -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><?= $lang->get('add_new_block') ?></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label"><?= $lang->get('block_name') ?> (system)</label>
                            <input type="text" name="name" class="form-control" required>
                            <div class="form-text">Име на файла без .php (напр. user_info)</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= $lang->get('block_title') ?> (display)</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= $lang->get('position') ?></label>
                            <select name="position" class="form-select">
                                <option value="left">Left</option>
                                <option value="center">Center</option>
                                <option value="right">Right</option>
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
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_locked" class="form-check-input">
                            <label class="form-check-label"><?= $lang->get('is_locked') ?></label>
                        </div>
                        <button type="submit" class="btn btn-success"><?= $lang->get('add_block') ?></button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Списък с блокове -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><?= $lang->get('existing_blocks') ?></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th><?= $lang->get('name') ?></th>
                                    <th><?= $lang->get('title') ?></th>
                                    <th><?= $lang->get('position') ?></th>
                                    <th><?= $lang->get('actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($blocks as $block): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($block['name']) ?></td>
                                        <td><?= htmlspecialchars($block['title']) ?></td>
                                        <td><?= $block['position'] ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary edit-btn" data-id="<?= $block['id'] ?>" data-bs-toggle="modal" data-bs-target="#editModal">✏️</button>
                                            <?php if (!$block['is_locked']): ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('<?= $lang->get('confirm_delete') ?>')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $block['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">🗑️</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">🔒</span>
                                            <?php endif; ?>
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
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit-id">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $lang->get('edit_block') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?= $lang->get('block_title') ?></label>
                        <input type="text" name="title" id="edit-title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= $lang->get('position') ?></label>
                        <select name="position" id="edit-position" class="form-select">
                            <option value="left">Left</option>
                            <option value="center">Center</option>
                            <option value="right">Right</option>
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
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_locked" id="edit-is_locked" class="form-check-input">
                        <label class="form-check-label"><?= $lang->get('is_locked') ?></label>
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
        
        // Взимаме текущите данни на блока чрез AJAX
        fetch(`/admin/get_block.php?id=${id}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('edit-id').value = data.id;
            document.getElementById('edit-title').value = data.title;
            document.getElementById('edit-position').value = data.position;
            document.getElementById('edit-order').value = data.order;
            document.getElementById('edit-is_active').checked = data.is_active == 1;
            document.getElementById('edit-is_locked').checked = data.is_locked == 1;
        });
    });
});
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>