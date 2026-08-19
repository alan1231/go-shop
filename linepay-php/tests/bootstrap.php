<?php

$envAutoload = getenv('LINE_PAY_SDK_AUTOLOAD');
$candidates = $envAutoload !== false && $envAutoload !== ''
    ? [$envAutoload]
    : [
        __DIR__ . '/../../backend/vendor/autoload.php',
        __DIR__ . '/../../vendor/autoload.php',
        __DIR__ . '/../../../autoload.php',
        getcwd() . '/vendor/autoload.php',
    ];

$loaded = false;
foreach ($candidates as $file) {
    if (is_file($file)) {
        require $file;
        $loaded = true;
        break;
    }
}

if (!$loaded) {
    fwrite(STDERR, "找不到 host 專案的 vendor/autoload.php。\n");
    fwrite(STDERR, "請在執行 phpunit 前設定 LINE_PAY_SDK_AUTOLOAD=/path/to/vendor/autoload.php。\n");
    exit(1);
}

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'LinePay\\Tests\\')) {
        return;
    }
    $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen('LinePay\\Tests\\'))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});