<?php
// 前台商品 API：列表、明細
class ApiProductController extends ApiController {
    private ProductService $productService;

    public function __construct() {
        $this->productService = new ProductService();
    }

    // GET /api/products — 僅回傳上架中商品
    public function index(): void {
        $products = $this->productService->getAll();
        // 過濾只回傳前台需要的欄位
        $result = array_map(function ($p) {
            return [
                'id'          => (int)$p['id'],
                'name'        => $p['name'],
                'image'       => $p['image'] ? BASE_URL . '/uploads/' . $p['image'] : null,
                'description' => $p['description'],
                'price'       => (float)$p['price'],
                'list_price'  => $p['list_price'] ? (float)$p['list_price'] : null,
                'stock'       => (int)$p['listed_stock'],
                'status'      => $p['status'],
            ];
        }, $products);

        // 只回傳上架中且有庫存的
        $result = array_values(array_filter($result, fn($p) => $p['status'] === 'active' && $p['stock'] > 0));

        $this->success($result);
    }

    // GET /api/products/{id}
    public function show(int $id): void {
        $p = $this->productService->getById($id);
        if (!$p || $p['status'] !== 'active') {
            $this->error('商品不存在', 404);
            return;
        }

        $this->success([
            'id'          => (int)$p['id'],
            'name'        => $p['name'],
            'image'       => $p['image'] ? BASE_URL . '/uploads/' . $p['image'] : null,
            'description' => $p['description'],
            'price'       => (float)$p['price'],
            'list_price'  => $p['list_price'] ? (float)$p['list_price'] : null,
            'stock'       => (int)$p['listed_stock'],
        ]);
    }
}