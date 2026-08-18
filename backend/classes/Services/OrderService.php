<?php

class OrderService {
    private PDO $pdo;
    private OrderRepository $repo;
    private ProductRepository $productRepo;
    private LinePayService $linePay;

    public function __construct(PDO $pdo, OrderRepository $repo, ProductRepository $productRepo, LinePayService $linePay) {
        $this->pdo = $pdo;
        $this->repo = $repo;
        $this->productRepo = $productRepo;
        $this->linePay = $linePay;
    }

    public function getAll(string $status, int $page, int $perPage): array {
        $total = $this->repo->countFindAll($status);
        $items = $this->repo->findAll($status, $perPage, ($page - 1) * $perPage);
        return Support::page($items, $total, $page, $perPage);
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

    public function createOrder(int $userId, array $items, array $receiver, string $remark): int {
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
                if (!$this->productRepo->decreaseStockIfAvailable($productId, $qty)) {
                    throw new ServiceException('商品「' . $p['name'] . '」庫存不足');
                }
                $lines[] = ['product' => $p, 'quantity' => $qty];
                $total += (float)$p['price'] * $qty;
            }
            if (count($lines) === 0) {
                throw new ServiceException('商品不存在或庫存不足');
            }
            $orderId = $this->repo->createOrder(
                $userId,
                $total,
                $receiver['name'],
                $receiver['phone'],
                $receiver['address'],
                $remark
            );
            foreach ($lines as $l) {
                $this->repo->createItem($orderId, (int)$l['product']['id'], (float)$l['product']['price'], $l['quantity']);
            }
            $this->repo->commit();
            return $orderId;
        } catch (Throwable $e) {
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
            $products[] = [
                'id' => (string)$item['product_id'],
                'name' => $item['name'],
                'quantity' => (int)$item['quantity'],
                'price' => (int)round((float)$item['price']),
            ];
        }

        $orderId = 'SHOP-' . str_pad((string)$id, 10, '0', STR_PAD_LEFT);
        $returnUrl = 'http://localhost:5173/orders/' . $id;
        $resp = $this->linePay->request([
            'amount' => $amount,
            'orderId' => $orderId,
            'packageName' => '購物訂單 #' . $id,
            'products' => $products,
        ], $returnUrl, $returnUrl);

        if (($resp['returnCode'] ?? '') !== '0000') {
            throw new ServiceException('LINE Pay 請求失敗：' . ($resp['returnMessage'] ?? '未知錯誤'));
        }

        $info = $resp['info'] ?? [];
        $transactionId = (string)($info['transactionId'] ?? '');
        if ($transactionId !== '') {
            $this->repo->updateLinePayTransactionId($id, $transactionId);
        }

        return [
            'sandbox' => $this->linePay->isSandbox(),
            'payment_access_token' => (string)($info['paymentAccessToken'] ?? ''),
            'payment_url' => $info['paymentUrl']['web'] ?? '',
            'payment_url_app' => (string)($info['paymentUrl']['app'] ?? ''),
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
        $resp = $this->linePay->checkStatus($transactionId);
        $code = (string)($resp['returnCode'] ?? '');
        if ($code === '0123') {
            $this->updateStatus($id, 'paid');
            return ['status' => 'paid'];
        }
        if ($code === '0110') {
            $confirm = $this->linePay->confirm($transactionId, $this->amountOf($id));
            $confirmCode = (string)($confirm['returnCode'] ?? '');
            if ($confirmCode === '0000') {
                $this->updateStatus($id, 'paid');
                return ['status' => 'paid'];
            }
            $recheck = $this->linePay->checkStatus($transactionId);
            if ((string)($recheck['returnCode'] ?? '') === '0123') {
                $this->updateStatus($id, 'paid');
                return ['status' => 'paid'];
            }
            return ['status' => 'pending', 'payment' => 'waiting'];
        }
        if ($code === '0121' || $code === '0122') {
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
