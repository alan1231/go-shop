<?php
// 儀表板統計：彙整多個 Repository 的彙總資料
class DashboardService {
    private ProductRepository $productRepo;
    private OrderRepository $orderRepo;
    private UserRepository $userRepo;

    public function __construct() {
        $this->productRepo = new ProductRepository();
        $this->orderRepo   = new OrderRepository();
        $this->userRepo    = new UserRepository();
    }

    // 回傳儀表板所需的所有統計數字
    public function getStats(): array {
        return [
            'totalProducts' => $this->productRepo->count(),
            'totalOrders'   => $this->orderRepo->count(),
            'totalUsers'    => $this->userRepo->countByRole('user'),
            'revenue'       => $this->orderRepo->getCompletedRevenue(),
            'recentOrders'  => $this->orderRepo->getRecent(),
        ];
    }
}