package httpapi

import (
	"context"
	"fmt"
	"net"
	"net/http"
	"strings"

	"shop/internal/repository"
)

type ctxKey string

const adminKey ctxKey = "admin"

func bearerToken(r *http.Request) string {
	auth := r.Header.Get("Authorization")
	token := strings.TrimPrefix(auth, "Bearer ")
	if token == auth {
		return ""
	}
	return strings.TrimSpace(token)
}

func clientIP(r *http.Request) string {
	host, _, err := net.SplitHostPort(r.RemoteAddr)
	if err != nil {
		return r.RemoteAddr
	}
	return host
}

// requireUser 公開 API 的登入檢查（對應前台 users.token）
func (a *App) requireUser(w http.ResponseWriter, r *http.Request) (*repository.User, bool) {
	token := bearerToken(r)
	if token == "" {
		fail(w, "請先登入", http.StatusUnauthorized)
		return nil, false
	}
	user, err := a.UserRepo.FindByToken(token)
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return nil, false
	}
	if user == nil {
		fail(w, "請先登入", http.StatusUnauthorized)
		return nil, false
	}
	return user, true
}

// requireAdmin 後台 API 的登入檢查（對應 admin_users.token）
func (a *App) requireAdmin(w http.ResponseWriter, r *http.Request) (*repository.AdminUser, bool) {
	token := bearerToken(r)
	if token == "" {
		fail(w, "請先登入", http.StatusUnauthorized)
		return nil, false
	}
	admin, err := a.AuthSvc.FindByToken(token)
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return nil, false
	}
	if admin == nil {
		fail(w, "請先登入", http.StatusUnauthorized)
		return nil, false
	}
	return admin, true
}

func adminFromContext(ctx context.Context) *repository.AdminUser {
	admin, _ := ctx.Value(adminKey).(*repository.AdminUser)
	return admin
}

func uploadURL(filename string) string {
	if filename == "" {
		return ""
	}
	return "/uploads/" + filename
}

func avatarURL(avatar string) string {
	if avatar == "" {
		return ""
	}
	if strings.HasPrefix(avatar, "http://") || strings.HasPrefix(avatar, "https://") {
		return avatar
	}
	return "/uploads/" + avatar
}

func userPayload(u *repository.User) map[string]any {
	return map[string]any{
		"id":         u.ID,
		"username":   u.Username,
		"email":      u.Email,
		"provider":   nilIfEmptyStr(u.Provider),
		"created_at": u.CreatedAt,
		"phone":      nilIfEmptyStr(u.Phone),
		"address":    nilIfEmptyStr(u.Address),
		"avatar":     avatarURL(u.Avatar),
	}
}

func nilIfEmptyStr(s string) any {
	if s == "" {
		return nil
	}
	return s
}

func queryInt(r *http.Request, key string, def int) int {
	v := r.URL.Query().Get(key)
	if v == "" {
		return def
	}
	n := 0
	_, _ = fmt.Sscanf(v, "%d", &n)
	return n
}

func pathInt(r *http.Request) int {
	n := 0
	_, _ = fmt.Sscanf(r.PathValue("id"), "%d", &n)
	return n
}

func parseFloat(s string) float64 {
	var f float64
	_, _ = fmt.Sscanf(strings.TrimSpace(s), "%f", &f)
	return f
}

func parseInt(s string) int {
	var n int
	_, _ = fmt.Sscanf(strings.TrimSpace(s), "%d", &n)
	return n
}
