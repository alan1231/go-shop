<?php

namespace App\Controllers;

use App\Registry;
use App\Response;

class ApiMarqueeController extends BaseController {
    public static function index(): void {
        Response::success(['content' => Registry::get('marqueeSvc')->getContent()], 'ok');
    }
}
