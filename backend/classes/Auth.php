<?php
// Session 認證輔助：啟動、檢查、取使用者資訊
class Auth {
    // 啟動 session（如尚未啟動）
    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // 回傳當前登入使用者資訊，未登入回傳 null
    public static function user(): ?array {
        self::start();
        if (!isset($_SESSION['user_id'])) return null;
        return [
            'id'       => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role'     => $_SESSION['role'] ?? 'user',
        ];
    }

    // 檢查是否為管理員，否則導向登入頁
    public static function check(): void {
        self::start();
        $user = self::user();
        if ($user === null || $user['role'] !== 'admin') {
            session_destroy();
            self::redirect(BASE_URL . '/login');
        }
    }

    // 是否已登入且為管理員
    public static function isAdmin(): bool {
        $user = self::user();
        return $user !== null && $user['role'] === 'admin';
    }

    // 導頁並結束
    public static function redirect(string $path): void {
        header('Location: ' . $path);
        exit;
    }

    // 產生/回傳 CSRF token（存於 session）
    public static function csrfToken(): string {
        self::start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // 驗證表單送出的 CSRF token，合法回傳 true
    public static function csrfCheck(?string $token): bool {
        self::start();
        $stored = $_SESSION['csrf_token'] ?? '';
        return $token !== null && $stored !== '' && hash_equals($stored, $token);
    }
}