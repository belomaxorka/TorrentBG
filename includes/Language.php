<?php
declare(strict_types=1);

class Language {
    private string $langCode;
    private array $translations = [];

    public function __construct(string $langCode = 'en') {
        // Поддържани езици
        $supported = ['en', 'bg', 'fr', 'de', 'ru'];
        $this->langCode = in_array($langCode, $supported) ? $langCode : 'en';
        $this->loadLanguage();
    }

    private function loadLanguage(): void {
        $file = __DIR__ . "/../language/{$this->langCode}.php";
        if (file_exists($file)) {
            $this->translations = require $file;
        } else {
            // Fallback към английски
            $this->translations = require __DIR__ . '/../language/en.php';
        }
    }

    /**
     * Получить перевод по ключу
     * 
     * @param string $key Ключ перевода
     * @param mixed ...$params Параметры для sprintf
     * @return string Перевод (НЕ экранированный - экранирование делается в шаблонах)
     */
    public function get(string $key, ...$params): string {
        $text = $this->translations[$key] ?? "{{$key}}";
        return $params ? vsprintf($text, $params) : $text;
    }
    
    /**
     * Получить перевод с автоматическим HTML-экранированием
     * Используется когда нужно вывести напрямую в HTML
     * 
     * @param string $key Ключ перевода
     * @param mixed ...$params Параметры для sprintf (будут экранированы)
     * @return string Экранированный перевод
     */
    public function e(string $key, ...$params): string {
        $text = $this->translations[$key] ?? "{{$key}}";
        if ($params) {
            $params = array_map('htmlspecialchars', $params);
            $text = vsprintf($text, $params);
        }
        return htmlspecialchars($text);
    }

    public function getCurrent(): string {
        return $this->langCode;
    }

    public function getAvailable(): array {
        return ['en', 'bg', 'fr', 'de', 'ru'];
    }
}