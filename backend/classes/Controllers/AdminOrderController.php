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
        $perPage = min(1000, max(1, (int)($_GET['per_page'] ?? 10)));
        $withItems = (string)($_GET['with_items'] ?? '') === '1';
        $start = (string)($_GET['start'] ?? '');
        $end = (string)($_GET['end'] ?? '');
        Response::success(Registry::get('orderSvc')->getAll($status, $page, $perPage, $withItems, $start, $end), 'ok');
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

    public static function updateItems(int $id): void {
        self::requireAdmin();
        $body = Support::jsonBody();
        Registry::get('orderSvc')->updateItems($id, (array)($body['items'] ?? []));
        Response::success(null, '訂單已更新');
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
        $orderType = (string)($body['order_type'] ?? 'dine_in');
        if (!in_array($orderType, ['dine_in', 'takeout'], true)) {
            $orderType = 'dine_in';
        }

        $settingsSvc = Registry::get('settingsSvc');
        $tableCount = $settingsSvc->getTableCount();
        if ($orderType === 'dine_in' && $tableNumber > 0 && ($tableCount <= 0 || $tableNumber > $tableCount)) {
            Response::fail('桌號無效，超出已設定的桌數範圍', 400);
        }

        $orderId = Registry::get('orderSvc')->createOrder(0, (array)($body['items'] ?? []), $receiver, $remark, $orderType === 'takeout' ? null : ($tableNumber > 0 ? $tableNumber : null), $orderType);

        $checkedOut = (bool)($body['checkout'] ?? false);
        if ($checkedOut) {
            Registry::get('orderSvc')->cashCheckout($orderId);
        }

        Response::success(['order_id' => $orderId], '訂單已建立');
    }
}
