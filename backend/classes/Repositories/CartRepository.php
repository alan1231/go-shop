<?php

namespace App\Repositories;

use App\Database;
use PDO;

class CartRepository {
    private PDO $pdo;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? Database::connect();
    }

    public function findByUserId(int $userId): array {
        $stmt = $this->pdo->prepare(
            'SELECT ci.product_id, ci.quantity, p.name, p.image, p.price, p.list_price, p.status
             FROM cart_items ci JOIN products p ON p.id = ci.product_id
             WHERE ci.user_id = ? ORDER BY ci.id'
        );
        $stmt->execute([$userId]);
        return array_map(function ($row) {
            $row['product_id'] = (int)$row['product_id'];
            $row['quantity'] = (int)$row['quantity'];
            $row['price'] = (float)$row['price'];
            $row['list_price'] = $row['list_price'] === null ? null : (float)$row['list_price'];
            return $row;
        }, $stmt->fetchAll());
    }

    public function getQuantity(int $userId, int $productId): int {
        $stmt = $this->pdo->prepare('SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$userId, $productId]);
        return (int)$stmt->fetchColumn();
    }

    public function upsert(int $userId, int $productId, int $quantity): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE quantity = quantity + ?'
        );
        $stmt->execute([$userId, $productId, $quantity, $quantity]);
    }

    public function setQuantity(int $userId, int $productId, int $quantity): void {
        $stmt = $this->pdo->prepare('UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$quantity, $userId, $productId]);
    }

    public function remove(int $userId, int $productId): void {
        $stmt = $this->pdo->prepare('DELETE FROM cart_items WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$userId, $productId]);
    }

    public function clear(int $userId): void {
        $stmt = $this->pdo->prepare('DELETE FROM cart_items WHERE user_id = ?');
        $stmt->execute([$userId]);
    }
}
