<?php
// HTTP 進入點：載入設定 → 定義 BASE_URL → 註冊路由 → 分發請求
require __DIR__ . '/db.php';

define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'));
$router = new Router(BASE_URL);

// 首頁 — 依登入狀態導向後台或登入頁
$router->get('/', function () {
    Auth::start();
    Auth::redirect(Auth::user() ? BASE_URL . '/admin' : BASE_URL . '/login');
});

// 登入
$router->get('/login',    [AuthController::class, 'showLogin']);
$router->post('/login',   [AuthController::class, 'login']);
$router->get('/logout',   [AuthController::class, 'logout']);

// 後台
$router->get('/admin',          [DashboardController::class, 'index']);
$router->get('/admin/products', [ProductController::class, 'index']);
$router->get('/admin/add',      [ProductController::class, 'add']);
$router->post('/admin/add',     [ProductController::class, 'add']);
$router->get('/admin/edit/{id}', [ProductController::class, 'edit']);
$router->post('/admin/edit/{id}', [ProductController::class, 'edit']);

// 訂單
$router->get('/admin/orders',             [OrdersController::class, 'index']);
$router->get('/admin/orders/{id}',        [OrdersController::class, 'show']);
$router->post('/admin/orders/{id}/status', [OrdersController::class, 'updateStatus']);

// 會員
$router->get('/admin/users',             [UsersController::class, 'index']);
$router->get('/admin/users/add',         [UsersController::class, 'add']);
$router->post('/admin/users/add',        [UsersController::class, 'add']);
$router->get('/admin/users/edit/{id}',   [UsersController::class, 'edit']);
$router->post('/admin/users/edit/{id}',  [UsersController::class, 'edit']);
$router->get('/admin/users/delete/{id}', [UsersController::class, 'delete']);

// 跑馬燈
$router->get('/admin/marquee',  [MarqueeController::class, 'edit']);
$router->post('/admin/marquee', [MarqueeController::class, 'edit']);

// API（前台）
$router->get('/api/marquee',       [ApiMarqueeController::class, 'index']);
$router->post('/api/auth/register',  [ApiAuthController::class, 'register']);
$router->post('/api/auth/login',     [ApiAuthController::class, 'login']);
$router->post('/api/auth/logout',    [ApiAuthController::class, 'logout']);
$router->get('/api/auth/me',         [ApiAuthController::class, 'me']);
$router->get('/api/products',        [ApiProductController::class, 'index']);
$router->get('/api/products/{id}',   [ApiProductController::class, 'show']);
$router->post('/api/orders',         [ApiOrderController::class, 'create']);
$router->get('/api/orders',          [ApiOrderController::class, 'index']);
$router->get('/api/orders/{id}',     [ApiOrderController::class, 'show']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);