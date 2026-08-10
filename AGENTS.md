# AGENTS.md

本專案為 **Go + MySQL + Vue 3** 的前後端分離購物網站，前台與後台皆為 Vue 3 SPA，由同一個 Go API 提供資料與 Token 認證。此文件提供 AI 代理在此專案工作的指引與規則。

## 專案位置

- 專案根目錄：`/Users/yuna/dev/go-shop`
- 後端：`server/`（Go 標準庫 net/http + MySQL）
- 前台：`frontend/`（Vue 3 + Vite + vue-router，dev 埠 5173）
- 後台：`frontend-admin/`（Vue 3 + Vite + vue-router + Chart.js，dev 埠 5174）
- 商品圖片：`uploads/`（前後端共用，由 Go server 提供 `/uploads/`）

## 目錄結構

```
.
├── server/                  # Go API 與靜態檔案伺服器
│   ├── cmd/server/main.go   # 進入點：config → DB → Migrate → seed 管理員 → 啟動
│   └── internal/
│       ├── config/          # 環境設定（.env，含 OAuth / LINE Pay 憑證）
│       ├── db/              # MySQL 連線與自動建表/補欄位
│       ├── httpapi/         # 前台/後台 API handler + 路由 + 認證
│       ├── repository/      # 資料存取（支援 *sql.DB / *sql.Tx）
│       ├── service/         # 商業邏輯
│       └── storage/         # 商品圖片儲存
├── frontend/                # Vue 3 前台 SPA
├── frontend-admin/          # Vue 3 後台 SPA
└── uploads/                 # 商品圖片
```

## 認證機制（重要）

前後台帳號與認證**完全分離**：

| | 後台 | 前台 |
|---|---|---|
| 資料表 | `admin_users` | `users` |
| 認證方式 | Bearer Token（存 `admin_users.token`） | Bearer Token（存 `users.token`） |
| 登入 | `POST /api/admin/login` | `POST /api/auth/login` |
| 登出 | `POST /api/admin/logout` | `POST /api/auth/logout` |

- 前台 token 存 `localStorage.token`；後台存 `localStorage.admin_token`
- 前端每次請求由 `api/index.js` 自動帶 `Authorization: Bearer <token>`
- handler 用 `requireUser()` / `requireAdmin()` 驗證（`httpapi/auth.go`）
- token 每次登入重新產生、登出即失效；無過期機制

## 資料表結構（自動遷移集中在 `internal/db/db.go`）

**users（前台會員）**：id、username、email、password（bcrypt）、role（預設 'user'）、token、provider、provider_id、phone、address、avatar、created_at；UNIQUE(provider, provider_id)

**admin_users（後台管理員）**：id、username、password、token、created_at

**products**：id、name、image（檔名）、description、category、price、list_price、stock、listed_stock、status（預設 'active'）、created_at

**orders**：id、user_id、total_amount、status（預設 'pending'）、remark、member_remark、receiver_name、receiver_phone、receiver_address、created_at

**order_items**：id、order_id、product_id、price、quantity

**marquee**：id（固定 1）、content、updated_at

**login_attempts**：ip、type、attempts、locked_until、updated_at；UNIQUE(ip, type)

## 資料流向

### 前台下單
```
Cart.vue → POST /api/orders (Bearer token)
  → ordersCreate → requireUser()
  → OrderService.CreateOrder → 開 tx → 檢查 active/庫存 → 扣 stock + listed_stock → 建單 + 明細 → commit
```

### 後台登入
```
/api/admin/login → adminLogin → AuthService.Authenticate
  → 查 admin_users → bcrypt 比對 → 產生 token 存 admin_users.token → 回傳 token
```

### 前台登入
```
/api/auth/login → authLogin
  → 查 users → bcrypt 比對 → 產生 token 存 users.token → 回傳 token
```

## 訂單狀態機

```
pending(待付款) → paid(已付款) → shipped(出貨中) → completed(已完成)
     └→ cancelled(已取消)
```

- 會員：只能 pending → paid（`POST /api/orders/{id}/pay`）
- 管理員：後台手動更新任一狀態；completed 為終態不可再變更

## 資料庫規則

- 建表集中在 `db.Migrate()`：`CREATE TABLE IF NOT EXISTS` + `SHOW COLUMNS` 檢查後 `ALTER TABLE` 補欄位
- Repository 的 `Querier` 介面同時接受 `*sql.DB` 與 `*sql.Tx`；下單在 service 內開 transaction
- 防超賣：`UPDATE products SET stock = stock - ?, listed_stock = listed_stock - ? WHERE id = ? AND stock >= ? AND listed_stock >= ?`，以 `RowsAffected == 1` 判斷成功
- 需登入的 API 必須帶 `Authorization: Bearer <token>`

## 已知陷阱（務必遵守）

1. **`http.Client{Timeout: 15}` 是 15 奈秒**：`Timeout` 是 `time.Duration`，裸數字會被當成奈秒，必須寫 `15 * time.Second`。OAuth 與 LINE Pay 都依賴它（`service/oauth.go`、`service/linepay.go`）。
2. **商品庫存欄位**：前台列表回傳 `listed_stock AS stock`，明細回傳 `listed_stock`（與歷史行為一致）；後台才看真實 `stock` / `listed_stock` 兩欄。修改時務必保持一致。
3. **商品圖片路徑**：前台 API 回 `/uploads/xxx`；`order_items.image` 只有檔名，前端需自行組 `/uploads/` + 檔名。
4. **金流用 float64**：價格計算全用 float64（DB 為 DECIMAL），可能產生小數誤差，如需嚴謹可改 int（分）。
5. **路徑穿越**：`spa()` 把使用者路徑丟給 `http.ServeFile`，Go 內建 `..` 檢查會回 400，不要自行改成 `os.Open` 直讀。
6. **圖片上傳無大小限制**：`Images.Save` 用 `io.Copy` 無上限；如要防護需 `http.MaxBytesReader`。

## 程式碼慣例

- 後端：
  - handler 不直接碰 DB，一律走 Service → Repository
  - 前台 handler 用 `requireUser()`；後台用 `requireAdmin()`（`httpapi/auth.go`）
  - API 統一 JSON 格式：`{"success":bool, "message":string, "data":...}`（`httpapi/respond.go`）
  - 路由集中在 `httpapi/app.go` 的 `routes()`
  - service 多值回傳慣例：`(值, error)`；需要「給使用者看的錯誤」時回 `(值, string, error)`，`string != ""` 即為錯誤訊息
  - 圖片 URL 組裝用 `uploadURL()` / `avatarURL()`（`httpapi/auth.go`）
- 前端：
  - 使用 vanilla fetch，**不用 Axios**
  - 共享狀態用 `reactive` store（`store/cart.js`、`store/toast.js`、`store/user.js`）
  - 圖片路徑需經 Vite proxy（`/uploads` 規則）
  - 不做任何加註解（程式碼不需要 comment）

## 常用指令

- 啟動 Go server：`cd server && go run ./cmd/server`（http://localhost:8080）
- 啟動前台 dev server：`cd frontend && npm run dev`（http://localhost:5173/）
- 啟動後台 dev server：`cd frontend-admin && npm run dev`（http://localhost:5174/admin/）
- Go 檢查：`cd server && go build ./... && go vet ./...`
- 建置 SPA：`cd frontend && npm run build`；`cd frontend-admin && npm run build`
- 預設管理員：admin / 123456
