<?php
// 後台共用版頭：側邊欄導航 + 使用者資訊
$current_page = $_SERVER['REQUEST_URI'];
$adminUser = Auth::user(); // 注意：此變數與 Controller 中的 $user 不衝突
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? '管理後台' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { background-color: #f0f2f5; }
        body { font-family: 'Segoe UI', Arial, sans-serif; display: flex; min-height: 100vh; background: #f0f2f5; }
        .sidebar { width: 240px; background: #1a1d29; color: #b0b3c5; display: flex; flex-direction: column; position: fixed; top: 0; left: 0; height: 100vh; z-index: 100; }
        .sidebar .logo { padding: 24px 20px; font-size: 20px; font-weight: 700; color: #fff; border-bottom: 1px solid rgba(255,255,255,0.06); letter-spacing: 1px; }
        .sidebar .logo i { margin-right: 10px; color: #4CAF50; }
        .sidebar nav { padding: 16px 12px; flex: 1; }
        .sidebar nav a { display: flex; align-items: center; gap: 12px; padding: 11px 14px; border-radius: 8px; color: #b0b3c5; text-decoration: none; font-size: 14px; transition: background 0.2s, color 0.2s; margin-bottom: 4px; outline: none; -webkit-tap-highlight-color: transparent; }
        .sidebar nav a:hover { background: rgba(255,255,255,0.06); color: #fff; }
        .sidebar nav a:focus, .sidebar nav a:focus-visible { outline: none; }
        .sidebar nav a:active { background: #43a047; color: #fff; }
        .sidebar nav a.active { background: #4CAF50; color: #fff; }
        .sidebar nav a i { width: 18px; text-align: center; font-size: 16px; }
        .sidebar .user-section { padding: 16px 20px; border-top: 1px solid rgba(255,255,255,0.06); font-size: 13px; }
        .sidebar .user-section .username { color: #fff; font-weight: 600; }
        .sidebar .user-section .logout { color: #f44336; text-decoration: none; display: block; margin-top: 6px; }
        .sidebar .user-section .logout:hover { text-decoration: underline; }
        .main-content { margin-left: 240px; flex: 1; padding: 30px 60px 30px 36px; min-height: 100vh; }
        .main-content .page-header { margin-bottom: 28px; display: flex; justify-content: space-between; align-items: center; }
        .main-content .page-header h1 { font-size: 22px; font-weight: 700; color: #1a1d29; }
        .main-content .page-header .btn { padding: 10px 20px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; border: none; cursor: pointer; transition: all 0.2s; }
        .btn { padding: 10px 22px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; border: none; cursor: pointer; transition: all 0.2s; }
        .btn-primary { background: #4CAF50; color: #fff; box-shadow: 0 1px 3px rgba(76,175,80,0.3); }
        .btn-primary:hover { background: #43a047; box-shadow: 0 2px 6px rgba(76,175,80,0.4); transform: translateY(-1px); }
        .btn-default { background: #f0f2f5; color: #444; }
        .btn-default:hover { background: #e4e7eb; }
        .card { background: #fff; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); padding: 24px; margin-bottom: 24px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-weight: 600; font-size: 13px; color: #444; margin-bottom: 6px; }
        .form-group input[type="text"], .form-group input[type="number"], .form-group textarea { width: 100%; max-width: 480px; padding: 10px 12px; border: 1px solid #d0d5dd; border-radius: 6px; font-size: 14px; transition: border-color 0.2s; outline: none; }
        .form-group input:focus, .form-group textarea:focus { border-color: #4CAF50; box-shadow: 0 0 0 3px rgba(76,175,80,0.12); }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .form-group input[type="file"] { font-size: 14px; padding: 6px 0; }
        .msg { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .msg-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .msg-error { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="logo"><i class="fas fa-store"></i>SHOP 後台</div>
        <nav id="sidebarNav">
            <a href="<?= BASE_URL ?>/admin" class="<?= $current_page === BASE_URL . '/admin' || $current_page === BASE_URL . '/' ? 'active' : '' ?>"><i class="fas fa-tachometer-alt"></i>儀表板</a>
            <a href="<?= BASE_URL ?>/admin/orders" class="<?= str_contains($current_page, '/admin/orders') ? 'active' : '' ?>"><i class="fas fa-shopping-cart"></i>訂單管理</a>
            <a href="<?= BASE_URL ?>/admin/products" class="<?= (str_contains($current_page, '/admin/products') || str_contains($current_page, '/admin/edit')) && !str_contains($current_page, '/admin/orders') ? 'active' : '' ?>"><i class="fas fa-box"></i>商品管理</a>
            <a href="<?= BASE_URL ?>/admin/add" class="<?= str_contains($current_page, '/admin/add') ? 'active' : '' ?>" style="padding-left:44px;font-size:13px;"><i class="fas fa-plus-circle" style="font-size:12px;"></i>新增商品</a>
            <a href="<?= BASE_URL ?>/admin/users" class="<?= str_contains($current_page, '/admin/users') ? 'active' : '' ?>"><i class="fas fa-users"></i>會員管理</a>
            <a href="<?= BASE_URL ?>/admin/marquee" class="<?= str_contains($current_page, '/admin/marquee') ? 'active' : '' ?>"><i class="fas fa-scroll"></i>跑馬燈</a>
        </nav>
        <div class="user-section">
            <div class="username"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($adminUser['username'] ?? '') ?></div>
            <a href="<?= BASE_URL ?>/logout" class="logout"><i class="fas fa-sign-out-alt"></i> 登出</a>
        </div>
    </aside>
    <main class="main-content">