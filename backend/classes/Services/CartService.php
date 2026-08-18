<?php

class CartService {
    private CartRepository $repo;
    private ProductRepository $productRepo;

    public function __construct(CartRepository $repo, ProductRepository $productRepo) {
        $this->repo = $repo;
        $this->productRepo = $productRepo;
    }

    public function getCart(int $userId): array {
        return array_map(function ($item) {
            return [
                'product_id' => $item['product_id'],
                'name' => $item['name'] ?? '',
                'image' => Support::nullIfEmpty(Support::uploadUrl($item['image'] ?? '')),
                'price' => (float)$item['price'],
                'list_price' => $item['list_price'],
                'stock' => (int)$item['listed_stock'],
                'quantity' => $item['quantity'],
            ];
        }, $this->repo->findByUserId($userId));
    }

    public function add(int $userId, int $productId, int $quantity): void {
        if ($quantity <= 0) {
            throw new ServiceException('數量不正確');
        }
        $p = $this->productRepo->getById($productId);
        if ($p === null || $p['status'] !== 'active') {
            throw new ServiceException('商品不存在或已下架');
        }
        $stock = (int)$p['listed_stock'];
        if ($stock < 1) {
            throw new ServiceException('商品「' . $p['name'] . '」已售完');
        }
        $next = $this->repo->getQuantity($userId, $productId) + $quantity;
        if ($next > $stock) {
            throw new ServiceException('商品「' . $p['name'] . '」庫存不足（最多 ' . $stock . ' 件）');
        }
        $this->repo->upsert($userId, $productId, $quantity);
    }

    public function setQuantity(int $userId, int $productId, int $quantity): void {
        if ($quantity < 1) {
            throw new ServiceException('數量不正確');
        }
        $p = $this->productRepo->getById($productId);
        if ($p === null || $p['status'] !== 'active') {
            throw new ServiceException('商品不存在或已下架');
        }
        if ($quantity > (int)$p['listed_stock']) {
            throw new ServiceException('商品「' . $p['name'] . '」庫存不足（最多 ' . (int)$p['listed_stock'] . ' 件）');
        }
        $this->repo->setQuantity($userId, $productId, $quantity);
    }

    public function remove(int $userId, int $productId): void {
        $this->repo->remove($userId, $productId);
    }

    public function clear(int $userId): void {
        $this->repo->clear($userId);
    }

    public function merge(int $userId, array $items): void {
        foreach ($items as $item) {
            $productId = (int)($item['product_id'] ?? 0);
            $quantity = (int)($item['quantity'] ?? 0);
            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }
            try {
                $this->add($userId, $productId, $quantity);
            } catch (ServiceException $e) {
                // 庫存不足等單項錯誤不中斷合併
            }
        }
    }
}
