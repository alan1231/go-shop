<?php

namespace App\Repositories;

use App\Database;
use App\Support;
use PDO;

class ProductRepository {
    private PDO $pdo;

    private const COLS = 'id, name, image, description, category, price, list_price, status, created_at';

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
            "SELECT p.category FROM products p "
            . "LEFT JOIN product_categories pc ON pc.name = p.category "
            . "WHERE p.category IS NOT NULL AND p.category != '' "
            . 'GROUP BY p.category '
            . 'ORDER BY (pc.sort_order IS NULL), pc.sort_order, p.category'
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getCategories(): array {
        $stmt = $this->pdo->query(
            "SELECT p.category FROM products p "
            . "LEFT JOIN product_categories pc ON pc.name = p.category "
            . "WHERE p.status = 'active' AND p.category IS NOT NULL AND p.category != '' "
            . 'GROUP BY p.category '
            . 'ORDER BY (pc.sort_order IS NULL), pc.sort_order, p.category'
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function ensureCategory(string $name): void {
        $name = trim($name);
        if ($name === '') {
            return;
        }
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM product_categories WHERE name = ?');
        $stmt->execute([$name]);
        if ((int)$stmt->fetchColumn() > 0) {
            return;
        }
        $max = (int)$this->pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM product_categories')->fetchColumn();
        $stmt = $this->pdo->prepare('INSERT INTO product_categories (name, sort_order) VALUES (?, ?)');
        $stmt->execute([$name, $max + 1]);
    }

    public function moveCategory(string $name, string $direction): void {
        $stmt = $this->pdo->query('SELECT name, sort_order FROM product_categories ORDER BY sort_order');
        $rows = $stmt->fetchAll();
        $pos = array_search($name, array_column($rows, 'name'), true);
        if ($pos === false) {
            return;
        }
        $target = $direction === 'down' ? $pos + 1 : $pos - 1;
        if ($target < 0 || $target >= count($rows)) {
            return;
        }
        $stmt = $this->pdo->prepare('UPDATE product_categories SET sort_order = ? WHERE name = ?');
        $stmt->execute([$rows[$target]['sort_order'], $rows[$pos]['name']]);
        $stmt->execute([$rows[$pos]['sort_order'], $rows[$target]['name']]);
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

    public function create(string $name, ?string $image, string $description, ?string $category, float $price, ?float $listPrice, string $status): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO products (name, image, description, category, price, list_price, status) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, Support::nullIfEmpty($image), $description, Support::nullIfEmpty($category), $price, $listPrice, $status]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateImage(int $id, string $image): void {
        $stmt = $this->pdo->prepare('UPDATE products SET image = ? WHERE id = ?');
        $stmt->execute([$image, $id]);
    }

    public function delete(int $id): void {
        $stmt = $this->pdo->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function hasOrderItems(int $id): bool {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM order_items WHERE product_id = ?');
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function findActive(string $keyword = '', string $category = '', int $limit = 100, int $offset = 0): array {
        [$sql, $args] = $this->buildFilterSql(
            $keyword,
            $category,
            "SELECT id, name, image, description, category, price, list_price, status, created_at FROM products WHERE status = 'active'"
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
            "SELECT COUNT(*) FROM products WHERE status = 'active'"
        );
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($args);
        return (int)$stmt->fetchColumn();
    }

    public function update(int $id, string $name, ?string $image, string $description, ?string $category, float $price, ?float $listPrice, string $status): void {
        $stmt = $this->pdo->prepare(
            'UPDATE products SET name = ?, image = ?, description = ?, category = ?, price = ?, list_price = ?, status = ? WHERE id = ?'
        );
        $stmt->execute([$name, $image, $description, Support::nullIfEmpty($category), $price, $listPrice, $status, $id]);
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
