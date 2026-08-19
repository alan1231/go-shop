<?php

namespace LinePay;

final class LinePayProduct {
    public function __construct(
        private readonly string $id,
        private readonly string $name,
        private readonly int $quantity,
        private readonly int $price,
    ) {
    }

    /**
     * @return array{id:string,name:string,quantity:int,price:int}
     */
    public function toArray(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'price' => $this->price,
        ];
    }
}