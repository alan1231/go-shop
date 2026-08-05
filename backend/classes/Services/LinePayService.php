<?php
// LINE Pay 支付服務：request（預授權）/ confirm（確認扣款）/ refund（退款）
class LinePayService {
    private string $channelId;
    private string $channelSecret;
    private string $host;

    public function __construct() {
        $this->channelId     = getenv('LINE_PAY_CHANNEL_ID') ?: '';
        $this->channelSecret = getenv('LINE_PAY_CHANNEL_SECRET') ?: '';
        $isSandbox = strtolower(getenv('LINE_PAY_SANDBOX') ?: 'true') !== 'false';
        $this->host = $isSandbox ? 'https://sandbox-api-pay.line.me' : 'https://api-pay.line.me';
    }

    // 憑證是否已設定（未設定則回傳有關於是否可用的判斷）
    public function isConfigured(): bool {
        return $this->channelId !== '' && $this->channelSecret !== '';
    }

    // 請求付款（預授權），回傳 LINE 的 transactionId 與 paymentUrl
    public function request(array $order, string $confirmUrl, string $cancelUrl): array {
        return $this->post('/v3/payments/request', [
            'amount'   => $order['amount'],
            'currency' => 'TWD',
            'orderId'  => $order['orderId'],
            'packages' => [[
                'id'     => '1',
                'amount' => $order['amount'],
                'name'   => $order['packageName'] ?? 'SHOP 訂單',
                'products' => $order['products'],
            ]],
            'redirectUrls' => [
                'confirmUrl' => $confirmUrl,
                'cancelUrl'  => $cancelUrl,
            ],
        ]);
    }

    // 確認付款（真正扣款）
    public function confirm(string $transactionId, int $amount): array {
        return $this->post('/v3/payments/' . $transactionId . '/confirm', [
            'amount'   => $amount,
            'currency' => 'TWD',
        ]);
    }

    // 退款
    public function refund(string $transactionId, int $amount): array {
        return $this->post('/v3/payments/' . $transactionId . '/refund', [
            'refundAmount' => $amount,
        ]);
    }

    private function post(string $path, array $body): array {
        $ch = curl_init($this->host . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-LINE-ChannelId: ' . $this->channelId,
                'X-LINE-ChannelSecret: ' . $this->channelSecret,
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $res  = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        $data = json_decode($res ?: '', true) ?: [];
        $data['http_code'] = $code;
        return $data;
    }
}