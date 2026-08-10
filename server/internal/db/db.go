package db

import (
	"database/sql"
	"fmt"

	_ "github.com/go-sql-driver/mysql"

	"shop/internal/config"
)

func Open(cfg *config.Config) (*sql.DB, error) {
	dsn := fmt.Sprintf("%s:%s@tcp(%s:%s)/%s?charset=utf8mb4&parseTime=false",
		cfg.DBUser, cfg.DBPass, cfg.DBHost, cfg.DBPort, cfg.DBName)
	conn, err := sql.Open("mysql", dsn)
	if err != nil {
		return nil, err
	}
	conn.SetMaxOpenConns(20)
	conn.SetMaxIdleConns(10)
	conn.SetConnMaxLifetime(0)
	if err := conn.Ping(); err != nil {
		return nil, err
	}
	return conn, nil
}

// Migrate 自動建立資料表與補欄位（對應 PHP 各 Repository 的 ensure 邏輯）
func Migrate(conn *sql.DB) error {
	statements := []string{
		`CREATE TABLE IF NOT EXISTS admin_users (
			id INT AUTO_INCREMENT PRIMARY KEY,
			username VARCHAR(100) NOT NULL,
			password VARCHAR(255) NOT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP
		)`,
		`CREATE TABLE IF NOT EXISTS users (
			id INT AUTO_INCREMENT PRIMARY KEY,
			username VARCHAR(100) NOT NULL,
			email VARCHAR(255) NOT NULL,
			password VARCHAR(255) NOT NULL,
			role VARCHAR(50) DEFAULT 'user',
			token VARCHAR(64) DEFAULT NULL,
			provider VARCHAR(20) DEFAULT NULL,
			provider_id VARCHAR(100) DEFAULT NULL,
			phone VARCHAR(20) DEFAULT NULL,
			address VARCHAR(255) DEFAULT NULL,
			avatar VARCHAR(500) DEFAULT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			UNIQUE KEY idx_provider (provider, provider_id)
		)`,
		`CREATE TABLE IF NOT EXISTS products (
			id INT AUTO_INCREMENT PRIMARY KEY,
			name VARCHAR(255) NOT NULL,
			image VARCHAR(255) DEFAULT NULL,
			description TEXT,
			category VARCHAR(100) DEFAULT NULL,
			price DECIMAL(10,2) NOT NULL,
			list_price DECIMAL(10,2) DEFAULT NULL,
			stock INT DEFAULT 0,
			listed_stock INT DEFAULT 0,
			status VARCHAR(50) DEFAULT 'active',
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP
		)`,
		`CREATE TABLE IF NOT EXISTS orders (
			id INT AUTO_INCREMENT PRIMARY KEY,
			user_id INT NOT NULL,
			total_amount DECIMAL(10,2) NOT NULL,
			status VARCHAR(50) DEFAULT 'pending',
			remark TEXT DEFAULT NULL,
			member_remark TEXT DEFAULT NULL,
			receiver_name VARCHAR(100) DEFAULT NULL,
			receiver_phone VARCHAR(20) DEFAULT NULL,
			receiver_address VARCHAR(255) DEFAULT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP
		)`,
		`CREATE TABLE IF NOT EXISTS order_items (
			id INT AUTO_INCREMENT PRIMARY KEY,
			order_id INT NOT NULL,
			product_id INT NOT NULL,
			price DECIMAL(10,2) NOT NULL,
			quantity INT NOT NULL
		)`,
		`CREATE TABLE IF NOT EXISTS marquee (
			id INT PRIMARY KEY DEFAULT 1,
			content TEXT NOT NULL,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
		)`,
		`CREATE TABLE IF NOT EXISTS login_attempts (
			id INT AUTO_INCREMENT PRIMARY KEY,
			ip VARCHAR(45) NOT NULL,
			type VARCHAR(20) NOT NULL,
			attempts INT NOT NULL DEFAULT 0,
			locked_until DATETIME NULL,
			updated_at DATETIME NOT NULL,
			UNIQUE KEY uniq_ip_type (ip, type)
		)`,
	}
	for _, s := range statements {
		if _, err := conn.Exec(s); err != nil {
			return fmt.Errorf("migrate: %v", err)
		}
	}

	// 舊表補欄位
	alterIfMissing := func(table, column, ddl string) error {
		cols, err := columnNames(conn, table)
		if err != nil {
			return err
		}
		for _, c := range cols {
			if c == column {
				return nil
			}
		}
		_, err = conn.Exec(ddl)
		return err
	}

	for _, op := range []struct{ t, c, ddl string }{
		{"users", "token", "ALTER TABLE users ADD COLUMN token VARCHAR(64) DEFAULT NULL"},
		{"users", "provider", "ALTER TABLE users ADD COLUMN provider VARCHAR(20) DEFAULT NULL"},
		{"users", "provider_id", "ALTER TABLE users ADD COLUMN provider_id VARCHAR(100) DEFAULT NULL"},
		{"users", "phone", "ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL"},
		{"users", "address", "ALTER TABLE users ADD COLUMN address VARCHAR(255) DEFAULT NULL"},
		{"users", "avatar", "ALTER TABLE users ADD COLUMN avatar VARCHAR(500) DEFAULT NULL"},
		{"products", "category", "ALTER TABLE products ADD COLUMN category VARCHAR(100) DEFAULT NULL"},
		{"orders", "remark", "ALTER TABLE orders ADD COLUMN remark TEXT DEFAULT NULL"},
		{"orders", "member_remark", "ALTER TABLE orders ADD COLUMN member_remark TEXT DEFAULT NULL"},
		{"orders", "receiver_name", "ALTER TABLE orders ADD COLUMN receiver_name VARCHAR(100) DEFAULT NULL"},
		{"orders", "receiver_phone", "ALTER TABLE orders ADD COLUMN receiver_phone VARCHAR(20) DEFAULT NULL"},
		{"orders", "receiver_address", "ALTER TABLE orders ADD COLUMN receiver_address VARCHAR(255) DEFAULT NULL"},
		{"admin_users", "token", "ALTER TABLE admin_users ADD COLUMN token VARCHAR(64) DEFAULT NULL"},
	} {
		if err := alterIfMissing(op.t, op.c, op.ddl); err != nil {
			return fmt.Errorf("migrate %s.%s: %v", op.t, op.c, err)
		}
	}

	// users idx_provider
	if err := ensureProviderIndex(conn); err != nil {
		return err
	}
	return nil
}

func columnNames(conn *sql.DB, table string) ([]string, error) {
	rows, err := conn.Query("SHOW COLUMNS FROM " + table)
	if err != nil {
		return nil, err
	}
	defer rows.Close()
	type col struct {
		Field string
	}
	var names []string
	for rows.Next() {
		var c col
		if err := rows.Scan(&c.Field, new(any), new(any), new(any), new(sql.NullString), new(any)); err != nil {
			return nil, err
		}
		names = append(names, c.Field)
	}
	return names, rows.Err()
}

func ensureProviderIndex(conn *sql.DB) error {
	var n int
	err := conn.QueryRow(
		"SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'users' AND index_name = 'idx_provider'",
	).Scan(&n)
	if err != nil {
		return err
	}
	if n > 0 {
		return nil
	}
	_, err = conn.Exec("CREATE UNIQUE INDEX idx_provider ON users (provider, provider_id)")
	return err
}
