<?php

namespace App\Controllers;

use App\Registry;
use App\Response;
use App\Support;

class AdminProductController extends BaseController {
    public static function index(): void {
        self::requireAdmin();
        $q = (string)($_GET['q'] ?? '');
        $category = (string)($_GET['category'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(1000, max(1, (int)($_GET['per_page'] ?? 10)));
        $paged = Registry::get('productSvc')->getFilteredPage($q, $category, $page, $perPage);
        $items = array_map(fn($p) => self::adminProductPayload($p), $paged['items']);
        Response::success([
            'items' => $items,
            'total' => $paged['total'],
            'page' => $paged['page'],
            'per_page' => $paged['per_page'],
            'total_pages' => $paged['total_pages'],
        ], 'ok');
    }

    public static function create(): void {
        self::requireAdmin();
        Registry::get('productSvc')->create(self::productInputFromForm());
        Response::success(null, '商品新增成功');
    }

    public static function show(int $id): void {
        self::requireAdmin();
        $p = Registry::get('productSvc')->getById($id);
        if ($p === null) {
            Response::fail('商品不存在', 404);
        }
        Response::success(self::adminProductPayload($p), 'ok');
    }

    public static function update(int $id): void {
        self::requireAdmin();
        Registry::get('productSvc')->update($id, self::productInputFromForm());
        Response::success(null, '商品修改成功');
    }

    public static function delete(int $id): void {
        self::requireAdmin();
        Registry::get('productSvc')->delete($id);
        Response::success(null, '商品已刪除');
    }

    public static function categories(): void {
        self::requireAdmin();
        Response::success(Registry::get('productSvc')->getAllCategories(), 'ok');
    }

    public static function moveCategory(): void {
        self::requireAdmin();
        $in = json_decode((string)file_get_contents('php://input'), true) ?: [];
        $name = trim((string)($in['name'] ?? ''));
        $direction = (string)($in['direction'] ?? '');
        Response::success(Registry::get('productSvc')->moveCategory($name, $direction), '分類順序已更新');
    }

    private static function productInputFromForm(): array {
        $in = [
            'name' => (string)($_POST['name'] ?? ''),
            'description' => (string)($_POST['description'] ?? ''),
            'category' => (string)($_POST['category'] ?? ''),
            'price' => Support::float((string)($_POST['price'] ?? '')),
            'list_price' => null,
            'status' => (string)($_POST['status'] ?? ''),
            'image' => '',
            'image_name' => '',
        ];
        if ($in['status'] === '') {
            $in['status'] = 'active';
        }
        if (isset($_POST['list_price']) && trim((string)$_POST['list_price']) !== '') {
            $in['list_price'] = Support::float((string)$_POST['list_price']);
        }
        if (!empty($_FILES['image']) && is_array($_FILES['image'])) {
            if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $in['image'] = file_get_contents((string)$_FILES['image']['tmp_name']);
                $in['image_name'] = (string)($_FILES['image']['name'] ?? '');
            } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                Response::fail('圖片上傳失敗', 400);
            }
        }
        return $in;
    }
}
