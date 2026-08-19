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

    public static function create(): void {
        self::requireAdmin();
        $body = Support::jsonBody();

        $receiver = [
            'name' => (string)($body['receiver_name'] ?? ''),
            'phone' => (string)($body['receiver_phone'] ?? ''),
            'address' => (string)($body['receiver_address'] ?? ''),
        ];
        $remark = (string)($body['remark'] ?? '');
        $tableNumber = (int)($body['table_number'] ?? 0);

        $settingsSvc = Registry::get('settingsSvc');
        $tableCount = $settingsSvc->getTableCount();
        if ($tableNumber > 0 && ($tableCount <= 0 || $tableNumber > $tableCount)) {
            Response::fail('桌號無效，超出已設定的桌數範圍', 400);
        }

        $orderId = Registry::get('orderSvc')->createOrder(0, (array)($body['items'] ?? []), $receiver, $remark, $tableNumber > 0 ? $tableNumber : null);

        $checkedOut = (bool)($body['checkout'] ?? false);
        if ($checkedOut) {
            Registry::get('orderSvc')->cashCheckout($orderId);
        }

        Response::success(['order_id' => $orderId], '訂單已建立');
    }
}
