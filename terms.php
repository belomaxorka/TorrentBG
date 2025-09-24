<?php
require_once __DIR__ . '/templates/header.php';
$lang = new Language($_SESSION['lang'] ?? 'en');
?>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0"><?= $lang->get('terms_of_service') ?></h2>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i><?= $lang->get('last_updated') ?>: <strong><?= date('d.m.Y') ?></strong>
                </div>

                <h4 class="mt-4"><?= $lang->get('1_acceptance_of_terms') ?></h4>
                <p><?= $lang->get('acceptance_of_terms_text') ?></p>

                <h4 class="mt-4"><?= $lang->get('2_use_of_service') ?></h4>
                <p><?= $lang->get('use_of_service_text') ?></p>

                <h4 class="mt-4"><?= $lang->get('3_user_conduct') ?></h4>
                <p><?= $lang->get('user_conduct_text') ?></p>

                <h4 class="mt-4"><?= $lang->get('4_content_ownership') ?></h4>
                <p><?= $lang->get('content_ownership_text') ?></p>

                <h4 class="mt-4"><?= $lang->get('5_disclaimer') ?></h4>
                <p><?= $lang->get('disclaimer_text') ?></p>

                <h4 class="mt-4"><?= $lang->get('6_termination') ?></h4>
                <p><?= $lang->get('termination_text') ?></p>

                <h4 class="mt-4"><?= $lang->get('7_governing_law') ?></h4>
                <p><?= $lang->get('governing_law_text') ?></p>

                <div class="alert alert-warning mt-4">
                    <i class="bi bi-exclamation-triangle me-2"></i><?= $lang->get('terms_agreement_warning') ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>