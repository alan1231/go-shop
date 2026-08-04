<?php
// 前台認證 API：註冊、登入、登出、三方登入（Token 認證，與後台 session 分離）
class ApiAuthController extends ApiController {
    private UserService $userService;
    private UserRepository $userRepo;
    private OAuthService $oauthService;

    public function __construct() {
        $this->userService  = new UserService();
        $this->userRepo     = new UserRepository();
        $this->oauthService = new OAuthService();
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
            'user'     => ['id' => (int)$user['id'], 'username' => $user['username'], 'email' => $user['email'], 'provider' => $user['provider'], 'created_at' => $user['created_at'], 'phone' => $user['phone'], 'address' => $user['address']],
        ], '登入成功');
    }

    // POST /api/auth/oauth — 三方登入（Google / LINE）
    public function oauth(): void {
        $body = $this->jsonBody();
        $provider = strtolower(trim($body['provider'] ?? ''));
        $code     = trim($body['code'] ?? '');

        if (!in_array($provider, ['google', 'line']) || !$code) {
            $this->error('無效的三方登入請求');
            return;
        }

        $info = $this->oauthService->getUserInfo($provider, $code);
        if (!$info) {
            $this->error('三方登入驗證失敗', 401);
            return;
        }

        // 查詢是否已綁定此三方帳號
        $user = $this->userRepo->findByProvider($provider, $info['provider_id']);
        if (!$user) {
            $name = $info['name'] ?: $info['email'] ?: $provider . '_' . substr($info['provider_id'], -6);
            $email = $info['email'] ?: '';
            $user = $this->userRepo->findByEmail($email);
            if ($user) {
                // email 已存在的一般帳號 → 綁定三方 provider
                $this->userRepo->setProvider((int)$user['id'], $provider, $info['provider_id']);
            } else {
                // 全新會員
                $id = $this->userRepo->createOAuthUser($name, $email, $provider, $info['provider_id']);
                $user = $this->userRepo->findById($id);
            }
        }

        $token = bin2hex(random_bytes(32));
        $this->userRepo->setToken((int)$user['id'], $token);

        $this->success([
            'token' => $token,
            'user'  => ['id' => (int)$user['id'], 'username' => $user['username'], 'email' => $user['email'], 'provider' => $user['provider'], 'created_at' => $user['created_at'], 'phone' => $user['phone'], 'address' => $user['address']],
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
        $this->success(['id' => (int)$user['id'], 'username' => $user['username'], 'email' => $user['email'], 'provider' => $user['provider'], 'created_at' => $user['created_at'], 'phone' => $user['phone'], 'address' => $user['address']]);
    }

    // POST /api/auth/update — 更新聯絡資料（手機、住址）
    public function updateContact(): void {
        $user = $this->requireAuth();
        $body = $this->jsonBody();
        $phone   = trim($body['phone'] ?? '');
        $address = trim($body['address'] ?? '');

        $result = $this->userService->updateContact((int)$user['id'], $phone, $address);
        if (!$result['success']) {
            $this->error($result['message']);
            return;
        }
        $this->success(null, $result['message']);
    }
}
