<?php
declare(strict_types=1);

// Задължително UTF-8 + header
header('Content-Type: text/html; charset=utf-8');

// Дефинираме ROOT пътя
$rootPath = dirname(__DIR__) . DIRECTORY_SEPARATOR;

// Проверка дали вече е инсталиран
if (file_exists($rootPath . 'config.php')) {
    die('<h2 style="color:red; text-align:center;">✅ Системата вече е инсталирана! Махнете или преименувайте папка /install/ за сигурност.</h2>');
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
        'title' => 'Torrent Tracker Installation',
        'success' => '✅ Installation successful! Admin user: <strong>%s</strong><br><br><a href="/index.php">👉 Go to site</a> | <strong>❗️ Delete or rename /install/ folder for security!</strong>',
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
        ]
    ],
    'bg' => [
        'title' => 'Инсталация на Торент Тракер',
        'success' => '✅ Инсталацията завърши успешно! Администратор: <strong>%s</strong><br><br><a href="/index.php">👉 Към сайта</a> | <strong>❗️ Изтрийте или преименувайте папка /install/ за сигурност!</strong>',
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
        ]
    ],
    'fr' => [
        'title' => 'Installation du Tracker Torrent',
        'success' => '✅ Installation réussie ! Utilisateur admin : <strong>%s</strong><br><br><a href="/index.php">👉 Aller au site</a> | <strong>❗️ Supprimez ou renommez le dossier /install/ pour la sécurité !</strong>',
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
        ]
    ],
    'de' => [
        'title' => 'Torrent Tracker Installation',
        'success' => '✅ Installation erfolgreich! Admin-Benutzer: <strong>%s</strong><br><br><a href="/index.php">👉 Zur Website</a> | <strong>❗️ Löschen oder benennen Sie den Ordner /install/ aus Sicherheitsgründen um!</strong>',
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
        ]
    ],
    'ru' => [
        'title' => 'Установка Торрент Трекера',
        'success' => '✅ Установка успешна! Администратор: <strong>%s</strong><br><br><a href="/index.php">👉 Перейти на сайт</a> | <strong>❗️ Удалите или переименуйте папку /install/ для безопасности!</strong>',
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
        ]
    ]
];

$errors = [];
$success = false;

// Ако е изпратена форма
if ($_POST['install'] ?? false) {
    $host = $_POST['db_host'] ?? '';
    $user = $_POST['db_user'] ?? '';
    $pass = $_POST['db_pass'] ?? '';
    $name = $_POST['db_name'] ?? '';
    $admin_user = $_POST['admin_user'] ?? '';
    $admin_pass = $_POST['admin_pass'] ?? '';
    $admin_email = $_POST['admin_email'] ?? '';
    $lang = $_POST['language'] ?? 'en';

    // Валидация
    if (empty($host) || empty($user) || empty($name)) {
        $errors[] = $translations[$lang]['errors']['db_fields'];
    }
    if (empty($admin_user) || empty($admin_pass) || empty($admin_email)) {
        $errors[] = $translations[$lang]['errors']['admin_fields'];
    }

    if (empty($errors)) {
        try {
            // Тестваме връзка с базата
            $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            // Създаваме папка sql ако не съществува
            if (!is_dir(__DIR__ . '/../sql')) {
                mkdir(__DIR__ . '/../sql', 0755, true);
            }

            // Път към SQL файла
            $sqlFile = __DIR__ . '/../sql/database.sql';

            // Проверка дали SQL файлът съществува
            if (!file_exists($sqlFile)) {
                $errors[] = "SQL файлът не съществува: $sqlFile";
            } else {
                // Четем и изпълняваме SQL файла
                $sql = file_get_contents($sqlFile);
                $statements = explode(';', $sql);
                
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement)) {
                        $pdo->exec($statement);
                    }
                }
            }

            // Създаваме администратор — ПОПРАВЕНО: `rank` с backticks
            $hashedPass = password_hash($admin_pass, PASSWORD_ARGON2ID);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, `rank`, language) VALUES (?, ?, ?, 6, ?)");
            $stmt->execute([$admin_user, $admin_email, $hashedPass, $lang]);

            // Генерираме config.php — СЕГА В ROOT ДИРЕКТОРИЯТА
            $configContent = "<?php\n" .
                "declare(strict_types=1);\n\n" .
                "return [\n" .
                "    'db' => [\n" .
                "        'host' => '$host',\n" .
                "        'user' => '$user',\n" .
                "        'pass' => '$pass',\n" .
                "        'name' => '$name',\n" .
                "        'charset' => 'utf8mb4',\n" .
                "    ],\n" .
                "    'site' => [\n" .
                "        'name' => 'Torrent Tracker',\n" .
                "        'default_lang' => '$lang',\n" .
                "        'default_style' => 'light',\n" .
                "        'installed' => true,\n" .
                "    ],\n" .
                "];\n";

            // Записваме в root директорията
            $configPath = $rootPath . 'config.php';
            if (!file_put_contents($configPath, $configContent)) {
                throw new Exception("Неуспешно създаване на config.php в: $configPath");
            }

            $success = true;

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
        .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        label { display: block; margin-top: 15px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; margin: 5px 0 15px; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; width: 100%; font-size: 16px; }
        button:hover { background: #0056b3; }
        .lang-selector { text-align: right; margin-bottom: 20px; }
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
            <div class="success"><?= sprintf($translations[$lang]['success'], htmlspecialchars($admin_user)) ?></div>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="language" value="<?= $lang ?>">

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
    </div>
</body>
</html>