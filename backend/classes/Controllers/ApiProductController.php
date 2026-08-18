<?php

namespace App\Controllers;

use App\Registry;
use App\Response;
use App\Support;

class ApiProductController extends BaseController {
    public static function index(): void {
        $q = (string)($_GET['q'] ?? '');
        $category = (string)($_GET['category'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, (int)($_GET['per_page'] ?? 10));
        $paged = Registry::get('productSvc')->getActivePage($q, $category, $page, $perPage);
        $items = array_map(fn($p) => self::productPublicPayload($p), $paged['items']);
        Response::success([
            'items' => $items,
            'total' => $paged['total'],
            'page' => $paged['page'],
            'per_page' => $paged['per_page'],
            'total_pages' => $paged['total_pages'],
        ], 'ok');
    }

    public static function categories(): void {
        Response::success(Registry::get('productSvc')->getCategories(), 'ok');
    }

    public static function show(int $id): void {
        $p = Registry::get('productSvc')->getById($id);
        if ($p === null || $p['status'] !== 'active') {
            Response::fail('商品不存在', 404);
        }
        Response::success(self::productShowPayload($p), 'ok');
    }
}
