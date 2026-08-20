<?php

namespace App\Controllers;

use App\Registry;
use App\Response;
use App\Support;

class AdminSettingsController extends BaseController {
    public static function show(): void {
        self::requireAdmin();
        Response::success(Registry::get('settingsSvc')->all(), 'ok');
    }

    public static function updateTableCount(): void {
        self::requireAdmin();
        $body = Support::jsonBody();
        Registry::get('settingsSvc')->setTableCount((int)($body['table_count'] ?? 0));
        Response::success(null, '桌數已更新');
    }

    public static function updateLinePay(): void {
        self::requireAdmin();
        $body = Support::jsonBody();
        Registry::get('settingsSvc')->setLinePay(
            (string)($body['channel_id'] ?? ''),
            (string)($body['channel_secret'] ?? ''),
            (string)($body['sandbox'] ?? '0')
        );
        Response::success(null, '三方支付設定已更新');
    }
}