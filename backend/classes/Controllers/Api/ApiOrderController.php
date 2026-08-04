<?php
// 前台訂單 API：下單、列表、明細（需登入）
class ApiOrderController extends ApiController {
    private OrderService $orderService;

    public function __construct() {
        $this->orderService = new OrderService();
    }

    // POST /api/orders — 建立訂單
    public function create(): void {
        $user = $this->requireAuth();

        $body = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $items = $body['items'] ?? [];
        $receiver = [
            'name'    => trim($body['receiver']['name'] ?? ''),
            'phone'   => trim($body['receiver']['phone'] ?? ''),
            'address' => trim($body['receiver']['address'] ?? ''),
        ];
        $remark = trim($body['remark'] ?? '');

        $result = $this->orderService->createOrder((int)$user['id'], $items, $receiver, $remark ?: null);
        if (!$result['success']) {
            $this->error($result['message']);
            return;
        }
        $this->success(['order_id' => $result['order_id']], $result['message']);
    }

    // GET /api/orders — 我的訂單列表
    public function index(): void {
        $user = $this->requireAuth();
        $orders = $this->orderService->getUserOrders((int)$user['id']);
        $this->success($orders);
    }

    // GET /api/orders/{id} — 訂單明細
    public function show(int $id): void {
        $user = $this->requireAuth();
        $order = $this->orderService->getWithItems($id);

        if (!$order || (int)$order['user_id'] !== (int)$user['id']) {
            $this->error('訂單不存在', 404);
            return;
        }

        $this->success($order);
    }

    // POST /api/orders/{id}/pay — 模擬付款（待付款 → 已付款）
    public function pay(int $id): void {
        $user = $this->requireAuth();
        $order = $this->orderService->getWithItems($id);

        if (!$order || (int)$order['user_id'] !== (int)$user['id']) {
            $this->error('訂單不存在', 404);
            return;
        }
        if ($order['status'] !== 'pending') {
            $this->error('此訂單無法付款');
            return;
        }

        $error = $this->orderService->updateStatus($id, 'paid');
        if ($error) {
            $this->error($error);
            return;
        }

        $this->success(null, '付款成功');
    }
}