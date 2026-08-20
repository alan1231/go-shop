# AGENTS.md

本專案為 **PHP + MySQL + Vue 3** 的前後端分離購物網站，前台與後台皆為 Vue 3 SPA，由同一個 PHP API（vanilla PHP，無框架）提供資料與 Token 認證。此文件提供 AI 代理在此專案工作的指引與規則。

## 專案位置

- 專案根目錄：`/Users/yuna/dev/php-shop`
- 後端：`backend/`（vanilla PHP 8，無框架，相容 Apache/Laragon）
- 前台：`frontend/`（Vue 3 + Vite + vue-router，dev 埠 5173）
- 後台：`frontend-admin/`（Vue 3 + Vite + vue-router + Chart.js，dev 埠 5174）
- 商品圖片：`uploads/`（前後端共用，由 PHP server 提供 `/uploads/`）

## 目錄結構

```
.
├── backend/                   # PHP API 與靜態檔案伺服器
│   ├── index.php              # 前端控制器：路由、/uploads/、SPA 伺服
│   ├── bootstrap.php          # composer autoload → Config → DB → Migrate → 註冊 services
│   ├── .htaccess              # Apache：非檔案請求導向 index.php
│   ├── composer.json          # PSR-4 autoload（App\ → classes/）+ PHPUnit（dev）
│   ├── phpunit.xml            # PHPUnit 設定
│   ├── tests/                 # PHPUnit 測試（App\Tests\）
│   └── classes/               # 全部 class 帶 namespace（App\）
│       ├── Config.php         # 讀取 .env（含預設值與專案根目錄）
│       ├── Database.php       # PDO 單例（ERRMODE_EXCEPTION、無模擬預備語句）
│       ├── Migrate.php        # 自動建表/補欄位/seed 管理員
│       ├── Router.php         # 簡易路由（支援 {id} 參數）
│       ├── Response.php       # JSON 回應（success/fail/file）
│       ├── Support.php        # helper（float/int/nullIfEmpty/uploadUrl/avatarUrl/bearerToken/randomToken/page）
│       ├── Registry.php       # 服務容器（bootstrap 註冊，controller 取用）
│       ├── ServiceException.php  # 給使用者看的錯誤（含 HTTP 狀態碼）
│       ├── Images.php         # 商品圖片儲存
│       ├── Repositories/      # 資料存取（6 個 Repository）
│       ├── Services/          # 商業邏輯（7 個 Service）
│       └── Controllers/       # 前台/後台 handler + BaseController
├── frontend/                  # Vue 3 前台 SPA
├── frontend-admin/            # Vue 3 後台 SPA
├── linepay-php/               # 獨立 LINE Pay SDK 套件（LinePay\ namespace，可複用於其他專案）
└── uploads/                   # 商品圖片
```

## 認證機制（重要）

- 僅**後台**有帳號登入；前台**無會員登入 / 無註冊**，下單為訪客身分（`POST /api/orders` 若帶 Bearer token 會自動關聯對應會員）

| | 後台 |
|---|---|
| 資料表 | `admin_users` |
| 認證方式 | Bearer Token（存 `admin_users.token`） |
| 登入 | `POST /api/admin/login` |
| 登出 | `POST /api/admin/logout` |

- 後台 token 存 `localStorage.admin_token`
- 前端每次請求由 `api/index.js` 自動帶 `Authorization: Bearer <token>`
- controller 用 `BaseController::requireAdmin()` 驗證後台；`requireUser()` 保留給前台可選會員關聯
- token 每次登入重新產生、登出即失效；無過期機制
- 密碼用 `password_hash()` / `password_verify()`（bcrypt，相容舊的 Go bcrypt hash）

## 資料表結構（自動遷移集中在 `classes/Migrate.php`）

**users（前台會員，僅後台管理）**：id、username、email、password、role（預設 'user'）、token、provider、provider_id、phone、address、avatar、created_at；UNIQUE(provider, provider_id)

**admin_users（後台管理員）**：id、username、password、token、role（選填，權限）、created_at

**products**：id、name、image（檔名）、description、category、price、list_price、status（預設 'active'）、created_at

**orders**：id、user_id、total_amount、status（預設 'pending'）、remark、member_remark、receiver_name、receiver_phone、receiver_address、table_number、order_type、linepay_transaction_id、payment_method、created_at

**order_items**：id、order_id、product_id、price、quantity

**marquee**：id（固定 1）、content、updated_at

**settings**：setting_key、setting_value、updated_at

> 已移除電商殘留：`/api/cart/*`、`/api/auth/*`、`cart_items`、`login_attempts`（含 RateLimitService / ApiCartController / ApiAuthController / CartService / CartRepository）。不要重新引入。
> 後台不再有三方登入與會員管理：`/api/admin/oauth/*`、`/api/admin/users/*`、OAuthService、UserService、AdminUserController 已移除；`admin_users.provider/provider_id` 欄位不再使用。會員（`users` 表）仍保留給前台訂單關聯與儀表板統計。後台帳號管理走 `/api/admin/accounts`（`AdminAccountController` + `AdminAccountService`，`role` 為選填權限欄位）。

## 資料流向

### 前台訪客下單
```
Order.vue（訂單內容）→ POST /api/orders
  → ApiOrderController::create → 若帶 Bearer 且 matched 則關聯 user_id（否則 0）
  → OrderService::createOrder → PDO transaction → 檢查 product 存在且 active
  → 建單 + 明細 → commit
```

### 後台登入
```
/api/admin/login → AdminAuthController::login → AuthService::authenticate
  → 查 admin_users → password_verify 比對 → 產生 token 存 admin_users.token → 回傳 token
```

### 後台帳號管理
```
Accounts.vue → /api/admin/accounts
  → AdminAccountController::index/create/delete → AdminAccountService
  → 新增帳號可帶選填 role（權限）；刪除不得刪除自己，且至少保留一個帳號
```

## 訂單狀態機

```
pending(待付款) → paid(已付款) → shipped(出貨中) → completed(已完成)
     └→ cancelled(已取消)
```

- 會員：只能 pending → paid（`POST /api/orders/{id}/pay`）
- 管理員：後台手動更新任一狀態；completed 為終態不可再變更

## 資料庫規則

- 建表集中在 `Migrate::run()`：`CREATE TABLE IF NOT EXISTS` + `SHOW COLUMNS` 檢查後 `ALTER TABLE` 補欄位
- Repository 建構子可帶 PDO；`Database::connect()` 回傳同一個連線（單例），下單在 service 內開 transaction
- 需登入的 API 必須帶 `Authorization: Bearer <token>`

## 錯誤處理慣例

- Service 遇到「要給使用者看的錯誤」就 `throw new ServiceException('訊息', 400/401/404/429)`
- `index.php` 的例外處理器：`ServiceException` → 回傳其訊息與狀態碼；其他例外 → log + `500 伺服器錯誤`
- API 統一 JSON 格式：`{"success":bool, "message":string, "data":...}`（`Response::success` 在 data 為 null 時省略 data 欄位，與舊 Go 版 `omitempty` 一致）
- 圖片 URL 組裝用 `Support::uploadUrl()` / `Support::avatarUrl()`

## 已知陷阱（務必遵守）

1. **curl `CURLOPT_TIMEOUT` 是秒**：LINE Pay 的 `CURLOPT_TIMEOUT => 15` 是 15 秒（與 Go 版 `15 * time.Second` 一致），不要寫成毫秒。見 `linepay-php/src/LinePayCurlTransport.php`。
2. **商品**：本系統為點餐系統，`products` 無庫存欄位（stock 已全面移除），前台列表/明細不帶任何 stock 資訊，也不做庫存/超賣檢查。不要重新引入庫存欄位。
3. **商品圖片路徑**：前台 API 回 `/uploads/xxx`；`order_items.image` 只有檔名，前端需自行組 `/uploads/` + 檔名。
4. **金流用 float**：價格計算全用 float，可能產生小數誤差，如需嚴謹可改 int（分）。
5. **路徑穿越**：`serveUpload` 用 `realpath` 比對 `UPLOADS_DIR` 前綴防 `..`；`serveSpa` 用 `realpath` + `is_file`，找不到再 fallback `index.html`。不要改成直接 `file_get_contents` 組合路徑。
6. **圖片上傳無大小限制**：`Images::save` 直接讀檔案內容，如要防護需 `ini` 的 `upload_max_filesize` 或手動檢查。
7. **Config 路徑**：`Config.php` 在 `backend/classes/` 內，專案根目錄要用 `dirname(__DIR__, 2)`（跳兩層），否則 UPLOADS_DIR / PUBLIC_DIST / ADMIN_DIST 會錯指到 `backend/` 下。
8. **啟動方式**：`php -S localhost:8080 index.php` 一定要帶 router script `index.php`，否則 `/uploads/` 與 `/api` 不會經過路由。
9. **namespace 內引用 PHP 內建類別**：所有 class 都有 `namespace App\...`，在類別內用 `PDO`、`Exception`、`Throwable` 必須 `use PDO;` / `use Exception;` 或寫 `\Throwable`，否則會被解析成 `App\PDO` 等不存在的類別。

## 程式碼慣例

- 後端：
  - controller 不直接碰 DB，一律走 Service → Repository
  - 後台 controller 用 `requireAdmin()`（`Controllers/BaseController.php`）；`requireUser()` 保留給前台可選會員關聯
  - 服務物件由 `bootstrap.php` 建好後放 `Registry`，controller 用 `Registry::get('orderSvc')` 等取用
  - 路由集中在 `backend/index.php`，handler 為 `[Controller::class, 'method']`，路由參數（如 {id}）會以位置參數傳入
- 前端：
  - 使用 vanilla fetch，**不用 Axios**
  - 共享狀態用 Pinia store（`store/order.js`、`store/session.js`、`store/toast.js` 等）
  - 訂單 guest（localStorage）集中在 `store/order.js`，localStorage 讀寫抽在 `store/guestCart.js`（品項，key `shop_cart`）與 `store/guestDine.js`（桌別/內外用，key `shp_dine`）
  - 圖片路徑需經 Vite proxy（`/uploads` 規則）
  - 不做任何加註解（程式碼不需要 comment）

## 常用指令

- 安裝後端依賴（第一次 clone 後）：`cd backend && composer install`
- 一鍵啟動（MySQL 需自行開啟）：`./dev.sh`（backend :8080 + frontend :5173 + frontend-admin :5174）；停止 `./dev.sh stop`；狀態 `./dev.sh status`；看log `./dev.sh logs`（記錄在專案根目錄 `.dev.log`）
- 啟動 PHP server：`cd backend && php -S localhost:8080 index.php`（http://localhost:8080）
- 啟動前台 dev server：`cd frontend && npm run dev`（http://localhost:5173/）
- 啟動後台 dev server：`cd frontend-admin && npm run dev`（http://localhost:5174/admin/）
- PHP 檢查：`cd backend && composer lint`（或 `find backend -name '*.php' -exec php -l {} \;`）
- 執行測試：`cd backend && composer test`（PHPUnit，OrderService 交易/超賣防護用 SQLite 記憶體跑，不需 MySQL）
- 建置 SPA：`cd frontend && npm run build`；`cd frontend-admin && npm run build`
- Apache/Laragon：用 `backend/.htaccess` 將非檔案請求導向 `index.php`
- 預設管理員：admin / 123456
