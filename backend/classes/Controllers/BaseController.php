<?php

class BaseController {
    protected static function requireUser(): array {
        $user = Registry::get('userRepo')->findByToken(Support::bearerToken());
        if ($user === null) {
            Response::fail('請先登入', 401);
        }
        return $user;
    }

    protected static function requireAdmin(): array {
        $admin = Registry::get('authSvc')->findByToken(Support::bearerToken());
        if ($admin === null) {
            Response::fail('請先登入', 401);
        }
        return $admin;
    }

    protected static function rateLimited(string $type): bool {
        [$allowed, $minutes] = Registry::get('rateLimitSvc')->check(Support::clientIP(), $type);
        if (!$allowed) {
            Response::fail(sprintf('嘗試次數過多，請 %d 分鐘後再試', $minutes), 429);
            return true;
        }
        return false;
    }

    protected static function userPayload(array $u): array {
        return [
            'id' => (int)$u['id'],
            'username' => $u['username'] ?? '',
            'email' => $u['email'] ?? '',
            'provider' => Support::nullIfEmpty($u['provider'] ?? ''),
            'created_at' => $u['created_at'] ?? '',
            'phone' => Support::nullIfEmpty($u['phone'] ?? ''),
            'address' => Support::nullIfEmpty($u['address'] ?? ''),
            'avatar' => Support::avatarUrl($u['avatar'] ?? ''),
        ];
    }

    protected static function adminPayload(array $a): array {
        return [
            'id' => (int)$a['id'],
            'username' => $a['username'] ?? '',
        ];
    }

    protected static function adminUserPayload(array $u): array {
        return [
            'id' => (int)$u['id'],
            'username' => $u['username'] ?? '',
            'email' => $u['email'] ?? '',
            'provider' => Support::nullIfEmpty($u['provider'] ?? ''),
            'phone' => Support::nullIfEmpty($u['phone'] ?? ''),
            'address' => Support::nullIfEmpty($u['address'] ?? ''),
            'avatar' => Support::avatarUrl($u['avatar'] ?? ''),
            'created_at' => $u['created_at'] ?? '',
        ];
    }

    protected static function productPublicPayload(array $p): array {
        return [
            'id' => (int)$p['id'],
            'name' => $p['name'] ?? '',
            'image' => Support::nullIfEmpty(Support::uploadUrl($p['image'] ?? '')),
            'description' => $p['description'] ?? '',
            'category' => Support::nullIfEmpty($p['category'] ?? ''),
            'price' => (float)$p['price'],
            'list_price' => $p['list_price'] === null ? null : (float)$p['list_price'],
            'stock' => (int)$p['stock'],
            'status' => $p['status'] ?? '',
        ];
    }

    protected static function productShowPayload(array $p): array {
        return [
            'id' => (int)$p['id'],
            'name' => $p['name'] ?? '',
            'image' => Support::nullIfEmpty(Support::uploadUrl($p['image'] ?? '')),
            'description' => $p['description'] ?? '',
            'category' => Support::nullIfEmpty($p['category'] ?? ''),
            'price' => (float)$p['price'],
            'list_price' => $p['list_price'] === null ? null : (float)$p['list_price'],
            'stock' => (int)$p['listed_stock'],
        ];
    }

    protected static function adminProductPayload(array $p): array {
        return [
            'id' => (int)$p['id'],
            'name' => $p['name'] ?? '',
            'image' => Support::nullIfEmpty(Support::uploadUrl($p['image'] ?? '')),
            'description' => $p['description'] ?? '',
            'category' => Support::nullIfEmpty($p['category'] ?? ''),
            'price' => (float)$p['price'],
            'list_price' => $p['list_price'] === null ? null : (float)$p['list_price'],
            'stock' => (int)$p['stock'],
            'listed_stock' => (int)$p['listed_stock'],
            'status' => $p['status'] ?? '',
            'created_at' => $p['created_at'] ?? '',
        ];
    }
}
