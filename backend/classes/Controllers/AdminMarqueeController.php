<?php

class AdminMarqueeController extends BaseController {
    public static function show(): void {
        self::requireAdmin();
        Response::success(['content' => Registry::get('marqueeSvc')->getContent()], 'ok');
    }

    public static function update(): void {
        self::requireAdmin();
        $body = Support::jsonBody();
        Registry::get('marqueeSvc')->updateContent((string)($body['content'] ?? ''));
        Response::success(null, '跑馬燈已更新');
    }
}
