<?php
declare(strict_types=1);

function parseBBC(string $text): string {
    // 1. Escape HTML to prevent XSS
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    // 2. Bold
    $text = preg_replace('/\[b\](.*?)\[\/b\]/is', '<strong>$1</strong>', $text);
    // Italic
    $text = preg_replace('/\[i\](.*?)\[\/i\]/is', '<em>$1</em>', $text);
    // Underline
    $text = preg_replace('/\[u\](.*?)\[\/u\]/is', '<u>$1</u>', $text);
    // Strikethrough
    $text = preg_replace('/\[s\](.*?)\[\/s\]/is', '<del>$1</del>', $text);

    // 3. Color
    $text = preg_replace('/\[color=([a-zA-Z0-9#]+)\](.*?)\[\/color\]/is', '<span style="color: $1;">$2</span>', $text);

    // 4. Font size
    $sizeMap = [
        'xx-small' => '0.7em',
        'x-small'  => '0.8em',
        'small'    => '0.9em',
        'medium'   => '1em',
        'large'    => '1.2em',
        'x-large'  => '1.5em',
        'xx-large' => '2em',
    ];
    $text = preg_replace_callback('/\[size=([a-z\-]+)\](.*?)\[\/size\]/is', function($matches) use ($sizeMap) {
        $size = $matches[1];
        $content = $matches[2];
        $fontSize = $sizeMap[$size] ?? '1em';
        return "<span style=\"font-size: {$fontSize};\">{$content}</span>";
    }, $text);

    // 5. Font
    $text = preg_replace('/\[font=([a-zA-Z0-9\-\s]+)\](.*?)\[\/font\]/is', '<span style="font-family: $1;">$2</span>', $text);

    // 6. Alignment
    $text = preg_replace('/\[align=(left|center|right|justify)\](.*?)\[\/align\]/is', '<div style="text-align: $1;">$2</div>', $text);

    // 7. Quote
    $text = preg_replace('/\[quote\](.*?)\[\/quote\]/is', '<blockquote style="border-left: 3px solid #ccc; margin: 1em 0; padding-left: 1em; color: #555;">$1</blockquote>', $text);

    // 8. Code
    $text = preg_replace('/\[code\](.*?)\[\/code\]/is', '<pre style="background: #f8f9fa; padding: 10px; border: 1px solid #e9ecef; border-radius: 4px; overflow-x: auto;">$1</pre>', $text);

    // 9. List
    $text = preg_replace('/\[list\](.*?)\[\/list\]/is', '<ul>$1</ul>', $text);
    $text = preg_replace('/\[\*\](.*?)(?=\[\*\]|\[\/list\]|$)/is', '<li>$1</li>', $text);

    // 10. Spoiler (with <details>)
    $text = preg_replace('/\[spoiler\](.*?)\[\/spoiler\]/is', '<details style="margin: 1em 0;"><summary style="cursor: pointer; padding: 6px 10px; background: #f1f1f1; display: inline-block;">Spoiler</summary><div style="padding: 10px; border: 1px solid #ddd; margin-top: 5px;">$1</div></details>', $text);

    // 11. URL
    $text = preg_replace('/\[url=(https?:\/\/[^\s\]]+)\](.*?)\[\/url\]/i', '<a href="$1" target="_blank" rel="nofollow">$2</a>', $text);
    $text = preg_replace('/\[url\](https?:\/\/[^\s\]]+)\[\/url\]/i', '<a href="$1" target="_blank" rel="nofollow">$1</a>', $text);

    // 12. Image
    $text = preg_replace('/\[img\](https?:\/\/[^\s\]]+)\[\/img\]/i', '<img src="$1" class="img-fluid" alt="Image" style="max-width: 100%; height: auto;">', $text);

    // 13. Smileys
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
        $text = str_replace("[smile=$code]", '<img src="/images/smiles/' . $file . '" alt="' . $code . '" class="smile-inline" style="vertical-align: middle; margin: 0 2px;">', $text);
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