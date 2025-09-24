<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Language.php';

$pdo = Database::getInstance();
$auth = new Auth($pdo);
$lang = new Language($_SESSION['lang'] ?? 'en');

$error = '';
$success = false;

if ($_POST['register'] ?? false) {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = $lang->get('fill_all_fields');
    } elseif ($password !== $password2) {
        $error = $lang->get('passwords_dont_match');
    } elseif (strlen($password) < 6) {
        $error = $lang->get('password_too_short');
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = $lang->get('user_or_email_exists');
            } else {
                $hashedPass = password_hash($password, PASSWORD_ARGON2ID);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, rank, language, style) VALUES (?, ?, ?, 2, 'en', 'light')");
                $stmt->execute([$username, $email, $hashedPass]);
                $success = true;
            }
        } catch (Exception $e) {
            $error = $lang->get('registration_error');
        }
    }
}

require_once __DIR__ . '/templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center"><?= $lang->get('register') ?></h3>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?= $lang->get('registration_success') ?><br>
                        <a href="login.php"><?= $lang->get('go_to_login') ?></a>
                    </div>
                <?php else: ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label"><?= $lang->get('username') ?></label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= $lang->get('email') ?></label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= $lang->get('password') ?></label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= $lang->get('confirm_password') ?></label>
                            <input type="password" name="password2" class="form-control" required>
                        </div>
                        <input type="hidden" name="register" value="1">
                        <button type="submit" class="btn btn-success w-100"><?= $lang->get('register') ?></button>
                    </form>

                    <div class="text-center mt-3">
                        <a href="login.php"><?= $lang->get('already_have_account') ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>