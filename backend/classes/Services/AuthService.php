<?php

namespace App\Services;

use App\Repositories\AdminUserRepository;
use App\ServiceException;
use App\Support;

class AuthService {
    private AdminUserRepository $repo;

    public function __construct(AdminUserRepository $repo) {
        $this->repo = $repo;
    }

    public function authenticate(string $username, string $password): array {
        if ($username === '' || $password === '') {
            throw new ServiceException('請輸入帳號與密碼');
        }
        $admin = $this->repo->findForAuth($username);
        if ($admin === null || !password_verify($password, $admin['password'])) {
            throw new ServiceException('帳號或密碼錯誤');
        }
        return $admin;
    }

    public function login(array $admin): string {
        $token = Support::randomToken();
        $this->repo->setToken((int)$admin['id'], $token);
        return $token;
    }

    public function logout(int $adminId): void {
        $this->repo->setToken($adminId, '');
    }

    public function findByToken(string $token): ?array {
        if ($token === '') {
            return null;
        }
        return $this->repo->findByToken($token);
    }
}
