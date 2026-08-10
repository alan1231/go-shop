package repository

import (
	"database/sql"
)

type UserRepository struct {
	DB Querier
}

func NewUserRepository(db Querier) *UserRepository {
	return &UserRepository{DB: db}
}

func scanUser(scan func(dest ...any) error) (User, error) {
	var u User
	var token, provider, pid, phone, address, avatar sql.NullString
	err := scan(&u.ID, &u.Username, &u.Email, &u.Password, &u.Role, &token, &provider, &pid, &phone, &address, &avatar, &u.CreatedAt)
	if err != nil {
		return User{}, err
	}
	u.Token = nstr(token)
	u.Provider = nstr(provider)
	u.ProviderID = nstr(pid)
	u.Phone = nstr(phone)
	u.Address = nstr(address)
	u.Avatar = nstr(avatar)
	return u, nil
}

func (r *UserRepository) FindByProvider(provider, providerID string) (*User, error) {
	row := r.DB.QueryRow("SELECT * FROM users WHERE provider = ? AND provider_id = ? LIMIT 1", provider, providerID)
	u, err := scanUser(row.Scan)
	if err == sql.ErrNoRows {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &u, nil
}

func (r *UserRepository) FindByEmail(email string) (*User, error) {
	row := r.DB.QueryRow("SELECT * FROM users WHERE email = ? LIMIT 1", email)
	u, err := scanUser(row.Scan)
	if err == sql.ErrNoRows {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &u, nil
}

func (r *UserRepository) SetProvider(id int, provider, providerID string) error {
	_, err := r.DB.Exec("UPDATE users SET provider = ?, provider_id = ? WHERE id = ?", provider, providerID, id)
	return err
}

func (r *UserRepository) CreateOAuthUser(username, email, provider, providerID, avatar string) (int, error) {
	res, err := r.DB.Exec(
		"INSERT INTO users (username, email, password, role, provider, provider_id, avatar) VALUES (?, ?, ?, 'user', ?, ?, ?)",
		username, email, randomHex(16), provider, providerID, nilIfEmpty(avatar),
	)
	if err != nil {
		return 0, err
	}
	id, err := res.LastInsertId()
	return int(id), err
}

func (r *UserRepository) FindByToken(token string) (*User, error) {
	row := r.DB.QueryRow("SELECT * FROM users WHERE token = ? LIMIT 1", token)
	u, err := scanUser(row.Scan)
	if err == sql.ErrNoRows {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &u, nil
}

func (r *UserRepository) SetToken(id int, token string) error {
	if token == "" {
		_, err := r.DB.Exec("UPDATE users SET token = NULL WHERE id = ?", id)
		return err
	}
	_, err := r.DB.Exec("UPDATE users SET token = ? WHERE id = ?", token, id)
	return err
}

func (r *UserRepository) FindAllByRole(role, q string) ([]User, error) {
	query := "SELECT id, username, email, provider, avatar, created_at FROM users WHERE role = ?"
	var args []any
	args = append(args, role)
	if q != "" {
		query += " AND (username LIKE ? OR email LIKE ?)"
		like := "%" + q + "%"
		args = append(args, like, like)
	}
	query += " ORDER BY created_at DESC"
	rows, err := r.DB.Query(query, args...)
	if err != nil {
		return nil, err
	}
	defer rows.Close()
	var users []User
	for rows.Next() {
		var u User
		var provider, avatar sql.NullString
		if err := rows.Scan(&u.ID, &u.Username, &u.Email, &provider, &avatar, &u.CreatedAt); err != nil {
			return nil, err
		}
		u.Provider = nstr(provider)
		u.Avatar = nstr(avatar)
		users = append(users, u)
	}
	return users, rows.Err()
}

func (r *UserRepository) FindByID(id int) (*User, error) {
	row := r.DB.QueryRow("SELECT * FROM users WHERE id = ?", id)
	u, err := scanUser(row.Scan)
	if err == sql.ErrNoRows {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &u, nil
}

func (r *UserRepository) FindForAuth(username string) (*User, error) {
	row := r.DB.QueryRow("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1", username, username)
	u, err := scanUser(row.Scan)
	if err == sql.ErrNoRows {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &u, nil
}

func (r *UserRepository) FindForAuthByID(id int) (*User, error) {
	row := r.DB.QueryRow("SELECT * FROM users WHERE id = ?", id)
	u, err := scanUser(row.Scan)
	if err == sql.ErrNoRows {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &u, nil
}

func (r *UserRepository) ExistsByUsernameOrEmail(username, email string, excludeID int) (bool, error) {
	query := "SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?)"
	var args []any
	args = append(args, username, email)
	if excludeID > 0 {
		query += " AND id != ?"
		args = append(args, excludeID)
	}
	var n int
	err := r.DB.QueryRow(query, args...).Scan(&n)
	return n > 0, err
}

func (r *UserRepository) CountByRole(role string) (int, error) {
	var n int
	err := r.DB.QueryRow("SELECT COUNT(*) FROM users WHERE role = ?", role).Scan(&n)
	return n, err
}

func (r *UserRepository) Create(username, email, passwordHash, role string) error {
	_, err := r.DB.Exec("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)", username, email, passwordHash, role)
	return err
}

func (r *UserRepository) Update(id int, username, email string, passwordHash string) error {
	if passwordHash != "" {
		_, err := r.DB.Exec("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?", username, email, passwordHash, id)
		return err
	}
	_, err := r.DB.Exec("UPDATE users SET username = ?, email = ? WHERE id = ?", username, email, id)
	return err
}

func (r *UserRepository) UpdatePassword(id int, passwordHash string) error {
	_, err := r.DB.Exec("UPDATE users SET password = ? WHERE id = ?", passwordHash, id)
	return err
}

func (r *UserRepository) UpdateContact(id int, phone, address string) error {
	_, err := r.DB.Exec("UPDATE users SET phone = ?, address = ? WHERE id = ?", phone, address, id)
	return err
}

func (r *UserRepository) UpdateAvatar(id int, avatar string) error {
	_, err := r.DB.Exec("UPDATE users SET avatar = ? WHERE id = ?", avatar, id)
	return err
}

func (r *UserRepository) Delete(id int) error {
	_, err := r.DB.Exec("DELETE FROM users WHERE id = ?", id)
	return err
}

func (r *UserRepository) GetRole(id int) (string, error) {
	var role sql.NullString
	err := r.DB.QueryRow("SELECT role FROM users WHERE id = ?", id).Scan(&role)
	if err == sql.ErrNoRows {
		return "", nil
	}
	if err != nil {
		return "", err
	}
	return nstr(role), nil
}
