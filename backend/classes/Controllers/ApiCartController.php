<?php

class ApiCartController extends BaseController {
    public static function index(): void {
        $user = self::requireUser();
        Response::success(Registry::get('cartSvc')->getCart((int)$user['id']), 'ok');
    }

    public static function add(): void {
        $user = self::requireUser();
        $body = Support::jsonBody();
        Registry::get('cartSvc')->add(
            (int)$user['id'],
            (int)($body['product_id'] ?? 0),
            (int)($body['quantity'] ?? 1)
        );
        Response::success(null, '已加入購物車');
    }

    public static function update(int $productId): void {
        $user = self::requireUser();
        $body = Support::jsonBody();
        Registry::get('cartSvc')->setQuantity(
            (int)$user['id'],
            (int)$productId,
            (int)($body['quantity'] ?? 0)
        );
        Response::success(null, '已更新');
    }

    public static function remove(int $productId): void {
        $user = self::requireUser();
        Registry::get('cartSvc')->remove((int)$user['id'], (int)$productId);
        Response::success(null, '已移除');
    }

    public static function clear(): void {
        $user = self::requireUser();
        Registry::get('cartSvc')->clear((int)$user['id']);
        Response::success(null, '購物車已清空');
    }

    public static function merge(): void {
        $user = self::requireUser();
        $body = Support::jsonBody();
        $items = is_array($body['items'] ?? null) ? $body['items'] : [];
        Registry::get('cartSvc')->merge((int)$user['id'], $items);
        Response::success(Registry::get('cartSvc')->getCart((int)$user['id']), 'ok');
    }
}
