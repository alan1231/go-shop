<?php

namespace LinePay;

interface LinePayTransport {
    /**
     * @param string[] $headers
     * @return array<string, mixed> decoded JSON body plus "http_code" key
     */
    public function request(string $method, string $url, array $headers, string $postBody): array;
}