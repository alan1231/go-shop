<?php

namespace App\Services;

use App\Repositories\AdminUserRepository;
use App\ServiceException;

class AdminAccountService {
    private AdminUserRepository $repo;

    public function __construct(AdminUserRepository $repo) {
        $this->repo = $repo;
    }

    public function getAll(): array {
        return $this->repo->findAll();
    }

    public function create(string $username, string $password, string $role): void {
        if ($username === '' || $password === '') {
            throw new ServiceException('請填寫帳號與密碼');
        }
        if ($this->repo->existsByUsername($username)) {
            throw new ServiceException('帳號已存在');
        }
        $this->repo->create($username, password_hash($password, PASSWORD_DEFAULT), $role !== '' ? $role : null);
    }

    public function delete(int $id, int $selfId): void {
        if ($id === $selfId) {
            throw new ServiceException('不能刪除目前登入的帳號');
        }
        if ($this->repo->findById($id) === null) {
            throw new ServiceException('帳號不存在');
        }
        if ($this->repo->count() <= 1) {
            throw new ServiceException('至少需保留一個後台帳號');
        }
        $this->repo->deleteById($id);
    }
}