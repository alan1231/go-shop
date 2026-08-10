<?php

class AdminDashboardController extends BaseController {
    public static function index(): void {
        self::requireAdmin();
        Response::success(Registry::get('dashboardSvc')->getStats(), 'ok');
    }
}
