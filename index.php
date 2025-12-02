<?php
// === ДОБАВЕНО: Проверка за инсталация — пренасочва към install/index.php ===
$configPath = __DIR__ . '/includes/config.php';
if (file_exists($configPath)) {
    $config = require $configPath;
    if (!($config['site']['installed'] ?? false)) {
        header('Location: install/index.php');
        exit;
    }
} else {
    // Ако config.php липсва — също пренасочи към инсталатора
    header('Location: install/index.php');
    exit;
}
// ========================================================================

require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/includes/BlockManager.php';

$lang = new Language($_SESSION['lang'] ?? 'en');

// Инициализираме BlockManager
$blockManager = new BlockManager($pdo);

// Главно съдържание
?>
<div class="container-fluid" id="main-content">
    <div class="alert alert-info">
        <?= $lang->get('welcome_to_site') ?>
    </div>

    <div class="row">
        <!-- Лява колона -->
        <div class="col-md-3">
            <?php
            // 1. Първо: показваме всички активни блокове от BlockManager (вкл. "Потребителска информация")
            $leftBlocks = $blockManager->getBlocksByPosition('left');
            foreach ($leftBlocks as $block) {
                if ($block['is_active']) {
                    $blockManager->renderBlock($block['name'], $pdo, $auth, $lang);
                }
            }

            // 2. След това: винаги добавяме анкетата
if (file_exists(__DIR__ . '/blocks/poll.php')) {
    if (!defined('IN_BLOCK')) {
        define('IN_BLOCK', true);
    }
    include __DIR__ . '/blocks/poll.php';
}
            ?>
        </div>

        <!-- Централна колона -->
        <div class="col-md-6">
            <?php
            $centerBlocks = $blockManager->getBlocksByPosition('center');
            foreach ($centerBlocks as $block) {
                if ($block['is_active']) {
                    $blockManager->renderBlock($block['name'], $pdo, $auth, $lang);
                }
            }
            ?>
        </div>

        <!-- Дясна колона -->
        <div class="col-md-3">
            <?php
            $rightBlocks = $blockManager->getBlocksByPosition('right');
            foreach ($rightBlocks as $block) {
                if ($block['is_active']) {
                    $blockManager->renderBlock($block['name'], $pdo, $auth, $lang);
                }
            }
            ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>