package service

import (
	"errors"

	"golang.org/x/crypto/bcrypt"

	"shop/internal/repository"
)

type AuthService struct {
	AdminRepo *repository.AdminUserRepository
}

func NewAuthService(adminRepo *repository.AdminUserRepository) *AuthService {
	return &AuthService{AdminRepo: adminRepo}
}

func (s *AuthService) Authenticate(username, password string) (*repository.AdminUser, error) {
	if username == "" || password == "" {
		return nil, errors.New("請輸入帳號與密碼")
	}
	admin, err := s.AdminRepo.FindForAuth(username)
	if err != nil {
		return nil, err
	}
	if admin == nil || bcrypt.CompareHashAndPassword([]byte(admin.Password), []byte(password)) != nil {
		return nil, errors.New("帳號或密碼錯誤")
	}
	return admin, nil
}

func (s *AuthService) Login(admin *repository.AdminUser) (string, error) {
	token := RandomToken()
	if err := s.AdminRepo.SetToken(admin.ID, token); err != nil {
		return "", err
	}
	return token, nil
}

func (s *AuthService) Logout(adminID int) error {
	return s.AdminRepo.SetToken(adminID, "")
}

func (s *AuthService) FindByToken(token string) (*repository.AdminUser, error) {
	if token == "" {
		return nil, nil
	}
	return s.AdminRepo.FindByToken(token)
}
