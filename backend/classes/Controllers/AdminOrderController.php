<?php

namespace App\Controllers;

use App\Registry;
use App\Response;
use App\Support;

class AdminOrderController extends BaseController {
    public static function index(): void {
        self::requireAdmin();
        $status = (string)($_GET['status'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        Response::success(Registry::get('orderSvc')->getAll($status, $page, 10), 'ok');
    }

    public static function show(int $id): void {
        self::requireAdmin();
        $order = Registry::get('orderSvc')->getWithItems($id);
        if ($order === null) {
            Response::fail('訂單不存在', 404);
        }
        Response::success($order, 'ok');
    }

    public static function updateStatus(int $id): void {
        self::requireAdmin();
        $body = Support::jsonBody();
        Registry::get('orderSvc')->updateStatus($id, (string)($body['status'] ?? ''));
        Response::success(null, '訂單狀態已更新');
    }

    public static function updateRemark(int $id): void {
        self::requireAdmin();
        $body = Support::jsonBody();
        Registry::get('orderSvc')->updateRemark($id, (string)($body['remark'] ?? ''));
        Response::success(null, '備註已更新');
    }
}
