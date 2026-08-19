<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\ServiceException;

class UserService {
    private UserRepository $repo;

    public function __construct(UserRepository $repo) {
        $this->repo = $repo;
    }

    public function getAllMembers(string $q = ''): array {
        return $this->repo->findAllByRole('user', $q);
    }

    public function getById(int $id): ?array {
        return $this->repo->findById($id);
    }

    public function createMember(string $username, string $email, string $password): void {
        if ($username === '' || $email === '' || $password === '') {
            throw new ServiceException('請填寫所有欄位');
        }
        if ($this->repo->existsByUsernameOrEmail($username, $email, 0)) {
            throw new ServiceException('帳號或 Email 已存在');
        }
        $this->repo->create($username, $email, password_hash($password, PASSWORD_BCRYPT), 'user');
    }

    public function updatePassword(int $id, string $password): void {
        if ($password === '') {
            throw new ServiceException('請輸入新密碼');
        }
        $this->repo->updatePassword($id, password_hash($password, PASSWORD_BCRYPT));
    }

    public function canDelete(int $targetId): void {
        $u = $this->repo->findById($targetId);
        if ($u === null) {
            throw new ServiceException('使用者不存在');
        }
    }

    public function delete(int $id): void {
        $this->repo->delete($id);
    }

    public function countMembers(): int {
        return $this->repo->countByRole('user');
    }
}
