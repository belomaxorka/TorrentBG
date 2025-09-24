<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Language.php';
require_once __DIR__ . '/includes/TranslationManager.php';

$pdo = Database::getInstance();
$auth = new Auth($pdo);
$lang = new Language($_SESSION['lang'] ?? 'en');
$translationManager = new TranslationManager($pdo);

if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$message = '';

if ($_POST['suggest'] ?? false) {
    $key = $_POST['key'] ?? '';
    $language = $_POST['language'] ?? '';
    $translation = $_POST['translation'] ?? '';
    
    if (empty($key) || empty($language) || empty($translation)) {
        $message = '<div class="alert alert-danger">' . $lang->get('fill_all_fields') . '</div>';
    } elseif (!in_array($language, ['en', 'bg', 'fr', 'de', 'ru'])) {
        $message = '<div class="alert alert-danger">' . $lang->get('invalid_language') . '</div>';
    } else {
        if ($translationManager->suggestTranslation($key, $language, $translation, $auth->getUser()['id'])) {
            $message = '<div class="alert alert-success">' . $lang->get('translation_suggested') . '</div>';
        } else {
            $message = '<div class="alert alert-danger">' . $lang->get('translation_suggestion_failed') . '</div>';
        }
    }
}

require_once __DIR__ . '/templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center"><?= $lang->get('suggest_translation') ?></h3>
            </div>
            <div class="card-body">
                <?= $message ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label"><?= $lang->get('translation_key') ?> *</label>
                        <input type="text" name="key" class="form-control" required>
                        <div class="form-text"><?= $lang->get('enter_the_key_from_language_files') ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?= $lang->get('language') ?> *</label>
                        <select name="language" class="form-select" required>
                            <option value=""><?= $lang->get('select_language') ?></option>
                            <option value="bg">Български (BG)</option>
                            <option value="fr">Français (FR)</option>
                            <option value="de">Deutsch (DE)</option>
                            <option value="ru">Русский (RU)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?= $lang->get('translation') ?> *</label>
                        <textarea name="translation" class="form-control" rows="3" required></textarea>
                        <div class="form-text"><?= $lang->get('enter_the_translation_for_the_selected_language') ?></div>
                    </div>
                    
                    <input type="hidden" name="suggest" value="1">
                    <button type="submit" class="btn btn-primary w-100"><?= $lang->get('suggest_translation') ?></button>
                </form>
                
                <div class="mt-4">
                    <h5><?= $lang->get('how_to_find_keys') ?></h5>
                    <p><?= $lang->get('translation_keys_can_be_found_in') ?> <code>/language/</code> <?= $lang->get('files') ?>.</p>
                    <p><?= $lang->get('example_key') ?>: <code>'welcome_message'</code></p>
                    <p><?= $lang->get('example_translation') ?>: <code>'Добре дошли в нашия торент тракер!'</code></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>