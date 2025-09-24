<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Language.php';

$pdo = Database::getInstance();
$auth = new Auth($pdo);
$lang = new Language($_SESSION['lang'] ?? 'en');

$error = '';

if ($_POST['login'] ?? false) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = $lang->get('fill_all_fields');
    } else {
        if ($auth->login($username, $password)) {
            header("Location: index.php");
            exit;
        } else {
            $error = $lang->get('invalid_login');
        }
    }
}

require_once __DIR__ . '/templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center"><?= $lang->get('login') ?></h3>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label"><?= $lang->get('username') ?></label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= $lang->get('password') ?></label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <input type="hidden" name="login" value="1">
                    <button type="submit" class="btn btn-primary w-100"><?= $lang->get('login') ?></button>
                </form>

                <div class="text-center mt-3">
                    <a href="register.php"><?= $lang->get('no_account_register') ?></a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>