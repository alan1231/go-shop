# GO SHOP

基於 **Go + MySQL + Vue 3** 的前後端分離購物網站。前台與後台皆為 Vue 3 SPA，由同一個 Go API 提供資料與 Token 認證。

## 功能總覽

### 前台（Vue 3）

| 功能 | 說明 |
|------|------|
| 商品瀏覽 | 商品列表、明細、搜尋、分類篩選、庫存與售價顯示 |
| 會員系統 | 註冊、登入、登出、Google / LINE 三方登入 |
| 會員中心 | 編輯聯絡資料與更改密碼 |
| 購物車 | 加入、增減、刪除、localStorage 同步與庫存上限檢查 |
| 訂單 | 建立訂單、狀態篩選、訂單明細、進度時間軸與模擬付款 |
| 其他 | 跑馬燈自動更新與響應式版面 |

### 後台（Vue 3）

| 功能 | 說明 |
|------|------|
| 儀表板 | 商品、會員、訂單與營收統計 |
| 商品管理 | 新增、編輯、圖片上傳、搜尋、分類篩選與分頁 |
| 訂單管理 | 訂單列表、明細、狀態與備註更新 |
| 會員管理 | 搜尋、新增、刪除與重設密碼 |
| 跑馬燈管理 | 編輯前台公告文字 |

## 技術架構

- **後端**：Go 標準庫 `net/http`、MySQL、Repository / Service 分層
- **前台**：Vue 3、Vite、Vue Router、原生 fetch
- **後台**：Vue 3、Vite、Vue Router、Chart.js、原生 fetch
- **認證**：前台與後台皆使用 Bearer Token，Token 分別存於 `localStorage.token` 與 `localStorage.admin_token`
- **帳號分離**：前台會員使用 `users`，後台管理員使用 `admin_users`
- **資料庫遷移**：Go server 啟動時自動建表並補齊舊表缺少的欄位
- **防超賣**：下單流程使用 transaction 與條件式庫存更新，失敗時整筆 rollback
- **靜態檔案**：正式環境由 Go server 提供兩個 SPA 與 `/uploads/` 商品圖片

## 目錄結構

```text
.
├── server/                  # Go API 與靜態檔案伺服器
│   ├── cmd/server/          # 程式進入點
│   └── internal/
│       ├── config/          # 環境設定
│       ├── db/              # MySQL 連線與自動遷移
│       ├── httpapi/         # 前台、後台 API 與路由
│       ├── repository/      # 資料存取
│       ├── service/         # 商業邏輯
│       └── storage/         # 商品圖片處理
├── frontend/                # Vue 3 前台（開發埠 5173）
├── frontend-admin/          # Vue 3 後台（開發埠 5174）
└── uploads/                 # 商品圖片
```

## 安裝與執行

### 環境需求

- Go 1.26+
- Node.js 18+
- MySQL 8+

### 1. 建立資料庫與環境設定

先建立 MySQL 資料庫：

```sql
CREATE DATABASE shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

複製環境設定：

```bash
cp .env.example .env
```

Go server 使用以下資料庫設定；未設定時會採用括號內的預設值：

| 變數 | 預設值 |
|------|--------|
| `PORT` | `8080` |
| `DB_HOST` | `127.0.0.1` |
| `DB_PORT` | `3306` |
| `DB_NAME` | `shop` |
| `DB_USER` | `root` |
| `DB_PASS` | 空字串 |

`.env` 也可填入 Google、LINE Login 與 LINE Pay 憑證。OAuth 回調網址預設為 `http://localhost:5173/auth/callback`。

### 2. 啟動 Go API

```bash
cd server
go mod download
go run ./cmd/server
```

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

### 4. 啟動 Vue 3 後台

```bash
cd frontend-admin
npm install
npm run dev
```

後台網址：`http://localhost:5174/admin/`

兩個 Vite dev server 都會將 `/api` 與 `/uploads` 代理至 `http://localhost:8080`。

## 正式環境

先建置兩個 SPA：

```bash
cd frontend && npm install && npm run build
cd ../frontend-admin && npm install && npm run build
cd ../server && go run ./cmd/server
```

Go server 會提供：

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
| POST | `/api/orders/{id}/pay` | 模擬付款 |
| GET | `/api/marquee` | 跑馬燈內容 |

### 後台 API

後台 API 以 `/api/admin` 為前綴，包含登入、儀表板統計、商品、訂單、會員與跑馬燈管理。

## License

MIT
