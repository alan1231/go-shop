<?php
require __DIR__ . '/db.php';

$count = Database::connect()->query('SELECT COUNT(*) FROM users')->fetchColumn();

if ($count == 0) {
    $stmt = Database::connect()->prepare('INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)');
    $stmt->execute([
        ':username' => 'admin',
        ':email'    => 'admin@example.com',
        ':password' => password_hash('123456', PASSWORD_DEFAULT),
        ':role'     => 'admin',
    ]);
    echo "預設管理員帳號建立成功！<br>";
    echo "帳號：admin 或 admin@example.com<br>";
    echo "密碼：123456<br><br>";
} else {
    echo "已有 {$count} 位使用者，無須重複建立。<br>";
}

echo '<a href="login.php">前往登入</a>';
