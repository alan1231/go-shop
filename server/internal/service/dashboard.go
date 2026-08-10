package service

import (
	"shop/internal/repository"
)

type DashboardService struct {
	ProductRepo *repository.ProductRepository
	OrderRepo   *repository.OrderRepository
	UserRepo    *repository.UserRepository
}

func NewDashboardService(productRepo *repository.ProductRepository, orderRepo *repository.OrderRepository, userRepo *repository.UserRepository) *DashboardService {
	return &DashboardService{ProductRepo: productRepo, OrderRepo: orderRepo, UserRepo: userRepo}
}

type DashboardStats struct {
	TotalProducts int                     `json:"totalProducts"`
	TotalOrders   int                     `json:"totalOrders"`
	TotalUsers    int                     `json:"totalUsers"`
	Revenue       float64                 `json:"revenue"`
	RecentOrders  []repository.Order      `json:"recentOrders"`
	StatusCounts  map[string]int          `json:"statusCounts"`
	DailyStats    []repository.DailyStat  `json:"dailyStats"`
	TopProducts   []repository.TopProduct `json:"topProducts"`
}

func (s *DashboardService) GetStats() (DashboardStats, error) {
	var stats DashboardStats
	var err error
	if stats.TotalProducts, err = s.ProductRepo.Count(); err != nil {
		return stats, err
	}
	if stats.TotalOrders, err = s.OrderRepo.Count(); err != nil {
		return stats, err
	}
	if stats.TotalUsers, err = s.UserRepo.CountByRole("user"); err != nil {
		return stats, err
	}
	if stats.Revenue, err = s.OrderRepo.GetCompletedRevenue(); err != nil {
		return stats, err
	}
	if stats.RecentOrders, err = s.OrderRepo.GetRecent(5); err != nil {
		return stats, err
	}
	if stats.StatusCounts, err = s.OrderRepo.CountByStatus(); err != nil {
		return stats, err
	}
	if stats.DailyStats, err = s.OrderRepo.GetDailyStats(7); err != nil {
		return stats, err
	}
	if stats.TopProducts, err = s.OrderRepo.GetTopProducts(5); err != nil {
		return stats, err
	}
	return stats, nil
}
