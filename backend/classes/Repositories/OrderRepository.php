<?php
// 訂單資料存取層，封裝 orders / order_items 查詢
class OrderRepository {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::connect();
        $this->ensureTables();
    }

    private function ensureTables(): void {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                total_amount DECIMAL(10,2) NOT NULL,
                status VARCHAR(50) DEFAULT \'pending\',
                remark TEXT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )'
        );
        // 舊表補欄位
        $cols = $this->pdo->query('SHOW COLUMNS FROM orders')->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('remark', $cols)) {
            $this->pdo->exec('ALTER TABLE orders ADD COLUMN remark TEXT DEFAULT NULL');
        }
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                product_id INT NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                quantity INT NOT NULL
            )'
        );
    }

    // 訂單列表（含會員名稱），可依狀態篩選
    public function findAll(?string $status = null): array {
        $sql = 'SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id';
        $params = [];
        if ($status && in_array($status, ['pending', 'paid', 'shipped', 'completed', 'cancelled'])) {
            $sql .= ' WHERE o.status = :status';
            $params[':status'] = $status;
        }
        $sql .= ' ORDER BY o.created_at DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // 單筆訂單（含會員名稱與聯絡資料）
    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT o.*, u.username, u.email, u.phone, u.address FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = :id');
        $stmt->execute([':id' => $id]);
        $order = $stmt->fetch();
        return $order ?: null;
    }

    // 訂單商品明細
    public function getItems(int $orderId): array {
        $stmt = $this->pdo->prepare(
            'SELECT oi.*, p.name, p.image FROM order_items oi
             JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = :id'
        );
        $stmt->execute([':id' => $orderId]);
        return $stmt->fetchAll();
    }

    // 更新訂單狀態
    public function updateStatus(int $id, string $status): void {
        $stmt = $this->pdo->prepare('UPDATE orders SET status = :status WHERE id = :id');
        $stmt->execute([':status' => $status, ':id' => $id]);
    }

    // 更新訂單備註
    public function updateRemark(int $id, string $remark): void {
        $stmt = $this->pdo->prepare('UPDATE orders SET remark = :remark WHERE id = :id');
        $stmt->execute([':remark' => $remark, ':id' => $id]);
    }

    // 訂單總數
    public function count(): int {
        return (int)$this->pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    }

    // 各狀態訂單數量（圓餅圖）
    public function countByStatus(): array {
        $rows = $this->pdo->query("SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status")->fetchAll();
        $result = ['pending' => 0, 'paid' => 0, 'shipped' => 0, 'completed' => 0, 'cancelled' => 0];
        foreach ($rows as $row) {
            if (isset($result[$row['status']])) {
                $result[$row['status']] = (int)$row['cnt'];
            }
        }
        return $result;
    }

    // 近 N 天每日訂單數與營收（折線圖，含 0 天）；營收只計已付款/出貨中/已完成
    public function getDailyStats(int $days = 7): array {
        $rows = $this->pdo->query(
            "SELECT DATE(created_at) AS day, COUNT(*) AS orders, SUM(total_amount) AS revenue
             FROM orders
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL " . ($days - 1) . " DAY)
               AND status IN ('paid', 'shipped', 'completed')
             GROUP BY DATE(created_at)"
        )->fetchAll();

        $byDay = [];
        foreach ($rows as $row) {
            $byDay[$row['day']] = $row;
        }

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} day"));
            $result[] = [
                'day'     => date('m/d', strtotime($day)),
                'orders'  => isset($byDay[$day]) ? (int)$byDay[$day]['orders'] : 0,
                'revenue' => isset($byDay[$day]) ? (float)$byDay[$day]['revenue'] : 0,
            ];
        }
        return $result;
    }

    // 熱銷商品 Top N（依銷售數量排序）
    public function getTopProducts(int $limit = 5): array {
        $stmt = $this->pdo->query(
            "SELECT p.name, SUM(oi.quantity) AS sold, SUM(oi.quantity * oi.price) AS revenue
             FROM order_items oi
             JOIN products p ON oi.product_id = p.id
             GROUP BY oi.product_id, p.name
             ORDER BY sold DESC
             LIMIT " . $limit
        );
        return $stmt->fetchAll();
    }

    // 已完成訂單的總金額
    public function getCompletedRevenue(): int {
        return (int)$this->pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'completed'")->fetchColumn();
    }

    // 依會員 id 查詢訂單列表
    public function findByUserId(int $userId): array {
        $stmt = $this->pdo->prepare('SELECT * FROM orders WHERE user_id = :uid ORDER BY created_at DESC');
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    // 新增訂單，回傳 insert id
    public function createOrder(int $userId, float $total): int {
        $stmt = $this->pdo->prepare('INSERT INTO orders (user_id, total_amount, status) VALUES (:uid, :total, :status)');
        $stmt->execute([':uid' => $userId, ':total' => $total, ':status' => 'pending']);
        return (int)$this->pdo->lastInsertId();
    }

    // 新增訂單明細
    public function createItem(int $orderId, int $productId, float $price, int $quantity): void {
        $stmt = $this->pdo->prepare('INSERT INTO order_items (order_id, product_id, price, quantity) VALUES (:oid, :pid, :price, :qty)');
        $stmt->execute([':oid' => $orderId, ':pid' => $productId, ':price' => $price, ':qty' => $quantity]);
    }

    // 最近 N 筆訂單
    public function getRecent(int $limit = 5): array {
        $stmt = $this->pdo->query(
            'SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT ' . $limit
        );
        return $stmt->fetchAll();
    }
}