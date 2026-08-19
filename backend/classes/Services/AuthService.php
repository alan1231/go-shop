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

    public function findByProvider(string $provider, string $providerId): ?array {
        if ($provider === '' || $providerId === '') {
            return null;
        }
        return $this->repo->findByProvider(strtolower($provider), $providerId);
    }

    public function bindOAuth(int $adminId, string $provider, string $providerId): void {
        if ($provider === '' || $providerId === '') {
            throw new ServiceException('三方登入驗證失敗', 401);
        }
        $existing = $this->repo->findByProvider(strtolower($provider), $providerId);
        if ($existing !== null && (int)$existing['id'] !== $adminId) {
            throw new ServiceException('此三方帳號已被其他帳號綁定');
        }
        $this->repo->setProvider($adminId, strtolower($provider), $providerId);
    }
}
