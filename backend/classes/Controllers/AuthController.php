<?php
// 登入/登入處理，不使用 admin 版型（login.php 獨立）
class AuthController {
    private AuthService $authService;

    public function __construct() {
        $this->authService = new AuthService();
    }

    // 顯示登入頁面；若已登入則導向後台
    public function showLogin(): void {
        Auth::start();
        if (Auth::isAdmin()) Auth::redirect(BASE_URL . '/admin');
        $user = Auth::user();
        if ($user) {
            session_destroy();
        }
        require __DIR__ . '/../../views/login.php';
    }

    // 處理登入 POST，成功寫入 session 後跳轉
    public function login(): void {
        Auth::start();
        $result = $this->authService->authenticate(
            trim($_POST['username'] ?? ''),
            $_POST['password'] ?? ''
        );

        if ($result['success']) {
            Auth::redirect(BASE_URL . '/admin');
        }

        $error = $result['message'];
        require __DIR__ . '/../../views/login.php';
    }

    // 登出：清除 session
    public function logout(): void {
        Auth::start();
        session_destroy();
        Auth::redirect(BASE_URL . '/login');
    }
}