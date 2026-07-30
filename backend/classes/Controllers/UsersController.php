<?php
// 會員管理：列表、新增、編輯（一般會員只能改密碼）、刪除
class UsersController extends Controller {
    private UserService $userService;

    public function __construct() {
        Auth::start();
        Auth::check();
        $this->userService = new UserService();
    }

    // 僅列出 role = 'user' 的一般會員
    public function index(): void {
        $users = $this->userService->getAllMembers();
        $this->render('admin-users', ['users' => $users, 'page_title' => '會員管理']);
    }

    // 新增會員
    public function add(): void {
        $message = '';
        $message_type = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if ($username && $email && $password) {
                $result = $this->userService->createMember($username, $email, $password);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
            } else {
                $message = '請填寫所有欄位';
                $message_type = 'error';
            }
        }

        $this->render('admin-user-form', compact('message', 'message_type') + ['page_title' => '新增會員']);
    }

    // 編輯會員：一般會員只改密碼，管理員可改完整資料
    public function edit(int $id): void {
        $user = $this->userService->getById($id);

        if (!$user) {
            $this->notFound('會員不存在');
            return;
        }

        // 禁止編輯其他管理員
        $current = Auth::user();
        $error = $this->userService->canEdit((int)$current['id'], $user);
        if ($error) {
            $this->forbidden($error);
            return;
        }

        $message = '';
        $message_type = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($user['role'] === 'user') {
                // 一般會員：只能改密碼
                $result = $this->userService->updatePassword($id, $_POST['password'] ?? '');
            } else {
                // 管理員（自己）：可改帳號/Email/密碼
                $username = trim($_POST['username'] ?? '');
                $email    = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $result = $this->userService->updateProfile($id, $username, $email, $password ?: null);
                if ($result['success'] && isset($result['username'])) {
                    $user = array_merge($user, ['username' => $result['username'], 'email' => $result['email']]);
                }
            }
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }

        $this->render('admin-user-form', compact('user', 'message', 'message_type') + ['page_title' => '編輯會員']);
    }

    // 刪除會員（禁止刪除自己或管理員）
    public function delete(int $id): void {
        $loginUser = Auth::user();

        $error = $this->userService->canDelete((int)$loginUser['id'], $id);
        if ($error) {
            $this->badRequest($error);
            return;
        }

        $this->userService->delete($id);
        header('Location: ' . BASE_URL . '/admin/users');
        exit;
    }
}