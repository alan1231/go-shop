<?php
// 專案入口：自動載入與基礎設定

// PSR-0 風格自動載入，依類別名稱搜尋多個目錄
spl_autoload_register(function (string $class) {
    $dirs = [
        __DIR__ . '/classes',
        __DIR__ . '/classes/Controllers',
        __DIR__ . '/classes/Controllers/Api',
        __DIR__ . '/classes/Services',
        __DIR__ . '/classes/Repositories',
    ];
    foreach ($dirs as $dir) {
        $path = $dir . '/' . $class . '.php';
        if (is_file($path)) {
            require $path;
            return;
        }
    }
});