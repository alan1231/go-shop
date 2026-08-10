package service

import (
	"errors"
	"strings"

	"golang.org/x/crypto/bcrypt"

	"shop/internal/repository"
)

type UserService struct {
	Repo *repository.UserRepository
}

func NewUserService(repo *repository.UserRepository) *UserService {
	return &UserService{Repo: repo}
}

func (s *UserService) GetAllMembers(q string) ([]repository.User, error) {
	return s.Repo.FindAllByRole("user", q)
}

func (s *UserService) GetByID(id int) (*repository.User, error) {
	return s.Repo.FindByID(id)
}

func (s *UserService) CreateMember(username, email, password string) (string, error) {
	if username == "" || email == "" || password == "" {
		return "請填寫所有欄位", nil
	}
	exists, err := s.Repo.ExistsByUsernameOrEmail(username, email, 0)
	if err != nil {
		return "", err
	}
	if exists {
		return "帳號或 Email 已存在", nil
	}
	hash, err := hashPassword(password)
	if err != nil {
		return "", err
	}
	if err := s.Repo.Create(username, email, hash, "user"); err != nil {
		return "", err
	}
	return "", nil
}

func (s *UserService) UpdatePassword(id int, password string) (string, error) {
	if password == "" {
		return "請輸入新密碼", nil
	}
	hash, err := hashPassword(password)
	if err != nil {
		return "", err
	}
	if err := s.Repo.UpdatePassword(id, hash); err != nil {
		return "", err
	}
	return "", nil
}

func (s *UserService) UpdateContact(id int, phone, address string) error {
	return s.Repo.UpdateContact(id, strings.TrimSpace(phone), strings.TrimSpace(address))
}

func (s *UserService) ChangePassword(id int, oldPassword, newPassword string) (string, error) {
	user, err := s.Repo.FindForAuthByID(id)
	if err != nil {
		return "", err
	}
	if user == nil {
		return "使用者不存在", nil
	}
	isOAuth := user.Provider != ""
	if !isOAuth || oldPassword != "" {
		if bcrypt.CompareHashAndPassword([]byte(user.Password), []byte(oldPassword)) != nil {
			return "原密碼錯誤", nil
		}
	}
	if len(newPassword) < 6 {
		return "新密碼至少需 6 個字元", nil
	}
	hash, err := hashPassword(newPassword)
	if err != nil {
		return "", err
	}
	if err := s.Repo.UpdatePassword(id, hash); err != nil {
		return "", err
	}
	return "", nil
}

func (s *UserService) UpdateProfile(id int, username, email string, password string) (string, error) {
	if username == "" || email == "" {
		return "請填寫帳號與 Email", nil
	}
	exists, err := s.Repo.ExistsByUsernameOrEmail(username, email, id)
	if err != nil {
		return "", err
	}
	if exists {
		return "帳號或 Email 已被其他會員使用", nil
	}
	var hash string
	if password != "" {
		h, err := hashPassword(password)
		if err != nil {
			return "", err
		}
		hash = h
	}
	if err := s.Repo.Update(id, username, email, hash); err != nil {
		return "", err
	}
	return "", nil
}

func (s *UserService) CanDelete(targetID int) error {
	u, err := s.Repo.FindByID(targetID)
	if err != nil {
		return err
	}
	if u == nil {
		return errors.New("使用者不存在")
	}
	return nil
}

func (s *UserService) Delete(id int) error {
	return s.Repo.Delete(id)
}

func (s *UserService) CountMembers() (int, error) {
	return s.Repo.CountByRole("user")
}

func (s *UserService) Register(username, email, password string) (string, error) {
	if username == "" || email == "" || password == "" {
		return "請填寫所有欄位", nil
	}
	exists, err := s.Repo.ExistsByUsernameOrEmail(username, email, 0)
	if err != nil {
		return "", err
	}
	if exists {
		return "帳號或 Email 已存在", nil
	}
	hash, err := hashPassword(password)
	if err != nil {
		return "", err
	}
	if err := s.Repo.Create(username, email, hash, "user"); err != nil {
		return "", err
	}
	return "", nil
}

func hashPassword(password string) (string, error) {
	b, err := bcrypt.GenerateFromPassword([]byte(password), bcrypt.DefaultCost)
	return string(b), err
}
