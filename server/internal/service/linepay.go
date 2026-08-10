package service

import (
	"bytes"
	"encoding/json"
	"net/http"
	"strings"
)

type LinePayService struct {
	ChannelID     string
	ChannelSecret string
	Host          string
	HTTP          *http.Client
}

func NewLinePayService(channelID, channelSecret, sandbox string) *LinePayService {
	host := "https://api-pay.line.me"
	if !strings.EqualFold(sandbox, "false") {
		host = "https://sandbox-api-pay.line.me"
	}
	return &LinePayService{ChannelID: channelID, ChannelSecret: channelSecret, Host: host, HTTP: &http.Client{Timeout: 15}}
}

func (s *LinePayService) IsConfigured() bool {
	return s.ChannelID != "" && s.ChannelSecret != ""
}

func (s *LinePayService) Request(order map[string]any, confirmURL, cancelURL string) (map[string]any, error) {
	body := map[string]any{
		"amount":   order["amount"],
		"currency": "TWD",
		"orderId":  order["orderId"],
		"packages": []map[string]any{{
			"id":       "1",
			"amount":   order["amount"],
			"name":     order["packageName"],
			"products": order["products"],
		}},
		"redirectUrls": map[string]string{
			"confirmUrl": confirmURL,
			"cancelUrl":  cancelURL,
		},
	}
	return s.post("/v3/payments/request", body)
}

func (s *LinePayService) Confirm(transactionID string, amount int) (map[string]any, error) {
	return s.post("/v3/payments/"+transactionID+"/confirm", map[string]any{
		"amount":   amount,
		"currency": "TWD",
	})
}

func (s *LinePayService) Refund(transactionID string, amount int) (map[string]any, error) {
	return s.post("/v3/payments/"+transactionID+"/refund", map[string]any{
		"refundAmount": amount,
	})
}

func (s *LinePayService) post(path string, body any) (map[string]any, error) {
	data, _ := json.Marshal(body)
	req, _ := http.NewRequest("POST", s.Host+path, bytes.NewReader(data))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-LINE-ChannelId", s.ChannelID)
	req.Header.Set("X-LINE-ChannelSecret", s.ChannelSecret)
	resp, err := s.HTTP.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()
	var m map[string]any
	_ = json.NewDecoder(resp.Body).Decode(&m)
	if m == nil {
		m = map[string]any{}
	}
	m["http_code"] = resp.StatusCode
	return m, nil
}
