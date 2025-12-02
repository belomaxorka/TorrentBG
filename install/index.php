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

// Езикови низове
$translations = [
    'en' => [
        'title' => 'TorrentBG Installation',
        'success' => '✅ Installation successful! Admin user: <strong>%s</strong><br><br><a href="/" class="btn btn-primary">👉 Go to site</a>',
        'db_config' => '⚙️ MySQL Configuration',
        'host' => 'Host (e.g. localhost)',
        'username' => 'Username',
        'password' => 'Password',
        'db_name' => 'Database Name',
        'admin_config' => '👑 Administrator',
        'admin_user' => 'Username',
        'admin_pass' => 'Password',
        'admin_email' => 'Email',
        'install_button' => '🚀 Install',
        'select_language' => 'Select Language',
        'errors' => [
            'db_fields' => 'Please fill all database fields.',
            'admin_fields' => 'Please fill all administrator fields.',
            'installation_error' => 'Installation error: %s',
            'sql_missing' => 'Missing SQL file: /sql/database.sql — please create it first.',
        ],
        'tracker_settings' => '⚙️ Tracker Settings',
        'tracker_name' => 'Tracker Name',
        'tracker_url' => 'Tracker URL',
        'announce_url' => 'Announce URL',
        'tracker_mode' => 'Tracker Mode',
        'private_mode' => 'Private (requires passkey)',
        'open_mode' => 'Open (accepts everyone)',
        'tracker_email' => 'Tracker Email',
        'omdb_api_key' => 'OMDb API Key',
        'get_key_from' => 'Get free key from OMDb API. Without key, IMDb data will not be displayed.',
        'save_settings' => 'Save Settings',
        'next_step' => 'Next Step →',
        'step1_title' => 'Step 1: Tracker Settings',
        'step2_title' => 'Step 2: Database & Admin Setup',
        'security_warning' => '❗️ <strong>For your security, please DELETE or RENAME the <code>/install/</code> folder immediately!</strong><br>Leaving it may allow attackers to reinstall or compromise your site.',
    ],
    'bg' => [
        'title' => 'Инсталация на TorrentBG',
        'success' => '✅ Инсталацията завърши успешно! Администратор: <strong>%s</strong><br><br><a href="/" class="btn btn-primary">👉 Към сайта</a>',
        'db_config' => '⚙️ MySQL Конфигурация',
        'host' => 'Хост (напр. localhost)',
        'username' => 'Потребител',
        'password' => 'Парола',
        'db_name' => 'Име на базата данни',
        'admin_config' => '👑 Администратор',
        'admin_user' => 'Потребителско име',
        'admin_pass' => 'Парола',
        'admin_email' => 'Имейл',
        'install_button' => '🚀 Инсталирай',
        'select_language' => 'Избери Език',
        'errors' => [
            'db_fields' => 'Моля, попълнете всички полета за базата данни.',
            'admin_fields' => 'Моля, попълнете администраторските данни.',
            'installation_error' => 'Грешка при инсталация: %s',
            'sql_missing' => 'Липсва SQL файл: /sql/database.sql — моля, създайте го предварително.',
        ],
        'tracker_settings' => '⚙️ Настройки на тракера',
        'tracker_name' => 'Име на тракера',
        'tracker_url' => 'URL на тракера',
        'announce_url' => 'Анонс URL',
        'tracker_mode' => 'Режим на тракера',
        'private_mode' => 'Частен (изисква passkey)',
        'open_mode' => 'Отворен (приема всички)',
        'tracker_email' => 'Имейл на тракера',
        'omdb_api_key' => 'OMDb API ключ',
        'get_key_from' => 'Получете безплатен ключ от OMDb API. Без ключ, IMDb данните няма да се показват.',
        'save_settings' => 'Запази настройките',
        'next_step' => 'Следващият стъп →',
        'step1_title' => 'Стъпка 1: Настройки на тракера',
        'step2_title' => 'Стъпка 2: База данни и администратор',
        'security_warning' => '❗️ <strong>За ваша сигурност, МОЛЯ ИЗТРИЙТЕ или ПРЕИМЕНУВАЙТЕ папка <code>/install/</code> веднага!</strong><br>Ако я оставите, злонамерени лица могат да преинсталират или компрометират сайта ви.',
    ],
    'fr' => [
        'title' => 'Installation du TorrentBG',
        'success' => '✅ Installation réussie ! Utilisateur admin : <strong>%s</strong><br><br><a href="/" class="btn btn-primary">👉 Aller au site</a>',
        'db_config' => '⚙️ Configuration MySQL',
        'host' => 'Hôte (ex: localhost)',
        'username' => 'Nom d\'utilisateur',
        'password' => 'Mot de passe',
        'db_name' => 'Nom de la base de données',
        'admin_config' => '👑 Administrateur',
        'admin_user' => 'Nom d\'utilisateur',
        'admin_pass' => 'Mot de passe',
        'admin_email' => 'Email',
        'install_button' => '🚀 Installer',
        'select_language' => 'Sélectionner la langue',
        'errors' => [
            'db_fields' => 'Veuillez remplir tous les champs de la base de données.',
            'admin_fields' => 'Veuillez remplir les informations de l\'administrateur.',
            'installation_error' => 'Erreur d\'installation : %s',
            'sql_missing' => 'Fichier SQL manquant : /sql/database.sql — veuillez le créer d\'abord.',
        ],
        'tracker_settings' => '⚙️ Paramètres du tracker',
        'tracker_name' => 'Nom du tracker',
        'tracker_url' => 'URL du tracker',
        'announce_url' => 'URL d\'annonce',
        'tracker_mode' => 'Mode du tracker',
        'private_mode' => 'Privé (nécessite une clé)',
        'open_mode' => 'Ouvert (accepte tout le monde)',
        'tracker_email' => 'Email du tracker',
        'omdb_api_key' => 'Clé API OMDb',
        'get_key_from' => 'Obtenez une clé gratuite sur OMDb API. Sans clé, les données IMDb ne seront pas affichées.',
        'save_settings' => 'Enregistrer les paramètres',
        'next_step' => 'Étape suivante →',
        'step1_title' => 'Étape 1 : Paramètres du tracker',
        'step2_title' => 'Étape 2 : Base de données et administrateur',
        'security_warning' => '❗️ <strong>Pour votre sécurité, veuillez SUPPRIMER ou RENOMMER le dossier <code>/install/</code> immédiatement !</strong><br>Le laisser pourrait permettre à des attaquants de réinstaller ou de compromettre votre site.',
    ],
    'de' => [
        'title' => 'TorrentBG Installation',
        'success' => '✅ Installation erfolgreich! Admin-Benutzer: <strong>%s</strong><br><br><a href="/" class="btn btn-primary">👉 Zur Website</a>',
        'db_config' => '⚙️ MySQL Konfiguration',
        'host' => 'Host (z.B. localhost)',
        'username' => 'Benutzername',
        'password' => 'Passwort',
        'db_name' => 'Datenbankname',
        'admin_config' => '👑 Administrator',
        'admin_user' => 'Benutzername',
        'admin_pass' => 'Passwort',
        'admin_email' => 'E-Mail',
        'install_button' => '🚀 Installieren',
        'select_language' => 'Sprache auswählen',
        'errors' => [
            'db_fields' => 'Bitte füllen Sie alle Datenbankfelder aus.',
            'admin_fields' => 'Bitte füllen Sie alle Administratorfelder aus.',
            'installation_error' => 'Installationsfehler: %s',
            'sql_missing' => 'SQL-Datei fehlt: /sql/database.sql — bitte erstellen Sie sie zuerst.',
        ],
        'tracker_settings' => '⚙️ Tracker-Einstellungen',
        'tracker_name' => 'Name des Trackers',
        'tracker_url' => 'URL des Trackers',
        'announce_url' => 'Ankündigungs-URL',
        'tracker_mode' => 'Tracker-Modus',
        'private_mode' => 'Privat (benötigt Passkey)',
        'open_mode' => 'Öffentlich (akzeptiert alle)',
        'tracker_email' => 'E-Mail des Trackers',
        'omdb_api_key' => 'OMDb API-Schlüssel',
        'get_key_from' => 'Hol dir einen kostenlosen Schlüssel von OMDb API. Ohne Schlüssel werden keine IMDb-Daten angezeigt.',
        'save_settings' => 'Einstellungen speichern',
        'next_step' => 'Nächster Schritt →',
        'step1_title' => 'Schritt 1: Tracker-Einstellungen',
        'step2_title' => 'Schritt 2: Datenbank und Administrator',
        'security_warning' => '❗️ <strong>Löschen oder benennen Sie aus Sicherheitsgründen bitte SOFORT den Ordner <code>/install/</code> um!</strong><br>Wenn Sie ihn belassen, könnten Angreifer Ihre Seite neu installieren oder kompromittieren.',
    ],
    'ru' => [
        'title' => 'Установка TorrentBG',
        'success' => '✅ Установка успешна! Администратор: <strong>%s</strong><br><br><a href="/" class="btn btn-primary">👉 Перейти на сайт</a>',
        'db_config' => '⚙️ Конфигурация MySQL',
        'host' => 'Хост (например localhost)',
        'username' => 'Имя пользователя',
        'password' => 'Пароль',
        'db_name' => 'Имя базы данных',
        'admin_config' => '👑 Администратор',
        'admin_user' => 'Имя пользователя',
        'admin_pass' => 'Пароль',
        'admin_email' => 'Email',
        'install_button' => '🚀 Установить',
        'select_language' => 'Выберите язык',
        'errors' => [
            'db_fields' => 'Пожалуйста, заполните все поля базы данных.',
            'admin_fields' => 'Пожалуйста, заполните данные администратора.',
            'installation_error' => 'Ошибка установки: %s',
            'sql_missing' => 'Отсутствует SQL-файл: /sql/database.sql — пожалуйста, создайте его заранее.',
        ],
        'tracker_settings' => '⚙️ Настройки трекера',
        'tracker_name' => 'Имя трекера',
        'tracker_url' => 'URL трекера',
        'announce_url' => 'Анонс URL',
        'tracker_mode' => 'Режим трекера',
        'private_mode' => 'Частный (требует passkey)',
        'open_mode' => 'Открытый (принимает всех)',
        'tracker_email' => 'Email трекера',
        'omdb_api_key' => 'Ключ OMDb API',
        'get_key_from' => 'Получите бесплатный ключ от OMDb API. Без ключа IMDb данные не будут показаны.',
        'save_settings' => 'Сохранить настройки',
        'next_step' => 'Следующий шаг →',
        'step1_title' => 'Шаг 1: Настройки трекера',
        'step2_title' => 'Шаг 2: База данных и администратор',
        'security_warning' => '❗️ <strong>В целях безопасности НЕМЕДЛЕННО УДАЛИТЕ или ПЕРЕИМЕНУЙТЕ папку <code>/install/</code>!</strong><br>Если оставить её, злоумышленники смогут переустановить или скомпрометировать ваш сайт.',
    ]
];

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

    if (empty($host) || empty($user) || empty($name)) {
        $errors[] = $translations[$lang]['errors']['db_fields'];
    }
    if (empty($admin_user) || empty($admin_pass) || empty($admin_email)) {
        $errors[] = $translations[$lang]['errors']['admin_fields'];
    }

    if (empty($errors)) {
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

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
                $settingsToSave = [
                    'site_name'         => $step1Data['tracker_name'] ?? 'TorrentBG',
                    'site_url'          => $step1Data['tracker_url'] ?? '',
                    'tracker_announce'  => $step1Data['announce_url'] ?? '',
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

                if (!file_put_contents($configPath, $configContent)) {
                    throw new Exception("Failed to update config.php");
                }

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
                    <label><?= $translations[$lang]['host'] ?></label>
                    <input type="text" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>

                    <label><?= $translations[$lang]['username'] ?></label>
                    <input type="text" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" required>

                    <label><?= $translations[$lang]['password'] ?></label>
                    <input type="password" name="db_pass" value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>">

                    <label><?= $translations[$lang]['db_name'] ?></label>
                    <input type="text" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" required>

                    <h3><?= $translations[$lang]['admin_config'] ?></h3>
                    <label><?= $translations[$lang]['admin_user'] ?></label>
                    <input type="text" name="admin_user" value="<?= htmlspecialchars($_POST['admin_user'] ?? '') ?>" required>

                    <label><?= $translations[$lang]['admin_pass'] ?></label>
                    <input type="password" name="admin_pass" required>

                    <label><?= $translations[$lang]['admin_email'] ?></label>
                    <input type="email" name="admin_email" value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" required>

                    <input type="hidden" name="install" value="1">
                    <br>
                    <button type="submit"><?= $translations[$lang]['install_button'] ?></button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>