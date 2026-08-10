package httpapi

import (
	"encoding/json"
	"net/http"
)

type envelope struct {
	Success bool   `json:"success"`
	Message string `json:"message"`
	Data    any    `json:"data,omitempty"`
}

func writeJSON(w http.ResponseWriter, code int, v any) {
	w.Header().Set("Content-Type", "application/json; charset=utf-8")
	w.WriteHeader(code)
	_ = json.NewEncoder(w).Encode(v)
}

func success(w http.ResponseWriter, data any, message string) {
	writeJSON(w, http.StatusOK, envelope{Success: true, Message: message, Data: data})
}

func fail(w http.ResponseWriter, message string, code int) {
	writeJSON(w, code, envelope{Success: false, Message: message})
}

func decodeJSON(r *http.Request, dst any) error {
	defer r.Body.Close()
	return json.NewDecoder(r.Body).Decode(dst)
}
