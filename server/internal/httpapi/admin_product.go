package httpapi

import (
	"net/http"

	"shop/internal/repository"
	"shop/internal/service"
)

func adminProductPayload(p repository.Product) map[string]any {
	return map[string]any{
		"id":           p.ID,
		"name":         p.Name,
		"image":        nilIfEmptyStr(uploadURL(p.Image)),
		"description":  p.Description,
		"category":     nilIfEmptyStr(p.Category),
		"price":        p.Price,
		"list_price":   p.ListPrice,
		"stock":        p.Stock,
		"listed_stock": p.ListedStock,
		"status":       p.Status,
		"created_at":   p.CreatedAt,
	}
}

func (a *App) adminProductsIndex(w http.ResponseWriter, r *http.Request) {
	if _, ok := a.requireAdmin(w, r); !ok {
		return
	}
	q := r.URL.Query().Get("q")
	category := r.URL.Query().Get("category")
	page := max(1, queryInt(r, "page", 1))
	perPage := 10

	paged, err := a.ProductSvc.GetFilteredPage(q, category, page, perPage)
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	items := make([]map[string]any, 0, len(paged.Items))
	for _, p := range paged.Items {
		items = append(items, adminProductPayload(p))
	}
	success(w, map[string]any{
		"items":       items,
		"total":       paged.Total,
		"page":        paged.Page,
		"per_page":    paged.PerPage,
		"total_pages": paged.TotalPages,
	}, "ok")
}

func (a *App) adminProductsShow(w http.ResponseWriter, r *http.Request) {
	if _, ok := a.requireAdmin(w, r); !ok {
		return
	}
	id := pathInt(r)
	p, err := a.ProductSvc.GetByID(id)
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	if p == nil {
		fail(w, "商品不存在", http.StatusNotFound)
		return
	}
	success(w, adminProductPayload(*p), "ok")
}

func (a *App) adminProductsCreate(w http.ResponseWriter, r *http.Request) {
	if _, ok := a.requireAdmin(w, r); !ok {
		return
	}
	in, errMsg := a.productInputFromForm(r)
	if errMsg != "" {
		fail(w, errMsg, http.StatusBadRequest)
		return
	}
	msg, err := a.ProductSvc.Create(in)
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	if msg != "" {
		fail(w, msg, http.StatusBadRequest)
		return
	}
	success(w, nil, "商品新增成功")
}

func (a *App) adminProductsUpdate(w http.ResponseWriter, r *http.Request) {
	if _, ok := a.requireAdmin(w, r); !ok {
		return
	}
	id := pathInt(r)
	in, errMsg := a.productInputFromForm(r)
	if errMsg != "" {
		fail(w, errMsg, http.StatusBadRequest)
		return
	}
	msg, err := a.ProductSvc.Update(id, in)
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	if msg != "" {
		fail(w, msg, http.StatusBadRequest)
		return
	}
	success(w, nil, "商品修改成功")
}

func (a *App) adminCategories(w http.ResponseWriter, r *http.Request) {
	if _, ok := a.requireAdmin(w, r); !ok {
		return
	}
	cats, err := a.ProductSvc.GetAllCategories()
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	success(w, cats, "ok")
}

func (a *App) productInputFromForm(r *http.Request) (service.ProductInput, string) {
	if err := r.ParseMultipartForm(10 << 20); err != nil {
		return service.ProductInput{}, "請求格式錯誤"
	}
	in := service.ProductInput{
		Name:        r.FormValue("name"),
		Description: r.FormValue("description"),
		Category:    r.FormValue("category"),
		Price:       parseFloat(r.FormValue("price")),
		Stock:       parseInt(r.FormValue("stock")),
		ListedStock: parseInt(r.FormValue("listed_stock")),
		Status:      r.FormValue("status"),
	}
	if in.Status == "" {
		in.Status = "active"
	}
	if lp := r.FormValue("list_price"); lp != "" {
		v := parseFloat(lp)
		in.ListPrice = &v
	}
	file, header, err := r.FormFile("image")
	if err == nil {
		defer file.Close()
		in.ImageFile = file
		in.ImageName = header.Filename
	} else if err != http.ErrMissingFile {
		return service.ProductInput{}, "圖片上傳失敗"
	}
	return in, ""
}
