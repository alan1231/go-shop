package main

import (
	"database/sql"
	"log"
	"net/http"
	"os"

	"golang.org/x/crypto/bcrypt"

	"shop/internal/config"
	"shop/internal/db"
	"shop/internal/httpapi"
)

func main() {
	cfg := config.Load()

	conn, err := db.Open(cfg)
	if err != nil {
		log.Fatalf("資料庫連線失敗: %v", err)
	}
	defer conn.Close()

	if err := db.Migrate(conn); err != nil {
		log.Fatalf("資料庫遷移失敗: %v", err)
	}

	if err := seedDefaultAdmin(conn); err != nil {
		log.Fatalf("預設管理員建立失敗: %v", err)
	}

	if err := os.MkdirAll(cfg.UploadsDir, 0o755); err != nil {
		log.Fatalf("建立上傳目錄失敗: %v", err)
	}

	app := httpapi.New(cfg, conn)

	addr := ":" + cfg.Port
	log.Printf("SHOP server listening on http://localhost%s", addr)
	if err := http.ListenAndServe(addr, httpapi.WithRecover(app.Handler())); err != nil {
		log.Fatal(err)
	}
}

func seedDefaultAdmin(conn *sql.DB) error {
	var count int
	if err := conn.QueryRow("SELECT COUNT(*) FROM admin_users").Scan(&count); err != nil {
		return err
	}
	if count > 0 {
		return nil
	}
	hash, err := bcrypt.GenerateFromPassword([]byte("123456"), bcrypt.DefaultCost)
	if err != nil {
		return err
	}
	_, err = conn.Exec("INSERT INTO admin_users (username, password) VALUES (?, ?)", "admin", string(hash))
	return err
}
