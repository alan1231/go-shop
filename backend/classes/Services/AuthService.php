<?php
// 登入驗證：查使用者、驗證密碼、檢查管理員權限
class AuthService {
    private UserRepository $repo;

    public function __construct() {
        $this->repo = new UserRepository();
    }

    // 嘗試登入，成功則寫入 session，回傳結果陣列
    public function authenticate(string $username, string $password): array {
        if ($username === '' || $password === '') {
            return ['success' => false, 'message' => '請輸入帳號與密碼'];
        }

        $user = $this->repo->findForAuth($username);
        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => '帳號或密碼錯誤'];
        }

        if ($user['role'] !== 'admin') {
            return ['success' => false, 'message' => '此帳號無後台管理權限'];
        }

        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'];
        return ['success' => true];
    }

    // 前台登入（允許任何角色）
    public function customerLogin(string $username, string $password): array {
        if ($username === '' || $password === '') {
            return ['success' => false, 'message' => '請輸入帳號與密碼'];
        }

        $user = $this->repo->findForAuth($username);
        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => '帳號或密碼錯誤'];
        }

        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'];
        return ['success' => true, 'user' => ['id' => $user['id'], 'username' => $user['username'], 'email' => $user['email']]];
    }
}