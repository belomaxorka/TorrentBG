<?php
declare(strict_types=1);

/**
 * Простой autoloader для классов
 * PSR-4 подобная автозагрузка
 */
spl_autoload_register(function ($className) {
    // Список классов и их путей
    $classMap = [
        'Database' => __DIR__ . '/Database.php',
        'Auth' => __DIR__ . '/Auth.php',
        'Language' => __DIR__ . '/Language.php',
        'StyleManager' => __DIR__ . '/StyleManager.php',
        'BlockManager' => __DIR__ . '/BlockManager.php',
        'Application' => __DIR__ . '/Application.php',
        'Config' => __DIR__ . '/Config.php',
        'Security' => __DIR__ . '/Security.php',
        'EmailManager' => __DIR__ . '/EmailManager.php',
        'ExternalAPI' => __DIR__ . '/ExternalAPI.php',
        'TranslationManager' => __DIR__ . '/TranslationManager.php',
    ];
    
    if (isset($classMap[$className])) {
        require_once $classMap[$className];
    }
});

