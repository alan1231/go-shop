<?php

namespace LinePay;

final class LinePayConfig {
    public const ENDPOINT_SANDBOX = 'https://sandbox-api-pay.line.me';
    public const ENDPOINT_PRODUCTION = 'https://api-pay.line.me';

    public function __construct(
        private readonly string $channelId,
        private readonly string $channelSecret,
        private readonly bool $sandbox = true,
    ) {
    }

    public function channelId(): string {
        return $this->channelId;
    }

    public function channelSecret(): string {
        return $this->channelSecret;
    }

    public function sandbox(): bool {
        return $this->sandbox;
    }

    public function endpoint(): string {
        return $this->sandbox ? self::ENDPOINT_SANDBOX : self::ENDPOINT_PRODUCTION;
    }

    public function isConfigured(): bool {
        return $this->channelId !== '' && $this->channelSecret !== '';
    }
}