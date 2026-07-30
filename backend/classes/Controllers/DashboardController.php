<?php
// 儀表板：彙整商品/訂單/會員統計
class DashboardController extends Controller {
    private DashboardService $dashboardService;

    public function __construct() {
        Auth::start();
        Auth::check();
        $this->dashboardService = new DashboardService();
    }

    // 顯示統計總覽
    public function index(): void {
        $stats = $this->dashboardService->getStats();
        $this->render('admin-dashboard', $stats + ['page_title' => '儀表板']);
    }
}