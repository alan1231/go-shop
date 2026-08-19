<?php

namespace App\Controllers;

use App\Registry;
use App\Response;
use App\ServiceException;
use App\Support;

class ApiAuthController extends BaseController {
    public static function register(): void {
        if (self::rateLimited('register')) {
            return;
        }
        $body = Support::jsonBody();
        $username = trim((string)($body['username'] ?? ''));
        $email = trim((string)($body['email'] ?? ''));
        $password = (string)($body['password'] ?? '');
        try {
            Registry::get('userSvc')->register($username, $email, $password);
        } catch (ServiceException $e) {
            Registry::get('rateLimitSvc')->recordFail(Support::clientIP(), 'register');
            Response::fail($e->getMessage(), $e->getCode() ?: 400);
        }
        Registry::get('rateLimitSvc')->clear(Support::clientIP(), 'register');
        Response::success(null, '註冊成功');
    }

    public static function login(): void {
        if (self::rateLimited('login')) {
            return;
        }
        $body = Support::jsonBody();
        $username = trim((string)($body['username'] ?? ''));
        $password = (string)($body['password'] ?? '');
        $user = Registry::get('userRepo')->findForAuth($username);
        if ($user === null || !password_verify($password, $user['password'])) {
            Registry::get('rateLimitSvc')->recordFail(Support::clientIP(), 'login');
            Response::fail('帳號或密碼錯誤', 400);
        }
        Registry::get('rateLimitSvc')->clear(Support::clientIP(), 'login');
        $token = Support::randomToken();
        Registry::get('userRepo')->setToken((int)$user['id'], $token);
        Response::success(['token' => $token, 'user' => self::userPayload($user)], '登入成功');
    }

    public static function oauth(): void {
        $body = Support::jsonBody();
        $provider = strtolower(trim((string)($body['provider'] ?? '')));
        $code = trim((string)($body['code'] ?? ''));
        if (($provider !== 'google' && $provider !== 'line') || $code === '') {
            Response::fail('無效的三方登入請求', 400);
        }
        $info = Registry::get('oauthSvc')->getUserInfo(
            $provider,
            $code,
            trim((string)($body['redirect_uri'] ?? ''))
        );
        $userRepo = Registry::get('userRepo');
        $user = $userRepo->findByProvider($provider, $info['provider_id']);
        $avatar = $user !== null && ($user['avatar'] ?? '') !== '' ? $user['avatar'] : self::saveAvatar($info['avatar']);
        if ($user === null) {
            $name = $info['name'];
            if ($name === '') {
                $name = $info['email'];
            }
            if ($name === '') {
                $name = $provider . '_' . substr($info['provider_id'], -6);
            }
            $existing = $userRepo->findByEmail($info['email']);
            if ($existing !== null) {
                $userRepo->setProvider((int)$existing['id'], $provider, $info['provider_id']);
                if ($avatar !== '') {
                    $userRepo->updateAvatar((int)$existing['id'], $avatar);
                }
                $user = $existing;
            } else {
                $id = $userRepo->createOAuthUser($name, $info['email'], $provider, $info['provider_id'], $avatar);
                $user = $userRepo->findById($id);
            }
        } elseif ($avatar !== '' && ($user['avatar'] ?? '') !== $avatar) {
            $userRepo->updateAvatar((int)$user['id'], $avatar);
            $user['avatar'] = $avatar;
        }
        $token = Support::randomToken();
        $userRepo->setToken((int)$user['id'], $token);
        Response::success(['token' => $token, 'user' => self::userPayload($user)], '登入成功');
    }

    public static function logout(): void {
        $user = self::requireUser();
        Registry::get('userRepo')->setToken((int)$user['id'], '');
        Response::success(null, '已登出');
    }

    public static function me(): void {
        $user = self::requireUser();
        Response::success(self::userPayload($user), 'ok');
    }

    public static function updateContact(): void {
        $user = self::requireUser();
        $body = Support::jsonBody();
        Registry::get('userSvc')->updateContact(
            (int)$user['id'],
            (string)($body['phone'] ?? ''),
            (string)($body['address'] ?? '')
        );
        Response::success(null, '聯絡資料已更新');
    }

    public static function changePassword(): void {
        $user = self::requireUser();
        $body = Support::jsonBody();
        Registry::get('userSvc')->changePassword(
            (int)$user['id'],
            (string)($body['old_password'] ?? ''),
            (string)($body['new_password'] ?? '')
        );
        Response::success(null, '密碼已更新');
    }

    private static function saveAvatar(string $url): string {
        if ($url === '' || (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://'))) {
            return '';
        }
        try {
            [$data, $ext] = Registry::get('oauthSvc')->fetchAvatar($url);
            if ($data === '') {
                return '';
            }
            return Registry::get('images')->save($data, 'avatar.' . $ext);
        } catch (\Throwable $e) {
            return '';
        }
    }
}
