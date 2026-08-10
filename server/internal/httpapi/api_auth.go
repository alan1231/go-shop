package httpapi

import (
	"bytes"
	"fmt"
	"net/http"
	"strings"

	"golang.org/x/crypto/bcrypt"

	"shop/internal/service"
)

func (a *App) rateLimited(w http.ResponseWriter, r *http.Request, typ string) bool {
	allowed, retry, err := a.RateLimitSvc.Check(clientIP(r), typ)
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return true
	}
	if !allowed {
		fail(w, fmt.Sprintf("嘗試次數過多，請 %d 分鐘後再試", (retry+59)/60), http.StatusTooManyRequests)
		return true
	}
	return false
}

func (a *App) authRegister(w http.ResponseWriter, r *http.Request) {
	if a.rateLimited(w, r, "register") {
		return
	}
	var body struct {
		Username string `json:"username"`
		Email    string `json:"email"`
		Password string `json:"password"`
	}
	if err := decodeJSON(r, &body); err != nil {
		fail(w, "請求格式錯誤", http.StatusBadRequest)
		return
	}
	msg, err := a.UserSvc.Register(strings.TrimSpace(body.Username), strings.TrimSpace(body.Email), body.Password)
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	if msg != "" {
		_ = a.RateLimitSvc.RecordFail(clientIP(r), "register")
		fail(w, msg, http.StatusBadRequest)
		return
	}
	_ = a.RateLimitSvc.Clear(clientIP(r), "register")
	success(w, nil, "註冊成功")
}

func (a *App) authLogin(w http.ResponseWriter, r *http.Request) {
	if a.rateLimited(w, r, "login") {
		return
	}
	var body struct {
		Username string `json:"username"`
		Password string `json:"password"`
	}
	if err := decodeJSON(r, &body); err != nil {
		fail(w, "請求格式錯誤", http.StatusBadRequest)
		return
	}
	user, err := a.UserRepo.FindForAuth(strings.TrimSpace(body.Username))
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	if user == nil || bcrypt.CompareHashAndPassword([]byte(user.Password), []byte(body.Password)) != nil {
		_ = a.RateLimitSvc.RecordFail(clientIP(r), "login")
		fail(w, "帳號或密碼錯誤", http.StatusBadRequest)
		return
	}
	_ = a.RateLimitSvc.Clear(clientIP(r), "login")
	token := service.RandomToken()
	if err := a.UserRepo.SetToken(user.ID, token); err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	success(w, map[string]any{"token": token, "user": userPayload(user)}, "登入成功")
}

func (a *App) authOAuth(w http.ResponseWriter, r *http.Request) {
	var body struct {
		Provider string `json:"provider"`
		Code     string `json:"code"`
	}
	if err := decodeJSON(r, &body); err != nil {
		fail(w, "請求格式錯誤", http.StatusBadRequest)
		return
	}
	provider := strings.ToLower(strings.TrimSpace(body.Provider))
	code := strings.TrimSpace(body.Code)
	if (provider != "google" && provider != "line") || code == "" {
		fail(w, "無效的三方登入請求", http.StatusBadRequest)
		return
	}
	info, err := a.OAuthSvc.GetUserInfo(provider, code)
	if err != nil || info == nil {
		fail(w, "三方登入驗證失敗", http.StatusUnauthorized)
		return
	}

	avatar := a.saveAvatar(info.Avatar)

	user, err := a.UserRepo.FindByProvider(provider, info.ProviderID)
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	if user == nil {
		name := info.Name
		if name == "" {
			name = info.Email
		}
		if name == "" {
			name = provider + "_" + last6(info.ProviderID)
		}
		existing, err := a.UserRepo.FindByEmail(info.Email)
		if err != nil {
			fail(w, "伺服器錯誤", http.StatusInternalServerError)
			return
		}
		if existing != nil {
			if err := a.UserRepo.SetProvider(existing.ID, provider, info.ProviderID); err != nil {
				fail(w, "伺服器錯誤", http.StatusInternalServerError)
				return
			}
			if avatar != "" {
				_ = a.UserRepo.UpdateAvatar(existing.ID, avatar)
			}
			user = existing
		} else {
			id, err := a.UserRepo.CreateOAuthUser(name, info.Email, provider, info.ProviderID, avatar)
			if err != nil {
				fail(w, "伺服器錯誤", http.StatusInternalServerError)
				return
			}
			user, err = a.UserRepo.FindByID(id)
			if err != nil {
				fail(w, "伺服器錯誤", http.StatusInternalServerError)
				return
			}
		}
	} else if avatar != "" && user.Avatar != avatar {
		_ = a.UserRepo.UpdateAvatar(user.ID, avatar)
		user.Avatar = avatar
	}

	token := service.RandomToken()
	if err := a.UserRepo.SetToken(user.ID, token); err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	success(w, map[string]any{"token": token, "user": userPayload(user)}, "登入成功")
}

func (a *App) authLogout(w http.ResponseWriter, r *http.Request) {
	user, ok := a.requireUser(w, r)
	if !ok {
		return
	}
	if err := a.UserRepo.SetToken(user.ID, ""); err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	success(w, nil, "已登出")
}

func (a *App) authMe(w http.ResponseWriter, r *http.Request) {
	user, ok := a.requireUser(w, r)
	if !ok {
		return
	}
	success(w, userPayload(user), "ok")
}

func (a *App) authUpdateContact(w http.ResponseWriter, r *http.Request) {
	user, ok := a.requireUser(w, r)
	if !ok {
		return
	}
	var body struct {
		Phone   string `json:"phone"`
		Address string `json:"address"`
	}
	if err := decodeJSON(r, &body); err != nil {
		fail(w, "請求格式錯誤", http.StatusBadRequest)
		return
	}
	if err := a.UserSvc.UpdateContact(user.ID, body.Phone, body.Address); err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	success(w, nil, "聯絡資料已更新")
}

func (a *App) authChangePassword(w http.ResponseWriter, r *http.Request) {
	user, ok := a.requireUser(w, r)
	if !ok {
		return
	}
	var body struct {
		OldPassword string `json:"old_password"`
		NewPassword string `json:"new_password"`
	}
	if err := decodeJSON(r, &body); err != nil {
		fail(w, "請求格式錯誤", http.StatusBadRequest)
		return
	}
	msg, err := a.UserSvc.ChangePassword(user.ID, body.OldPassword, body.NewPassword)
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	if msg != "" {
		fail(w, msg, http.StatusBadRequest)
		return
	}
	success(w, nil, "密碼已更新")
}

func (a *App) saveAvatar(avatarURL string) string {
	if avatarURL == "" {
		return ""
	}
	if !strings.HasPrefix(avatarURL, "http://") && !strings.HasPrefix(avatarURL, "https://") {
		return ""
	}
	data, ext, err := a.OAuthSvc.FetchAvatar(avatarURL)
	if err != nil || len(data) == 0 {
		return ""
	}
	name, err := a.Images.Save(bytes.NewReader(data), "avatar."+ext)
	if err != nil {
		return ""
	}
	return name
}

func last6(s string) string {
	if len(s) <= 6 {
		return s
	}
	return s[len(s)-6:]
}
