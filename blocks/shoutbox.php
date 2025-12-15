<?php
if (!defined('IN_BLOCK')) die('Direct access not allowed.');

$canShout = $auth->isLoggedIn();

$stmt = $pdo->prepare("
    SELECT s.id, s.message, s.created_at, u.username, u.rank, u.id as user_id
    FROM shoutbox s 
    JOIN users u ON s.user_id = u.id 
    ORDER BY s.created_at DESC LIMIT 10
");
$stmt->execute();
$messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

if (!isset($shoutboxTableCreated)) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `shoutbox` (
              `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              `user_id` INT UNSIGNED NOT NULL,
              `message` TEXT NOT NULL,
              `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $shoutboxTableCreated = true;
    } catch (Exception $e) {
        error_log("Error creating shoutbox table: " . $e->getMessage());
    }
}

// ✅ Generate unique timestamp
$timestamp = time();
?>

<div class="card mb-4" id="shoutboxCard">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><?= htmlspecialchars($lang->get('shoutbox')) ?></span>
        <?php if ($canShout): ?>
            <button class="btn btn-sm btn-outline-secondary" id="smilesBtn">😊</button>
        <?php endif; ?>
    </div>
    <div class="card-body p-2">
        <div id="shoutboxMessages" style="max-height: 300px; overflow-y: auto;">
            <?php if (empty($messages)): ?>
                <div class="text-muted small"><?= htmlspecialchars($lang->get('shoutbox_empty')) ?></div>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="mb-2 d-flex justify-content-between align-items-start">
                        <div>
                            <strong><?= htmlspecialchars($msg['username']) ?>:</strong>
                            <span class="message-content">
                                <?php
                                $message = htmlspecialchars($msg['message']);
                                $smileMap = [
                                    'smile' => '😊',
                                    'wink' => '😉',
                                    'grin' => '😀',
                                    'tongue' => '😛',
                                    'laugh' => '😂',
                                    'sad' => '😢',
                                    'angry' => '😠',
                                    'shock' => '😲',
                                    'cool' => '😎',
                                    'blush' => '😳'
                                ];
                                $message = preg_replace_callback('/\[smile=([a-z]+)\]/', function($matches) use ($smileMap) {
                                    return $smileMap[$matches[1]] ?? $matches[0];
                                }, $message);
                                echo $message;
                                ?>
                            </span>
                            <div class="small text-muted"><?= date('H:i', strtotime($msg['created_at'])) ?></div>
                        </div>
                        <?php if ($auth->isLoggedIn() && ($auth->getRank() >= 5 || $auth->getUser()['id'] == $msg['user_id'])): ?>
                            <button class="btn btn-sm btn-outline-danger delete-btn ms-2" data-id="<?= $msg['id'] ?>" title="<?= htmlspecialchars($lang->get('delete_message')) ?>">
                                🗑️
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($canShout): ?>
        <div class="card-footer p-2">
            <form id="shoutForm" class="d-flex">
                <input type="text" name="message" class="form-control form-control-sm me-2" placeholder="<?= htmlspecialchars($lang->get('type_message')) ?>" required maxlength="255">
                <button type="submit" class="btn btn-primary btn-sm"><?= htmlspecialchars($lang->get('send')) ?></button>
            </form>
        </div>
    <?php else: ?>
        <div class="card-footer p-2 text-center">
            <a href="/login.php"><?= htmlspecialchars($lang->get('login_to_shout')) ?></a>
        </div>
    <?php endif; ?>

    <?php if ($auth->isLoggedIn() && $auth->getRank() >= 6): ?>
        <div class="card-footer p-2 bg-light">
            <button id="clearShoutboxBtn" class="btn btn-sm btn-outline-danger" title="<?= htmlspecialchars($lang->get('clear_shoutbox_confirm')) ?>">
                <?= htmlspecialchars($lang->get('clear_shoutbox')) ?>
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Modal window for smiles -->
<div class="modal fade" id="smilesModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= htmlspecialchars($lang->get('smiles')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="row g-2" id="smilesGrid">
                    <?php
                    $smileCodes = ['smile', 'wink', 'grin', 'tongue', 'laugh', 'sad', 'angry', 'shock', 'cool', 'blush'];
                    foreach ($smileCodes as $smileCode):
                        $smileFile = null;
                        if (file_exists(__DIR__ . '/../images/smiles/' . $smileCode . '.gif')) {
                            $smileFile = $smileCode . '.gif';
                        } elseif (file_exists(__DIR__ . '/../images/smiles/' . $smileCode . '.png')) {
                            $smileFile = $smileCode . '.png';
                        }
                        
                        if ($smileFile):
                    ?>
                        <div class="col-3 text-center">
                            <img src="/images/smiles/<?= $smileFile ?>" class="img-fluid smile-img" alt="<?= htmlspecialchars($smileCode) ?>" style="cursor: pointer; max-height: 40px;" data-code="<?= $smileCode ?>">
                        </div>
                    <?php
                        endif;
                    endforeach;
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('shoutForm');
    const messagesContainer = document.getElementById('shoutboxMessages');

    // 🚀 Send message
    form?.addEventListener('submit', function(e) {
        e.preventDefault();
        const input = this.message;
        if (!input.value.trim()) return;

        fetch('/shoutbox.php?action=post&_=<?= $timestamp ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'message=' + encodeURIComponent(input.value)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                input.value = '';
                loadMessages();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(err => {
            console.error('Post error:', err);
        });
    });

    // 🔄 Load messages
    function loadMessages() {
        fetch('/shoutbox.php?action=get&_=<?= $timestamp ?>')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                messagesContainer.innerHTML = data.html;
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            } else {
                console.error('Error loading messages:', data.error);
            }
        })
        .catch(err => {
            console.error('Fetch error:', err);
        });
    }

    // 🔄 Auto refresh
    loadMessages();
    setInterval(loadMessages, 10000);

    // ✅ DELEGATED EVENTS — work for dynamically added elements

    // 😊 Open smiles modal
    document.addEventListener('click', function(e) {
        if (e.target.id === 'smilesBtn' || e.target.closest('#smilesBtn')) {
            const modal = new bootstrap.Modal(document.getElementById('smilesModal'));
            modal.show();
        }
    });

    // 🖼️ Insert smile
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('smile-img')) {
            const smileCode = e.target.getAttribute('data-code');
            const textarea = document.querySelector('#shoutForm input[name="message"]');
            if (textarea) {
                textarea.value += ' [smile=' + smileCode + '] ';
                textarea.focus();
            }
            const modal = bootstrap.Modal.getInstance(document.getElementById('smilesModal'));
            if (modal) modal.hide();
        }
    });

    // 🗑️ Delete message
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-btn')) {
            const id = e.target.getAttribute('data-id');
            if (confirm('<?= addslashes($lang->get('confirm_delete_message')) ?>')) {
                fetch('/shoutbox.php?action=delete&_=<?= $timestamp ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + id
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        loadMessages();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(err => {
                    console.error('Delete error:', err);
                });
            }
        }
    });

    // 🧹 Clear history
    document.addEventListener('click', function(e) {
        if (e.target.id === 'clearShoutboxBtn' || e.target.closest('#clearShoutboxBtn')) {
            if (confirm('<?= addslashes($lang->get('confirm_clear_shoutbox')) ?>')) {
                fetch('/shoutbox.php?action=clear&_=<?= $timestamp ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        loadMessages();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(err => {
                    console.error('Clear error:', err);
                });
            }
        }
    });
});
</script>