package service

import (
	"errors"
	"io"
	"math"
	"strings"

	"shop/internal/repository"
	"shop/internal/storage"
)

type PageResult[T any] struct {
	Items      []T `json:"items"`
	Total      int `json:"total"`
	Page       int `json:"page"`
	PerPage    int `json:"per_page"`
	TotalPages int `json:"total_pages"`
}

func page[T any](items []T, total, page, perPage int) PageResult[T] {
	totalPages := int(math.Ceil(float64(total) / float64(perPage)))
	if totalPages < 1 {
		totalPages = 1
	}
	return PageResult[T]{Items: items, Total: total, Page: page, PerPage: perPage, TotalPages: totalPages}
}

type ProductService struct {
	Repo   *repository.ProductRepository
	Images *storage.Images
}

func NewProductService(repo *repository.ProductRepository, images *storage.Images) *ProductService {
	return &ProductService{Repo: repo, Images: images}
}

func (s *ProductService) GetFilteredPage(keyword, category string, pageNum, perPage int) (PageResult[repository.Product], error) {
	total, err := s.Repo.CountSearch(keyword, category)
	if err != nil {
		return PageResult[repository.Product]{}, err
	}
	items, err := s.Repo.Search(keyword, category, perPage, (pageNum-1)*perPage)
	if err != nil {
		return PageResult[repository.Product]{}, err
	}
	return page(items, total, pageNum, perPage), nil
}

func (s *ProductService) GetActivePage(keyword, category string, pageNum, perPage int) (PageResult[repository.Product], error) {
	total, err := s.Repo.CountActive(keyword, category)
	if err != nil {
		return PageResult[repository.Product]{}, err
	}
	items, err := s.Repo.FindActive(keyword, category, perPage, (pageNum-1)*perPage)
	if err != nil {
		return PageResult[repository.Product]{}, err
	}
	return page(items, total, pageNum, perPage), nil
}

func (s *ProductService) GetAllCategories() ([]string, error) {
	return s.Repo.GetAllCategories()
}

func (s *ProductService) GetCategories() ([]string, error) {
	return s.Repo.GetCategories()
}

func (s *ProductService) GetByID(id int) (*repository.Product, error) {
	return s.Repo.GetByID(id)
}

type ProductInput struct {
	Name        string
	Description string
	Category    string
	Price       float64
	ListPrice   *float64
	Stock       int
	ListedStock int
	Status      string
	ImageFile   io.Reader
	ImageName   string
}

func (s *ProductService) Create(in ProductInput) (string, error) {
	if strings.TrimSpace(in.Name) == "" || in.Price <= 0 {
		return "請填寫商品名稱且售價需大於 0", nil
	}
	imageName := ""
	if in.ImageFile != nil {
		name, err := s.Images.Save(in.ImageFile, in.ImageName)
		if err != nil {
			if errors.Is(err, storage.ErrInvalidType) {
				return "只允許上傳 JPG, PNG, GIF, WEBP 格式的圖片！", nil
			}
			return "圖片上傳失敗，請檢查目錄權限", err
		}
		imageName = name
	}
	if err := s.Repo.Create(strings.TrimSpace(in.Name), imageName, in.Description, strings.TrimSpace(in.Category), in.Price, in.ListPrice, in.Stock, in.ListedStock, in.Status); err != nil {
		return "", err
	}
	return "", nil
}

func (s *ProductService) Update(id int, in ProductInput) (string, error) {
	p, err := s.Repo.GetByID(id)
	if err != nil {
		return "", err
	}
	if p == nil {
		return "商品不存在", nil
	}
	if strings.TrimSpace(in.Name) == "" || in.Price <= 0 {
		return "請填寫商品名稱且售價需大於 0", nil
	}
	imageName := p.Image
	if in.ImageFile != nil {
		name, err := s.Images.Save(in.ImageFile, in.ImageName)
		if err != nil {
			if errors.Is(err, storage.ErrInvalidType) {
				return "只允許上傳 JPG, PNG, GIF, WEBP 格式的圖片！", nil
			}
			return "圖片上傳失敗，請檢查目錄權限", err
		}
		if p.Image != "" {
			s.Images.Delete(p.Image)
		}
		imageName = name
	}
	if err := s.Repo.Update(id, strings.TrimSpace(in.Name), imageName, in.Description, strings.TrimSpace(in.Category), in.Price, in.ListPrice, in.Stock, in.ListedStock, in.Status); err != nil {
		return "", err
	}
	return "", nil
}
