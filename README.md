# SHOP

基於 **PHP + MySQL + Vue 3** 的前後端分離購物網站。前台與後台皆為 Vue 3 SPA，由同一個 PHP API（vanilla PHP，無框架）提供資料與 Token 認證。

## 功能總覽

### 前台（Vue 3）

| 功能 | 說明 |
|------|------|
| 商品瀏覽 | 首頁精選、限時優惠、最新商品、明細、搜尋、分類篩選與分頁 |
| 會員系統 | 註冊、登入、登出、Google / LINE 三方登入 |
| 會員中心 | 編輯聯絡資料與更改密碼 |
| 購物車 | 加入、增減、刪除、localStorage 同步與庫存上限檢查 |
| 訂單 | 建立訂單、狀態篩選、訂單明細、進度時間軸與付款（LINE Pay / 貨到付款） |
| 其他 | 跑馬燈自動更新、桌面／手機響應式版面與 Toast 通知 |

### 後台（Vue 3）

| 功能 | 說明 |
|------|------|
| 儀表板 | 商品、會員、訂單與營收統計 |
| 商品管理 | 新增、編輯、圖片上傳、搜尋、分類篩選與分頁 |
| 訂單管理 | 訂單列表、明細、狀態與備註更新 |
| 會員管理 | 搜尋、新增、刪除與重設密碼 |
| 跑馬燈管理 | 編輯前台公告文字 |

## 技術架構

- **後端**：vanilla PHP 8（無框架）、PDO + MySQL、Controller / Service / Repository 分層
- **前台**：Vue 3、Vite、Vue Router、Tailwind CSS、原生 fetch
- **後台**：Vue 3、Vite、Vue Router、Chart.js、原生 fetch
- **認證**：前台與後台皆使用 Bearer Token，Token 分別存於 `localStorage.token` 與 `localStorage.admin_token`
- **帳號分離**：前台會員使用 `users`，後台管理員使用 `admin_users`
- **資料庫遷移**：啟動時自動建表並補齊舊表缺少的欄位（`Migrate.php`）
- **防超賣**：下單流程使用 transaction 與條件式庫存更新，失敗時整筆 rollback
- **靜態檔案**：PHP server 提供兩個 SPA 與 `/uploads/` 商品圖片

## 目錄結構

```text
.
├── backend/                   # PHP API 與靜態檔案伺服器
│   ├── index.php              # 前端控制器：路由、/uploads/、SPA 伺服
│   ├── bootstrap.php          # autoloader → Config → DB → Migrate → 註冊 services
│   ├── .htaccess              # Apache：非檔案請求導向 index.php
│   └── classes/
│       ├── Config.php         # 讀取 .env（含預設值）
│       ├── Database.php       # PDO 單例
│       ├── Migrate.php        # 自動建表/補欄位/seed 管理員
│       ├── Router.php         # 簡易路由（支援 {id} 參數）
│       ├── Response.php       # JSON 回應
│       ├── Support.php        # helper
│       ├── Registry.php       # 服務容器
│       ├── ServiceException.php  # 給使用者看的錯誤
│       ├── Images.php         # 商品圖片儲存
│       ├── Repositories/      # 資料存取
│       ├── Services/          # 商業邏輯
│       └── Controllers/       # 前台/後台 handler + BaseController
├── frontend/                  # Vue 3 前台（開發埠 5173）
│   ├── src/components/        # 共用商品卡、登入外框與通知元件
│   ├── src/store/             # 購物車、會員與通知狀態
│   ├── src/utils/             # 金額、日期、圖片與狀態格式化
│   └── src/views/             # 首頁、商品、購物車、會員與訂單頁面
├── frontend-admin/            # Vue 3 後台（開發埠 5174）
└── uploads/                   # 商品圖片
```

## 安裝與執行

### 環境需求

- PHP 8.0+（啟用 pdo_mysql、curl 擴充）
- Node.js 18+
- MySQL 8+
- Apache（Laragon 等）或 PHP 內建伺服器

### 1. 建立資料庫與環境設定

先建立 MySQL 資料庫：

```sql
CREATE DATABASE shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

複製環境設定：

```bash
cp .env.example .env
```

PHP server 使用以下資料庫設定；未設定時會採用括號內的預設值：

| 變數 | 預設值 |
|------|--------|
| `PORT` | `8080` |
| `DB_HOST` | `127.0.0.1` |
| `DB_PORT` | `3306` |
| `DB_NAME` | `shop` |
| `DB_USER` | `root` |
| `DB_PASS` | 空字串 |

`.env` 也可填入 Google、LINE Login 與 LINE Pay 憑證。OAuth 回調網址預設為 `http://localhost:5173/auth/callback`。

### 2. 啟動 PHP API

```bash
cd backend
php -S localhost:8080 index.php
```

> 一定要帶 router script `index.php`，否則 `/uploads/` 與 `/api` 不會經過路由。

啟動時會自動遷移資料表，並在尚無管理員時建立預設帳號：

| 帳號 | 密碼 |
|------|------|
| `admin` | `123456` |

API 與圖片服務：`http://localhost:8080`

### 3. 啟動 Vue 3 前台

```bash
cd frontend
npm install
npm run dev
```

前台網址：`http://localhost:5173/`

前台的限時優惠與最新商品共用同一個商品卡元件；商品列表每頁顯示 10 件，超過 10 件才會出現分頁控制。

### 4. 啟動 Vue 3 後台

```bash
cd frontend-admin
npm install
npm run dev
```

後台網址：`http://localhost:5174/admin/`

兩個 Vite dev server 都會將 `/api` 與 `/uploads` 代理至 `http://localhost:8080`。

## 常用檢查

```bash
cd frontend && npm run build
cd ../frontend-admin && npm run build
find ../backend -name '*.php' -exec php -l {} \;
```

前台 Tailwind CSS 由 Vite/PostCSS 編入正式產物，不依賴瀏覽器端 CDN。

## 正式環境（Apache / Laragon）

先建置兩個 SPA：

```bash
cd frontend && npm install && npm run build
cd ../frontend-admin && npm install && npm run build
```

將 Apache 虛擬主機的文件根目錄指向 `backend/`，`backend/.htaccess` 會把非檔案請求導向 `index.php`，PHP server 提供：

- 前台：`http://localhost:8080/`
- 後台：`http://localhost:8080/admin/`
- API：`http://localhost:8080/api/`
- 商品圖片：`http://localhost:8080/uploads/`

## API 清單

需登入的 API 必須帶入 `Authorization: Bearer <token>`。

### 前台 API

| Method | Endpoint | 說明 |
|--------|----------|------|
| POST | `/api/auth/register` | 會員註冊 |
| POST | `/api/auth/login` | 會員登入 |
| POST | `/api/auth/oauth` | Google / LINE 登入 |
| POST | `/api/auth/logout` | 會員登出 |
| GET | `/api/auth/me` | 目前登入會員 |
| POST | `/api/auth/update` | 更新會員聯絡資料 |
| POST | `/api/auth/password` | 更改會員密碼 |
| GET | `/api/products` | 商品列表與搜尋 |
| GET | `/api/products/{id}` | 商品明細 |
| GET | `/api/categories` | 商品分類 |
| POST | `/api/orders` | 建立訂單 |
| GET | `/api/orders` | 會員訂單列表 |
| GET | `/api/orders/{id}` | 訂單明細 |
| POST | `/api/orders/{id}/pay` | 付款（LINE Pay / 貨到付款） |
| GET | `/api/orders/{id}/pay/status` | 查詢 LINE Pay 付款狀態 |
| GET | `/api/marquee` | 跑馬燈內容 |

### 後台 API

後台 API 以 `/api/admin` 為前綴，包含登入、儀表板統計、商品、訂單、會員與跑馬燈管理。

## LINE Pay 付款整合

本專案整合 **LINE Pay Online API v3**（`/v3/payments/*`），支援兩種付款方式：

| 方式 | `payment_method` 值 | 前端行為 |
|------|---------------------|----------|
| LINE Pay | `linepay` | 顯示 QR Code + 彈窗付款，伺服器輪詢確認後標為已付款 |
| 貨到付款 | `cod` | 直接標為已付款 |

### 申請流程（正式環境）

1. 到 LINE 官方合作平台申請 **LINE Pay 商家（Merchant）** 資格，需提供公司/統編等商家資料並完成簽約審核。
2. 取得一組 **Channel ID** 與 **Channel Secret**（商家的付款金鑰，非 LINE Login 的憑證）。
3. 設定 **Payment Confirm URL / Cancel URL**（可留空，本專案不做跳轉，改由伺服器輪詢確認）。
4. 若需使用實體 POS / 掃碼設備，另申請 **Offline API** 權限；一般網頁收款使用 Online API 即可。
5. 正式環境建議聯絡 LINE Pay 業務團隊完成整合測試後再上線。

### 環境變數（`.env`）

| 變數 | 說明 |
|------|------|
| `LINE_PAY_CHANNEL_ID` | 商家 Channel ID |
| `LINE_PAY_CHANNEL_SECRET` | 商家 Channel Secret |
| `LINE_PAY_SANDBOX` | `true` 使用沙箱（`sandbox-api-pay.line.me`），`false` 使用正式（`api-pay.line.me`） |

沒填憑證時，使用 LINE Pay 付款會在 API 回傳「LINE Pay 尚未設定」。

### 付款流程

```
前端 OrderDetail.vue                    PHP 後端          LINE Pay
─────────────────────                ────────────      ──────────
按下「LINE Pay」
      │  POST /api/orders/{id}/pay
      │  body: { method: 'linepay' } ──→ startLinePay()
      │                                  │  1. 寫入 payment_method='linepay'
      │                                  │  2. request() 建立付款請求
      │                                  │      （amount/currency/orderId/packages/redirectUrls）
      │ ◄── paymentAccessToken、paymentUrl、transactionId
產生 QR Code（paymentAccessToken）
開啟付款彈窗（paymentUrl，以目前視窗為中心）
      │
      │ 每 3 秒 GET /api/orders/{id}/pay/status ──→ checkLinePay()
      │                                          checkStatus(transactionId)
      │                                          │  returnCode 判定：
      │                                          │  0000 尚未認證 → 繼續等待
      │                                          │  0110 認證完成 → confirm()
      │                                          │  0123 已付款   → 不重複 confirm
      │                                          │  0121/0122 取消/失敗 → 結束輪詢
      │ ◄── status: 'paid'  → 前端停止輪詢、關閉彈窗、刷新頁面
```

- **金額計算**：以訂單明細 `price × quantity` 加總、四捨五入為整數（TWD），`request()` 與 `confirm()` 使用相同金額，避免金額不一致錯誤（returnCode `1153`）。
- **確認（confirm）**：收到 `0110` 後呼叫 confirm 才能真正完成付款；若 confirm 失敗會再查一次狀態，避免並發輪詢誤判。
- **QR Code**：以 `paymentAccessToken` 產生（僅正式環境可用 LINE App 掃描；沙箱不支援掃描）。
- **付款彈窗**：LINE Pay 網頁不允許被 iframe 嵌入（anti-clickjacking），因此使用 `window.open` 開獨立彈窗，置中以目前瀏覽器視窗為基準；付款成功後由輪詢偵測並自動關閉。
- **沙箱限制**：沙箱環境不支援 LINE App 掃描 QR Code 與 `line://` deep link，只能開啟網頁付款頁。

### 付款狀態回碼（checkStatus 判定）

| returnCode | 意義 | 本專案處理 |
|------------|------|------------|
| `0000` | 尚未完成 LINE Pay 認證 | 繼續輪詢 |
| `0110` | 認證完成，可進行付款確認 | 呼叫 confirm |
| `0121` | 使用者取消或超時 | 結束輪詢，提示重新操作 |
| `0122` | 付款失敗 | 結束輪詢，提示重新操作 |
| `0123` | 付款已完成（終態） | 直接標為已付款 |
| 其他 / 空值 | 連線或伺服器錯誤 | 繼續輪詢（或回傳等待） |

### 相關檔位

- 後端：`backend/classes/Services/LinePayService.php`（簽章與 HTTP 呼叫）、`backend/classes/Services/OrderService.php`（`startLinePay` / `checkLinePay` / `payWithCashOnDelivery`）、`backend/classes/Controllers/ApiOrderController.php`（`pay` / `payStatus`）
- 資料表：`orders.linepay_transaction_id`（LINE Pay 交易 ID）、`orders.payment_method`（`linepay` / `cod`）
- 前端：`frontend/src/views/OrderDetail.vue`（付款按鈕、QR Code、彈窗、輪詢）、`frontend/src/store/order.js`（`pay` / `startPolling` / `stopPolling`）
- 顯示付款方式：`frontend/src/utils/format.js:paymentMethodLabel()`、後台 `frontend-admin/src/views/Orders.vue`、`frontend-admin/src/views/OrderDetail.vue`

> 注意：LINE Pay 商家金鑰請存放在 `.env`（已在 `.gitignore`），切勿提交至版本控管。正式金鑰與沙箱金鑰不同，上線前務必改為正式值並將 `LINE_PAY_SANDBOX` 設為 `false`。

## License

MIT
