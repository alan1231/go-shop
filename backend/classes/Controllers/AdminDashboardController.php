<?php

namespace App\Controllers;

use App\Registry;
use App\Response;

class AdminDashboardController extends BaseController {
    public static function index(): void {
        self::requireAdmin();
        Response::success(Registry::get('dashboardSvc')->getStats(), 'ok');
    }
}
