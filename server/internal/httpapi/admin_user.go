package httpapi

import (
	"net/http"

	"shop/internal/repository"
)

func adminUserPayload(u repository.User) map[string]any {
	return map[string]any{
		"id":         u.ID,
		"username":   u.Username,
		"email":      u.Email,
		"provider":   nilIfEmptyStr(u.Provider),
		"phone":      nilIfEmptyStr(u.Phone),
		"address":    nilIfEmptyStr(u.Address),
		"avatar":     avatarURL(u.Avatar),
		"created_at": u.CreatedAt,
	}
}

func (a *App) adminUsersIndex(w http.ResponseWriter, r *http.Request) {
	if _, ok := a.requireAdmin(w, r); !ok {
		return
	}
	q := r.URL.Query().Get("q")
	users, err := a.UserSvc.GetAllMembers(q)
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	items := make([]map[string]any, 0, len(users))
	for _, u := range users {
		items = append(items, adminUserPayload(u))
	}
	success(w, items, "ok")
}

func (a *App) adminUsersCreate(w http.ResponseWriter, r *http.Request) {
	if _, ok := a.requireAdmin(w, r); !ok {
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
	msg, err := a.UserSvc.CreateMember(body.Username, body.Email, body.Password)
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	if msg != "" {
		fail(w, msg, http.StatusBadRequest)
		return
	}
	success(w, nil, "會員新增成功")
}

func (a *App) adminUsersShow(w http.ResponseWriter, r *http.Request) {
	if _, ok := a.requireAdmin(w, r); !ok {
		return
	}
	id := pathInt(r)
	u, err := a.UserSvc.GetByID(id)
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	if u == nil {
		fail(w, "會員不存在", http.StatusNotFound)
		return
	}
	success(w, adminUserPayload(*u), "ok")
}

func (a *App) adminUsersUpdatePassword(w http.ResponseWriter, r *http.Request) {
	if _, ok := a.requireAdmin(w, r); !ok {
		return
	}
	id := pathInt(r)
	var body struct {
		Password string `json:"password"`
	}
	if err := decodeJSON(r, &body); err != nil {
		fail(w, "請求格式錯誤", http.StatusBadRequest)
		return
	}
	u, err := a.UserSvc.GetByID(id)
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	if u == nil {
		fail(w, "會員不存在", http.StatusNotFound)
		return
	}
	if u.Provider != "" {
		fail(w, "此會員為三方登入，無密碼可修改", http.StatusBadRequest)
		return
	}
	msg, err := a.UserSvc.UpdatePassword(id, body.Password)
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

func (a *App) adminUsersDelete(w http.ResponseWriter, r *http.Request) {
	if _, ok := a.requireAdmin(w, r); !ok {
		return
	}
	id := pathInt(r)
	if err := a.UserSvc.CanDelete(id); err != nil {
		fail(w, err.Error(), http.StatusBadRequest)
		return
	}
	if err := a.UserSvc.Delete(id); err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	success(w, nil, "會員已刪除")
}
