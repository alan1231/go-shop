<?php
// 三方登入服務：處理 Google / LINE 的授權 code → 換 token → 取使用者資料
class OAuthService {
    private array $config;

    public function __construct() {
        $this->config = require __DIR__ . '/../../oauth-config.php';
    }

    // 依 provider 取得使用者資料，成功回傳 ['provider_id','email','name']，失敗回傳 null
    public function getUserInfo(string $provider, string $code): ?array {
        return match ($provider) {
            'google' => $this->google($code),
            'line'   => $this->line($code),
            default  => null,
        };
    }

    private function google(string $code): ?array {
        $cfg = $this->config['google'];
        $token = $this->httpPost('https://oauth2.googleapis.com/token', [
            'code'          => $code,
            'client_id'     => $cfg['client_id'],
            'client_secret' => $cfg['client_secret'],
            'redirect_uri'  => $cfg['redirect_uri'],
            'grant_type'    => 'authorization_code',
        ]);

        if (!isset($token['access_token'])) return null;

        $info = $this->httpGet('https://www.googleapis.com/oauth2/v2/userinfo', $token['access_token']);
        if (!isset($info['id'])) return null;

        return [
            'provider_id' => (string)$info['id'],
            'email'       => $info['email'] ?? '',
            'name'        => $info['name'] ?? '',
        ];
    }

    private function line(string $code): ?array {
        $cfg = $this->config['line'];
        $token = $this->httpPost('https://api.line.me/oauth2/v2.1/token', [
            'code'          => $code,
            'client_id'     => $cfg['channel_id'],
            'client_secret' => $cfg['channel_secret'],
            'redirect_uri'  => $cfg['redirect_uri'],
            'grant_type'    => 'authorization_code',
        ]);

        if (!isset($token['access_token'])) return null;

        $info = $this->httpGet('https://api.line.me/v2/profile', $token['access_token']);
        if (!isset($info['userId'])) return null;

        return [
            'provider_id' => (string)$info['userId'],
            'email'       => $info['email'] ?? '',
            'name'        => $info['displayName'] ?? '',
        ];
    }

    private function httpPost(string $url, array $data): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        return json_decode($res ?: '', true) ?: [];
    }

    private function httpGet(string $url, string $token): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        return json_decode($res ?: '', true) ?: [];
    }
}
