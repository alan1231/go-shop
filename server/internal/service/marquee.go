package service

import (
	"strings"

	"shop/internal/repository"
)

type MarqueeService struct {
	Repo *repository.MarqueeRepository
}

func NewMarqueeService(repo *repository.MarqueeRepository) *MarqueeService {
	return &MarqueeService{Repo: repo}
}

func (s *MarqueeService) GetContent() (string, error) {
	return s.Repo.Get()
}

func (s *MarqueeService) UpdateContent(content string) (string, error) {
	if strings.TrimSpace(content) == "" {
		return "內容不能為空", nil
	}
	if err := s.Repo.Update(strings.TrimSpace(content)); err != nil {
		return "", err
	}
	return "", nil
}
