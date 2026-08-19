<?php

namespace LinePay;

final class LinePayStatus {
    public const PAID = 'paid';
    public const PENDING = 'pending';
    public const CANCELLED = 'cancelled';

    private function __construct(
        private readonly string $value,
    ) {
    }

    public static function paid(): self {
        return new self(self::PAID);
    }

    public static function pending(): self {
        return new self(self::PENDING);
    }

    public static function cancelled(): self {
        return new self(self::CANCELLED);
    }

    public function value(): string {
        return $this->value;
    }

    public function is(string $value): bool {
        return $this->value === $value;
    }
}