<?php

namespace App\Repositories;

use App\Database;
use App\Support;
use PDO;

class ProductRepository {
    private PDO $pdo;

    private const COLS = 'id, name, image, description, category, price, list_price, stock, listed_stock, status, created_at';

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? Database::connect();
    }

    public function getAll(int $limit = 100, int $offset = 0): array {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLS . ' FROM products ORDER BY created_at DESC LIMIT ? OFFSET ?');
        $stmt->execute([$limit, $offset]);
        return array_map('\\App\\Support::normalizeProduct', $stmt->fetchAll());
    }

    public function countAll(): int {
        return (int)$this->pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    }

    public function search(string $keyword = '', string $category = '', int $limit = 100, int $offset = 0): array {
        [$sql, $args] = $this->buildFilterSql($keyword, $category, 'SELECT ' . self::COLS . ' FROM products');
        $sql .= ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
        $args[] = $limit;
        $args[] = $offset;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($args);
        return array_map('\\App\\Support::normalizeProduct', $stmt->fetchAll());
    }

    public function countSearch(string $keyword = '', string $category = ''): int {
        [$sql, $args] = $this->buildFilterSql($keyword, $category, 'SELECT COUNT(*) FROM products');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($args);
        return (int)$stmt->fetchColumn();
    }

    public function getAllCategories(): array {
        $stmt = $this->pdo->query(
            "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLS . ' FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? Support::normalizeProduct($row) : null;
    }

    public function count(): int {
        return (int)$this->pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    }

    public function create(string $name, ?string $image, string $description, ?string $category, float $price, ?float $listPrice, int $stock, int $listedStock, string $status): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO products (name, image, description, category, price, list_price, stock, listed_stock, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, Support::nullIfEmpty($image), $description, Support::nullIfEmpty($category), $price, $listPrice, $stock, $listedStock, $status]);
    }

    public function findActive(string $keyword = '', string $category = '', int $limit = 100, int $offset = 0): array {
        [$sql, $args] = $this->buildFilterSql(
            $keyword,
            $category,
            "SELECT id, name, image, description, category, price, list_price, listed_stock AS stock, status, created_at FROM products WHERE status = 'active' AND listed_stock > 0"
        );
        $sql .= ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
        $args[] = $limit;
        $args[] = $offset;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($args);
        return array_map('\\App\\Support::normalizeProduct', $stmt->fetchAll());
    }

    public function countActive(string $keyword = '', string $category = ''): int {
        [$sql, $args] = $this->buildFilterSql(
            $keyword,
            $category,
            "SELECT COUNT(*) FROM products WHERE status = 'active' AND listed_stock > 0"
        );
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($args);
        return (int)$stmt->fetchColumn();
    }

    public function getCategories(): array {
        $stmt = $this->pdo->query(
            "SELECT DISTINCT category FROM products WHERE status = 'active' AND category IS NOT NULL AND category != '' ORDER BY category"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function decreaseStockIfAvailable(int $id, int $qty): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE products SET stock = stock - ?, listed_stock = listed_stock - ? WHERE id = ? AND stock >= ? AND listed_stock >= ?'
        );
        $stmt->execute([$qty, $qty, $id, $qty, $qty]);
        return $stmt->rowCount() === 1;
    }

    public function update(int $id, string $name, ?string $image, string $description, ?string $category, float $price, ?float $listPrice, int $stock, int $listedStock, string $status): void {
        $stmt = $this->pdo->prepare(
            'UPDATE products SET name = ?, image = ?, description = ?, category = ?, price = ?, list_price = ?, stock = ?, listed_stock = ?, status = ? WHERE id = ?'
        );
        $stmt->execute([$name, $image, $description, Support::nullIfEmpty($category), $price, $listPrice, $stock, $listedStock, $status, $id]);
    }

    private function buildFilterSql(string $keyword, string $category, string $base): array {
        $sql = str_contains($base, ' WHERE ') ? $base : $base . ' WHERE 1=1';
        $args = [];
        if ($keyword !== '') {
            $sql .= ' AND (name LIKE ? OR description LIKE ?)';
            $like = '%' . $keyword . '%';
            $args[] = $like;
            $args[] = $like;
        }
        if ($category !== '') {
            $sql .= ' AND category = ?';
            $args[] = $category;
        }
        return [$sql, $args];
    }
}
