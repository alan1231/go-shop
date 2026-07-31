<?php
// 登入驗證：後台查 admin_users、前台查 users
class AuthService {
    private AdminUserRepository $adminRepo;
    private UserRepository $userRepo;

    public function __construct() {
        $this->adminRepo = new AdminUserRepository();
        $this->userRepo  = new UserRepository();
    }

    // 後台登入：查 admin_users，成功則寫入 session
    public function authenticate(string $username, string $password): array {
        if ($username === '' || $password === '') {
            return ['success' => false, 'message' => '請輸入帳號與密碼'];
        }

        $admin = $this->adminRepo->findForAuth($username);
        if (!$admin || !password_verify($password, $admin['password'])) {
            return ['success' => false, 'message' => '帳號或密碼錯誤'];
        }

        $_SESSION['user_id']  = $admin['id'];
        $_SESSION['username'] = $admin['username'];
        $_SESSION['role']     = 'admin';
        return ['success' => true];
    }
}