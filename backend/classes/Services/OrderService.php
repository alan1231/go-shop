<?php

namespace App\Services;

use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\ServiceException;
use App\Support;
use LinePay\LinePayException;
use LinePay\LinePayGateway;
use LinePay\LinePayOrder;
use LinePay\LinePayProduct;
use LinePay\LinePayStatus;
use PDO;

class OrderService {
    private PDO $pdo;
    private OrderRepository $repo;
    private ProductRepository $productRepo;
    private LinePayGateway $linePay;
    private ?PrintService $printSvc;

    public function __construct(PDO $pdo, OrderRepository $repo, ProductRepository $productRepo, LinePayGateway $linePay, ?PrintService $printSvc = null) {
        $this->pdo = $pdo;
        $this->repo = $repo;
        $this->productRepo = $productRepo;
        $this->linePay = $linePay;
        $this->printSvc = $printSvc;
    }

    public function getAll(string $status, int $page, int $perPage, bool $withItems = false, string $start = '', string $end = ''): array {
        $total = $this->repo->countFindAll($status, $start, $end);
        $method = $withItems ? 'findAllWithItems' : 'findAll';
        $items = $this->repo->{$method}($status, $perPage, ($page - 1) * $perPage, $start, $end);
        $income = $this->repo->sumTotal($status, $start, $end);
        return Support::page($items, $total, $page, $perPage) + ['income' => $income];
    }

    public function getWithItems(int $id): ?array {
        $order = $this->repo->findById($id);
        if ($order === null) {
            return null;
        }
        $order['items'] = $this->repo->getItems($id);
        return $order;
    }

    public function getUserOrders(int $userId, string $status): array {
        return $this->repo->findByUserId($userId, $status);
    }

    public function availableTable(int $tableCount): int {
        if ($tableCount <= 0) {
            return 0;
        }
        $used = array_flip($this->repo->activeTables());
        for ($i = 1; $i <= $tableCount; $i++) {
            if (!isset($used[$i])) {
                return $i;
            }
        }
        return 0;
    }

    public function createOrder(int $userId, array $items, array $receiver, string $remark, ?int $tableNumber = null, string $orderType = 'dine_in'): int {
        if (!in_array($orderType, ['dine_in', 'takeout'], true)) {
            throw new ServiceException('用餐方式無效');
        }
        if ($orderType === 'takeout') {
            $tableNumber = null;
        }
        if (count($items) === 0) {
            throw new ServiceException('訂單不得為空');
        }
        $this->repo->beginTransaction();
        try {
            $total = 0.0;
            $lines = [];
            foreach ($items as $item) {
                $productId = (int)($item['product_id'] ?? 0);
                $qty = (int)($item['quantity'] ?? 0);
                if ($productId <= 0 || $qty <= 0) {
                    continue;
                }
                $p = $this->productRepo->getById($productId);
                if ($p === null || $p['status'] !== 'active') {
                    throw new ServiceException('商品不存在或已下架');
                }
                $lines[] = ['product' => $p, 'quantity' => $qty];
                $total += (float)$p['price'] * $qty;
            }
            if (count($lines) === 0) {
                throw new ServiceException('商品不存在或已下架');
            }
            $orderId = $this->repo->createOrder(
                $userId,
                $total,
                $receiver['name'],
                $receiver['phone'],
                $receiver['address'],
                $remark,
                $tableNumber,
                $orderType
            );
            foreach ($lines as $l) {
                $this->repo->createItem($orderId, (int)$l['product']['id'], (float)$l['product']['price'], $l['quantity']);
            }
            $this->repo->commit();
            $this->printTicket($orderId);
            return $orderId;
        } catch (\Throwable $e) {
            $this->repo->rollBack();
            throw $e;
        }
    }

    public function updateStatus(int $id, string $status): void {
        if (!Support::validStatus($status)) {
            throw new ServiceException('無效的狀態');
        }
        $order = $this->repo->findById($id);
        if ($order === null) {
            throw new ServiceException('訂單不存在');
        }
        if ($order['status'] === 'completed') {
            throw new ServiceException('訂單已完成，狀態不可再變更');
        }
        $this->repo->updateStatus($id, $status);
    }

    public function updateRemark(int $id, string $remark): void {
        $order = $this->repo->findById($id);
        if ($order === null) {
            throw new ServiceException('訂單不存在');
        }
        $this->repo->updateRemark($id, trim($remark));
    }

    public function updateItems(int $id, array $items): void {
        $order = $this->repo->findById($id);
        if ($order === null) {
            throw new ServiceException('訂單不存在', 404);
        }
        if (in_array($order['status'], ['completed', 'cancelled'], true)) {
            throw new ServiceException('此狀態的訂單不可修改');
        }
        $total = 0.0;
        $lines = [];
        foreach ($items as $item) {
            $productId = (int)($item['product_id'] ?? 0);
            $qty = (int)($item['quantity'] ?? 0);
            if ($productId <= 0 || $qty <= 0) {
                continue;
            }
            $p = $this->productRepo->getById($productId);
            if ($p === null || $p['status'] !== 'active') {
                throw new ServiceException('商品不存在或已下架');
            }
            $lines[] = ['product' => $p, 'quantity' => $qty];
            $total += (float)$p['price'] * $qty;
        }
        if (count($lines) === 0) {
            throw new ServiceException('訂單至少需保留一項餐點');
        }
        $this->repo->beginTransaction();
        try {
            $this->repo->deleteItems($id);
            foreach ($lines as $l) {
                $this->repo->createItem($id, (int)$l['product']['id'], (float)$l['product']['price'], $l['quantity']);
            }
            $this->repo->updateTotal($id, $total);
            $this->repo->commit();
        } catch (\Throwable $e) {
            $this->repo->rollBack();
            throw $e;
        }
    }

    public function startLinePay(int $id, int $userId): array {
        $order = $this->requireUserOrder($id, $userId);
        if ($order['status'] !== 'pending') {
            throw new ServiceException('此訂單無法付款', 400);
        }
        if (!$this->linePay->isConfigured()) {
            throw new ServiceException('LINE Pay 尚未設定');
        }

        $this->repo->updatePaymentMethod($id, 'linepay');

        $amount = $this->amountOf($id);
        if ($amount <= 0) {
            throw new ServiceException('訂單金額異常');
        }

        $products = [];
        foreach ($this->repo->getItems($id) as $item) {
            $products[] = new LinePayProduct(
                (string)$item['product_id'],
                $item['name'],
                (int)$item['quantity'],
                (int)round((float)$item['price']),
            );
        }

        $orderId = 'SHOP-' . str_pad((string)$id, 10, '0', STR_PAD_LEFT);
        $returnUrl = 'http://localhost:5173/orders/' . $id;

        try {
            $result = $this->linePay->start(new LinePayOrder(
                $amount,
                $orderId,
                '購物訂單 #' . $id,
                $products,
            ), $returnUrl, $returnUrl);
        } catch (LinePayException $e) {
            throw new ServiceException('LINE Pay 請求失敗：' . $e->getMessage());
        }

        $transactionId = $result->transactionId();
        if ($transactionId !== '') {
            $this->repo->updateLinePayTransactionId($id, $transactionId);
        }

        return [
            'sandbox' => $result->isSandbox(),
            'payment_access_token' => $result->paymentAccessToken(),
            'payment_url' => $result->paymentUrlWeb(),
            'payment_url_app' => $result->paymentUrlApp(),
            'transaction_id' => $transactionId,
        ];
    }

    public function checkLinePay(int $id, int $userId): array {
        $order = $this->requireUserOrder($id, $userId);
        if ($order['status'] !== 'pending') {
            return ['status' => $order['status']];
        }
        $transactionId = (string)($order['linepay_transaction_id'] ?? '');
        if ($transactionId === '') {
            return ['status' => 'pending', 'payment' => 'none'];
        }
        $status = $this->linePay->capture($transactionId, $this->amountOf($id));
        if ($status->value() === LinePayStatus::PAID) {
            $this->updateStatus($id, 'paid');
            return ['status' => 'paid'];
        }
        if ($status->value() === LinePayStatus::CANCELLED) {
            return ['status' => 'pending', 'payment' => 'cancelled'];
        }
        return ['status' => 'pending', 'payment' => 'waiting'];
    }

    public function payWithCashOnDelivery(int $id, int $userId): void {
        $order = $this->requireUserOrder($id, $userId);
        if ($order['status'] !== 'pending') {
            throw new ServiceException('此訂單無法付款', 400);
        }
        $this->repo->updatePaymentMethod($id, 'cod');
        $this->updateStatus($id, 'paid');
    }

    public function cashCheckout(int $id): void {
        $order = $this->repo->findById($id);
        if ($order === null) {
            throw new ServiceException('訂單不存在', 404);
        }
        if ($order['status'] !== 'pending') {
            throw new ServiceException('此訂單無法付款', 400);
        }
        $this->repo->updatePaymentMethod($id, 'cash');
        $this->repo->updateStatus($id, 'paid');
    }

    private function printTicket(int $id): void {
        if ($this->printSvc === null) {
            return;
        }
        try {
            $order = $this->repo->findById($id);
            if ($order === null) {
                return;
            }
            $this->printSvc->printReceipt($order, $this->repo->getItems($id));
        } catch (\Throwable $e) {
            // 印表機失敗不影響訂單流程
        }
    }

    private function requireUserOrder(int $id, int $userId): array {
        $order = $this->repo->findById($id);
        if ($order === null || (int)$order['user_id'] !== $userId) {
            throw new ServiceException('訂單不存在', 404);
        }
        return $order;
    }

    private function amountOf(int $id): int {
        $amount = 0;
        foreach ($this->repo->getItems($id) as $item) {
            $amount += (int)round((float)$item['price']) * (int)$item['quantity'];
        }
        return $amount;
    }
}
