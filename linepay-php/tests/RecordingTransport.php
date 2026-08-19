<?php

namespace LinePay\Tests;

use LinePay\LinePayTransport;
use PHPUnit\Framework\TestCase;

final class RecordingTransport implements LinePayTransport {
    /**
     * @var array<int, mixed>
     */
    public array $calls = [];

    public function __construct(
        private readonly array $response,
    ) {
    }

    public function request(string $method, string $url, array $headers, string $postBody): array {
        $this->calls[] = [
            'method' => $method,
            'url' => $url,
            'headers' => $headers,
            'body' => $postBody,
        ];
        return $this->response;
    }
}