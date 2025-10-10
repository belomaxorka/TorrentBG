<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Language.php';

$pdo = Database::getInstance();
$auth = new Auth($pdo);
$lang = new Language($_SESSION['lang'] ?? 'en');

if ($auth->getRank() < 6) {
    die($lang->get('no_permission'));
}

$message = '';

if ($_POST['action'] ?? false) {
    if ($_POST['action'] === 'add') {
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $order = (int)$_POST['order'];
        
        if (empty($name)) {
            $message = '<div class="alert alert-danger">' . $lang->get('fill_all_fields') . '</div>';
        } else {
            $icon = null;
            if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['image/jpeg', 'image/png', 'image/webp'];
                if (in_array($_FILES['icon']['type'], $allowed)) {
                    $ext = pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION);
                    $iconName = 'category_' . uniqid() . '.' . $ext;
                    $iconPath = 'images/categories/' . $iconName;
                    if (move_uploaded_file($_FILES['icon']['tmp_name'], __DIR__ . '/../' . $iconPath)) {
                        $icon = $iconPath;
                    }
                }
            }

            $stmt = $pdo->prepare("INSERT INTO categories (name, icon, description, `order`) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$name, $icon, $description, $order])) {
                $message = '<div class="alert alert-success">' . $lang->get('category_added') . '</div>';
            } else {
                $message = '<div class="alert alert-danger">' . $lang->get('category_add_failed') . '</div>';
            }
        }
    }

    if ($_POST['action'] === 'edit') {
        $id = (int)$_POST['id'];
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
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
                    $iconName = 'category_' . uniqid() . '.' . $ext;
                    $iconPath = 'images/categories/' . $iconName;
                    if (move_uploaded_file($_FILES['icon']['tmp_name'], __DIR__ . '/../' . $iconPath)) {
                        $icon = $iconPath;
                    }
                }
            }

            $sql = "UPDATE categories SET name = ?, description = ?, `order` = ?, is_active = ?";
            $params = [$name, $description, $order, $is_active];
            if ($icon) {
                $sql .= ", icon = ?";
                $params[] = $icon;
            }
            $sql .= " WHERE id = ?";
            $params[] = $id;

            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($params)) {
                $message = '<div class="alert alert-success">' . $lang->get('category_updated') . '</div>';
            } else {
                $message = '<div class="alert alert-danger">' . $lang->get('category_update_failed') . '</div>';
            }
        }
    }

    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = '<div class="alert alert-success">' . $lang->get('category_deleted') . '</div>';
        } else {
            $message = '<div class="alert alert-danger">' . $lang->get('category_delete_failed') . '</div>';
        }
    }
}

$stmt = $pdo->query("SELECT * FROM categories ORDER BY `order`");
$categories = $stmt->fetchAll();

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
    <h2><?= $lang->get('manage_categories') ?></h2>
    <?= $message ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><?= $lang->get('add_category') ?></div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label"><?= $lang->get('name') ?> *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= $lang->get('icon') ?> (optional)</label>
                            <input type="file" name="icon" class="form-control" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= $lang->get('description') ?></label>
                            <input type="text" name="description" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= $lang->get('order') ?></label>
                            <input type="number" name="order" class="form-control" value="0">
                        </div>
                        <button type="submit" class="btn btn-success"><?= $lang->get('add_category') ?></button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><?= $lang->get('existing_categories') ?></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th><?= $lang->get('name') ?></th>
                                    <th><?= $lang->get('active') ?></th>
                                    <th><?= $lang->get('actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td>
                                            <?php if ($cat['icon']): ?>
                                                <img src="/<?= $cat['icon'] ?>" width="20" height="20" class="me-2">
                                            <?php endif; ?>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </td>
                                        <td><?= $cat['is_active'] ? '✅' : '❌' ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editCatModal<?= $cat['id'] ?>">✏️</button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('<?= $lang->get('confirm_delete') ?>')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">🗑️</button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Модален прозорец за редакция -->
                                    <div class="modal fade" id="editCatModal<?= $cat['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" enctype="multipart/form-data">
                                                    <input type="hidden" name="action" value="edit">
                                                    <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title"><?= $lang->get('edit_category') ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label"><?= $lang->get('name') ?> *</label>
                                                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($cat['name']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label"><?= $lang->get('icon') ?> (optional)</label>
                                                            <input type="file" name="icon" class="form-control" accept="image/*">
                                                            <?php if ($cat['icon']): ?>
                                                                <div class="mt-2">
                                                                    <img src="/<?= $cat['icon'] ?>" width="50">
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label"><?= $lang->get('description') ?></label>
                                                            <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($cat['description'] ?? '') ?>">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label"><?= $lang->get('order') ?></label>
                                                            <input type="number" name="order" class="form-control" value="<?= $cat['order'] ?>">
                                                        </div>
                                                        <div class="mb-3 form-check">
                                                            <input type="checkbox" name="is_active" class="form-check-input" <?= $cat['is_active'] ? 'checked' : '' ?>>
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
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>