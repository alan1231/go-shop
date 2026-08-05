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
        $q = trim($_GET['q'] ?? '');
        $users = $this->userService->getAllMembers($q !== '' ? $q : null);
        $this->render('admin-users', compact('users', 'q') + ['page_title' => '會員管理']);
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

        $message = '';
        $message_type = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 三方登入會員無密碼，無法修改
            if (!empty($user['provider'])) {
                $message = '此會員為三方登入，無密碼可修改';
                $message_type = 'error';
            } else {
                $result = $this->userService->updatePassword($id, $_POST['password'] ?? '');
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
            }
        }

        $this->render('admin-user-form', compact('user', 'message', 'message_type') + ['page_title' => '編輯會員']);
    }

    // 刪除會員（POST + CSRF）
    public function delete(int $id): void {
        if (!Auth::csrfCheck($_POST['csrf_token'] ?? null)) {
            $this->forbidden('CSRF token 驗證失敗');
            return;
        }

        $error = $this->userService->canDelete($id);
        if ($error) {
            $this->badRequest($error);
            return;
        }

        $this->userService->delete($id);
        header('Location: ' . BASE_URL . '/admin/users');
        exit;
    }
}