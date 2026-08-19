<?php

namespace LinePay;

final class LinePayRawResult {
    public function __construct(
        private readonly string $returnCode,
        private readonly string $returnMessage,
        private readonly array $info,
        private readonly int $httpCode,
    ) {
    }

    public function returnCode(): string {
        return $this->returnCode;
    }

    public function returnMessage(): string {
        return $this->returnMessage;
    }

    /**
     * @return array<string, mixed>
     */
    public function info(): array {
        return $this->info;
    }

    public function httpCode(): int {
        return $this->httpCode;
    }

    public function isSuccess(): bool {
        return $this->returnCode === '0000';
    }
}