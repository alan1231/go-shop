<?php
// 訂單管理：列表、明細、狀態更新
class OrdersController extends Controller {
    private OrderService $orderService;

    public function __construct() {
        Auth::start();
        Auth::check();
        $this->orderService = new OrderService();
    }

    // 列表，支援 status 查詢參數篩選
    public function index(): void {
        $status = $_GET['status'] ?? '';
        $orders = $this->orderService->getAll($status);
        $this->render('admin-orders', ['orders' => $orders, 'page_title' => '訂單管理']);
    }

    // 訂單明細（含商品項目）
    public function show(int $id): void {
        $order = $this->orderService->getWithItems($id);
        if (!$order) {
            $this->notFound('訂單不存在');
            return;
        }

        $this->render('admin-order-detail', ['order' => $order, 'orderItems' => $order['items'], 'page_title' => "訂單 #{$id}"]);
    }

    // 更新訂單狀態（POST）
    public function updateStatus(int $id): void {
        $status = $_POST['status'] ?? '';
        $error = $this->orderService->updateStatus($id, $status);
        if ($error) {
            $this->badRequest($error);
            return;
        }
        header('Location: ' . BASE_URL . '/admin/orders/' . $id);
        exit;
    }
}