package httpapi

import (
	"net/http"
	"strings"

	"shop/internal/repository"
)

func adminPayload(admin *repository.AdminUser) map[string]any {
	return map[string]any{"id": admin.ID, "username": admin.Username}
}

func (a *App) adminLogin(w http.ResponseWriter, r *http.Request) {
	var body struct {
		Username string `json:"username"`
		Password string `json:"password"`
	}
	if err := decodeJSON(r, &body); err != nil {
		fail(w, "請求格式錯誤", http.StatusBadRequest)
		return
	}
	admin, err := a.AuthSvc.Authenticate(strings.TrimSpace(body.Username), body.Password)
	if err != nil {
		fail(w, err.Error(), http.StatusBadRequest)
		return
	}
	token, err := a.AuthSvc.Login(admin)
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	success(w, map[string]any{"token": token, "user": adminPayload(admin)}, "登入成功")
}

func (a *App) adminMe(w http.ResponseWriter, r *http.Request) {
	admin, ok := a.requireAdmin(w, r)
	if !ok {
		return
	}
	success(w, adminPayload(admin), "ok")
}

func (a *App) adminLogout(w http.ResponseWriter, r *http.Request) {
	admin, ok := a.requireAdmin(w, r)
	if !ok {
		return
	}
	if err := a.AuthSvc.Logout(admin.ID); err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	success(w, nil, "已登出")
}
