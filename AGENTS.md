# AGENTS.md

本專案為 PHP + MySQL 後台 + Vue 3 前台的購物網站。此文件提供 AI 代理在此專案工作的指引與規則。

## 專案位置

- 專案根目錄：`C:\laragon\www\php\shop`
- 後端：`backend/`（PHP 8 + MySQL PDO）
- 前端：`frontend/`（Vue 3 + Vite + vue-router）
- 商品圖片：`uploads/`（前後端共用）

## 目錄結構

```
.
├── index.php              # 進入點：require backend/index.php
├── .htaccess              # 非檔案請求 → index.php
├── backend/
│   ├── index.php          # 路由定義 + 分發
│   ├── db.php             # PSR-0 自動載入
│   ├── setup.php          # 初始化 DB + 預設管理員
│   ├── classes/
│   │   ├── Router.php     # 簡易路由器（{id} 佔位符）
│   │   ├── Database.php   # PDO Singleton（EMULATE_PREPARES=false）
│   │   ├── Auth.php       # 後台 Session 認證
│   │   ├── Controllers/
│   │   │   ├── Controller.php        # 後台基底（render/notFound/forbidden/badRequest）
│   │   │   ├── Api/ApiController.php # API 基底（json/success/error/requireAuth）
│   │   │   ├── Api/*.php             # 前台 API 控制器
│   │   │   └── *.php                # 後台頁面控制器
│   │   ├── Services/      # 商業邏輯
│   │   └── Repositories/  # 資料存取（自動建表）
│   └── views/             # 後台 PHP 模板（admin-*.php）
├── frontend/
│   ├── vite.config.js     # proxy: /api, /php/shop → http://localhost/php/shop
│   └── src/
│       ├── main.js
│       ├── App.vue        # 主布局（nav、跑馬燈、footer）
│       ├── api/index.js   # fetch 封裝
│       ├── router/index.js
│       ├── store/cart.js  # 購物車 reactive store
│       └── views/         # 頁面元件
└── uploads/               # 商品圖片
```

## 認證機制（重要）

前後台帳號與認證**完全分離**：

| | 後台 | 前台 |
|---|---|---|
| 資料表 | `admin_users` | `users` |
| 認證方式 | PHP Session + Cookie | Token（`Authorization: Bearer`） |
| 登入頁 | `/php/shop/login`（表單 POST） | `POST /api/auth/login`（JSON body） |
| 登出 | 銷毀 session | 清除 users.token |

- 前端登入 token 存 `localStorage.token`
- 前端每次請求由 `frontend/src/api/index.js` 自動帶 `Authorization: Bearer <token>`
- 後台 Controller 用 `Auth::check()` / `Auth::user()` 驗證
- 前台 API 用 `ApiController::requireAuth()` 驗證 token

## 資料表結構

**users（前台會員）**：id PK、username、email、password（雜湊）、role（預設 'user'）、token VARCHAR(64)、created_at

**admin_users（後台管理員）**：id PK、username、password、created_at

**products**：id PK、name、image（檔名）、description、price、list_price、stock、listed_stock、status（'active'）、created_at

**orders**：id PK、user_id、total_amount、status（預設 'pending'）、created_at

**order_items**：id PK、order_id、product_id、price、quantity

**marquee**：id PK、content

## 資料流向

### 前台下單
```
Cart.vue → POST /api/orders (Bearer token)
  → ApiOrderController::create → requireAuth()
  → OrderService::createOrder → 檢查庫存/算總價
  → OrderRepository::createOrder → 建單 (status=pending)
  → ProductRepository::decreaseStock → 扣 stock + listed_stock
```

### 後台登入
```
/login → AuthController::login → AuthService::authenticate
  → 查 admin_users → password_verify → 寫 $_SESSION → 導向 /admin
```

### 前台登入
```
/api/auth/login → ApiAuthController::login
  → 查 users → password_verify → 產生 random token → 存 users.token → 回傳 token
```

## 訂單狀態機

```
pending(待付款) → paid(已付款) → shipped(出貨中) → completed(已完成)
     └→ cancelled(已取消)
```

- 會員：只能 pending → paid（模擬付款 `POST /api/orders/{id}/pay`）
- 管理員：後台手動更新任一狀態

## 資料庫規則

- 每個 Repository 的 `ensureTable()` 自動建表，不寫手動 SQL
- 舊表缺欄位時，用 `SHOW COLUMNS` 檢查後再 `ALTER TABLE`（參考 `UserRepository::ensureTable`）
- `Database::connect()` 為 Singleton，`PDO::ATTR_EMULATE_PREPARES = false`

## 已知陷阱（務必遵守）

1. **PDO 禁用參數重用**：`EMULATE_PREPARES = false` 時，SQL 中同名 named parameter（如 `:qty` 出現兩次）會報 `HY093`。必須用不同名稱（`:qty1` / `:qty2`）或改用 positional placeholder。
2. **PHP 不解析 JSON body**：`$_POST` 只支援 form-urlencoded。前台 API 一律用 `json_decode(file_get_contents('php://input'), true)` 讀取（參考 `ApiAuthController::jsonBody`）。
3. **商品圖片路徑**：API 回傳 `/php/shop/uploads/xxx`（BASE_URL 前綴）；`order_items` 的 `image` 只有檔名，前端需自行組 `/php/shop/uploads/` + 檔名。

## 程式碼慣例

- 後端：
  - 控制器不直接碰 DB，一律走 Service → Repository
  - 後台 Controller 繼承 `Controller`；前台 API 繼承 `ApiController`
  - API 統一 JSON 格式：`{"success":bool, "message":string, "data":...}`
  - 類別自動載入依 PSR-0 搜尋 `classes/` 底下多個目錄
- 前端：
  - 使用 vanilla fetch，**不用 Axios**
  - 共享狀態用 `reactive` store（`store/cart.js`），購物車存 localStorage 並即時同步
  - 圖片路徑需經 Vite proxy（`/php/shop` 規則）
  - 不做任何加註解（程式碼不需要 comment）

## 常用指令

- 啟動前端 dev server：`cd frontend && npm run dev`（http://localhost:5173/）
- PHP 語法檢查：`php -l <file>`
- 前端依賴：`cd frontend && npm install`
- 後台初始化：http://localhost/php/shop/setup.php
- 預設管理員：admin / 123456
