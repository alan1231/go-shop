package httpapi

import (
	"net/http"

	"shop/internal/repository"
)

func productPublicPayload(p repository.Product) map[string]any {
	return map[string]any{
		"id":          p.ID,
		"name":        p.Name,
		"image":       nilIfEmptyStr(uploadURL(p.Image)),
		"description": p.Description,
		"category":    nilIfEmptyStr(p.Category),
		"price":       p.Price,
		"list_price":  p.ListPrice,
		"stock":       p.Stock,
		"status":      p.Status,
	}
}

func productShowPayload(p repository.Product) map[string]any {
	return map[string]any{
		"id":          p.ID,
		"name":        p.Name,
		"image":       nilIfEmptyStr(uploadURL(p.Image)),
		"description": p.Description,
		"price":       p.Price,
		"list_price":  p.ListPrice,
		"stock":       p.Stock,
	}
}

func (a *App) productsIndex(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query().Get("q")
	category := r.URL.Query().Get("category")
	page := max(1, queryInt(r, "page", 1))
	perPage := max(1, queryInt(r, "per_page", 10))

	paged, err := a.ProductSvc.GetActivePage(q, category, page, perPage)
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	items := make([]map[string]any, 0, len(paged.Items))
	for _, p := range paged.Items {
		items = append(items, productPublicPayload(p))
	}
	success(w, map[string]any{
		"items":       items,
		"total":       paged.Total,
		"page":        paged.Page,
		"per_page":    paged.PerPage,
		"total_pages": paged.TotalPages,
	}, "ok")
}

func (a *App) productsCategories(w http.ResponseWriter, r *http.Request) {
	cats, err := a.ProductSvc.GetCategories()
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	success(w, cats, "ok")
}

func (a *App) productsShow(w http.ResponseWriter, r *http.Request) {
	id := pathInt(r)
	p, err := a.ProductSvc.GetByID(id)
	if err != nil {
		fail(w, "伺服器錯誤", http.StatusInternalServerError)
		return
	}
	if p == nil || p.Status != "active" {
		fail(w, "商品不存在", http.StatusNotFound)
		return
	}
	success(w, productShowPayload(*p), "ok")
}
