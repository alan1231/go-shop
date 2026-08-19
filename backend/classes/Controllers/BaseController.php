<?php

namespace App\Controllers;

use App\Registry;
use App\Response;
use App\Support;

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

    protected static function adminPayload(array $a): array {
        return [
            'id' => (int)$a['id'],
            'username' => $a['username'] ?? '',
            'provider' => Support::nullIfEmpty($a['provider'] ?? ''),
            'provider_id' => Support::nullIfEmpty($a['provider_id'] ?? ''),
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
            'status' => $p['status'] ?? '',
            'created_at' => $p['created_at'] ?? '',
        ];
    }
}
