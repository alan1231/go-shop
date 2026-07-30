<?php
// 會員商業邏輯：新增/編輯/刪除的驗證與處理
class UserService {
    private UserRepository $repo;

    public function __construct() {
        $this->repo = new UserRepository();
    }

    // 取得所有一般會員（role = 'user'）
    public function getAllMembers(): array {
        return $this->repo->findAllByRole('user');
    }

    public function getById(int $id): ?array {
        return $this->repo->findById($id);
    }

    // 新增一般會員，檢查帳號/Email 不重複
    public function createMember(string $username, string $email, string $password): array {
        if ($this->repo->existsByUsernameOrEmail($username, $email)) {
            return ['success' => false, 'message' => '帳號或 Email 已存在'];
        }
        $this->repo->create($username, $email, password_hash($password, PASSWORD_DEFAULT), 'user');
        return ['success' => true, 'message' => "會員「{$username}」新增成功！"];
    }

    // 僅更新密碼（用於一般會員編輯）
    public function updatePassword(int $id, string $password): array {
        if (!$password) {
            return ['success' => false, 'message' => '請輸入新密碼'];
        }
        $this->repo->updatePassword($id, password_hash($password, PASSWORD_DEFAULT));
        return ['success' => true, 'message' => '密碼已更新'];
    }

    // 更新完整資料（用於管理員編輯自己）
    public function updateProfile(int $id, string $username, string $email, ?string $password = null): array {
        if (!$username || !$email) {
            return ['success' => false, 'message' => '請填寫帳號與 Email'];
        }
        if ($this->repo->existsByUsernameOrEmail($username, $email, $id)) {
            return ['success' => false, 'message' => '帳號或 Email 已被其他會員使用'];
        }
        $hash = $password ? password_hash($password, PASSWORD_DEFAULT) : null;
        $this->repo->update($id, $username, $email, $hash);
        return ['success' => true, 'message' => '會員資料已更新', 'username' => $username, 'email' => $email];
    }

    // 檢查是否能刪除，字串 = 錯誤訊息，null = 允許
    public function canDelete(int $currentId, int $targetId): ?string {
        if ($currentId === $targetId) {
            return '無法刪除自己';
        }
        $role = $this->repo->getRole($targetId);
        if ($role === null) {
            return '使用者不存在';
        }
        if ($role === 'admin') {
            return '無法刪除管理員帳號';
        }
        return null;
    }

    public function delete(int $id): void {
        $this->repo->delete($id);
    }

    // 檢查是否能編輯，僅擋掉編輯其他管理員
    public function canEdit(int $currentId, array $targetUser): ?string {
        if ($targetUser['role'] === 'admin' && $currentId !== (int)$targetUser['id']) {
            return '無法編輯其他管理員帳號';
        }
        return null;
    }

    public function countMembers(): int {
        return $this->repo->countByRole('user');
    }

    // 前台註冊（不需管理員權限）
    public function register(string $username, string $email, string $password): array {
        if (!$username || !$email || !$password) {
            return ['success' => false, 'message' => '請填寫所有欄位'];
        }
        if ($this->repo->existsByUsernameOrEmail($username, $email)) {
            return ['success' => false, 'message' => '帳號或 Email 已存在'];
        }
        $this->repo->create($username, $email, password_hash($password, PASSWORD_DEFAULT), 'user');
        return ['success' => true, 'message' => '註冊成功'];
    }
}