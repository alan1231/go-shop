<?php
// 專案入口：自動載入與基礎設定

// 載入 .env（本機憑證，不入 git）
$_envFile = __DIR__ . '/../.env';
if (is_file($_envFile)) {
    foreach (file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $_line) {
        if (str_starts_with(trim($_line), '#') || !str_contains($_line, '=')) continue;
        [$k, $v] = explode('=', $_line, 2);
        $k = trim($k); $v = trim($v);
        if ($k && getenv($k) === false) putenv("$k=$v");
    }
}

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