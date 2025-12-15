<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Language.php';

$pdo = Database::getInstance();
$auth = new Auth($pdo);
$lang = new Language($_SESSION['lang'] ?? 'en');

// Only Owner can access
if ($auth->getRank() < 6) {
    die($lang->get('no_permission'));
}

$message = '';

// Process POST request
if ($_POST['action'] ?? false) {
    try {
        $pdo->beginTransaction();

        // Delete old permissions
        $pdo->exec("DELETE FROM ranks_permissions");

        // Save new permissions
        $permissions = [
            'torrents' => $lang->get('torrents'),
            'users' => $lang->get('users'),
            'categories' => $lang->get('categories'),
            'news' => $lang->get('news'),
            'reports' => $lang->get('reports'),
            'statistics' => $lang->get('statistics')
        ];

        $ranks = [
            1 => $lang->get('guest'),
            2 => $lang->get('user'),
            3 => $lang->get('uploader'),
            4 => $lang->get('validator'),
            5 => $lang->get('moderator'),
            6 => $lang->get('owner')
        ];

        foreach ($ranks as $rankId => $rankName) {
            foreach ($permissions as $key => $label) {
                $canView = isset($_POST["view_{$rankId}_{$key}"]) ? 1 : 0;
                $canEdit = isset($_POST["edit_{$rankId}_{$key}"]) ? 1 : 0;

                $stmt = $pdo->prepare("
                    INSERT INTO ranks_permissions (rank_id, permission_key, can_view, can_edit)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE can_view = VALUES(can_view), can_edit = VALUES(can_edit)
                ");
                $stmt->execute([$rankId, $key, $canView, $canEdit]);
            }
        }

        $pdo->commit();
        $message = '<div class="alert alert-success">' . $lang->get('permissions_saved') . '</div>';
        
        // 🔄 Redirect after save to reload data
        header("Refresh: 0");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $message = '<div class="alert alert-danger">' . $lang->get('save_failed') . ': ' . $e->getMessage() . '</div>';
    }
}

// ✅ Fixed fetching current permissions — without FETCH_GROUP
$stmt = $pdo->query("
    SELECT rank_id, permission_key, can_view, can_edit
    FROM ranks_permissions
    ORDER BY rank_id DESC, permission_key
");
$permissionsData = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $permissionsData[$row['rank_id']][$row['permission_key']] = [
        'can_view' => (bool)$row['can_view'],
        'can_edit' => (bool)$row['can_edit']
    ];
}

// Define ranks and permissions
$ranks = [
    6 => $lang->get('owner'),
    5 => $lang->get('moderator'),
    4 => $lang->get('validator'),
    3 => $lang->get('uploader'),
    2 => $lang->get('user'),
    1 => $lang->get('guest')
];

$permissions = [
    'torrents' => $lang->get('torrents'),
    'users' => $lang->get('users'),
    'categories' => $lang->get('categories'),
    'news' => $lang->get('news'),
    'reports' => $lang->get('reports'),
    'statistics' => $lang->get('statistics')
];

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
    <h2><?= $lang->get('manage_ranks_permissions') ?></h2>
    <?= $message ?>

    <form method="POST">
        <input type="hidden" name="action" value="save_permissions">

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th><?= $lang->get('rank') ?></th>
                        <?php foreach ($permissions as $key => $label): ?>
                            <th colspan="2" class="text-center"><?= $label ?></th>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <th></th>
                        <?php foreach ($permissions as $key => $label): ?>
                            <th class="text-center"><?= $lang->get('view') ?></th>
                            <th class="text-center"><?= $lang->get('edit') ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ranks as $rankId => $rankName): ?>
                        <tr>
                            <td><strong><?= $rankName ?></strong></td>
                            <?php foreach ($permissions as $key => $label): ?>
                                <?php
                                $data = $permissionsData[$rankId][$key] ?? ['can_view' => false, 'can_edit' => false];
                                $viewChecked = $data['can_view'] ? 'checked' : '';
                                $editChecked = $data['can_edit'] ? 'checked' : '';
                                ?>
                                <td class="text-center">
                                    <input type="checkbox" name="view_<?= $rankId ?>_<?= $key ?>" <?= $viewChecked ?> <?= $rankId == 6 ? 'disabled' : '' ?>>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="edit_<?= $rankId ?>_<?= $key ?>" <?= $editChecked ?> <?= $rankId == 6 ? 'disabled' : '' ?>>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="text-end">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-save"></i> <?= $lang->get('save_changes') ?>
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>