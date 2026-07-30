<?php
// 商品管理：列表、新增、編輯（含圖片上傳）
class ProductController extends Controller {
    private ProductService $productService;

    public function __construct() {
        Auth::start();
        Auth::check();
        $this->productService = new ProductService();
    }

    // 商品列表
    public function index(): void {
        $products = $this->productService->getAll();
        $this->render('admin-index', ['products' => $products, 'page_title' => '商品管理']);
    }

    // 新增商品（GET 顯示表單，POST 處理新增）
    public function add(): void {
        $message = '';
        $message_type = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->productService->create($_POST, $_FILES['image'] ?? null);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }

        $this->render('admin-add', compact('message', 'message_type') + ['page_title' => '新增商品']);
    }

    // 編輯商品（GET 顯示表單，POST 處理更新）
    public function edit(int $id): void {
        $p = $this->productService->getById($id);
        if (!$p) {
            $this->notFound('商品不存在');
            return;
        }

        $message = '';
        $message_type = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->productService->update($id, $_POST, $_FILES['image'] ?? null);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
            if ($result['success']) {
                $p = $this->productService->getById($id);
            }
        }

        $this->render('admin-edit', compact('p', 'message', 'message_type') + ['page_title' => '修改商品']);
    }
}