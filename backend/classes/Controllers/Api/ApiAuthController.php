<?php
// 前台認證 API：註冊、登入、登出（Token 認證，與後台 session 分離）
class ApiAuthController extends ApiController {
    private UserService $userService;
    private UserRepository $userRepo;

    public function __construct() {
        $this->userService = new UserService();
        $this->userRepo    = new UserRepository();
    }

    private function jsonBody(): array {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }

    // POST /api/auth/register
    public function register(): void {
        $body = $this->jsonBody();
        $username = trim($body['username'] ?? '');
        $email    = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';

        $result = $this->userService->register($username, $email, $password);
        if (!$result['success']) {
            $this->error($result['message']);
            return;
        }
        $this->success(null, $result['message']);
    }

    // POST /api/auth/login — 登入成功回傳 token
    public function login(): void {
        $body = $this->jsonBody();
        $username = trim($body['username'] ?? '');
        $password = $body['password'] ?? '';

        $user = $this->userRepo->findForAuth($username);
        if (!$user || !password_verify($password, $user['password'])) {
            $this->error('帳號或密碼錯誤');
            return;
        }

        $token = bin2hex(random_bytes(32));
        $this->userRepo->setToken((int)$user['id'], $token);

        $this->success([
            'token'    => $token,
            'user'     => ['id' => (int)$user['id'], 'username' => $user['username'], 'email' => $user['email']],
        ], '登入成功');
    }

    // POST /api/auth/logout — 清除 token
    public function logout(): void {
        $user = $this->requireAuth();
        $this->userRepo->setToken((int)$user['id'], null);
        $this->success(null, '已登出');
    }

    // GET /api/auth/me — 取得目前登入使用者資訊
    public function me(): void {
        $user = $this->requireAuth();
        $this->success(['id' => (int)$user['id'], 'username' => $user['username'], 'email' => $user['email']]);
    }
}
