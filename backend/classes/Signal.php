<?php

namespace App;

class Signal {
    private static ?string $path = null;

    private static function path(): string {
        if (self::$path === null) {
            $dir = dirname(__DIR__, 2) . '/storage/signal';
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            self::$path = $dir . '/orders.sig';
        }
        return self::$path;
    }

    public static function bump(): void {
        @file_put_contents(self::path(), microtime(true) . ':' . mt_rand(0, 999999), LOCK_EX);
    }

    public static function get(): string {
        $v = @file_get_contents(self::path());
        return $v === false ? '' : $v;
    }
}
