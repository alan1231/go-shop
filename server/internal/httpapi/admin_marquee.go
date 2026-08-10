package httpapi

import (
	"net/http"
)

func (a *App) adminMarqueeGet(w http.ResponseWriter, r *http.Request) {
	if _, ok := a.requireAdmin(w, r); !ok {
		return
	}
	content, err := a.MarqueeSvc.GetContent()
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	success(w, map[string]any{"content": content}, "ok")
}

func (a *App) adminMarqueeUpdate(w http.ResponseWriter, r *http.Request) {
	if _, ok := a.requireAdmin(w, r); !ok {
		return
	}
	var body struct {
		Content string `json:"content"`
	}
	if err := decodeJSON(r, &body); err != nil {
		fail(w, "請求格式錯誤", http.StatusBadRequest)
		return
	}
	msg, err := a.MarqueeSvc.UpdateContent(body.Content)
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	if msg != "" {
		fail(w, msg, http.StatusBadRequest)
		return
	}
	success(w, nil, "跑馬燈已更新")
}
