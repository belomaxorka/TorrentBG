<?php
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
            $leftBlocks = $blockManager->getBlocksByPosition('left');
            foreach ($leftBlocks as $block) {
                if ($block['is_active']) {
                    $blockManager->renderBlock($block['name'], $pdo, $auth, $lang);
                }
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