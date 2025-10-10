<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Language.php';

$pdo = Database::getInstance();
$auth = new Auth($pdo);
$lang = new Language($_SESSION['lang'] ?? 'en');

if ($auth->getRank() < 5) { // Moderator+
    die($lang->get('no_permission'));
}

$message = '';

// Обработка на форми
if ($_POST['action'] ?? false) {
    if ($_POST['action'] === 'add') {
        $question = $_POST['question'] ?? '';
        $description = $_POST['description'] ?? '';
        $options = $_POST['options'] ?? [];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($question) || count($options) < 2) {
            $message = '<div class="alert alert-danger">' . $lang->get('fill_all_fields_and_min_2_options') . '</div>';
        } else {
            $pdo->beginTransaction();
            try {
                // Вмъкваме анкетата
                $stmt = $pdo->prepare("INSERT INTO polls (question, description, is_active, created_by) VALUES (?, ?, ?, ?)");
                $stmt->execute([$question, $description, $is_active, $auth->getUser()['id']]);
                $pollId = $pdo->lastInsertId();

                // Вмъкваме опциите
                $stmt = $pdo->prepare("INSERT INTO poll_options (poll_id, option_text) VALUES (?, ?)");
                foreach ($options as $option) {
                    if (trim($option)) {
                        $stmt->execute([$pollId, trim($option)]);
                    }
                }

                $pdo->commit();
                $message = '<div class="alert alert-success">' . $lang->get('poll_created') . '</div>';
            } catch (Exception $e) {
                $pdo->rollback();
                $message = '<div class="alert alert-danger">' . $lang->get('poll_create_failed') . ': ' . $e->getMessage() . '</div>';
            }
        }
    }

    if ($_POST['action'] === 'toggle') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("UPDATE polls SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$id]);
        $message = '<div class="alert alert-info">' . $lang->get('poll_status_updated') . '</div>';
    }

    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM polls WHERE id = ?");
        $stmt->execute([$id]);
        $message = '<div class="alert alert-success">' . $lang->get('poll_deleted') . '</div>';
    }
}

$stmt = $pdo->query("
    SELECT p.*, u.username as creator,
           (SELECT COUNT(*) FROM poll_votes pv WHERE pv.poll_id = p.id) as total_votes
    FROM polls p
    JOIN users u ON p.created_by = u.id
    ORDER BY p.created_at DESC
");
$polls = $stmt->fetchAll();

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
    <h2><?= $lang->get('manage_polls') ?></h2>
    <?= $message ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><?= $lang->get('create_new_poll') ?></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label"><?= $lang->get('question') ?> *</label>
                            <input type="text" name="question" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= $lang->get('description') ?></label>
                            <textarea name="description" class="form-control"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= $lang->get('options') ?> *</label>
                            <div id="options-container">
                                <input type="text" name="options[]" class="form-control mb-2" placeholder="<?= $lang->get('option') ?> 1" required>
                                <input type="text" name="options[]" class="form-control mb-2" placeholder="<?= $lang->get('option') ?> 2" required>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addOption()">+ <?= $lang->get('add_option') ?></button>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" checked>
                            <label class="form-check-label"><?= $lang->get('is_active') ?></label>
                        </div>
                        <button type="submit" class="btn btn-success"><?= $lang->get('create_poll') ?></button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><?= $lang->get('existing_polls') ?></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th><?= $lang->get('question') ?></th>
                                    <th><?= $lang->get('votes') ?></th>
                                    <th><?= $lang->get('status') ?></th>
                                    <th><?= $lang->get('actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($polls as $poll): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($poll['question']) ?></td>
                                        <td><?= $poll['total_votes'] ?></td>
                                        <td>
                                            <?php if ($poll['is_active']): ?>
                                                <span class="badge bg-success"><?= $lang->get('active') ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?= $lang->get('inactive') ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="id" value="<?= $poll['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-<?= $poll['is_active'] ? 'danger' : 'success' ?>">
                                                    <?= $poll['is_active'] ? '⏹️' : '▶️' ?>
                                                </button>
                                            </form>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('<?= $lang->get('confirm_delete') ?>')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $poll['id'] ?>">
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

<script>
function addOption() {
    const container = document.getElementById('options-container');
    const count = container.children.length + 1;
    const input = document.createElement('input');
    input.type = 'text';
    input.name = 'options[]';
    input.className = 'form-control mb-2';
    input.placeholder = '<?= $lang->get('option') ?> ' + count;
    input.required = true;
    container.appendChild(input);
}
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>