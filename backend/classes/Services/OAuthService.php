<?php

namespace App\Services;

use App\ServiceException;

class OAuthService {
    private string $googleClientId;
    private string $googleClientSecret;
    private string $lineChannelId;
    private string $lineChannelSecret;
    private string $oauthRedirectUri;

    public function __construct(
        string $googleClientId,
        string $googleClientSecret,
        string $lineChannelId,
        string $lineChannelSecret,
        string $oauthRedirectUri
    ) {
        $this->googleClientId = $googleClientId;
        $this->googleClientSecret = $googleClientSecret;
        $this->lineChannelId = $lineChannelId;
        $this->lineChannelSecret = $lineChannelSecret;
        $this->oauthRedirectUri = $oauthRedirectUri;
    }

    public function getUserInfo(string $provider, string $code): array {
        $provider = strtolower($provider);
        if ($provider === 'google') {
            return $this->google($code);
        }
        if ($provider === 'line') {
            return $this->line($code);
        }
        throw new ServiceException('三方登入驗證失敗', 401);
    }

    private function google(string $code): array {
        $token = $this->httpPostForm('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $this->googleClientId,
            'client_secret' => $this->googleClientSecret,
            'redirect_uri' => $this->oauthRedirectUri,
            'grant_type' => 'authorization_code',
        ]);
        if (empty($token['access_token'])) {
            throw new ServiceException('三方登入驗證失敗', 401);
        }
        $info = $this->httpGetJson('https://www.googleapis.com/oauth2/v2/userinfo', (string)$token['access_token']);
        $id = (string)($info['id'] ?? '');
        if ($id === '') {
            throw new ServiceException('三方登入驗證失敗', 401);
        }
        return [
            'provider_id' => $id,
            'email' => (string)($info['email'] ?? ''),
            'name' => (string)($info['name'] ?? ''),
            'avatar' => (string)($info['picture'] ?? ''),
        ];
    }

    private function line(string $code): array {
        $token = $this->httpPostForm('https://api.line.me/oauth2/v2.1/token', [
            'code' => $code,
            'client_id' => $this->lineChannelId,
            'client_secret' => $this->lineChannelSecret,
            'redirect_uri' => $this->oauthRedirectUri,
            'grant_type' => 'authorization_code',
        ]);
        if (empty($token['access_token'])) {
            throw new ServiceException('三方登入驗證失敗', 401);
        }
        $info = $this->httpGetJson('https://api.line.me/v2/profile', (string)$token['access_token']);
        $uid = (string)($info['userId'] ?? '');
        if ($uid === '') {
            throw new ServiceException('三方登入驗證失敗', 401);
        }
        return [
            'provider_id' => $uid,
            'email' => '',
            'name' => (string)($info['displayName'] ?? ''),
            'avatar' => (string)($info['pictureUrl'] ?? ''),
        ];
    }

    public function fetchAvatar(string $avatarUrl): array {
        $ch = curl_init($avatarUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $contentType = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        if ($status !== 200 || $body === false || $body === '') {
            throw new ServiceException('三方登入驗證失敗', 401);
        }
        $ext = 'jpg';
        if (str_contains($contentType, 'png')) {
            $ext = 'png';
        } elseif (str_contains($contentType, 'gif')) {
            $ext = 'gif';
        } elseif (str_contains($contentType, 'webp')) {
            $ext = 'webp';
        }
        return [$body, $ext];
    }

    private function httpPostForm(string $url, array $fields): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $resp = curl_exec($ch);
        if ($resp === false) {
            return [];
        }
        $json = json_decode($resp, true);
        return is_array($json) ? $json : [];
    }

    private function httpGetJson(string $url, string $token): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
        ]);
        $resp = curl_exec($ch);
        if ($resp === false) {
            return [];
        }
        $json = json_decode($resp, true);
        return is_array($json) ? $json : [];
    }
}
