<?php

class AdminUserController extends BaseController {
    public static function index(): void {
        self::requireAdmin();
        $q = (string)($_GET['q'] ?? '');
        $users = Registry::get('userSvc')->getAllMembers($q);
        $items = array_map(fn($u) => self::adminUserPayload($u), $users);
        Response::success($items, 'ok');
    }

    public static function create(): void {
        self::requireAdmin();
        $body = Support::jsonBody();
        Registry::get('userSvc')->createMember(
            (string)($body['username'] ?? ''),
            (string)($body['email'] ?? ''),
            (string)($body['password'] ?? '')
        );
        Response::success(null, '會員新增成功');
    }

    public static function show(int $id): void {
        self::requireAdmin();
        $u = Registry::get('userSvc')->getById($id);
        if ($u === null) {
            Response::fail('會員不存在', 404);
        }
        Response::success(self::adminUserPayload($u), 'ok');
    }

    public static function updatePassword(int $id): void {
        self::requireAdmin();
        $body = Support::jsonBody();
        $u = Registry::get('userSvc')->getById($id);
        if ($u === null) {
            Response::fail('會員不存在', 404);
        }
        if (($u['provider'] ?? '') !== '') {
            Response::fail('此會員為三方登入，無密碼可修改', 400);
        }
        Registry::get('userSvc')->updatePassword($id, (string)($body['password'] ?? ''));
        Response::success(null, '密碼已更新');
    }

    public static function delete(int $id): void {
        self::requireAdmin();
        Registry::get('userSvc')->canDelete($id);
        Registry::get('userSvc')->delete($id);
        Response::success(null, '會員已刪除');
    }
}
