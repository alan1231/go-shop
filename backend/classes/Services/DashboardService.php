<?php

class DashboardService {
    private ProductRepository $productRepo;
    private OrderRepository $orderRepo;
    private UserRepository $userRepo;

    public function __construct(ProductRepository $productRepo, OrderRepository $orderRepo, UserRepository $userRepo) {
        $this->productRepo = $productRepo;
        $this->orderRepo = $orderRepo;
        $this->userRepo = $userRepo;
    }

    public function getStats(): array {
        return [
            'totalProducts' => $this->productRepo->count(),
            'totalOrders' => $this->orderRepo->count(),
            'totalUsers' => $this->userRepo->countByRole('user'),
            'revenue' => $this->orderRepo->getCompletedRevenue(),
            'recentOrders' => $this->orderRepo->getRecent(5),
            'statusCounts' => $this->orderRepo->countByStatus(),
            'dailyStats' => $this->orderRepo->getDailyStats(7),
            'topProducts' => $this->orderRepo->getTopProducts(5),
        ];
    }
}
