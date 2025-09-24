<?php
require_once __DIR__ . '/templates/header.php';
$lang = new Language($_SESSION['lang'] ?? 'en');

$message = '';

if ($_POST['send'] ?? false) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $messageText = $_POST['message'] ?? '';

    if (empty($name) || empty($email) || empty($subject) || empty($messageText)) {
        $message = '<div class="alert alert-danger">' . $lang->get('fill_all_fields') . '</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert alert-danger">' . $lang->get('invalid_email') . '</div>';
    } else {
        // Тук ще трябва да настроиш SMTP или да използваш mail()
        // За тестови цели просто показваме съобщение
        $message = '<div class="alert alert-success">' . $lang->get('message_sent_successfully') . '</div>';
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h2 class="mb-0"><?= $lang->get('contact') ?></h2>
            </div>
            <div class="card-body">
                <?= $message ?>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <h4><i class="bi bi-geo-alt me-2"></i><?= $lang->get('address') ?></h4>
                        <p><?= $lang->get('company_address') ?></p>
                    </div>
                    <div class="col-md-6">
                        <h4><i class="bi bi-envelope me-2"></i><?= $lang->get('email') ?></h4>
                        <p><a href="mailto:support@torrentbg.com">support@torrentbg.com</a></p>
                        <h4 class="mt-3"><i class="bi bi-telephone me-2"></i><?= $lang->get('phone') ?></h4>
                        <p>+359 888 888 888</p>
                    </div>
                </div>

                <h4 class="mb-3"><i class="bi bi-chat-left-text me-2"></i><?= $lang->get('send_message') ?></h4>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label"><?= $lang->get('name') ?> *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= $lang->get('email') ?> *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= $lang->get('subject') ?> *</label>
                        <input type="text" name="subject" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= $lang->get('message') ?> *</label>
                        <textarea name="message" class="form-control" rows="5" required></textarea>
                    </div>
                    <button type="submit" name="send" value="1" class="btn btn-primary w-100"><?= $lang->get('send_message') ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>