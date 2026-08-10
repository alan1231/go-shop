package repository

import "database/sql"

type MarqueeRepository struct {
	DB Querier
}

func NewMarqueeRepository(db Querier) *MarqueeRepository {
	return &MarqueeRepository{DB: db}
}

func (r *MarqueeRepository) Get() (string, error) {
	var content string
	err := r.DB.QueryRow("SELECT content FROM marquee WHERE id = 1").Scan(&content)
	if err == sql.ErrNoRows {
		return "", nil
	}
	if err != nil {
		return "", err
	}
	return content, nil
}

func (r *MarqueeRepository) Update(content string) error {
	_, err := r.DB.Exec(
		"INSERT INTO marquee (id, content) VALUES (1, ?) AS mv ON DUPLICATE KEY UPDATE content = mv.content",
		content)
	return err
}
