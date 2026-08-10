package repository

import "database/sql"

type AdminUserRepository struct {
	DB Querier
}

func NewAdminUserRepository(db Querier) *AdminUserRepository {
	return &AdminUserRepository{DB: db}
}

func (r *AdminUserRepository) FindForAuth(username string) (*AdminUser, error) {
	var a AdminUser
	var token sql.NullString
	err := r.DB.QueryRow("SELECT id, username, password, token FROM admin_users WHERE username = ? LIMIT 1", username).
		Scan(&a.ID, &a.Username, &a.Password, &token)
	if err == sql.ErrNoRows {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	a.Token = nstr(token)
	return &a, nil
}

func (r *AdminUserRepository) FindByToken(token string) (*AdminUser, error) {
	var a AdminUser
	err := r.DB.QueryRow("SELECT id, username, password, token FROM admin_users WHERE token = ? LIMIT 1", token).
		Scan(&a.ID, &a.Username, &a.Password, &a.Token)
	if err == sql.ErrNoRows {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &a, nil
}

func (r *AdminUserRepository) SetToken(id int, token string) error {
	if token == "" {
		_, err := r.DB.Exec("UPDATE admin_users SET token = NULL WHERE id = ?", id)
		return err
	}
	_, err := r.DB.Exec("UPDATE admin_users SET token = ? WHERE id = ?", token, id)
	return err
}
