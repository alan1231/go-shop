<?php

namespace LinePay;

final class LinePayOrder {
    /**
     * @param LinePayProduct[] $products
     */
    public function __construct(
        private readonly int $amount,
        private readonly string $orderId,
        private readonly string $packageName,
        private readonly array $products = [],
    ) {
    }

    public function amount(): int {
        return $this->amount;
    }

    public function orderId(): string {
        return $this->orderId;
    }

    public function packageName(): string {
        return $this->packageName;
    }

    /**
     * @return LinePayProduct[]
     */
    public function products(): array {
        return $this->products;
    }

    /**
     * @return array<int, array{id:string,name:string,quantity:int,price:int}>
     */
    public function productsArray(): array {
        return array_map(fn (LinePayProduct $p): array => $p->toArray(), $this->products);
    }

    /**
     * @return array<string, mixed>
     */
    public function toRequestBody(string $confirmUrl, string $cancelUrl): array {
        return [
            'amount' => $this->amount,
            'currency' => 'TWD',
            'orderId' => $this->orderId,
            'packages' => [[
                'id' => '1',
                'amount' => $this->amount,
                'name' => $this->packageName,
                'products' => $this->productsArray(),
            ]],
            'redirectUrls' => [
                'confirmUrl' => $confirmUrl,
                'cancelUrl' => $cancelUrl,
            ],
        ];
    }
}