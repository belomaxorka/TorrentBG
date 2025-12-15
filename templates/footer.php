<?php
// Calculate page load time
$endTime = microtime(true);
$loadTime = round(($endTime - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2); // in milliseconds
?>

    </div> <!-- container -->
</main>

<footer class="bg-light py-4 mt-5 border-top">
    <div class="container">
        <div class="row">
            <div class="col-md-6 text-center text-md-start">
                <p class="mb-1">
                    <strong>TorrentBG</strong> &copy; <?= date('Y') ?>. <?= $lang->get('all_rights_reserved') ?>.
                </p>
                <p class="text-muted small mb-0">
                    <?= $lang->get('page_generated_in') ?> <strong><?= $loadTime ?></strong> <?= $lang->get('milliseconds') ?>
                </p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <p class="mb-1">
                    <a href="/terms.php" class="text-decoration-none"><?= $lang->get('terms_of_service') ?></a> | 
                    <a href="/privacy.php" class="text-decoration-none"><?= $lang->get('privacy_policy') ?></a> | 
                    <a href="/contact.php" class="text-decoration-none"><?= $lang->get('contact') ?></a>
                </p>
                <p class="text-muted small mb-0">
                    <?= $lang->get('powered_by') ?> <strong>PHP 8.4</strong> &amp; <strong>MySQL</strong>
                </p>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>