# linepay-php

LINE Pay（v3）PHP SDK。framework-agnostic、零相依（僅需 PHP 8.2+、ext-curl、ext-json），可放入任何 PHP 專案。

包含：

- HTTP 客戶端（HMAC-SHA256 簽章、Nonce、沙箱/正式環境切換）
- 付款流程（request / confirm / refund / check）
- 回碼判定狀態機（`0000` / `0110` / `0123` / `0121` / `0122`），含 confirm 失敗後的 recheck 併發保護

## 安裝

### 複製目錄到專案

直接把 `linepay-php/` 複製到目標專案，在 `composer.json` 以 path repository 引用：

```json
{
    "repositories": [
        { "type": "path", "url": "../linepay-php", "options": { "symlink": false } }
    ],
    "require": {
        "linepay/php-sdk": "^1.0"
    }
}
```

接著 `composer update linepay/php-sdk`。

## 使用

```php
use LinePay\LinePayConfig;
use LinePay\LinePayClient;
use LinePay\LinePayGateway;
use LinePay\LinePayOrder;
use LinePay\LinePayProduct;

$config = new LinePayConfig(
    $channelId,   // LINE Pay 商家 Channel ID
    $channelSecret,
    true,         // true = 沙箱 (sandbox-api-pay.line.me)，false = 正式
);
$gateway = new LinePayGateway(new LinePayClient($config));

if (!$gateway->isConfigured()) {
    // 尚未設定金鑰，提示使用者
}

// 1. 建立付款請求
$order = new LinePayOrder(
    amount: 1200,              // int，全部商品金額（未打折原始總額）
    orderId: 'SHOP-0000000042',
    packageName: '購物訂單 #42',
    products: [
        new LinePayProduct('9', '蛋', 2, 150),
        new LinePayProduct('14', '001', 1, 900),
    ],
);
$result = $gateway->start($order, $confirmUrl, $cancelUrl);
// $result->paymentUrlWeb()   網頁付款頁（可開彈窗）
// $result->paymentUrlApp()   LINE App 付款（僅正式環境可用）
// $result->paymentAccessToken()  可用來產生 QR Code
// $result->transactionId()   務必存進你的訂單資料，後續 check/capture 用

// 2. 輪詢付款狀態（前端每 ~3 秒呼叫一次對應的後端 API）
$status = $gateway->capture($result->transactionId(), 1200);
switch ($status->value()) {
    case LinePayStatus::PAID:
        // 標記訂單已付款
        break;
    case LinePayStatus::CANCELLED:
        // 付款已取消
        break;
    case LinePayStatus::PENDING:
        // 仍在等待使用者付款
        break;
}

// 3. 退款（如需要）
$refund = $gateway->refund($result->transactionId(), 1200);
if (!$refund->isSuccess()) {
    // 退款失敗
}
```

成功時 `start()` 回傳 `LinePayStartResult`；失敗時丟出 `LinePayException`（`getMessage()` / `returnCode()` / `httpCode()`）。

## 回碼狀態機（`capture()`）

`capture()` 已是完整「查詢 → 確認 → 失敗 recheck」的流程：

| checkStatus 回碼 | 動作 | 結果 |
|---|---|---|
| `0123` | 直接視為已付款（不可再 confirm） | `paid` |
| `0110` | confirm；`0000` → paid；失敗再 recheck 一次，`0123` → paid | `paid` / `pending` |
| `0121` / `0122` | 付款已取消/作廢 | `cancelled` |
| `0000` / 其他 | 等待使用者付款 | `pending` |

## 測試

```bash
cd linepay-php
php ../backend/vendor/bin/phpunit -c phpunit.xml
```

`phpunit.xml` 的 bootstrap 會自動找 host 專案的 `vendor/autoload.php`（依次嘗試 SDK 內 `../vendor/`、本 repo 的 `../backend/vendor/`、目前目錄的 `vendor/`）。複製到其他專案後若找不到，請用環境變數指定：

```bash
LINE_PAY_SDK_AUTOLOAD=/path/to/your/vendor/autoload.php php vendor/bin/phpunit -c phpunit.xml
```

## 金鑰安全

Channel ID / Secret 請放環境變數或 `.env`（勿 commit）。此 SDK 不在任何地方記錄金鑰；`LinePayConfig` 只存於記憶體。