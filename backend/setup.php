<?php
require __DIR__ . '/db.php';

// 建立 admin_users 表，若 users 表仍有 admin 則遷移過去
Database::connect()->exec(
    'CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )'
);

$pdo = Database::connect();

// 從 users 遷移 admin 帳號到 admin_users
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'admin'");
$admins = $stmt->fetchAll();

foreach ($admins as $admin) {
    $check = $pdo->prepare('SELECT COUNT(*) FROM admin_users WHERE username = :username');
    $check->execute([':username' => $admin['username']]);
    if ($check->fetchColumn() == 0) {
        $ins = $pdo->prepare('INSERT INTO admin_users (username, password) VALUES (:username, :password)');
        $ins->execute([':username' => $admin['username'], ':password' => $admin['password']]);
    }
    // 從 users 移除 admin 角色，僅保留為一般會員（若有）
    $upd = $pdo->prepare("UPDATE users SET role = 'user' WHERE id = :id AND role = 'admin'");
    $upd->execute([':id' => $admin['id']]);
}

// 若沒有 admin，建立預設帳號
$count = $pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
if ($count == 0) {
    $ins = $pdo->prepare('INSERT INTO admin_users (username, password) VALUES (:username, :password)');
    $ins->execute([':username' => 'admin', ':password' => password_hash('123456', PASSWORD_DEFAULT)]);
    echo "預設管理員帳號建立成功！<br>";
    echo "帳號：admin<br>";
    echo "密碼：123456<br><br>";
} else {
    echo "已有 {$count} 位管理員，無須重複建立。<br>";
}

echo '<a href="login.php">前往登入</a>';
