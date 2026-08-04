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
                category VARCHAR(100) DEFAULT NULL,
                price DECIMAL(10,2) NOT NULL,
                list_price DECIMAL(10,2) DEFAULT NULL,
                stock INT DEFAULT 0,
                listed_stock INT DEFAULT 0,
                status VARCHAR(50) DEFAULT \'active\',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )'
        );
        $cols = $this->pdo->query('SHOW COLUMNS FROM products')->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('category', $cols)) {
            $this->pdo->exec('ALTER TABLE products ADD COLUMN category VARCHAR(100) DEFAULT NULL');
        }
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
    public function create(string $name, ?string $image, string $description, ?string $category, float $price, ?float $listPrice, int $stock, int $listedStock, string $status): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO products (name, image, description, category, price, list_price, stock, listed_stock, status)
             VALUES (:name, :image, :description, :category, :price, :list_price, :stock, :listed_stock, :status)'
        );
        $stmt->execute([
            ':name'         => $name,
            ':image'        => $image,
            ':description'  => $description,
            ':category'     => $category,
            ':price'        => $price,
            ':list_price'   => $listPrice,
            ':stock'        => $stock,
            ':listed_stock' => $listedStock,
            ':status'       => $status,
        ]);
    }

    // 僅列出上架中且有上架庫存的商品（前台用），可依關鍵字與分類篩選
    public function findActive(?string $keyword = null, ?string $category = null): array {
        $sql = "SELECT id, name, image, description, category, price, list_price, listed_stock AS stock, status, created_at
                FROM products WHERE status = 'active' AND listed_stock > 0";
        $params = [];
        if ($keyword !== null && $keyword !== '') {
            $sql .= ' AND (name LIKE :kw OR description LIKE :kwd)';
            $params[':kw'] = '%' . $keyword . '%';
            $params[':kwd'] = '%' . $keyword . '%';
        }
        if ($category !== null && $category !== '') {
            $sql .= ' AND category = :cat';
            $params[':cat'] = $category;
        }
        $sql .= ' ORDER BY created_at DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // 商品分類清單（前台篩選用）
    public function getCategories(): array {
        $rows = $this->pdo->query("SELECT DISTINCT category FROM products WHERE status = 'active' AND category IS NOT NULL AND category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
        return $rows;
    }

    // 扣減庫存（下單時用）
    public function decreaseStock(int $id, int $quantity): void {
        $stmt = $this->pdo->prepare('UPDATE products SET stock = stock - :qty1, listed_stock = listed_stock - :qty2 WHERE id = :id');
        $stmt->execute([':qty1' => $quantity, ':qty2' => $quantity, ':id' => $id]);
    }

    // 更新商品
    public function update(int $id, string $name, ?string $image, string $description, ?string $category, float $price, ?float $listPrice, int $stock, int $listedStock, string $status): void {
        $stmt = $this->pdo->prepare(
            'UPDATE products SET name = :name, image = :image, description = :description, category = :category,
             price = :price, list_price = :list_price, stock = :stock,
             listed_stock = :listed_stock, status = :status WHERE id = :id'
        );
        $stmt->execute([
            ':id'           => $id,
            ':name'         => $name,
            ':image'        => $image,
            ':description'  => $description,
            ':category'     => $category,
            ':price'        => $price,
            ':list_price'   => $listPrice,
            ':stock'        => $stock,
            ':listed_stock' => $listedStock,
            ':status'       => $status,
        ]);
    }
}