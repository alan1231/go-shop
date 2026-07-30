<?php
// 商品資料存取層，封裝所有 products 資料表查詢
class ProductRepository {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::connect();
        $this->ensureTable();
    }

    private function ensureTable(): void {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                image VARCHAR(255) DEFAULT NULL,
                description TEXT,
                price DECIMAL(10,2) NOT NULL,
                list_price DECIMAL(10,2) DEFAULT NULL,
                stock INT DEFAULT 0,
                listed_stock INT DEFAULT 0,
                status VARCHAR(50) DEFAULT \'active\',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )'
        );
    }

    // 全部商品，依建立時間降冪
    public function getAll(): array {
        return $this->pdo->query('SELECT * FROM products ORDER BY created_at DESC')->fetchAll();
    }

    // 依 id 查詢單一商品
    public function getById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM products WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $product = $stmt->fetch();
        return $product ?: null;
    }

    // 商品總數
    public function count(): int {
        return (int)$this->pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    }

    // 新增商品
    public function create(string $name, ?string $image, string $description, float $price, ?float $listPrice, int $stock, int $listedStock, string $status): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO products (name, image, description, price, list_price, stock, listed_stock, status)
             VALUES (:name, :image, :description, :price, :list_price, :stock, :listed_stock, :status)'
        );
        $stmt->execute([
            ':name'         => $name,
            ':image'        => $image,
            ':description'  => $description,
            ':price'        => $price,
            ':list_price'   => $listPrice,
            ':stock'        => $stock,
            ':listed_stock' => $listedStock,
            ':status'       => $status,
        ]);
    }

    // 僅列出上架中且有上架庫存的商品（前台用）
    public function findActive(): array {
        return $this->pdo->query(
            "SELECT id, name, image, description, price, list_price, listed_stock AS stock, status, created_at
             FROM products WHERE status = 'active' AND listed_stock > 0
             ORDER BY created_at DESC"
        )->fetchAll();
    }

    // 扣減庫存（下單時用）
    public function decreaseStock(int $id, int $quantity): void {
        $stmt = $this->pdo->prepare('UPDATE products SET stock = stock - :qty1, listed_stock = listed_stock - :qty2 WHERE id = :id');
        $stmt->execute([':qty1' => $quantity, ':qty2' => $quantity, ':id' => $id]);
    }

    // 更新商品
    public function update(int $id, string $name, ?string $image, string $description, float $price, ?float $listPrice, int $stock, int $listedStock, string $status): void {
        $stmt = $this->pdo->prepare(
            'UPDATE products SET name = :name, image = :image, description = :description,
             price = :price, list_price = :list_price, stock = :stock,
             listed_stock = :listed_stock, status = :status WHERE id = :id'
        );
        $stmt->execute([
            ':id'           => $id,
            ':name'         => $name,
            ':image'        => $image,
            ':description'  => $description,
            ':price'        => $price,
            ':list_price'   => $listPrice,
            ':stock'        => $stock,
            ':listed_stock' => $listedStock,
            ':status'       => $status,
        ]);
    }
}