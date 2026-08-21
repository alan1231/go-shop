<?php

use App\Config;
use App\Controllers\AdminAccountController;
use App\Controllers\AdminAuthController;
use App\Controllers\AdminDashboardController;
use App\Controllers\AdminMarqueeController;
use App\Controllers\AdminOrderController;
use App\Controllers\AdminProductController;
use App\Controllers\AdminSettingsController;
use App\Controllers\ApiMarqueeController;
use App\Controllers\ApiOrderController;
use App\Controllers\ApiProductController;
use App\Controllers\ApiSettingsController;
use App\Response;
use App\Router;
use App\ServiceException;

require __DIR__ . '/bootstrap.php';

set_exception_handler(function (Throwable $e): void {
    if ($e instanceof ServiceException) {
        Response::fail($e->getMessage(), $e->getCode() ?: 400);
    }
    error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    Response::json(['success' => false, 'message' => '伺服器錯誤'], 500);
});

$router = new Router();

$router->get('/api/marquee', [ApiMarqueeController::class, 'index']);

$router->get('/api/settings', [ApiSettingsController::class, 'index']);

$router->get('/api/products', [ApiProductController::class, 'index']);
$router->get('/api/categories', [ApiProductController::class, 'categories']);
$router->get('/api/products/{id}', [ApiProductController::class, 'show']);

$router->get('/api/tables/available', [ApiOrderController::class, 'availableTable']);

$router->post('/api/orders', [ApiOrderController::class, 'create']);
$router->get('/api/orders', [ApiOrderController::class, 'index']);
$router->get('/api/orders/{id}', [ApiOrderController::class, 'show']);
$router->get('/api/orders/{id}/pay/status', [ApiOrderController::class, 'payStatus']);
$router->post('/api/orders/{id}/pay', [ApiOrderController::class, 'pay']);

$router->post('/api/admin/login', [AdminAuthController::class, 'login']);
$router->get('/api/admin/me', [AdminAuthController::class, 'me']);
$router->post('/api/admin/logout', [AdminAuthController::class, 'logout']);
$router->get('/api/admin/stats', [AdminDashboardController::class, 'index']);

$router->get('/api/admin/products', [AdminProductController::class, 'index']);
$router->post('/api/admin/products', [AdminProductController::class, 'create']);
$router->post('/api/admin/products/order', [AdminProductController::class, 'reorderProducts']);
$router->get('/api/admin/products/{id}', [AdminProductController::class, 'show']);
$router->post('/api/admin/products/{id}', [AdminProductController::class, 'update']);
$router->post('/api/admin/products/{id}/delete', [AdminProductController::class, 'delete']);
$router->get('/api/admin/categories', [AdminProductController::class, 'categories']);
$router->post('/api/admin/categories/move', [AdminProductController::class, 'moveCategory']);
$router->post('/api/admin/categories/order', [AdminProductController::class, 'reorderCategories']);

$router->post('/api/admin/orders', [AdminOrderController::class, 'create']);
$router->get('/api/admin/orders', [AdminOrderController::class, 'index']);
$router->get('/api/admin/orders/{id}', [AdminOrderController::class, 'show']);
$router->post('/api/admin/orders/{id}/status', [AdminOrderController::class, 'updateStatus']);
$router->post('/api/admin/orders/{id}/remark', [AdminOrderController::class, 'updateRemark']);
$router->post('/api/admin/orders/{id}/items', [AdminOrderController::class, 'updateItems']);

$router->get('/api/admin/accounts', [AdminAccountController::class, 'index']);
$router->post('/api/admin/accounts', [AdminAccountController::class, 'create']);
$router->post('/api/admin/accounts/{id}/delete', [AdminAccountController::class, 'delete']);

$router->get('/api/admin/marquee', [AdminMarqueeController::class, 'show']);
$router->post('/api/admin/marquee', [AdminMarqueeController::class, 'update']);

$router->get('/api/admin/settings', [AdminSettingsController::class, 'show']);
$router->post('/api/admin/settings/table-count', [AdminSettingsController::class, 'updateTableCount']);
$router->post('/api/admin/settings/linepay', [AdminSettingsController::class, 'updateLinePay']);
$router->post('/api/admin/settings/menu-layout', [AdminSettingsController::class, 'updateMenuLayout']);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$path = rawurldecode($path);

if (str_starts_with($path, '/uploads/')) {
    serveUpload(substr($path, strlen('/uploads/')));
}

if (str_starts_with($path, '/api')) {
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $path);
}

serveSpa($path);

function serveUpload(string $name): void {
    $base = realpath(Config::get('UPLOADS_DIR')) ?: Config::get('UPLOADS_DIR');
    $full = realpath($base . '/' . $name);
    if ($full === false || !str_starts_with($full, $base)) {
        Response::notFoundFile();
    }
    if (!is_file($full)) {
        Response::notFoundFile();
    }
    Response::file($full, mimeFor(pathinfo($full, PATHINFO_EXTENSION)));
}

function serveSpa(string $path): void {
    $dir = str_starts_with($path, '/admin') ? Config::get('ADMIN_DIST') : Config::get('PUBLIC_DIST');
    $rel = ltrim(str_starts_with($path, '/admin') ? substr($path, strlen('/admin')) : $path, '/');
    if ($rel === '') {
        $rel = 'index.html';
    }
    $full = realpath($dir . '/' . $rel);
    if ($full !== false && is_file($full)) {
        Response::file($full, mimeFor(pathinfo($full, PATHINFO_EXTENSION)));
    }
    Response::file($dir . '/index.html', 'text/html; charset=utf-8');
}

function mimeFor(string $ext): string {
    $map = [
        'html' => 'text/html; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'js' => 'text/javascript; charset=utf-8',
        'mjs' => 'text/javascript; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
    ];
    return $map[strtolower($ext)] ?? 'application/octet-stream';
}
