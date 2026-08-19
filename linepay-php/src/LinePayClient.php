<?php

namespace LinePay;

class LinePayClient {
    public function __construct(
        private readonly LinePayConfig $config,
        private readonly ?LinePayTransport $transport = null,
    ) {
    }

    public function isConfigured(): bool {
        return $this->config->isConfigured();
    }

    public function isSandbox(): bool {
        return $this->config->sandbox();
    }

    public function request(array $body): LinePayRawResult {
        return $this->send('POST', '/v3/payments/request', $body);
    }

    public function confirm(string $transactionId, int $amount): LinePayRawResult {
        return $this->send('POST', '/v3/payments/' . $transactionId . '/confirm', [
            'amount' => $amount,
            'currency' => 'TWD',
        ]);
    }

    public function refund(string $transactionId, int $amount): LinePayRawResult {
        return $this->send('POST', '/v3/payments/' . $transactionId . '/refund', [
            'refundAmount' => $amount,
        ]);
    }

    public function checkStatus(string $transactionId): LinePayRawResult {
        return $this->send('GET', '/v3/payments/requests/' . $transactionId . '/check', []);
    }

    public static function signature(string $channelSecret, string $path, string $body, string $nonce): string {
        return base64_encode(hash_hmac(
            'sha256',
            $channelSecret . $path . $body . $nonce,
            $channelSecret,
            true
        ));
    }

    private function send(string $method, string $path, array $body): LinePayRawResult {
        $nonce = self::nonce();
        $postBody = $method === 'POST' ? json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        $signature = self::signature($this->config->channelSecret(), $path, $postBody, $nonce);

        $transport = $this->transport ?? new LinePayCurlTransport();
        $resp = $transport->request($method, $this->config->endpoint() . $path, [
            'Content-Type: application/json',
            'X-LINE-ChannelId: ' . $this->config->channelId(),
            'X-LINE-Authorization: ' . $signature,
            'X-LINE-Authorization-Nonce: ' . $nonce,
        ], $postBody);

        $httpCode = (int)($resp['http_code'] ?? 0);
        $returnCode = (string)($resp['returnCode'] ?? '');
        $returnMessage = (string)($resp['returnMessage'] ?? ($httpCode > 0 ? 'HTTP ' . $httpCode : '網路錯誤'));
        $info = isset($resp['info']) && is_array($resp['info']) ? $resp['info'] : [];
        return new LinePayRawResult($returnCode, $returnMessage, $info, $httpCode);
    }

    private static function nonce(): string {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}