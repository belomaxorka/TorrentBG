<?php
declare(strict_types=1);

class StyleManager {
    private string $style;

    public function __construct() {
        $this->style = $_GET['style'] ?? $_SESSION['style'] ?? $_COOKIE['style'] ?? 'light';
        if (!in_array($this->style, ['light', 'dark'])) {
            $this->style = 'light';
        }

        $_SESSION['style'] = $this->style;
        setcookie('style', $this->style, time() + 365*24*3600, '/');
    }

    public function getCurrent(): string {
        return $this->style;
    }

    public function getCSS(): string {
        return "styles/{$this->style}/style.css";
    }

    public function getAvailable(): array {
        return ['light', 'dark'];
    }
}