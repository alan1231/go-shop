package config

import (
	"os"
	"path/filepath"

	"github.com/joho/godotenv"
)

type Config struct {
	Port       string
	DBHost     string
	DBPort     string
	DBName     string
	DBUser     string
	DBPass     string
	UploadsDir string
	PublicDist string
	AdminDist  string

	GoogleClientID     string
	GoogleClientSecret string
	LineChannelID      string
	LineChannelSecret  string
	OAuthRedirectURI   string

	LinePayChannelID     string
	LinePayChannelSecret string
	LinePaySandbox       string
}

func Load() *Config {
	_ = godotenv.Load()
	_ = godotenv.Load("../.env")
	root, _ := os.Getwd()
	return &Config{
		Port:       envOr("PORT", "8080"),
		DBHost:     envOr("DB_HOST", "127.0.0.1"),
		DBPort:     envOr("DB_PORT", "3306"),
		DBName:     envOr("DB_NAME", "shop"),
		DBUser:     envOr("DB_USER", "root"),
		DBPass:     os.Getenv("DB_PASS"),
		UploadsDir: envOr("UPLOADS_DIR", filepath.Join(root, "..", "uploads")),
		PublicDist: envOr("PUBLIC_DIST", filepath.Join(root, "..", "frontend", "dist")),
		AdminDist:  envOr("ADMIN_DIST", filepath.Join(root, "..", "frontend-admin", "dist")),

		GoogleClientID:     os.Getenv("GOOGLE_CLIENT_ID"),
		GoogleClientSecret: os.Getenv("GOOGLE_CLIENT_SECRET"),
		LineChannelID:      os.Getenv("LINE_CHANNEL_ID"),
		LineChannelSecret:  os.Getenv("LINE_CHANNEL_SECRET"),
		OAuthRedirectURI:   envOr("OAUTH_REDIRECT_URI", "http://localhost:5173/auth/callback"),

		LinePayChannelID:     os.Getenv("LINE_PAY_CHANNEL_ID"),
		LinePayChannelSecret: os.Getenv("LINE_PAY_CHANNEL_SECRET"),
		LinePaySandbox:       envOr("LINE_PAY_SANDBOX", "true"),
	}
}

func envOr(key, def string) string {
	if v := os.Getenv(key); v != "" {
		return v
	}
	return def
}
