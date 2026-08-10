package repository

import (
	"crypto/rand"
	"database/sql"
	"encoding/hex"
	"time"
)

func timeNow() time.Time {
	return time.Now()
}

func randomHex(n int) string {
	b := make([]byte, n)
	if _, err := rand.Read(b); err != nil {
		panic(err)
	}
	return hex.EncodeToString(b)
}

func nstr(s sql.NullString) string {
	if !s.Valid {
		return ""
	}
	return s.String
}

func nfloat(s sql.NullFloat64) *float64 {
	if !s.Valid {
		return nil
	}
	v := s.Float64
	return &v
}

func scanProduct(scan func(dest ...any) error) (Product, error) {
	var p Product
	var img, cat, desc sql.NullString
	var lp sql.NullFloat64
	err := scan(&p.ID, &p.Name, &img, &desc, &cat, &p.Price, &lp, &p.Stock, &p.ListedStock, &p.Status, &p.CreatedAt)
	if err != nil {
		return Product{}, err
	}
	p.Image = nstr(img)
	p.Description = nstr(desc)
	p.Category = nstr(cat)
	p.ListPrice = nfloat(lp)
	return p, nil
}

const productColumns = "id, name, image, description, category, price, list_price, stock, listed_stock, status, created_at"
