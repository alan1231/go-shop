<?php

class LinePayService {
    private string $channelId;
    private string $channelSecret;
    private string $host;

    public function __construct(string $channelId, string $channelSecret, string $sandbox) {
        $this->channelId = $channelId;
        $this->channelSecret = $channelSecret;
        $this->host = strtolower($sandbox) === 'false'
            ? 'https://api-pay.line.me'
            : 'https://sandbox-api-pay.line.me';
    }

    public function isConfigured(): bool {
        return $this->channelId !== '' && $this->channelSecret !== '';
    }

    public function request(array $order, string $confirmUrl, string $cancelUrl): array {
        $body = [
            'amount' => $order['amount'],
            'currency' => 'TWD',
            'orderId' => $order['orderId'],
            'packages' => [[
                'id' => '1',
                'amount' => $order['amount'],
                'name' => $order['packageName'],
                'products' => $order['products'],
            ]],
            'redirectUrls' => [
                'confirmUrl' => $confirmUrl,
                'cancelUrl' => $cancelUrl,
            ],
        ];
        return $this->post('/v3/payments/request', $body);
    }

    public function confirm(string $transactionId, int $amount): array {
        return $this->post('/v3/payments/' . $transactionId . '/confirm', [
            'amount' => $amount,
            'currency' => 'TWD',
        ]);
    }

    public function refund(string $transactionId, int $amount): array {
        return $this->post('/v3/payments/' . $transactionId . '/refund', [
            'refundAmount' => $amount,
        ]);
    }

    private function post(string $path, array $body): array {
        $ch = curl_init($this->host . $path);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-LINE-ChannelId: ' . $this->channelId,
                'X-LINE-ChannelSecret: ' . $this->channelSecret,
            ],
            CURLOPT_POSTFIELDS => json_encode($body),
        ]);
        $resp = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        $json = $resp === false ? [] : json_decode($resp, true);
        if (!is_array($json)) {
            $json = [];
        }
        $json['http_code'] = $status;
        return $json;
    }
}
