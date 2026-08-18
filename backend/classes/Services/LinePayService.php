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

    public function isSandbox(): bool {
        return $this->host === 'https://sandbox-api-pay.line.me';
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

    public function checkStatus(string $transactionId): array {
        return $this->get('/v3/payments/requests/' . $transactionId . '/check');
    }

    private function get(string $path): array {
        $nonce = $this->nonce();
        $signature = base64_encode(hash_hmac(
            'sha256',
            $this->channelSecret . $path . $nonce,
            $this->channelSecret,
            true
        ));

        $ch = curl_init($this->host . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-LINE-ChannelId: ' . $this->channelId,
                'X-LINE-Authorization: ' . $signature,
                'X-LINE-Authorization-Nonce: ' . $nonce,
            ],
        ]);
        return $this->execute($ch);
    }

    private function post(string $path, array $body): array {
        $jsonBody = json_encode($body);
        $nonce = $this->nonce();
        $signature = base64_encode(hash_hmac(
            'sha256',
            $this->channelSecret . $path . $jsonBody . $nonce,
            $this->channelSecret,
            true
        ));

        $ch = curl_init($this->host . $path);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-LINE-ChannelId: ' . $this->channelId,
                'X-LINE-Authorization: ' . $signature,
                'X-LINE-Authorization-Nonce: ' . $nonce,
            ],
            CURLOPT_POSTFIELDS => $jsonBody,
        ]);
        return $this->execute($ch);
    }

    private function execute($ch): array {
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

    private function nonce(): string {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
