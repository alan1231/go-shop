<?php

namespace App\Controllers;

use App\Registry;
use App\Response;

class ApiSettingsController extends BaseController {
    public static function index(): void {
        Response::success([
            'menu_layout' => Registry::get('settingsSvc')->getMenuLayout(),
        ], 'ok');
    }
}