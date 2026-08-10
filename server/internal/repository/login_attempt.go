package repository

import (
	"database/sql"
	"time"
)

type LoginAttemptRepository struct {
	DB Querier
}

func NewLoginAttemptRepository(db Querier) *LoginAttemptRepository {
	return &LoginAttemptRepository{DB: db}
}

type Attempt struct {
	IP          string
	Type        string
	Attempts    int
	LockedUntil *time.Time
}

func (r *LoginAttemptRepository) Find(ip, typ string) (*Attempt, error) {
	var a Attempt
	var lockedUntil sql.NullString
	err := r.DB.QueryRow("SELECT attempts, locked_until FROM login_attempts WHERE ip = ? AND type = ?", ip, typ).
		Scan(&a.Attempts, &lockedUntil)
	if err == sql.ErrNoRows {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	a.IP = ip
	a.Type = typ
	if lockedUntil.Valid && lockedUntil.String != "" {
		if t, perr := time.Parse("2006-01-02 15:04:05", lockedUntil.String); perr == nil {
			a.LockedUntil = &t
		}
	}
	return &a, nil
}

func (r *LoginAttemptRepository) RecordFail(ip, typ string, maxAttempts int, lockMinutes int) error {
	now := time.Now().Format("2006-01-02 15:04:05")
	_, err := r.DB.Exec(
		"INSERT INTO login_attempts (ip, type, attempts, locked_until, updated_at) VALUES (?, ?, 1, NULL, ?) "+
			"ON DUPLICATE KEY UPDATE attempts = attempts + 1, "+
			"locked_until = IF(attempts + 1 >= ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), NULL), updated_at = ?",
		ip, typ, now, maxAttempts, lockMinutes, now)
	return err
}

func (r *LoginAttemptRepository) Clear(ip, typ string) error {
	_, err := r.DB.Exec("DELETE FROM login_attempts WHERE ip = ? AND type = ?", ip, typ)
	return err
}
