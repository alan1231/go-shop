package httpapi

import (
	"net/http"
	"strings"

	"shop/internal/service"
)

func (a *App) ordersCreate(w http.ResponseWriter, r *http.Request) {
	user, ok := a.requireUser(w, r)
	if !ok {
		return
	}
	var body struct {
		Items    []service.OrderItemInput `json:"items"`
		Receiver struct {
			Name    string `json:"name"`
			Phone   string `json:"phone"`
			Address string `json:"address"`
		} `json:"receiver"`
		Remark string `json:"remark"`
	}
	if err := decodeJSON(r, &body); err != nil {
		fail(w, "請求格式錯誤", http.StatusBadRequest)
		return
	}
	receiver := service.ReceiverInput{
		Name:    strings.TrimSpace(body.Receiver.Name),
		Phone:   strings.TrimSpace(body.Receiver.Phone),
		Address: strings.TrimSpace(body.Receiver.Address),
	}
	orderID, msg, err := a.OrderSvc.CreateOrder(user.ID, body.Items, receiver, strings.TrimSpace(body.Remark))
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	if msg != "" {
		fail(w, msg, http.StatusBadRequest)
		return
	}
	success(w, map[string]any{"order_id": orderID}, "訂單已建立")
}

func (a *App) ordersIndex(w http.ResponseWriter, r *http.Request) {
	user, ok := a.requireUser(w, r)
	if !ok {
		return
	}
	status := r.URL.Query().Get("status")
	orders, err := a.OrderSvc.GetUserOrders(user.ID, status)
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	success(w, orders, "ok")
}

func (a *App) ordersShow(w http.ResponseWriter, r *http.Request) {
	user, ok := a.requireUser(w, r)
	if !ok {
		return
	}
	id := pathInt(r)
	order, err := a.OrderSvc.GetWithItems(id)
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	if order == nil || order.UserID != user.ID {
		fail(w, "訂單不存在", http.StatusNotFound)
		return
	}
	success(w, order, "ok")
}

func (a *App) ordersPay(w http.ResponseWriter, r *http.Request) {
	user, ok := a.requireUser(w, r)
	if !ok {
		return
	}
	id := pathInt(r)
	order, err := a.OrderSvc.GetWithItems(id)
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	if order == nil || order.UserID != user.ID {
		fail(w, "訂單不存在", http.StatusNotFound)
		return
	}
	if order.Status != "pending" {
		fail(w, "此訂單無法付款", http.StatusBadRequest)
		return
	}
	if err := a.OrderSvc.UpdateStatus(id, "paid"); err != nil {
		fail(w, err.Error(), http.StatusBadRequest)
		return
	}
	success(w, nil, "付款成功")
}
