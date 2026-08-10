package service

import (
	"database/sql"
	"errors"
	"strings"

	"shop/internal/repository"
)

type ReceiverInput struct {
	Name    string
	Phone   string
	Address string
}

type OrderItemInput struct {
	ProductID int `json:"product_id"`
	Quantity  int `json:"quantity"`
}

type OrderService struct {
	Repo        *repository.OrderRepository
	ProductRepo *repository.ProductRepository
	DB          *sql.DB
}

func NewOrderService(db *sql.DB, repo *repository.OrderRepository, productRepo *repository.ProductRepository) *OrderService {
	return &OrderService{Repo: repo, ProductRepo: productRepo, DB: db}
}

func (s *OrderService) GetAll(status string, pageNum, perPage int) (PageResult[repository.Order], error) {
	total, err := s.Repo.CountFindAll(status)
	if err != nil {
		return PageResult[repository.Order]{}, err
	}
	items, err := s.Repo.FindAll(status, perPage, (pageNum-1)*perPage)
	if err != nil {
		return PageResult[repository.Order]{}, err
	}
	return page(items, total, pageNum, perPage), nil
}

func (s *OrderService) GetWithItems(id int) (*repository.Order, error) {
	order, err := s.Repo.FindByID(id)
	if err != nil {
		return nil, err
	}
	if order == nil {
		return nil, nil
	}
	items, err := s.Repo.GetItems(id)
	if err != nil {
		return nil, err
	}
	order.Items = items
	return order, nil
}

func (s *OrderService) GetUserOrders(userID int, status string) ([]repository.Order, error) {
	return s.Repo.FindByUserID(userID, status)
}

func (s *OrderService) CreateOrder(userID int, items []OrderItemInput, receiver ReceiverInput, remark string) (int, string, error) {
	if len(items) == 0 {
		return 0, "訂單不得為空", nil
	}

	tx, err := s.DB.Begin()
	if err != nil {
		return 0, "", err
	}
	defer tx.Rollback()

	orderRepo := repository.NewOrderRepository(tx)
	productRepo := repository.NewProductRepository(tx)

	total := 0.0
	type line struct {
		product  *repository.Product
		quantity int
	}
	var lines []line

	for _, item := range items {
		if item.ProductID <= 0 || item.Quantity <= 0 {
			continue
		}
		p, err := productRepo.GetByID(item.ProductID)
		if err != nil {
			return 0, "", err
		}
		if p == nil || p.Status != "active" {
			return 0, "商品不存在或已下架", nil
		}
		ok, err := productRepo.DecreaseStockIfAvailable(item.ProductID, item.Quantity)
		if err != nil {
			return 0, "", err
		}
		if !ok {
			return 0, "商品「" + p.Name + "」庫存不足", nil
		}
		lines = append(lines, line{product: p, quantity: item.Quantity})
		total += p.Price * float64(item.Quantity)
	}

	if len(lines) == 0 {
		return 0, "商品不存在或庫存不足", nil
	}

	orderID, err := orderRepo.CreateOrder(userID, total, receiver.Name, receiver.Phone, receiver.Address, remark)
	if err != nil {
		return 0, "", err
	}
	for _, l := range lines {
		if err := orderRepo.CreateItem(orderID, l.product.ID, l.product.Price, l.quantity); err != nil {
			return 0, "", err
		}
	}
	if err := tx.Commit(); err != nil {
		return 0, "", err
	}
	return orderID, "", nil
}

func (s *OrderService) UpdateStatus(id int, status string) error {
	if !repository.ValidStatus(status) {
		return errors.New("無效的狀態")
	}
	order, err := s.Repo.FindByID(id)
	if err != nil {
		return err
	}
	if order == nil {
		return errors.New("訂單不存在")
	}
	if order.Status == "completed" {
		return errors.New("訂單已完成，狀態不可再變更")
	}
	return s.Repo.UpdateStatus(id, status)
}

func (s *OrderService) UpdateRemark(id int, remark string) error {
	order, err := s.Repo.FindByID(id)
	if err != nil {
		return err
	}
	if order == nil {
		return errors.New("訂單不存在")
	}
	return s.Repo.UpdateRemark(id, strings.TrimSpace(remark))
}
