package httpapi

import (
	"net/http"
)

func (a *App) marqueeIndex(w http.ResponseWriter, r *http.Request) {
	content, err := a.MarqueeSvc.GetContent()
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	success(w, map[string]any{"content": content}, "ok")
}
