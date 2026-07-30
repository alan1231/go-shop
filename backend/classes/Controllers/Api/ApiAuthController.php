<?php
// 前台認證 API：註冊、登入、登入
class ApiAuthController extends ApiController {
    private AuthService $authService;
    private UserService $userService;

    public function __construct() {
        $this->authService = new AuthService();
        $this->userService = new UserService();
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

    // POST /api/auth/login
    public function login(): void {
        Auth::start();
        $body = $this->jsonBody();
        $username = trim($body['username'] ?? '');
        $password = $body['password'] ?? '';

        $result = $this->authService->customerLogin($username, $password);
        if (!$result['success']) {
            $this->error($result['message']);
            return;
        }
        $this->success($result['user'] ?? null, '登入成功');
    }

    // POST /api/auth/logout
    public function logout(): void {
        Auth::start();
        session_destroy();
        $this->success(null, '已登出');
    }

    // GET /api/auth/me（取得目前登入使用者資訊）
    public function me(): void {
        Auth::start();
        $user = Auth::user();
        if (!$user) {
            $this->error('未登入', 401);
            return;
        }
        $this->success($user);
    }
}