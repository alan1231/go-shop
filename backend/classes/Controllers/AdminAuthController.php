<?php

namespace App\Controllers;

use App\Registry;
use App\Response;
use App\Support;

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

    public static function oauth(): void {
        $body = Support::jsonBody();
        $provider = (string)($body['provider'] ?? '');
        $info = Registry::get('oauthSvc')->getUserInfo(
            $provider,
            (string)($body['code'] ?? ''),
            (string)($body['redirect_uri'] ?? '')
        );
        $admin = Registry::get('authSvc')->findByProvider($provider, $info['provider_id']);
        if ($admin === null) {
            Response::fail('此帳號尚未綁定，請先使用帳號密碼登入後綁定', 401);
        }
        $token = Registry::get('authSvc')->login($admin);
        Response::success(['token' => $token, 'user' => self::adminPayload($admin)], '登入成功');
    }

    public static function bind(): void {
        $admin = self::requireAdmin();
        $body = Support::jsonBody();
        $info = Registry::get('oauthSvc')->getUserInfo(
            (string)($body['provider'] ?? ''),
            (string)($body['code'] ?? ''),
            (string)($body['redirect_uri'] ?? '')
        );
        Registry::get('authSvc')->bindOAuth((int)$admin['id'], (string)($body['provider'] ?? ''), $info['provider_id']);
        Response::success(null, '綁定成功');
    }

    public static function unbind(): void {
        $admin = self::requireAdmin();
        $body = Support::jsonBody();
        $provider = (string)($body['provider'] ?? '');
        Registry::get('adminRepo')->setProvider((int)$admin['id'], $provider, '');
        Response::success(null, '已解除綁定');
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