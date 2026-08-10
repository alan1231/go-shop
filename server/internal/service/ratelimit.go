package service

import (
	"time"

	"shop/internal/repository"
)

type RateLimitService struct {
	Repo        *repository.LoginAttemptRepository
	MaxAttempts int
	LockMinutes int
}

func NewRateLimitService(repo *repository.LoginAttemptRepository) *RateLimitService {
	return &RateLimitService{Repo: repo, MaxAttempts: 5, LockMinutes: 15}
}

func (s *RateLimitService) Check(ip, typ string) (allowed bool, retryAfter int, err error) {
	attempt, err := s.Repo.Find(ip, typ)
	if err != nil {
		return false, 0, err
	}
	if attempt != nil && attempt.LockedUntil != nil {
		until := *attempt.LockedUntil
		if until.After(time.Now()) {
			return false, int(time.Until(until).Seconds()), nil
		}
		_ = s.Repo.Clear(ip, typ)
	}
	return true, 0, nil
}

func (s *RateLimitService) RecordFail(ip, typ string) error {
	return s.Repo.RecordFail(ip, typ, s.MaxAttempts, s.LockMinutes)
}

func (s *RateLimitService) Clear(ip, typ string) error {
	return s.Repo.Clear(ip, typ)
}
