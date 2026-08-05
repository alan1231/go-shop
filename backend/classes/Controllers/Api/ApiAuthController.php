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
            'user'     => ['id' => (int)$user['id'], 'username' => $user['username'], 'email' => $user['email'], 'provider' => $user['provider'], 'created_at' => $user['created_at'], 'phone' => $user['phone'], 'address' => $user['address'], 'avatar' => $this->avatarUrl($user['avatar'])],
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

        $avatar = $this->saveAvatarUrl($info['avatar'] ?? '');

        // 查詢是否已綁定此三方帳號
        $user = $this->userRepo->findByProvider($provider, $info['provider_id']);
        if (!$user) {
            $name = $info['name'] ?: $info['email'] ?: $provider . '_' . substr($info['provider_id'], -6);
            $email = $info['email'] ?: '';
            $user = $this->userRepo->findByEmail($email);
            if ($user) {
                // email 已存在的一般帳號 → 綁定三方 provider
                $this->userRepo->setProvider((int)$user['id'], $provider, $info['provider_id']);
                if ($avatar) {
                    $this->userRepo->updateAvatar((int)$user['id'], $avatar);
                }
            } else {
                // 全新會員
                $id = $this->userRepo->createOAuthUser($name, $email, $provider, $info['provider_id'], $avatar);
                $user = $this->userRepo->findById($id);
            }
        } elseif ($avatar && $user['avatar'] !== $avatar) {
            // 每次登入刷新頭像
            $this->userRepo->updateAvatar((int)$user['id'], $avatar);
            $user['avatar'] = $avatar;
        }

        $token = bin2hex(random_bytes(32));
        $this->userRepo->setToken((int)$user['id'], $token);

        $this->success([
            'token' => $token,
            'user'  => ['id' => (int)$user['id'], 'username' => $user['username'], 'email' => $user['email'], 'provider' => $user['provider'], 'created_at' => $user['created_at'], 'phone' => $user['phone'], 'address' => $user['address'], 'avatar' => $this->avatarUrl($user['avatar'])],
        ], '登入成功');
    }

    // 下載三方頭像存到 uploads/，回傳本地檔名；失敗回傳 null
    private function saveAvatarUrl(string $url): ?string {
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $data = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $type = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        if ($code !== 200 || !$data) {
            return null;
        }

        $ext = 'jpg';
        if (str_contains($type, 'png')) { $ext = 'png'; }
        elseif (str_contains($type, 'gif')) { $ext = 'gif'; }
        elseif (str_contains($type, 'webp')) { $ext = 'webp'; }

        $uploadDir = __DIR__ . '/../../../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $name = 'avatar_' . bin2hex(random_bytes(8)) . '.' . $ext;
        if (file_put_contents($uploadDir . $name, $data) === false) {
            return null;
        }
        return $name;
    }

    // 將 avatar 欄位轉為可顯示 URL（舊資料可能存外部網址）
    private function avatarUrl(?string $avatar): ?string {
        if (!$avatar) {
            return null;
        }
        if (str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://')) {
            return $avatar;
        }
        return BASE_URL . '/uploads/' . $avatar;
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
        $this->success(['id' => (int)$user['id'], 'username' => $user['username'], 'email' => $user['email'], 'provider' => $user['provider'], 'created_at' => $user['created_at'], 'phone' => $user['phone'], 'address' => $user['address'], 'avatar' => $this->avatarUrl($user['avatar'])]);
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

    // POST /api/auth/password — 更改密碼
    public function changePassword(): void {
        $user = $this->requireAuth();
        $body = $this->jsonBody();
        $oldPassword = $body['old_password'] ?? '';
        $newPassword = $body['new_password'] ?? '';

        $result = $this->userService->changePassword((int)$user['id'], $oldPassword, $newPassword);
        if (!$result['success']) {
            $this->error($result['message']);
            return;
        }
        $this->success(null, $result['message']);
    }
}
