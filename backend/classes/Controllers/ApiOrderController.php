<?php

namespace App\Controllers;

use App\Registry;
use App\Response;
use App\Support;

class ApiOrderController extends BaseController {
    public static function create(): void {
        $body = Support::jsonBody();
        $items = is_array($body['items'] ?? null) ? $body['items'] : [];
        $receiver = is_array($body['receiver'] ?? null) ? $body['receiver'] : [];
        $remark = trim((string)($body['remark'] ?? ''));
        $tableNumber = (int)($body['table_number'] ?? 0);

        if ($tableNumber > 0) {
            $tableCount = Registry::get('settingsSvc')->getTableCount();
            if ($tableCount <= 0 || $tableNumber > $tableCount) {
                Response::fail('桌號無效，超出已設定的桌數範圍', 400);
            }
        }

        $userId = 0;
        $user = Registry::get('userRepo')->findByToken(Support::bearerToken());
        if ($user !== null) {
            $userId = (int)$user['id'];
        }

        $orderId = Registry::get('orderSvc')->createOrder(
            $userId,
            $items,
            [
                'name' => trim((string)($receiver['name'] ?? '')),
                'phone' => trim((string)($receiver['phone'] ?? '')),
                'address' => trim((string)($receiver['address'] ?? '')),
            ],
            $remark,
            $tableNumber > 0 ? $tableNumber : null
        );
        $order = Registry::get('orderSvc')->getWithItems($orderId);
        Response::success(['order_id' => $orderId, 'order' => $order], '訂單已建立');
    }

    public static function index(): void {
        $user = self::requireUser();
        $status = (string)($_GET['status'] ?? '');
        Response::success(Registry::get('orderSvc')->getUserOrders((int)$user['id'], $status), 'ok');
    }

    public static function show(int $id): void {
        $user = self::requireUser();
        $order = Registry::get('orderSvc')->getWithItems($id);
        if ($order === null || (int)$order['user_id'] !== (int)$user['id']) {
            Response::fail('訂單不存在', 404);
        }
        Response::success($order, 'ok');
    }

    public static function pay(int $id): void {
        $user = self::requireUser();
        $body = Support::jsonBody();
        $method = (string)($body['method'] ?? 'linepay');
        if ($method === 'cod') {
            Registry::get('orderSvc')->payWithCashOnDelivery($id, (int)$user['id']);
            Response::success(null, '付款成功');
        }
        $payment = Registry::get('orderSvc')->startLinePay($id, (int)$user['id']);
        Response::success($payment, 'ok');
    }

    public static function payStatus(int $id): void {
        $user = self::requireUser();
        $status = Registry::get('orderSvc')->checkLinePay($id, (int)$user['id']);
        Response::success($status, 'ok');
    }
}
