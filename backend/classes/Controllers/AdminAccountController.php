<?php

namespace App\Controllers;

use App\Registry;
use App\Response;
use App\Support;

class AdminAccountController extends BaseController {
    public static function index(): void {
        $admin = self::requireAdmin();
        $items = array_map(fn($a) => self::adminPayload($a), Registry::get('adminAccountSvc')->getAll());
        Response::success(['self_id' => (int)$admin['id'], 'items' => $items], 'ok');
    }

    public static function create(): void {
        self::requireAdmin();
        $body = Support::jsonBody();
        Registry::get('adminAccountSvc')->create(
            trim((string)($body['username'] ?? '')),
            (string)($body['password'] ?? ''),
            trim((string)($body['role'] ?? ''))
        );
        Response::success(null, '後台帳號已建立');
    }

    public static function delete(int $id): void {
        $admin = self::requireAdmin();
        Registry::get('adminAccountSvc')->delete($id, (int)$admin['id']);
        Response::success(null, '後台帳號已刪除');
    }
}