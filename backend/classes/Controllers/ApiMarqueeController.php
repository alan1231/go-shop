<?php

class ApiMarqueeController extends BaseController {
    public static function index(): void {
        Response::success(['content' => Registry::get('marqueeSvc')->getContent()], 'ok');
    }
}
