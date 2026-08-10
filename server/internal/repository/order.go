package repository

import (
	"database/sql"
)

type DailyStat struct {
	Day     string  `json:"day"`
	Orders  int     `json:"orders"`
	Revenue float64 `json:"revenue"`
}

type TopProduct struct {
	Name    string  `json:"name"`
	Sold    int     `json:"sold"`
	Revenue float64 `json:"revenue"`
}

type OrderRepository struct {
	DB Querier
}

func NewOrderRepository(db Querier) *OrderRepository {
	return &OrderRepository{DB: db}
}

const orderCols = "o.id, o.user_id, o.total_amount, o.status, o.remark, o.member_remark, o.receiver_name, o.receiver_phone, o.receiver_address, o.created_at"

func scanOrderBasic(scan func(dest ...any) error) (Order, error) {
	var o Order
	var remark, mremark, rname, rphone, raddress sql.NullString
	err := scan(&o.ID, &o.UserID, &o.TotalAmount, &o.Status, &remark, &mremark, &rname, &rphone, &raddress, &o.CreatedAt)
	if err != nil {
		return Order{}, err
	}
	o.Remark = nstr(remark)
	o.MemberRemark = nstr(mremark)
	o.ReceiverName = nstr(rname)
	o.ReceiverPhone = nstr(rphone)
	o.ReceiverAddress = nstr(raddress)
	return o, nil
}

// scanOrderJoined 單次 Scan：order 欄位 + username（sql.Row / sql.Rows 皆只能掃一次）
func scanOrderJoined(scan func(dest ...any) error) (Order, error) {
	var o Order
	var remark, mremark, rname, rphone, raddress, username sql.NullString
	err := scan(&o.ID, &o.UserID, &o.TotalAmount, &o.Status, &remark, &mremark, &rname, &rphone, &raddress, &o.CreatedAt, &username)
	if err != nil {
		return Order{}, err
	}
	o.Remark = nstr(remark)
	o.MemberRemark = nstr(mremark)
	o.ReceiverName = nstr(rname)
	o.ReceiverPhone = nstr(rphone)
	o.ReceiverAddress = nstr(raddress)
	o.Username = nstr(username)
	return o, nil
}

// scanOrderFull 單次 Scan：order 欄位 + username + email + phone + address
func scanOrderFull(scan func(dest ...any) error) (Order, error) {
	var o Order
	var remark, mremark, rname, rphone, raddress, username, email, phone, address sql.NullString
	err := scan(&o.ID, &o.UserID, &o.TotalAmount, &o.Status, &remark, &mremark, &rname, &rphone, &raddress, &o.CreatedAt, &username, &email, &phone, &address)
	if err != nil {
		return Order{}, err
	}
	o.Remark = nstr(remark)
	o.MemberRemark = nstr(mremark)
	o.ReceiverName = nstr(rname)
	o.ReceiverPhone = nstr(rphone)
	o.ReceiverAddress = nstr(raddress)
	o.Username = nstr(username)
	o.Email = nstr(email)
	o.Phone = nstr(phone)
	o.Address = nstr(address)
	return o, nil
}

func (r *OrderRepository) FindAll(status string, limit, offset int) ([]Order, error) {
	query := "SELECT " + orderCols + ", u.username FROM orders o JOIN users u ON o.user_id = u.id"
	var args []any
	if status != "" && ValidStatus(status) {
		query += " WHERE o.status = ?"
		args = append(args, status)
	}
	query += " ORDER BY o.created_at DESC LIMIT ? OFFSET ?"
	args = append(args, limit, offset)
	rows, err := r.DB.Query(query, args...)
	if err != nil {
		return nil, err
	}
	defer rows.Close()
	var orders []Order
	for rows.Next() {
		o, err := scanOrderJoined(rows.Scan)
		if err != nil {
			return nil, err
		}
		orders = append(orders, o)
	}
	return orders, rows.Err()
}

func (r *OrderRepository) CountFindAll(status string) (int, error) {
	query := "SELECT COUNT(*) FROM orders"
	var args []any
	if status != "" && ValidStatus(status) {
		query += " WHERE status = ?"
		args = append(args, status)
	}
	var n int
	err := r.DB.QueryRow(query, args...).Scan(&n)
	return n, err
}

func (r *OrderRepository) FindByID(id int) (*Order, error) {
	row := r.DB.QueryRow("SELECT "+orderCols+", u.username, u.email, u.phone, u.address FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?", id)
	o, err := scanOrderFull(row.Scan)
	if err == sql.ErrNoRows {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &o, nil
}

func (r *OrderRepository) GetItems(orderID int) ([]OrderItem, error) {
	rows, err := r.DB.Query(
		"SELECT oi.*, p.name, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?", orderID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()
	var items []OrderItem
	for rows.Next() {
		var it OrderItem
		var img sql.NullString
		if err := rows.Scan(&it.ID, &it.OrderID, &it.ProductID, &it.Price, &it.Quantity, &it.Name, &img); err != nil {
			return nil, err
		}
		it.Image = nstr(img)
		items = append(items, it)
	}
	return items, rows.Err()
}

func (r *OrderRepository) UpdateStatus(id int, status string) error {
	_, err := r.DB.Exec("UPDATE orders SET status = ? WHERE id = ?", status, id)
	return err
}

func (r *OrderRepository) UpdateRemark(id int, remark string) error {
	_, err := r.DB.Exec("UPDATE orders SET remark = ? WHERE id = ?", remark, id)
	return err
}

func (r *OrderRepository) Count() (int, error) {
	var n int
	err := r.DB.QueryRow("SELECT COUNT(*) FROM orders").Scan(&n)
	return n, err
}

func (r *OrderRepository) CountByStatus() (map[string]int, error) {
	rows, err := r.DB.Query("SELECT status, COUNT(*) FROM orders GROUP BY status")
	if err != nil {
		return nil, err
	}
	defer rows.Close()
	result := map[string]int{"pending": 0, "paid": 0, "shipped": 0, "completed": 0, "cancelled": 0}
	for rows.Next() {
		var status string
		var cnt int
		if err := rows.Scan(&status, &cnt); err != nil {
			return nil, err
		}
		if _, ok := result[status]; ok {
			result[status] = cnt
		}
	}
	return result, rows.Err()
}

func (r *OrderRepository) GetDailyStats(days int) ([]DailyStat, error) {
	rows, err := r.DB.Query(
		"SELECT DATE(created_at) AS day, COUNT(*) AS orders, SUM(total_amount) AS revenue FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY) AND status IN ('paid', 'shipped', 'completed') GROUP BY DATE(created_at)",
		days-1)
	if err != nil {
		return nil, err
	}
	defer rows.Close()
	byDay := map[string]DailyStat{}
	for rows.Next() {
		var day string
		var orders int
		var revenue sql.NullFloat64
		if err := rows.Scan(&day, &orders, &revenue); err != nil {
			return nil, err
		}
		rev := 0.0
		if revenue.Valid {
			rev = revenue.Float64
		}
		byDay[day] = DailyStat{Day: day, Orders: orders, Revenue: rev}
	}
	var result []DailyStat
	for i := days - 1; i >= 0; i-- {
		day := timeNow().AddDate(0, 0, -i).Format("2006-01-02")
		s, ok := byDay[day]
		if !ok {
			s = DailyStat{}
		}
		result = append(result, DailyStat{
			Day:     day[5:],
			Orders:  s.Orders,
			Revenue: s.Revenue,
		})
	}
	return result, rows.Err()
}

func (r *OrderRepository) GetTopProducts(limit int) ([]TopProduct, error) {
	rows, err := r.DB.Query(
		"SELECT p.name, SUM(oi.quantity) AS sold, SUM(oi.quantity * oi.price) AS revenue FROM order_items oi JOIN products p ON oi.product_id = p.id GROUP BY oi.product_id, p.name ORDER BY sold DESC LIMIT ?", limit)
	if err != nil {
		return nil, err
	}
	defer rows.Close()
	var items []TopProduct
	for rows.Next() {
		var t TopProduct
		if err := rows.Scan(&t.Name, &t.Sold, &t.Revenue); err != nil {
			return nil, err
		}
		items = append(items, t)
	}
	return items, rows.Err()
}

func (r *OrderRepository) GetCompletedRevenue() (float64, error) {
	var v float64
	err := r.DB.QueryRow("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'completed'").Scan(&v)
	return v, err
}

func (r *OrderRepository) FindByUserID(userID int, status string) ([]Order, error) {
	query := "SELECT " + orderCols + " FROM orders o WHERE o.user_id = ?"
	var args []any
	args = append(args, userID)
	if status != "" && ValidStatus(status) {
		query += " AND o.status = ?"
		args = append(args, status)
	}
	query += " ORDER BY o.created_at DESC"
	rows, err := r.DB.Query(query, args...)
	if err != nil {
		return nil, err
	}
	defer rows.Close()
	var orders []Order
	for rows.Next() {
		o, err := scanOrderBasic(rows.Scan)
		if err != nil {
			return nil, err
		}
		orders = append(orders, o)
	}
	return orders, rows.Err()
}

func (r *OrderRepository) CreateOrder(userID int, total float64, receiverName, receiverPhone, receiverAddress, memberRemark string) (int, error) {
	res, err := r.DB.Exec(
		"INSERT INTO orders (user_id, total_amount, status, receiver_name, receiver_phone, receiver_address, member_remark) VALUES (?, ?, 'pending', ?, ?, ?, ?)",
		userID, total, nilIfEmpty(receiverName), nilIfEmpty(receiverPhone), nilIfEmpty(receiverAddress), nilIfEmpty(memberRemark),
	)
	if err != nil {
		return 0, err
	}
	id, err := res.LastInsertId()
	return int(id), err
}

func (r *OrderRepository) CreateItem(orderID, productID int, price float64, qty int) error {
	_, err := r.DB.Exec("INSERT INTO order_items (order_id, product_id, price, quantity) VALUES (?, ?, ?, ?)", orderID, productID, price, qty)
	return err
}

func (r *OrderRepository) GetRecent(limit int) ([]Order, error) {
	rows, err := r.DB.Query(
		"SELECT "+orderCols+", u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT ?", limit)
	if err != nil {
		return nil, err
	}
	defer rows.Close()
	var orders []Order
	for rows.Next() {
		o, err := scanOrderJoined(rows.Scan)
		if err != nil {
			return nil, err
		}
		orders = append(orders, o)
	}
	return orders, rows.Err()
}

func ValidStatus(s string) bool {
	switch s {
	case "pending", "paid", "shipped", "completed", "cancelled":
		return true
	}
	return false
}
