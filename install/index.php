<?php
declare(strict_types=1);
session_start();
header('Content-Type: text/html; charset=utf-8');

// Път към config.php в includes/
$configPath = __DIR__ . '/../includes/config.php';

// Ако config.php липсва — грешка
if (!file_exists($configPath)) {
    die('<h2 style="color:red; text-align:center;">❌ Грешка: Липсва файлът <code>includes/config.php</code>!<br>Моля, създайте го ръчно.</h2>');
}

// Зареждаме текущата конфигурация
$currentConfig = require $configPath;

// Ако вече е инсталирано — показваме съобщение
if ($currentConfig['site']['installed'] ?? false) {
    die('<h2 style="color:green; text-align:center;">✅ Системата вече е инсталирана!<br><br><strong>❗️ Моля, изтрийте или преименувайте папка <code>/install/</code> за ваша сигурност!</strong></h2>');
}

// Език по подразбиране
$lang = $_POST['language'] ?? $_GET['lang'] ?? 'en';
$supportedLangs = ['en', 'bg', 'fr', 'de', 'ru'];
if (!in_array($lang, $supportedLangs)) {
    $lang = 'en';
}

// Зареждане на езиковия файл
$langFile = __DIR__ . "/lang/{$lang}.php";
if (!file_exists($langFile)) {
    $langFile = __DIR__ . '/lang/en.php';
}
$translations = [$lang => require $langFile];

$errors = [];
$success = false;
$currentStep = $_POST['step'] ?? $_GET['step'] ?? '1';

// --- СТЪПКА 1: Настройки на трекера ---
if ($currentStep === '1' && ($_POST['step'] ?? null) === '1') {
    // Винаги запазваме данните от POST в сесията
    $_SESSION['install_step1'] = [
        'tracker_name' => trim($_POST['tracker_name'] ?? 'TorrentBG'),
        'tracker_url' => trim($_POST['tracker_url'] ?? ''),
        'announce_url' => trim($_POST['announce_url'] ?? ''),
        'tracker_mode' => $_POST['tracker_mode'] ?? 'open',
        'tracker_email' => trim($_POST['tracker_email'] ?? ''),
        'omdb_api_key' => trim($_POST['omdb_api_key'] ?? ''),
    ];

    // Ако е натиснат "Save Settings", правим валидация
    if ($_POST['save_settings'] ?? false) {
        $errors = [];
        if (empty($_SESSION['install_step1']['tracker_name'])) $errors[] = "Tracker name is required.";
        if (empty($_SESSION['install_step1']['tracker_url'])) $errors[] = "Tracker URL is required.";
        if (empty($_SESSION['install_step1']['announce_url'])) $errors[] = "Announce URL is required.";
        if (empty($_SESSION['install_step1']['tracker_email'])) $errors[] = "Tracker email is required.";

        if (empty($errors)) {
            $currentStep = '2';
        }
    } else {
        // Ако е натиснат "Next Step" или друг submit — преминаваме без валидация
        $currentStep = '2';
    }
}

// --- СТЪПКА 2: База данни и администратор ---
if ($currentStep === '2' && ($_POST['install'] ?? false)) {
    $host = $_POST['db_host'] ?? '';
    $user = $_POST['db_user'] ?? '';
    $pass = $_POST['db_pass'] ?? '';
    $name = $_POST['db_name'] ?? '';
    $admin_user = $_POST['admin_user'] ?? '';
    $admin_pass = $_POST['admin_pass'] ?? '';
    $admin_email = $_POST['admin_email'] ?? '';

    // Сохраняем данные в сессии для восстановления при ошибке
    $_SESSION['install_step2'] = [
        'db_host' => $host,
        'db_user' => $user,
        'db_name' => $name,
        'admin_user' => $admin_user,
        'admin_email' => $admin_email,
    ];

    if (empty($host) || empty($user) || empty($name)) {
        $errors[] = $translations[$lang]['errors']['db_fields'];
    }
    if (empty($admin_user) || empty($admin_pass) || empty($admin_email)) {
        $errors[] = $translations[$lang]['errors']['admin_fields'];
    }
    if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }

    if (empty($errors)) {
        try {
            // Проверка подключения к БД
            $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            
            // Проверка существования БД
            $pdo->exec("USE `$name`");

            // Проверка за SQL файл в root/sql/database.sql
            $sqlFile = __DIR__ . '/../sql/database.sql';
            if (!file_exists($sqlFile)) {
                $errors[] = $translations[$lang]['errors']['sql_missing'];
            } else {
                $sql = file_get_contents($sqlFile);
                $statements = explode(';', $sql);
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement)) {
                        $pdo->exec($statement);
                    }
                }
            }

            if (empty($errors)) {
                $hashedPass = password_hash($admin_pass, PASSWORD_ARGON2ID);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, `rank`, language) VALUES (?, ?, ?, 6, ?)");
                $stmt->execute([$admin_user, $admin_email, $hashedPass, $lang]);

                // === ЗАПИС НА НАСТРОЙКИТЕ В ТАБЛИЦА `settings` ===
                $step1Data = $_SESSION['install_step1'] ?? [];
                
                // Валидация URL
                $trackerUrl = filter_var($step1Data['tracker_url'] ?? '', FILTER_VALIDATE_URL) ?: '';
                $announceUrl = filter_var($step1Data['announce_url'] ?? '', FILTER_VALIDATE_URL) ?: '';
                
                $settingsToSave = [
                    'site_name'         => $step1Data['tracker_name'] ?? 'TorrentBG',
                    'site_url'          => $trackerUrl,
                    'tracker_announce'  => $announceUrl,
                    'tracker_mode'      => $step1Data['tracker_mode'] ?? 'open',
                    'site_email'        => $step1Data['tracker_email'] ?? '',
                    'omdb_api_key'      => $step1Data['omdb_api_key'] ?? '',
                    'default_lang'      => $lang,
                ];

                foreach ($settingsToSave as $key => $value) {
                    $pdo->prepare("INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)")
                        ->execute([$key, $value]);
                }

                // === АКТУАЛИЗИРАМЕ config.php ===
                $newConfig = [
                    'db' => [
                        'host' => $host,
                        'user' => $user,
                        'pass' => $pass,
                        'name' => $name,
                        'charset' => 'utf8mb4',
                    ],
                    'site' => [
                        'name' => $settingsToSave['site_name'],
                        'url' => $settingsToSave['site_url'],
                        'announce_url' => $settingsToSave['tracker_announce'],
                        'mode' => $settingsToSave['tracker_mode'],
                        'email' => $settingsToSave['site_email'],
                        'omdb_api_key' => $settingsToSave['omdb_api_key'],
                        'default_lang' => $lang,
                        'default_style' => 'light',
                        'installed' => true,
                    ],
                ];

                $configContent = "<?php\n" .
                    "declare(strict_types=1);\n\n" .
                    "return " . var_export($newConfig, true) . ";\n";

                if (!is_writable(dirname($configPath))) {
                    throw new Exception("Directory " . dirname($configPath) . " is not writable");
                }
                
                if (!file_put_contents($configPath, $configContent)) {
                    throw new Exception("Failed to update config.php");
                }

                // Очистка сессии после успешной установки
                unset($_SESSION['install_step1'], $_SESSION['install_step2']);
                $success = true;
            }

        } catch (Exception $e) {
            $errors[] = sprintf($translations[$lang]['errors']['installation_error'], $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $translations[$lang]['title'] ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; padding: 30px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #333; }
        .error { color: red; background: #ffecec; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .success { color: green; background: #e6ffe6; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .security-warning { 
            background: #fff3cd; 
            border: 1px solid #ffeaa7; 
            color: #856404; 
            padding: 15px; 
            border-radius: 5px; 
            margin-top: 20px;
            font-weight: bold;
        }
        label { display: block; margin-top: 15px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; margin: 5px 0 15px; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; width: 100%; font-size: 16px; }
        button:hover { background: #0056b3; }
        .lang-selector { text-align: right; margin-bottom: 20px; }
        .step-navigation { display: flex; justify-content: space-between; margin: 20px 0; font-weight: bold; }
        .step-navigation span { padding: 5px 10px; border-radius: 5px; background: #eee; }
        .step-navigation span.active { background: #007bff; color: white; }
        .radio-group { display: flex; gap: 15px; margin: 5px 0; }
        .radio-group label { display: flex; align-items: center; gap: 5px; cursor: pointer; }
        .help-text { font-size: 0.9em; color: #666; margin-top: -10px; }
        .btn { display: inline-block; padding: 8px 16px; text-decoration: none; border-radius: 4px; }
        .btn-primary { background: #007bff; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="lang-selector">
            <form method="GET" style="display:inline;">
                <select name="lang" onchange="this.form.submit()">
                    <option value="en" <?= $lang === 'en' ? 'selected' : '' ?>>English</option>
                    <option value="bg" <?= $lang === 'bg' ? 'selected' : '' ?>>Български</option>
                    <option value="fr" <?= $lang === 'fr' ? 'selected' : '' ?>>Français</option>
                    <option value="de" <?= $lang === 'de' ? 'selected' : '' ?>>Deutsch</option>
                    <option value="ru" <?= $lang === 'ru' ? 'selected' : '' ?>>Русский</option>
                </select>
            </form>
        </div>

        <h1>🚀 <?= $translations[$lang]['title'] ?></h1>

        <?php if ($success): ?>
            <div class="success">
                <?= sprintf($translations[$lang]['success'], htmlspecialchars($admin_user)) ?>
            </div>
            <div class="security-warning">
                <?= $translations[$lang]['security_warning'] ?>
            </div>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="step-navigation">
                <span class="<?= $currentStep === '1' ? 'active' : '' ?>"><?= $translations[$lang]['step1_title'] ?></span>
                <span class="<?= $currentStep === '2' ? 'active' : '' ?>"><?= $translations[$lang]['step2_title'] ?></span>
            </div>

            <?php if ($currentStep === '1'): ?>
                <form method="POST">
                    <input type="hidden" name="language" value="<?= $lang ?>">
                    <input type="hidden" name="step" value="1">

                    <h3><?= $translations[$lang]['tracker_settings'] ?></h3>

                    <label><?= $translations[$lang]['tracker_name'] ?></label>
                    <input type="text" name="tracker_name" value="<?= htmlspecialchars($_POST['tracker_name'] ?? 'TorrentBG') ?>" required>

                    <label><?= $translations[$lang]['tracker_url'] ?></label>
                    <input type="url" name="tracker_url" value="<?= htmlspecialchars(trim($_POST['tracker_url'] ?? 'https://your-tracker.com')) ?>" required>

                    <label><?= $translations[$lang]['announce_url'] ?></label>
                    <input type="url" name="announce_url" value="<?= htmlspecialchars(trim($_POST['announce_url'] ?? 'http://your-tracker.com:8080/announce')) ?>" required>

                    <label><?= $translations[$lang]['tracker_mode'] ?></label>
                    <div class="radio-group">
                        <label><input type="radio" name="tracker_mode" value="private" <?= ($_POST['tracker_mode'] ?? 'open') === 'private' ? 'checked' : '' ?>> <?= $translations[$lang]['private_mode'] ?></label>
                        <label><input type="radio" name="tracker_mode" value="open" <?= ($_POST['tracker_mode'] ?? 'open') === 'open' ? 'checked' : '' ?>> <?= $translations[$lang]['open_mode'] ?></label>
                    </div>

                    <label><?= $translations[$lang]['tracker_email'] ?></label>
                    <input type="email" name="tracker_email" value="<?= htmlspecialchars(trim($_POST['tracker_email'] ?? 'admin@your-tracker.com')) ?>" required>

                    <label><?= $translations[$lang]['omdb_api_key'] ?></label>
                    <input type="text" name="omdb_api_key" value="<?= htmlspecialchars(trim($_POST['omdb_api_key'] ?? '')) ?>">
                    <div class="help-text"><?= $translations[$lang]['get_key_from'] ?> <a href="https://www.omdbapi.com/apikey.aspx" target="_blank">OMDb API</a>.</div>

                    <br>
                    <button type="submit" name="save_settings"><?= $translations[$lang]['save_settings'] ?></button>
                    <button type="submit" name="next_step" style="background: #6c757d; margin-left: 10px;"><?= $translations[$lang]['next_step'] ?></button>
                </form>

            <?php elseif ($currentStep === '2'): ?>
                <form method="POST">
                    <input type="hidden" name="language" value="<?= $lang ?>">
                    <input type="hidden" name="step" value="2">

                    <h3><?= $translations[$lang]['db_config'] ?></h3>
                    <?php $step2Data = $_SESSION['install_step2'] ?? []; ?>
                    <label><?= $translations[$lang]['host'] ?></label>
                    <input type="text" name="db_host" value="<?= htmlspecialchars($step2Data['db_host'] ?? 'localhost') ?>" required>

                    <label><?= $translations[$lang]['username'] ?></label>
                    <input type="text" name="db_user" value="<?= htmlspecialchars($step2Data['db_user'] ?? '') ?>" required>

                    <label><?= $translations[$lang]['password'] ?></label>
                    <input type="password" name="db_pass" value="">

                    <label><?= $translations[$lang]['db_name'] ?></label>
                    <input type="text" name="db_name" value="<?= htmlspecialchars($step2Data['db_name'] ?? '') ?>" required>

                    <h3><?= $translations[$lang]['admin_config'] ?></h3>
                    <label><?= $translations[$lang]['admin_user'] ?></label>
                    <input type="text" name="admin_user" value="<?= htmlspecialchars($step2Data['admin_user'] ?? '') ?>" required>

                    <label><?= $translations[$lang]['admin_pass'] ?></label>
                    <input type="password" name="admin_pass" required>

                    <label><?= $translations[$lang]['admin_email'] ?></label>
                    <input type="email" name="admin_email" value="<?= htmlspecialchars($step2Data['admin_email'] ?? '') ?>" required>

                    <input type="hidden" name="install" value="1">
                    <br>
                    <button type="submit"><?= $translations[$lang]['install_button'] ?></button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>