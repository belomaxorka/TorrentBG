<?php
declare(strict_types=1);

class StyleManager {
    private string $style;

    public function __construct() {
        // If ?style=... in URL — process and redirect
        if (isset($_GET['style'])) {
            $requestedStyle = $_GET['style'];
            if (in_array($requestedStyle, ['light', 'dark'])) {
                $_SESSION['style'] = $requestedStyle;
                setcookie('style', $requestedStyle, time() + 365*24*3600, '/');
                // Redirect to same page but without ?style=...
                $currentUrl = strtok($_SERVER['REQUEST_URI'], '?');
                header("Location: $currentUrl", true, 303);
                exit;
            }
        }

        // Get theme from session or cookie
        $this->style = $_SESSION['style'] ?? $_COOKIE['style'] ?? 'light';
        if (!in_array($this->style, ['light', 'dark'])) {
            $this->style = 'light';
        }

        // Ensure session and cookie are up to date
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