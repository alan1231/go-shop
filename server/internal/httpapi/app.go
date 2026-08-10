package httpapi

import (
	"database/sql"
	"net/http"
	"os"
	"path/filepath"
	"strings"

	"shop/internal/config"
	"shop/internal/repository"
	"shop/internal/service"
	"shop/internal/storage"
)

type App struct {
	Cfg    *config.Config
	DB     *sql.DB
	Images *storage.Images

	UserRepo    *repository.UserRepository
	AdminRepo   *repository.AdminUserRepository
	ProductRepo *repository.ProductRepository
	OrderRepo   *repository.OrderRepository
	MarqueeRepo *repository.MarqueeRepository

	AuthSvc      *service.AuthService
	UserSvc      *service.UserService
	ProductSvc   *service.ProductService
	OrderSvc     *service.OrderService
	MarqueeSvc   *service.MarqueeService
	DashboardSvc *service.DashboardService
	OAuthSvc     *service.OAuthService
	RateLimitSvc *service.RateLimitService
	LinePaySvc   *service.LinePayService

	Mux *http.ServeMux
}

func New(cfg *config.Config, db *sql.DB) *App {
	images := &storage.Images{Dir: cfg.UploadsDir}

	userRepo := repository.NewUserRepository(db)
	adminRepo := repository.NewAdminUserRepository(db)
	productRepo := repository.NewProductRepository(db)
	orderRepo := repository.NewOrderRepository(db)
	marqueeRepo := repository.NewMarqueeRepository(db)

	a := &App{
		Cfg:          cfg,
		DB:           db,
		Images:       images,
		UserRepo:     userRepo,
		AdminRepo:    adminRepo,
		ProductRepo:  productRepo,
		OrderRepo:    orderRepo,
		MarqueeRepo:  marqueeRepo,
		AuthSvc:      service.NewAuthService(adminRepo),
		UserSvc:      service.NewUserService(userRepo),
		ProductSvc:   service.NewProductService(productRepo, images),
		OrderSvc:     service.NewOrderService(db, orderRepo, productRepo),
		MarqueeSvc:   service.NewMarqueeService(marqueeRepo),
		DashboardSvc: service.NewDashboardService(productRepo, orderRepo, userRepo),
		OAuthSvc: service.NewOAuthService(service.Config{
			GoogleClientID:     cfg.GoogleClientID,
			GoogleClientSecret: cfg.GoogleClientSecret,
			LineChannelID:      cfg.LineChannelID,
			LineChannelSecret:  cfg.LineChannelSecret,
			OAuthRedirectURI:   cfg.OAuthRedirectURI,
		}),
		RateLimitSvc: service.NewRateLimitService(repository.NewLoginAttemptRepository(db)),
		LinePaySvc:   service.NewLinePayService(cfg.LinePayChannelID, cfg.LinePayChannelSecret, cfg.LinePaySandbox),
		Mux:          http.NewServeMux(),
	}
	a.routes()
	return a
}

func (a *App) routes() {
	m := a.Mux

	m.Handle("GET /uploads/", http.StripPrefix("/uploads/", http.FileServer(http.Dir(a.Cfg.UploadsDir))))

	// 公開 API（前台）
	m.HandleFunc("GET /api/marquee", a.marqueeIndex)
	m.HandleFunc("POST /api/auth/register", a.authRegister)
	m.HandleFunc("POST /api/auth/login", a.authLogin)
	m.HandleFunc("POST /api/auth/oauth", a.authOAuth)
	m.HandleFunc("POST /api/auth/logout", a.authLogout)
	m.HandleFunc("GET /api/auth/me", a.authMe)
	m.HandleFunc("POST /api/auth/update", a.authUpdateContact)
	m.HandleFunc("POST /api/auth/password", a.authChangePassword)
	m.HandleFunc("GET /api/products", a.productsIndex)
	m.HandleFunc("GET /api/categories", a.productsCategories)
	m.HandleFunc("GET /api/products/{id}", a.productsShow)
	m.HandleFunc("POST /api/orders", a.ordersCreate)
	m.HandleFunc("GET /api/orders", a.ordersIndex)
	m.HandleFunc("GET /api/orders/{id}", a.ordersShow)
	m.HandleFunc("POST /api/orders/{id}/pay", a.ordersPay)

	// 後台 API
	m.HandleFunc("POST /api/admin/login", a.adminLogin)
	m.HandleFunc("GET /api/admin/me", a.adminMe)
	m.HandleFunc("POST /api/admin/logout", a.adminLogout)
	m.HandleFunc("GET /api/admin/stats", a.adminDashboard)
	m.HandleFunc("GET /api/admin/products", a.adminProductsIndex)
	m.HandleFunc("POST /api/admin/products", a.adminProductsCreate)
	m.HandleFunc("GET /api/admin/products/{id}", a.adminProductsShow)
	m.HandleFunc("POST /api/admin/products/{id}", a.adminProductsUpdate)
	m.HandleFunc("GET /api/admin/categories", a.adminCategories)
	m.HandleFunc("GET /api/admin/orders", a.adminOrdersIndex)
	m.HandleFunc("GET /api/admin/orders/{id}", a.adminOrdersShow)
	m.HandleFunc("POST /api/admin/orders/{id}/status", a.adminOrdersStatus)
	m.HandleFunc("POST /api/admin/orders/{id}/remark", a.adminOrdersRemark)
	m.HandleFunc("GET /api/admin/users", a.adminUsersIndex)
	m.HandleFunc("POST /api/admin/users", a.adminUsersCreate)
	m.HandleFunc("GET /api/admin/users/{id}", a.adminUsersShow)
	m.HandleFunc("POST /api/admin/users/{id}", a.adminUsersUpdatePassword)
	m.HandleFunc("POST /api/admin/users/{id}/delete", a.adminUsersDelete)
	m.HandleFunc("GET /api/admin/marquee", a.adminMarqueeGet)
	m.HandleFunc("POST /api/admin/marquee", a.adminMarqueeUpdate)
}

func (a *App) Handler() http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		path := r.URL.Path
		if strings.HasPrefix(path, "/api") || strings.HasPrefix(path, "/uploads/") {
			a.Mux.ServeHTTP(w, r)
			return
		}
		a.serveSPA(w, r)
	})
}

func (a *App) serveSPA(w http.ResponseWriter, r *http.Request) {
	path := r.URL.Path
	if strings.HasPrefix(path, "/admin") {
		spa(w, r, strings.TrimPrefix(path, "/admin"), a.Cfg.AdminDist)
		return
	}
	spa(w, r, path, a.Cfg.PublicDist)
}

func spa(w http.ResponseWriter, r *http.Request, path, dir string) {
	clean := strings.TrimPrefix(path, "/")
	if clean == "" {
		clean = "index.html"
	}
	if _, err := os.Stat(filepath.Join(dir, clean)); err == nil {
		http.ServeFile(w, r, filepath.Join(dir, clean))
		return
	}
	http.ServeFile(w, r, filepath.Join(dir, "index.html"))
}
