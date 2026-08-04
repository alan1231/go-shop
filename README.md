# PHP SHOP

基於 **PHP 8 + MySQL** 與 **Vue 3 + Vite** 的前後端分離購物網站。後台為 PHP Session 認證的傳統頁面，前台為 SPA，兩者帳號與認證機制完全分離。

## 功能總覽

### 前台（Vue 3 SPA）

| 功能 | 說明 |
|------|------|
| 商品瀏覽 | 列表、明細、庫存狀態、原價/售價顯示 |
| 會員系統 | 註冊、登入、登出（Token 認證） |
| 購物車 | 加入、增減、刪除，localStorage 共享狀態即時同步 |
| 訂單 | 下單、我的訂單、訂單明細 |
| 模擬付款 | 待付款 → 已付款 |
| 跑馬燈 | 每 30 秒自動輪詢更新 |

### 後台（PHP 頁面）

| 功能 | 說明 |
|------|------|
| 儀表板 | 統計：商品、會員、訂單數與營收 |
| 商品管理 | 新增、編輯、刪除、圖片上傳 |
| 訂單管理 | 列表、明細、狀態更新（待付款/已付款/出貨中/已完成/已取消） |
| 會員管理 | 新增、刪除、改密碼 |
| 跑馬燈管理 | 編輯前台公告文字 |

## 技術架構

- **後端**：原生 PHP 8 + MySQL（PDO），分層架構 `Controller → Service → Repository`
- **前端**：Vue 3 + Vite + Vue Router，使用原生 fetch（無 Axios）
- **認證分離**
  - 後台：PHP Session + Cookie
  - 前台：隨機 Token，存於 localStorage，透過 `Authorization: Bearer <token>` 傳遞
- **帳號分離**
  - `admin_users` 表：後台管理員
  - `users` 表：前台會員
- **資料庫**：所有 Repository 皆自動建立資料表，無需手動 SQL

## 目錄結構

```
.
├── index.php              # 進入點（載入 backend）
├── .htaccess              # 路由重寫
├── backend/
│   ├── index.php          # 路由定義與分發
│   ├── db.php             # 自動載入
│   ├── setup.php          # 初始化資料庫與預設管理員
│   ├── classes/
│   │   ├── Controllers/   # 控制器（含 Api/ 前台 API）
│   │   ├── Services/      # 商業邏輯
│   │   └── Repositories/  # 資料存取（自動建表）
│   └── views/             # 後台畫面（PHP 模板）
├── frontend/              # Vue 3 前台
│   ├── src/
│   │   ├── api/           # API 呼叫封裝
│   │   ├── router/        # 路由
│   │   ├── store/         # 共享狀態（購物車）
│   │   └── views/         # 頁面元件
│   └── vite.config.js     # Vite + proxy 設定
└── uploads/               # 商品圖片
```

## 安裝與執行

### 環境需求

- [Laragon](https://laragon.org/)（或任一 Apache + PHP 8 + MySQL 環境）
- Node.js 18+

### 後端

```bash
# 1. 將專案放入 Laragon 的 www 目錄
# 2. 建立資料庫 shop（或由 setup 自動建立）
# 3. 開啟以下網址初始化資料庫與預設管理員
#    http://localhost/php/shop/setup.php
```

預設管理員帳號：

| 帳號 | 密碼 |
|------|------|
| admin | 123456 |

後台網址：`http://localhost/php/shop/admin`

### 三方登入（Google / LINE）設定

clone 後需填入自己的 OAuth 憑證（**secret 不會進 git**）：

```bash
# 1. 產生設定檔（在專案根目錄）
cp .env.example .env

# 2. 編輯 .env，填入下列憑證
```

`.env` 需要填入的位置：

| 欄位 | 說明 | 去哪申請 |
|------|------|----------|
| `GOOGLE_CLIENT_ID` | Google OAuth Client ID | [Google Cloud Console](https://console.cloud.google.com) → 憑證 → OAuth 用戶端 ID |
| `GOOGLE_CLIENT_SECRET` | Google OAuth Client Secret | 同上（產生時才會顯示） |
| `LINE_CHANNEL_ID` | LINE Login Channel ID | [LINE Developers](https://developers.line.biz/console/) → Channel → Basic settings |
| `LINE_CHANNEL_SECRET` | LINE Login Channel Secret | 同上 |
| `OAUTH_REDIRECT_URI` | 授權回調網址 | 預設 `http://localhost:5173/auth/callback`，通常不用改 |

同時要在各平台後台設定回調網址：

- **Google**：Authorized redirect URIs 加入 `http://localhost:5173/auth/callback`
- **LINE**：LINE Login → Callback URL 加入 `http://localhost:5173/auth/callback`

### 前端

```bash
cd frontend
npm install
npm run dev
```

開發網址：`http://localhost:5173/`

Vite 已設定 proxy：`/api` 與 `/php/shop` 自動轉發至 `http://localhost/php/shop`，前端不需設定 CORS。

## API 清單

需登入的 API 需帶 Header：`Authorization: Bearer <token>`

| Method | Endpoint             | 說明                 |
|--------|----------------------|----------------------|
| POST   | `/api/auth/register` | 會員註冊             |
| POST   | `/api/auth/login`    | 登入（回傳 token）   |
| POST   | `/api/auth/logout`   | 登出                 |
| GET    | `/api/auth/me`       | 目前登入使用者       |
| GET    | `/api/products`      | 商品列表（僅上架中） |
| GET    | `/api/products/{id}` | 商品明細             |
| POST   | `/api/orders`        | 建立訂單             |
| GET    | `/api/orders`        | 我的訂單列表         |
| GET    | `/api/orders/{id}`   | 訂單明細             |
| POST   | `/api/orders/{id}/pay` | 模擬付款           |
| GET    | `/api/marquee`       | 跑馬燈內容           |

## License

MIT
