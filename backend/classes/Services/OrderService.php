<?php
// 訂單商業邏輯：列表、明細、狀態更新驗證
class OrderService {
    private OrderRepository $repo;

    private array $validStatuses = ['pending', 'paid', 'shipped', 'completed', 'cancelled'];

    public function __construct() {
        $this->repo = new OrderRepository();
    }

    // 取得訂單列表，可依狀態篩選
    public function getAll(?string $status = null): array {
        return $this->repo->findAll($status);
    }

    // 取得訂單 + 商品明細，不存在回傳 null
    public function getWithItems(int $id): ?array {
        $order = $this->repo->findById($id);
        if (!$order) return null;
        $order['items'] = $this->repo->getItems($id);
        return $order;
    }

    // 取得指定會員的訂單列表
    public function getUserOrders(int $userId): array {
        return $this->repo->findByUserId($userId);
    }

    // 前台下單：items = [[product_id, quantity], ...]，receiver = [name, phone, address]，remark = 會員備註
    public function createOrder(int $userId, array $items, array $receiver = [], ?string $remark = null): array {
        if (empty($items)) {
            return ['success' => false, 'message' => '訂單不得為空'];
        }

        $productRepo = new ProductRepository();
        $total = 0;
        $orderItems = [];

        foreach ($items as $item) {
            $pid = (int)($item['product_id'] ?? 0);
            $qty = (int)($item['quantity'] ?? 0);
            if ($pid <= 0 || $qty <= 0) continue;

            $product = $productRepo->getById($pid);
            if (!$product || $product['status'] !== 'active' || (int)$product['listed_stock'] < $qty) {
                return ['success' => false, 'message' => "商品「{$product['name']}」庫存不足"];
            }
            $orderItems[] = ['product' => $product, 'quantity' => $qty];
            $total += (float)$product['price'] * $qty;
        }

        if (empty($orderItems)) {
            return ['success' => false, 'message' => '商品不存在或庫存不足'];
        }

        $orderId = $this->repo->createOrder($userId, $total, $receiver, $remark);
        foreach ($orderItems as $oi) {
            $this->repo->createItem($orderId, $oi['product']['id'], $oi['product']['price'], $oi['quantity']);
            $productRepo->decreaseStock($oi['product']['id'], $oi['quantity']);
        }

        return ['success' => true, 'message' => '訂單已建立', 'order_id' => $orderId];
    }

    // 更新狀態，回傳 null 表示成功，字串表示錯誤訊息
    public function updateStatus(int $id, string $status): ?string {
        if (!in_array($status, $this->validStatuses)) {
            return '無效的狀態';
        }
        $order = $this->repo->findById($id);
        if (!$order) {
            return '訂單不存在';
        }
        if ($order['status'] === 'completed') {
            return '訂單已完成，狀態不可再變更';
        }
        $this->repo->updateStatus($id, $status);
        return null;
    }

    // 更新備註
    public function updateRemark(int $id, string $remark): ?string {
        if (!$this->repo->findById($id)) {
            return '訂單不存在';
        }
        $this->repo->updateRemark($id, trim($remark));
        return null;
    }
}