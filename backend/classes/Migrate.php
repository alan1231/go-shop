<?php

namespace App;

use PDO;

class Migrate {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function run(): void {
        $statements = [
            'CREATE TABLE IF NOT EXISTS admin_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) NOT NULL,
                password VARCHAR(255) NOT NULL,
                token VARCHAR(64) DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) NOT NULL,
                email VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(50) DEFAULT \'user\',
                token VARCHAR(64) DEFAULT NULL,
                provider VARCHAR(20) DEFAULT NULL,
                provider_id VARCHAR(100) DEFAULT NULL,
                phone VARCHAR(20) DEFAULT NULL,
                address VARCHAR(255) DEFAULT NULL,
                avatar VARCHAR(500) DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY idx_provider (provider, provider_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                image VARCHAR(255) DEFAULT NULL,
                description TEXT,
                category VARCHAR(100) DEFAULT NULL,
                price DECIMAL(10,2) NOT NULL,
                list_price DECIMAL(10,2) DEFAULT NULL,
                status VARCHAR(50) DEFAULT \'active\',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                total_amount DECIMAL(10,2) NOT NULL,
                status VARCHAR(50) DEFAULT \'pending\',
                remark TEXT DEFAULT NULL,
                member_remark TEXT DEFAULT NULL,
                receiver_name VARCHAR(100) DEFAULT NULL,
                receiver_phone VARCHAR(20) DEFAULT NULL,
                receiver_address VARCHAR(255) DEFAULT NULL,
                linepay_transaction_id VARCHAR(64) DEFAULT NULL,
                payment_method VARCHAR(20) DEFAULT \'\',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                product_id INT NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                quantity INT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS marquee (
                id INT PRIMARY KEY DEFAULT 1,
                content TEXT NOT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip VARCHAR(45) NOT NULL,
                type VARCHAR(20) NOT NULL,
                attempts INT NOT NULL DEFAULT 0,
                locked_until DATETIME NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY uniq_ip_type (ip, type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS cart_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT NOT NULL DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_user_product (user_id, product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS settings (
                setting_key VARCHAR(64) PRIMARY KEY,
                setting_value TEXT,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        ];

        foreach ($statements as $sql) {
            $this->pdo->exec($sql);
        }

        $alterIfMissing = function (string $table, string $column, string $ddl): void {
            if (!$this->hasColumn($table, $column)) {
                $this->pdo->exec($ddl);
            }
        };

        $alterIfMissing('users', 'token', 'ALTER TABLE users ADD COLUMN token VARCHAR(64) DEFAULT NULL');
        $alterIfMissing('users', 'provider', 'ALTER TABLE users ADD COLUMN provider VARCHAR(20) DEFAULT NULL');
        $alterIfMissing('users', 'provider_id', 'ALTER TABLE users ADD COLUMN provider_id VARCHAR(100) DEFAULT NULL');
        $alterIfMissing('users', 'phone', 'ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL');
        $alterIfMissing('users', 'address', 'ALTER TABLE users ADD COLUMN address VARCHAR(255) DEFAULT NULL');
        $alterIfMissing('users', 'avatar', 'ALTER TABLE users ADD COLUMN avatar VARCHAR(500) DEFAULT NULL');
        $alterIfMissing('products', 'category', 'ALTER TABLE products ADD COLUMN category VARCHAR(100) DEFAULT NULL');
        $alterIfMissing('orders', 'remark', 'ALTER TABLE orders ADD COLUMN remark TEXT DEFAULT NULL');
        $alterIfMissing('orders', 'member_remark', 'ALTER TABLE orders ADD COLUMN member_remark TEXT DEFAULT NULL');
        $alterIfMissing('orders', 'receiver_name', 'ALTER TABLE orders ADD COLUMN receiver_name VARCHAR(100) DEFAULT NULL');
        $alterIfMissing('orders', 'receiver_phone', 'ALTER TABLE orders ADD COLUMN receiver_phone VARCHAR(20) DEFAULT NULL');
        $alterIfMissing('orders', 'receiver_address', 'ALTER TABLE orders ADD COLUMN receiver_address VARCHAR(255) DEFAULT NULL');
        $alterIfMissing('orders', 'linepay_transaction_id', 'ALTER TABLE orders ADD COLUMN linepay_transaction_id VARCHAR(64) DEFAULT NULL');
        $alterIfMissing('orders', 'payment_method', 'ALTER TABLE orders ADD COLUMN payment_method VARCHAR(20) DEFAULT \'\'');
        $alterIfMissing('orders', 'table_number', 'ALTER TABLE orders ADD COLUMN table_number INT DEFAULT NULL');
        $alterIfMissing('orders', 'order_type', "ALTER TABLE orders ADD COLUMN order_type VARCHAR(20) DEFAULT 'dine_in'");
        $alterIfMissing('order_items', 'note', 'ALTER TABLE order_items ADD COLUMN note TEXT DEFAULT NULL');
        $alterIfMissing('admin_users', 'token', 'ALTER TABLE admin_users ADD COLUMN token VARCHAR(64) DEFAULT NULL');
        $alterIfMissing('admin_users', 'provider', "ALTER TABLE admin_users ADD COLUMN provider VARCHAR(20) DEFAULT NULL");
        $alterIfMissing('admin_users', 'provider_id', 'ALTER TABLE admin_users ADD COLUMN provider_id VARCHAR(100) DEFAULT NULL');

        $this->ensureProviderIndex();
        $this->seedDefaultAdmin();
        $this->seedDefaultSettings();
        $this->ensureOrdersUserIdNullable();
    }

    private function hasColumn(string $table, string $column): bool {
        $stmt = $this->pdo->query('SHOW COLUMNS FROM `' . $table . '`');
        while ($row = $stmt->fetch()) {
            if ($row['Field'] === $column) {
                return true;
            }
        }
        return false;
    }

    private function ensureProviderIndex(): void {
        $stmt = $this->pdo->query(
            "SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'users' AND index_name = 'idx_provider'"
        );
        if ((int)$stmt->fetchColumn() === 0) {
            $this->pdo->exec('CREATE UNIQUE INDEX idx_provider ON users (provider, provider_id)');
        }
    }

    private function seedDefaultAdmin(): void {
        $count = (int)$this->pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
        if ($count > 0) {
            return;
        }
        $stmt = $this->pdo->prepare('INSERT INTO admin_users (username, password) VALUES (?, ?)');
        $stmt->execute(['admin', password_hash('123456', PASSWORD_DEFAULT)]);
    }

    private function seedDefaultSettings(): void {
        $stmt = $this->pdo->prepare('INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (\'table_count\', \'0\')');
        $stmt->execute();
    }

    private function ensureOrdersUserIdNullable(): void {
        $stmt = $this->pdo->prepare(
            "SELECT IS_NULLABLE FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'orders' AND column_name = 'user_id'"
        );
        $stmt->execute();
        $nullable = $stmt->fetchColumn();
        if ($nullable !== false && strtoupper((string)$nullable) === 'NO') {
            $this->pdo->exec('ALTER TABLE orders MODIFY user_id INT NULL');
        }
    }
}
