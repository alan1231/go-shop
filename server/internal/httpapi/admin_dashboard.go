package httpapi

import (
	"net/http"
)

func (a *App) adminDashboard(w http.ResponseWriter, r *http.Request) {
	if _, ok := a.requireAdmin(w, r); !ok {
		return
	}
	stats, err := a.DashboardSvc.GetStats()
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	success(w, stats, "ok")
}
