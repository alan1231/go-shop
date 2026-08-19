<?php

namespace LinePay;

final class LinePayException extends \Exception {
    public function __construct(
        string $message,
        private readonly string $returnCode = '',
        private readonly int $httpCode = 0,
    ) {
        parent::__construct($message);
    }

    public function returnCode(): string {
        return $this->returnCode;
    }

    public function httpCode(): int {
        return $this->httpCode;
    }
}