<?php
declare(strict_types=1);

function parseBBC(string $text): string {
    // Bold
    $text = preg_replace('/\[b\](.*?)\[\/b\]/is', '<strong>$1</strong>', $text);
    // Italic
    $text = preg_replace('/\[i\](.*?)\[\/i\]/is', '<em>$1</em>', $text);
    // Underline
    $text = preg_replace('/\[u\](.*?)\[\/u\]/is', '<u>$1</u>', $text);
    // URL
    $text = preg_replace('/\[url=(.*?)\](.*?)\[\/url\]/is', '<a href="$1" target="_blank">$2</a>', $text);
    $text = preg_replace('/\[url\](.*?)\[\/url\]/is', '<a href="$1" target="_blank">$1</a>', $text);
    // Image
    $text = preg_replace('/\[img\](.*?)\[\/img\]/is', '<img src="$1" class="img-fluid" alt="Image">', $text);
    // Smiles
$smiles = [
    'smile' => 'smile.gif',
    'wink' => 'wink.gif',
    'grin' => 'grin.gif',
    'tongue' => 'tongue.gif',
    'laugh' => 'laugh.gif',
    'sad' => 'sad.gif',
    'angry' => 'angry.gif',
    'shock' => 'shock.gif',
    'cool' => 'cool.gif',
    'blush' => 'blush.gif',
];
foreach ($smiles as $code => $file) {
    $text = str_replace("[smile=$code]", '<img src="/images/smiles/' . $file . '" alt="' . $code . '" class="smile-inline">', $text);
}
    return $text;
}

function formatBytes(int $bytes, int $precision = 2): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}