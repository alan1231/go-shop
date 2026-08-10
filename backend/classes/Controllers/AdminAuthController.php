<?php

class AdminAuthController extends BaseController {
    public static function login(): void {
        $body = Support::jsonBody();
        $admin = Registry::get('authSvc')->authenticate(
            trim((string)($body['username'] ?? '')),
            (string)($body['password'] ?? '')
        );
        $token = Registry::get('authSvc')->login($admin);
        Response::success(['token' => $token, 'user' => self::adminPayload($admin)], '登入成功');
    }

    public static function me(): void {
        $admin = self::requireAdmin();
        Response::success(self::adminPayload($admin), 'ok');
    }

    public static function logout(): void {
        $admin = self::requireAdmin();
        Registry::get('authSvc')->logout((int)$admin['id']);
        Response::success(null, '已登出');
    }
}
