<?php

namespace LinePay;

final class LinePayCurlTransport implements LinePayTransport {
    public function request(string $method, string $url, array $headers, string $postBody): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        if ($method === 'POST' && $postBody !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postBody);
        }
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