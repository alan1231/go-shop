<?php

namespace App\Repositories;

use App\Database;
use App\Support;
use PDO;

class OrderRepository {
    private PDO $pdo;

    private const ORDER_COLS = 'o.id, o.user_id, o.total_amount, o.status, o.remark, o.member_remark, o.receiver_name, o.receiver_phone, o.receiver_address, o.linepay_transaction_id, o.payment_method, o.table_number, o.order_type, o.created_at';

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? Database::connect();
    }

    public function findAll(string $status = '', int $limit = 100, int $offset = 0, string $start = '', string $end = ''): array {
        $sql = 'SELECT ' . self::ORDER_COLS . ', u.username FROM orders o LEFT JOIN users u ON o.user_id = u.id';
        $args = [];
        if ($status !== '' && Support::validStatus($status)) {
            $sql .= ' WHERE o.status = ?';
            $args[] = $status;
        }
        $this->appendDateRange($sql, $args, $start, $end, $status === '', 'o.created_at');
        $sql .= ' ORDER BY o.created_at DESC LIMIT ? OFFSET ?';
        $args[] = $limit;
        $args[] = $offset;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($args);
        return array_map([$this, 'normalize'], $stmt->fetchAll());
    }

    public function findAllWithItems(string $status = '', int $limit = 200, int $offset = 0, string $start = '', string $end = ''): array {
        $orders = $this->findAll($status, $limit, $offset, $start, $end);
        if (count($orders) === 0) {
            return $orders;
        }
        $ids = array_map('intval', array_column($orders, 'id'));
        $in = implode(',', $ids);
        $stmt = $this->pdo->query(
            'SELECT oi.order_id, oi.product_id, oi.price, oi.quantity, p.name, p.image '
            . 'FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id IN (' . $in . ') ORDER BY oi.id'
        );
        $byOrder = [];
        foreach ($stmt->fetchAll() as $r) {
            $byOrder[(int)$r['order_id']][] = [
                'product_id' => (int)$r['product_id'],
                'price' => (float)$r['price'],
                'quantity' => (int)$r['quantity'],
                'name' => (string)($r['name'] ?? ''),
                'image' => (string)($r['image'] ?? ''),
            ];
        }
        foreach ($orders as &$o) {
            $o['items'] = $byOrder[$o['id']] ?? [];
        }
        unset($o);
        return $orders;
    }

    public function countFindAll(string $status = '', string $start = '', string $end = ''): int {
        $sql = 'SELECT COUNT(*) FROM orders';
        $args = [];
        if ($status !== '' && Support::validStatus($status)) {
            $sql .= ' WHERE status = ?';
            $args[] = $status;
        }
        $this->appendDateRange($sql, $args, $start, $end, $status === '');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($args);
        return (int)$stmt->fetchColumn();
    }

    public function sumTotal(string $status = '', string $start = '', string $end = ''): float {
        $sql = 'SELECT COALESCE(SUM(total_amount), 0) FROM orders';
        $args = [];
        if ($status !== '' && Support::validStatus($status)) {
            $sql .= ' WHERE status = ?';
            $args[] = $status;
        }
        $this->appendDateRange($sql, $args, $start, $end, $status === '');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($args);
        return (float)$stmt->fetchColumn();
    }

    private function appendDateRange(string &$sql, array &$args, string $start, string $end, bool $first = false, string $col = 'created_at'): void {
        $where = $first ? ' WHERE' : '';
        if ($start !== '') {
            $sql .= $where . ($args ? ' AND' : '') . ' ' . $col . ' >= ?';
            $args[] = $start . ' 00:00:00';
            $where = '';
        }
        if ($end !== '') {
            $sql .= $where . ($args ? ' AND' : '') . ' ' . $col . ' <= ?';
            $args[] = $end . ' 23:59:59';
        }
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::ORDER_COLS . ', u.username, u.email, u.phone, u.address FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $this->normalize($row) : null;
    }

    public function getItems(int $orderId): array {
        $stmt = $this->pdo->prepare(
            'SELECT oi.*, p.name, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?'
        );
        $stmt->execute([$orderId]);
        return array_map(function ($i) {
            $i['id'] = (int)$i['id'];
            $i['order_id'] = (int)$i['order_id'];
            $i['product_id'] = (int)$i['product_id'];
            $i['price'] = (float)$i['price'];
            $i['quantity'] = (int)$i['quantity'];
            $i['name'] = $i['name'] ?? '';
            $i['image'] = $i['image'] ?? '';
            return $i;
        }, $stmt->fetchAll());
    }

    public function updateStatus(int $id, string $status): void {
        $stmt = $this->pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public function updateLinePayTransactionId(int $id, string $transactionId): void {
        $stmt = $this->pdo->prepare('UPDATE orders SET linepay_transaction_id = ? WHERE id = ?');
        $stmt->execute([$transactionId, $id]);
    }

    public function updatePaymentMethod(int $id, string $method): void {
        $stmt = $this->pdo->prepare('UPDATE orders SET payment_method = ? WHERE id = ?');
        $stmt->execute([$method, $id]);
    }

    public function updateRemark(int $id, string $remark): void {
        $stmt = $this->pdo->prepare('UPDATE orders SET remark = ? WHERE id = ?');
        $stmt->execute([$remark, $id]);
    }

    public function deleteItems(int $orderId): void {
        $stmt = $this->pdo->prepare('DELETE FROM order_items WHERE order_id = ?');
        $stmt->execute([$orderId]);
    }

    public function updateTotal(int $id, float $total): void {
        $stmt = $this->pdo->prepare('UPDATE orders SET total_amount = ? WHERE id = ?');
        $stmt->execute([$total, $id]);
    }

    public function count(): int {
        return (int)$this->pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    }

    public function countByStatus(): array {
        $result = ['pending' => 0, 'paid' => 0, 'shipped' => 0, 'completed' => 0, 'cancelled' => 0];
        $rows = $this->pdo->query('SELECT status, COUNT(*) AS c FROM orders GROUP BY status')->fetchAll();
        foreach ($rows as $row) {
            if (isset($result[$row['status']])) {
                $result[$row['status']] = (int)$row['c'];
            }
        }
        return $result;
    }

    public function getDailyStats(int $days = 7): array {
        $stmt = $this->pdo->prepare(
            "SELECT DATE(created_at) AS day, COUNT(*) AS orders, SUM(total_amount) AS revenue FROM orders
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY) AND status IN ('paid', 'shipped', 'completed')
             GROUP BY DATE(created_at)"
        );
        $stmt->execute([$days - 1]);
        $byDay = [];
        foreach ($stmt->fetchAll() as $row) {
            $byDay[$row['day']] = ['day' => $row['day'], 'orders' => (int)$row['orders'], 'revenue' => (float)($row['revenue'] ?? 0)];
        }
        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $full = date('Y-m-d', strtotime("-$i days"));
            $stat = $byDay[$full] ?? ['orders' => 0, 'revenue' => 0.0];
            $result[] = ['day' => substr($full, 5), 'orders' => $stat['orders'], 'revenue' => (float)$stat['revenue']];
        }
        return $result;
    }

    public function getTopProducts(int $limit = 5): array {
        $stmt = $this->pdo->prepare(
            'SELECT p.name, SUM(oi.quantity) AS sold, SUM(oi.quantity * oi.price) AS revenue
             FROM order_items oi JOIN products p ON oi.product_id = p.id
             GROUP BY oi.product_id, p.name ORDER BY sold DESC LIMIT ?'
        );
        $stmt->execute([$limit]);
        return array_map(function ($t) {
            $t['name'] = $t['name'] ?? '';
            $t['sold'] = (int)$t['sold'];
            $t['revenue'] = (float)($t['revenue'] ?? 0);
            return $t;
        }, $stmt->fetchAll());
    }

    public function getCompletedRevenue(): float {
        return (float)$this->pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'completed'")->fetchColumn();
    }

    public function findByUserId(int $userId, string $status = ''): array {
        $sql = 'SELECT ' . self::ORDER_COLS . ', u.username, u.email, u.phone, u.address,
                       (SELECT p.name FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = o.id ORDER BY oi.id LIMIT 1) AS item_name,
                       (SELECT p.image FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = o.id ORDER BY oi.id LIMIT 1) AS item_image,
                       (SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi WHERE oi.order_id = o.id) AS item_count,
                       (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_types
                FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.user_id = ?';
        $args = [$userId];
        if ($status !== '' && Support::validStatus($status)) {
            $sql .= ' AND o.status = ?';
            $args[] = $status;
        }
        $sql .= ' ORDER BY o.created_at DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($args);
        return array_map([$this, 'normalize'], $stmt->fetchAll());
    }

    public function createOrder(int $userId, float $total, string $receiverName, string $receiverPhone, string $receiverAddress, string $memberRemark, ?int $tableNumber = null, string $orderType = 'dine_in'): int {
        $stmt = $this->pdo->prepare(
            "INSERT INTO orders (user_id, total_amount, status, receiver_name, receiver_phone, receiver_address, member_remark, table_number, order_type)
             VALUES (?, ?, 'pending', ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $userId > 0 ? $userId : null,
            $total,
            Support::nullIfEmpty($receiverName),
            Support::nullIfEmpty($receiverPhone),
            Support::nullIfEmpty($receiverAddress),
            Support::nullIfEmpty($memberRemark),
            $tableNumber !== null && $tableNumber > 0 ? $tableNumber : null,
            $orderType,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function createItem(int $orderId, int $productId, float $price, int $quantity): void {
        $stmt = $this->pdo->prepare('INSERT INTO order_items (order_id, product_id, price, quantity) VALUES (?, ?, ?, ?)');
        $stmt->execute([$orderId, $productId, $price, $quantity]);
    }

    public function getRecent(int $limit = 5): array {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::ORDER_COLS . ', u.username FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT ?'
        );
        $stmt->execute([$limit]);
        return array_map([$this, 'normalize'], $stmt->fetchAll());
    }

    public function activeTables(): array {
        $rows = $this->pdo->query(
            "SELECT DISTINCT table_number FROM orders WHERE order_type = 'dine_in' AND table_number IS NOT NULL AND table_number > 0 AND status IN ('pending', 'paid', 'shipped')"
        )->fetchAll(PDO::FETCH_COLUMN);
        return array_map('intval', $rows);
    }

    public function beginTransaction(): void {
        $this->pdo->beginTransaction();
    }

    public function commit(): void {
        $this->pdo->commit();
    }

    public function rollBack(): void {
        $this->pdo->rollBack();
    }

    private function normalize(array $o): array {
        $o['id'] = (int)$o['id'];
        $o['user_id'] = (int)$o['user_id'];
        $o['total_amount'] = (float)$o['total_amount'];
        $o['table_number'] = isset($o['table_number']) && $o['table_number'] !== null ? (int)$o['table_number'] : 0;
        $o['order_type'] = $o['order_type'] ?? 'dine_in';
        foreach (['remark', 'member_remark', 'receiver_name', 'receiver_phone', 'receiver_address', 'linepay_transaction_id', 'payment_method', 'username', 'email', 'phone', 'address'] as $f) {
            $o[$f] = $o[$f] ?? '';
        }
        if (array_key_exists('item_count', $o)) {
            $o['item_name'] = $o['item_name'] ?? '';
            $o['item_image'] = $o['item_image'] ?? '';
            $o['item_count'] = (int)$o['item_count'];
            $o['item_types'] = (int)$o['item_types'];
        }
        return $o;
    }
}
