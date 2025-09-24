<?php
// Първо — дефинираме namespace Bencode
namespace Bencode {
    class Bencode {
        public static function decode($string) {
            if (!is_string($string)) {
                throw new \InvalidArgumentException('Input must be a string');
            }
            $data = $string;
            $result = self::decodeValue($data);
            if (strlen($data) > 0) {
                throw new \RuntimeException('Unexpected data at end of input');
            }
            return $result;
        }

        private static function decodeValue(&$data) {
            if ($data === '') {
                throw new \RuntimeException('Unexpected end of data');
            }
            $first = $data[0];
            if ($first === 'd') {
                return self::decodeDict($data);
            } elseif ($first === 'l') {
                return self::decodeList($data);
            } elseif ($first === 'i') {
                return self::decodeInt($data);
            } elseif (ctype_digit($first)) {
                return self::decodeString($data);
            } else {
                throw new \RuntimeException('Invalid bencoded value (unexpected character: ' . $first . ')');
            }
        }

        private static function decodeString(&$data) {
            $colon = strpos($data, ':');
            if ($colon === false) {
                throw new \RuntimeException('Invalid string: missing colon');
            }
            $length = substr($data, 0, $colon);
            if (!ctype_digit($length) || ($length[0] === '0' && strlen($length) > 1)) {
                throw new \RuntimeException('Invalid string length: ' . $length);
            }
            $length = (int)$length;
            $data = substr($data, $colon + 1);
            if (strlen($data) < $length) {
                throw new \RuntimeException('String too short (expected ' . $length . ' bytes)');
            }
            $string = substr($data, 0, $length);
            $data = substr($data, $length);
            return $string;
        }

        private static function decodeInt(&$data) {
            $end = strpos($data, 'e');
            if ($end === false) {
                throw new \RuntimeException('Invalid integer: missing end marker');
            }
            $number = substr($data, 1, $end - 1);
            if ($number === '' || ($number[0] === '0' && strlen($number) > 1) || ($number[0] === '-' && strlen($number) > 1 && $number[1] === '0')) {
                throw new \RuntimeException('Invalid integer format: ' . $number);
            }
            $data = substr($data, $end + 1);
            return (int)$number;
        }

        private static function decodeList(&$data) {
            $list = [];
            $data = substr($data, 1); // skip 'l'
            while (strlen($data) > 0 && $data[0] !== 'e') {
                $list[] = self::decodeValue($data);
            }
            if (strlen($data) === 0 || $data[0] !== 'e') {
                throw new \RuntimeException('List not terminated by "e"');
            }
            $data = substr($data, 1); // skip 'e'
            return $list;
        }

        private static function decodeDict(&$data) {
            $dict = [];
            $data = substr($data, 1); // skip 'd'
            while (strlen($data) > 0 && $data[0] !== 'e') {
                $key = self::decodeString($data);
                $value = self::decodeValue($data);
                $dict[$key] = $value;
            }
            if (strlen($data) === 0 || $data[0] !== 'e') {
                throw new \RuntimeException('Dictionary not terminated by "e"');
            }
            $data = substr($data, 1); // skip 'e'
            ksort($dict);
            return $dict;
        }

        public static function encode($value) {
            if (is_string($value)) {
                return strlen($value) . ':' . $value;
            } elseif (is_int($value)) {
                return 'i' . $value . 'e';
            } elseif (is_array($value)) {
                if (self::isList($value)) {
                    $encoded = 'l';
                    foreach ($value as $item) {
                        $encoded .= self::encode($item);
                    }
                    return $encoded . 'e';
                } else {
                    ksort($value);
                    $encoded = 'd';
                    foreach ($value as $key => $val) {
                        if (!is_string($key)) {
                            throw new \InvalidArgumentException('Dictionary keys must be strings');
                        }
                        $encoded .= self::encode($key) . self::encode($val);
                    }
                    return $encoded . 'e';
                }
            } else {
                throw new \InvalidArgumentException('Unsupported type: ' . gettype($value));
            }
        }

        private static function isList($array) {
            if (empty($array)) return true;
            return array_keys($array) === range(0, count($array) - 1);
        }
    }
}

// След namespace — преминаваме към глобалното ниво и включваме зависимости
namespace {
    require_once __DIR__ . '/includes/Database.php';
    require_once __DIR__ . '/includes/Auth.php';
    require_once __DIR__ . '/includes/Language.php';
    require_once __DIR__ . '/includes/functions.php';

    use Bencode\Bencode as BencodeParser;

    $pdo = Database::getInstance();
    $auth = new Auth($pdo);
    $lang = new Language($_SESSION['lang'] ?? 'en');

    if (!$auth->isLoggedIn()) {
        header("Location: login.php");
        exit;
    }

    $error = '';
    $success = false;

    // Извличаме категориите
    $stmt = $pdo->query("SELECT id, name, icon FROM categories WHERE is_active = 1 ORDER BY `order`");
    $categories = $stmt->fetchAll();

    if ($_POST['upload'] ?? false) {
        if (!isset($_FILES['torrent_file']) || $_FILES['torrent_file']['error'] !== UPLOAD_ERR_OK) {
            $error = $lang->get('select_torrent_file');
        } else {
            $file = $_FILES['torrent_file'];
            $allowedTypes = ['application/x-bittorrent', 'application/octet-stream'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            if (!in_array($file['type'], $allowedTypes)) {
                $error = $lang->get('invalid_torrent_file');
            } elseif ($file['size'] > $maxSize) {
                $error = $lang->get('file_too_large');
            } else {
                // Парсваме .torrent файла
                $torrentData = file_get_contents($file['tmp_name']);
                if ($torrentData === false) {
                    $error = $lang->get('upload_failed') . ': Не може да се прочете файла.';
                } else {
                    try {
                        $decoded = BencodeParser::decode($torrentData);
                    } catch (Exception $e) {
                        $decoded = null;
                    }

                    if (!$decoded || !isset($decoded['info'])) {
                        $error = $lang->get('invalid_torrent_structure');
                    } else {
                        $info = $decoded['info'];
                        $name = $info['name'] ?? 'Unknown';
                        $size = calculateTorrentSize($info);
                        $filesCount = isset($info['files']) ? count($info['files']) : 1;

                        // Генерираме info_hash — ВАЖНО: sha1(..., true) за бинарен изход!
                        $infoBencoded = BencodeParser::encode($info);
                        $infoHash = sha1($infoBencoded, true); // ← БИНАРЕН HASH (важно!)

                        // Качваме постер ако има
                        $posterPath = null;
                        if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
                            $poster = $_FILES['poster'];
                            $allowedImageTypes = ['image/jpeg', 'image/png', 'image/webp'];
                            $maxImageSize = 2 * 1024 * 1024; // 2MB

                            if (in_array($poster['type'], $allowedImageTypes) && $poster['size'] <= $maxImageSize) {
                                $ext = pathinfo($poster['name'], PATHINFO_EXTENSION);
                                $posterName = uniqid() . '.' . $ext;
                                $posterPath = 'images/posters/' . $posterName;
                                move_uploaded_file($poster['tmp_name'], $posterPath);
                            }
                        }

                        // Валидираме категорията
                        $categoryId = (int)($_POST['category_id'] ?? 1);
                        $validCategory = false;
                        foreach ($categories as $cat) {
                            if ($cat['id'] == $categoryId) {
                                $validCategory = true;
                                break;
                            }
                        }
                        if (!$validCategory) {
                            $categoryId = 1; // Default category
                        }

                        // Записваме в базата
                        try {
                            $stmt = $pdo->prepare("
                                INSERT INTO torrents 
                                (info_hash, name, description, poster, imdb_link, youtube_link, category_id, uploader_id, size, files_count)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $infoHash, // ← Бинарен хеш — базата трябва да е BLOB или VARCHAR(40) в hex!
                                $name,
                                $_POST['description'] ?? '',
                                $posterPath,
                                $_POST['imdb_link'] ?? null,
                                $_POST['youtube_link'] ?? null,
                                $categoryId,
                                $auth->getUser()['id'],
                                $size,
                                $filesCount
                            ]);

                            // Преместваме .torrent файла
                            $torrentFileName = bin2hex($infoHash) . '.torrent'; // ← Ако искаш filename да е хекс
                            move_uploaded_file($file['tmp_name'], 'torrents/' . $torrentFileName);

                            $success = true;
                        } catch (Exception $e) {
                            $error = $lang->get('upload_failed') . ': ' . $e->getMessage();
                        }
                    }
                }
            }
        }
    }

    require_once __DIR__ . '/templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center"><?= $lang->get('upload_torrent') ?></h3>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?= $lang->get('upload_success') ?><br>
                        <a href="/torrents.php"><?= $lang->get('view_torrents') ?></a>
                    </div>
                <?php else: ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <!-- Ограничаваме максималния размер на файла на 5MB -->
                        <input type="hidden" name="MAX_FILE_SIZE" value="5242880">

                        <div class="mb-3">
                            <label class="form-label"><?= $lang->get('torrent_file') ?> *</label>
                            <input type="file" name="torrent_file" class="form-control" accept=".torrent" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?= $lang->get('category') ?> *</label>
                            <select name="category_id" class="form-select" required>
                                <option value=""><?= $lang->get('select_category') ?></option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>">
                                        <?php if ($cat['icon']): ?>
                                            <img src="/<?= $cat['icon'] ?>" width="20" height="20" class="me-2">
                                        <?php endif; ?>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?= $lang->get('poster') ?> (optional)</label>
                            <input type="file" name="poster" class="form-control" accept="image/*">
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?= $lang->get('imdb_link') ?></label>
                            <input type="url" name="imdb_link" class="form-control" placeholder="https://www.imdb.com/title/tt...  ">
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?= $lang->get('youtube_link') ?></label>
                            <input type="url" name="youtube_link" class="form-control" placeholder="https://www.youtube.com/watch?v=...">
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?= $lang->get('description') ?></label>
                            <textarea name="description" class="form-control" rows="5"></textarea>
                            <div class="form-text"><?= $lang->get('bbc_codes_supported') ?></div>
                        </div>

                        <input type="hidden" name="upload" value="1">
                        <button type="submit" class="btn btn-success w-100"><?= $lang->get('upload_torrent') ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>

<?php
// Запазваме calculateTorrentSize — тя не е свързана с bencode и работи добре
function calculateTorrentSize($info) {
    if (isset($info['files'])) {
        $size = 0;
        foreach ($info['files'] as $file) {
            $size += $file['length'] ?? 0;
        }
        return $size;
    }
    return $info['length'] ?? 0;
}
}
?>