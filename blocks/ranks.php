<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Language.php';

$pdo = Database::getInstance();
$auth = new Auth($pdo);
$lang = new Language($_SESSION['lang'] ?? 'en');

// Само Owner може да влиза
if ($auth->getRank() < 6) {
    die($lang->get('no_permission'));
}

$message = '';

// Обработка на POST заявка
if ($_POST['action'] ?? false) {
    try {
        $pdo->beginTransaction();

        // Изтриваме старите права
        $pdo->exec("DELETE FROM ranks_permissions");

        // Записваме новите
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
                    ON DUPLICATE KEY UPDATE can_view = ?, can_edit = ?
                ");
                $stmt->execute([$rankId, $key, $canView, $canEdit, $canView, $canEdit]);
            }
        }

        $pdo->commit();
        $message = '<div class="alert alert-success">' . $lang->get('permissions_saved') . '</div>';
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = '<div class="alert alert-danger">' . $lang->get('save_failed') . ': ' . $e->getMessage() . '</div>';
    }
}

// Извличаме текущите права
$stmt = $pdo->query("
    SELECT rank_id, permission_key, can_view, can_edit
    FROM ranks_permissions
    ORDER BY rank_id DESC, permission_key
");
$permissionsData = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);

// Дефинираме ранговете и правата
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
                                $data = $permissionsData[$rankId][$key] ?? ['can_view' => 0, 'can_edit' => 0];
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