<?php

namespace LinePay;

final class LinePayStartResult {
    public function __construct(
        private readonly string $transactionId,
        private readonly string $paymentAccessToken,
        private readonly string $paymentUrlWeb,
        private readonly string $paymentUrlApp,
        private readonly bool $sandbox,
    ) {
    }

    public function transactionId(): string {
        return $this->transactionId;
    }

    public function paymentAccessToken(): string {
        return $this->paymentAccessToken;
    }

    public function paymentUrlWeb(): string {
        return $this->paymentUrlWeb;
    }

    public function paymentUrlApp(): string {
        return $this->paymentUrlApp;
    }

    public function isSandbox(): bool {
        return $this->sandbox;
    }
}