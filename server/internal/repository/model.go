package repository

import (
	"database/sql"
)

// Querier 讓 repository 方法可同時接受 *sql.DB 或 *sql.Tx
type Querier interface {
	Exec(query string, args ...any) (sql.Result, error)
	Query(query string, args ...any) (*sql.Rows, error)
	QueryRow(query string, args ...any) *sql.Row
}

type Product struct {
	ID          int      `json:"id"`
	Name        string   `json:"name"`
	Image       string   `json:"image"`
	Description string   `json:"description"`
	Category    string   `json:"category"`
	Price       float64  `json:"price"`
	ListPrice   *float64 `json:"list_price"`
	Stock       int      `json:"stock"`
	ListedStock int      `json:"listed_stock"`
	Status      string   `json:"status"`
	CreatedAt   string   `json:"created_at"`
}

type User struct {
	ID         int    `json:"id"`
	Username   string `json:"username"`
	Email      string `json:"email"`
	Password   string `json:"-"`
	Role       string `json:"role"`
	Token      string `json:"-"`
	Provider   string `json:"provider"`
	ProviderID string `json:"-"`
	Phone      string `json:"phone"`
	Address    string `json:"address"`
	Avatar     string `json:"avatar"`
	CreatedAt  string `json:"created_at"`
}

type AdminUser struct {
	ID       int
	Username string
	Password string
	Token    string
}

type OrderItem struct {
	ID        int     `json:"id"`
	OrderID   int     `json:"order_id"`
	ProductID int     `json:"product_id"`
	Price     float64 `json:"price"`
	Quantity  int     `json:"quantity"`
	Name      string  `json:"name"`
	Image     string  `json:"image"`
}

type Order struct {
	ID              int         `json:"id"`
	UserID          int         `json:"user_id"`
	TotalAmount     float64     `json:"total_amount"`
	Status          string      `json:"status"`
	Remark          string      `json:"remark"`
	MemberRemark    string      `json:"member_remark"`
	ReceiverName    string      `json:"receiver_name"`
	ReceiverPhone   string      `json:"receiver_phone"`
	ReceiverAddress string      `json:"receiver_address"`
	CreatedAt       string      `json:"created_at"`
	Username        string      `json:"username"`
	Email           string      `json:"email"`
	Phone           string      `json:"phone"`
	Address         string      `json:"address"`
	Items           []OrderItem `json:"items,omitempty"`
}
