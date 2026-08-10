package httpapi

import (
	"net/http"
)

func (a *App) adminOrdersIndex(w http.ResponseWriter, r *http.Request) {
	if _, ok := a.requireAdmin(w, r); !ok {
		return
	}
	status := r.URL.Query().Get("status")
	page := max(1, queryInt(r, "page", 1))
	perPage := 10

	paged, err := a.OrderSvc.GetAll(status, page, perPage)
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	success(w, paged, "ok")
}

func (a *App) adminOrdersShow(w http.ResponseWriter, r *http.Request) {
	if _, ok := a.requireAdmin(w, r); !ok {
		return
	}
	id := pathInt(r)
	order, err := a.OrderSvc.GetWithItems(id)
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	if order == nil {
		fail(w, "訂單不存在", http.StatusNotFound)
		return
	}
	success(w, order, "ok")
}

func (a *App) adminOrdersStatus(w http.ResponseWriter, r *http.Request) {
	if _, ok := a.requireAdmin(w, r); !ok {
		return
	}
	id := pathInt(r)
	var body struct {
		Status     string `json:"status"`
		BackStatus string `json:"back_status"`
	}
	if err := decodeJSON(r, &body); err != nil {
		fail(w, "請求格式錯誤", http.StatusBadRequest)
		return
	}
	if err := a.OrderSvc.UpdateStatus(id, body.Status); err != nil {
		fail(w, err.Error(), http.StatusBadRequest)
		return
	}
	success(w, nil, "訂單狀態已更新")
}

func (a *App) adminOrdersRemark(w http.ResponseWriter, r *http.Request) {
	if _, ok := a.requireAdmin(w, r); !ok {
		return
	}
	id := pathInt(r)
	var body struct {
		Remark     string `json:"remark"`
		BackStatus string `json:"back_status"`
	}
	if err := decodeJSON(r, &body); err != nil {
		fail(w, "請求格式錯誤", http.StatusBadRequest)
		return
	}
	if err := a.OrderSvc.UpdateRemark(id, body.Remark); err != nil {
		fail(w, err.Error(), http.StatusBadRequest)
		return
	}
	success(w, nil, "備註已更新")
}
