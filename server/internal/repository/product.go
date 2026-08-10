package repository

import (
	"database/sql"
)

type ProductRepository struct {
	DB Querier
}

func NewProductRepository(db Querier) *ProductRepository {
	return &ProductRepository{DB: db}
}

func (r *ProductRepository) GetAll(limit, offset int) ([]Product, error) {
	rows, err := r.DB.Query("SELECT "+productColumns+" FROM products ORDER BY created_at DESC LIMIT ? OFFSET ?", limit, offset)
	if err != nil {
		return nil, err
	}
	defer rows.Close()
	return scanProducts(rows)
}

func (r *ProductRepository) CountAll() (int, error) {
	var n int
	err := r.DB.QueryRow("SELECT COUNT(*) FROM products").Scan(&n)
	return n, err
}

func (r *ProductRepository) Search(keyword, category string, limit, offset int) ([]Product, error) {
	query := "SELECT " + productColumns + " FROM products WHERE 1=1"
	var args []any
	if keyword != "" {
		query += " AND (name LIKE ? OR description LIKE ?)"
		args = append(args, "%"+keyword+"%", "%"+keyword+"%")
	}
	if category != "" {
		query += " AND category = ?"
		args = append(args, category)
	}
	query += " ORDER BY created_at DESC LIMIT ? OFFSET ?"
	args = append(args, limit, offset)
	rows, err := r.DB.Query(query, args...)
	if err != nil {
		return nil, err
	}
	defer rows.Close()
	return scanProducts(rows)
}

func (r *ProductRepository) CountSearch(keyword, category string) (int, error) {
	query := "SELECT COUNT(*) FROM products WHERE 1=1"
	var args []any
	if keyword != "" {
		query += " AND (name LIKE ? OR description LIKE ?)"
		args = append(args, "%"+keyword+"%", "%"+keyword+"%")
	}
	if category != "" {
		query += " AND category = ?"
		args = append(args, category)
	}
	var n int
	err := r.DB.QueryRow(query, args...).Scan(&n)
	return n, err
}

func (r *ProductRepository) GetAllCategories() ([]string, error) {
	rows, err := r.DB.Query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category")
	if err != nil {
		return nil, err
	}
	defer rows.Close()
	var cats []string
	for rows.Next() {
		var c string
		if err := rows.Scan(&c); err != nil {
			return nil, err
		}
		cats = append(cats, c)
	}
	return cats, rows.Err()
}

func (r *ProductRepository) GetByID(id int) (*Product, error) {
	row := r.DB.QueryRow("SELECT "+productColumns+" FROM products WHERE id = ?", id)
	p, err := scanProduct(row.Scan)
	if err == sql.ErrNoRows {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &p, nil
}

func (r *ProductRepository) Count() (int, error) {
	var n int
	err := r.DB.QueryRow("SELECT COUNT(*) FROM products").Scan(&n)
	return n, err
}

func (r *ProductRepository) Create(name string, image string, description, category string, price float64, listPrice *float64, stock, listedStock int, status string) error {
	_, err := r.DB.Exec(
		"INSERT INTO products (name, image, description, category, price, list_price, stock, listed_stock, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
		name, nilIfEmpty(image), description, nilIfEmpty(category), price, listPrice, stock, listedStock, status,
	)
	return err
}

func (r *ProductRepository) FindActive(keyword, category string, limit, offset int) ([]Product, error) {
	query := "SELECT id, name, image, description, category, price, list_price, listed_stock AS stock, status, created_at FROM products WHERE status = 'active' AND listed_stock > 0"
	var args []any
	if keyword != "" {
		query += " AND (name LIKE ? OR description LIKE ?)"
		args = append(args, "%"+keyword+"%", "%"+keyword+"%")
	}
	if category != "" {
		query += " AND category = ?"
		args = append(args, category)
	}
	query += " ORDER BY created_at DESC LIMIT ? OFFSET ?"
	args = append(args, limit, offset)
	rows, err := r.DB.Query(query, args...)
	if err != nil {
		return nil, err
	}
	defer rows.Close()
	var items []Product
	for rows.Next() {
		var p Product
		var img, desc, cat sql.NullString
		var lp sql.NullFloat64
		if err := rows.Scan(&p.ID, &p.Name, &img, &desc, &cat, &p.Price, &lp, &p.Stock, &p.Status, &p.CreatedAt); err != nil {
			return nil, err
		}
		p.Image = nstr(img)
		p.Description = nstr(desc)
		p.Category = nstr(cat)
		p.ListPrice = nfloat(lp)
		items = append(items, p)
	}
	return items, rows.Err()
}

func (r *ProductRepository) CountActive(keyword, category string) (int, error) {
	query := "SELECT COUNT(*) FROM products WHERE status = 'active' AND listed_stock > 0"
	var args []any
	if keyword != "" {
		query += " AND (name LIKE ? OR description LIKE ?)"
		args = append(args, "%"+keyword+"%", "%"+keyword+"%")
	}
	if category != "" {
		query += " AND category = ?"
		args = append(args, category)
	}
	var n int
	err := r.DB.QueryRow(query, args...).Scan(&n)
	return n, err
}

func (r *ProductRepository) GetCategories() ([]string, error) {
	rows, err := r.DB.Query("SELECT DISTINCT category FROM products WHERE status = 'active' AND category IS NOT NULL AND category != '' ORDER BY category")
	if err != nil {
		return nil, err
	}
	defer rows.Close()
	var cats []string
	for rows.Next() {
		var c string
		if err := rows.Scan(&c); err != nil {
			return nil, err
		}
		cats = append(cats, c)
	}
	return cats, rows.Err()
}

func (r *ProductRepository) DecreaseStockIfAvailable(id, qty int) (bool, error) {
	res, err := r.DB.Exec(
		"UPDATE products SET stock = stock - ?, listed_stock = listed_stock - ? WHERE id = ? AND stock >= ? AND listed_stock >= ?",
		qty, qty, id, qty, qty,
	)
	if err != nil {
		return false, err
	}
	n, err := res.RowsAffected()
	return n == 1, err
}

func (r *ProductRepository) Update(id int, name, image, description, category string, price float64, listPrice *float64, stock, listedStock int, status string) error {
	_, err := r.DB.Exec(
		"UPDATE products SET name = ?, image = ?, description = ?, category = ?, price = ?, list_price = ?, stock = ?, listed_stock = ?, status = ? WHERE id = ?",
		name, image, description, nilIfEmpty(category), price, listPrice, stock, listedStock, status, id,
	)
	return err
}

func scanProducts(rows *sql.Rows) ([]Product, error) {
	var items []Product
	for rows.Next() {
		p, err := scanProduct(rows.Scan)
		if err != nil {
			return nil, err
		}
		items = append(items, p)
	}
	return items, rows.Err()
}

func nilIfEmpty(s string) any {
	if s == "" {
		return nil
	}
	return s
}
