<?php
declare(strict_types=1);

class StyleManager {
    private string $style;

    public function __construct() {
        // Ако има ?style=... в URL-то — обработи и пренасочи
        if (isset($_GET['style'])) {
            $requestedStyle = $_GET['style'];
            if (in_array($requestedStyle, ['light', 'dark'])) {
                $_SESSION['style'] = $requestedStyle;
                setcookie('style', $requestedStyle, time() + 365*24*3600, '/');
                // Пренасочи към същата страница, но без ?style=...
                $currentUrl = strtok($_SERVER['REQUEST_URI'], '?');
                header("Location: $currentUrl", true, 303);
                exit;
            }
        }

        // Вземи темата от сесия или бисквитка
        $this->style = $_SESSION['style'] ?? $_COOKIE['style'] ?? 'light';
        if (!in_array($this->style, ['light', 'dark'])) {
            $this->style = 'light';
        }

        // Увери се, че сесията и бисквитката са актуални
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