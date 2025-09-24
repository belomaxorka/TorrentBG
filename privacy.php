<?php
require_once __DIR__ . '/templates/header.php';
$lang = new Language($_SESSION['lang'] ?? 'en');
?>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h2 class="mb-0"><?= $lang->get('privacy_policy') ?></h2>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i><?= $lang->get('last_updated') ?>: <strong><?= date('d.m.Y') ?></strong>
                </div>

                <h4 class="mt-4"><?= $lang->get('1_information_we_collect') ?></h4>
                <p><?= $lang->get('information_we_collect_text') ?></p>

                <h4 class="mt-4"><?= $lang->get('2_how_we_use_information') ?></h4>
                <p><?= $lang->get('how_we_use_information_text') ?></p>

                <h4 class="mt-4"><?= $lang->get('3_data_protection') ?></h4>
                <p><?= $lang->get('data_protection_text') ?></p>

                <h4 class="mt-4"><?= $lang->get('4_cookies') ?></h4>
                <p><?= $lang->get('cookies_text') ?></p>

                <h4 class="mt-4"><?= $lang->get('5_third_party_services') ?></h4>
                <p><?= $lang->get('third_party_services_text') ?></p>

                <h4 class="mt-4"><?= $lang->get('6_your_rights') ?></h4>
                <p><?= $lang->get('your_rights_text') ?></p>

                <h4 class="mt-4"><?= $lang->get('7_policy_changes') ?></h4>
                <p><?= $lang->get('policy_changes_text') ?></p>

                <div class="alert alert-warning mt-4">
                    <i class="bi bi-exclamation-triangle me-2"></i><?= $lang->get('privacy_agreement_warning') ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>