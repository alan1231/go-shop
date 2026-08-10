<?php

class OrderService {
    private PDO $pdo;
    private OrderRepository $repo;
    private ProductRepository $productRepo;

    public function __construct(PDO $pdo, OrderRepository $repo, ProductRepository $productRepo) {
        $this->pdo = $pdo;
        $this->repo = $repo;
        $this->productRepo = $productRepo;
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
}
