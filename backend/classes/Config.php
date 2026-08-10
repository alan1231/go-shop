<?php

class Config {
    private static array $values = [];

    public static function load(string $envFile): void {
        if (file_exists($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                if (!array_key_exists($key, self::$values)) {
                    self::$values[$key] = $value;
                }
            }
        }
        $root = dirname(__DIR__, 2);
        self::$values += [
            'PORT' => '8080',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '3306',
            'DB_NAME' => 'shop',
            'DB_USER' => 'root',
            'DB_PASS' => '',
            'UPLOADS_DIR' => $root . '/uploads',
            'PUBLIC_DIST' => $root . '/frontend/dist',
            'ADMIN_DIST' => $root . '/frontend-admin/dist',
            'OAUTH_REDIRECT_URI' => 'http://localhost:5173/auth/callback',
            'LINE_PAY_SANDBOX' => 'true',
        ];
    }

    public static function get(string $key, string $default = ''): string {
        return self::$values[$key] ?? $default;
    }
}
