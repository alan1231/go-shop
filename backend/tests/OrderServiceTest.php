<?php

namespace App\Tests;

use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\ServiceException;
use App\Services\OrderService;
use LinePay\LinePayClient;
use LinePay\LinePayConfig;
use LinePay\LinePayGateway;
use PDO;
use PHPUnit\Framework\TestCase;

final class OrderServiceTest extends TestCase {
    private PDO $pdo;
    private OrderService $orderSvc;

    protected function setUp(): void {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->pdo->exec('CREATE TABLE products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            image TEXT,
            description TEXT,
            category TEXT,
            price REAL NOT NULL,
            list_price REAL,
            status TEXT DEFAULT \'active\',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');
        $this->pdo->exec('CREATE TABLE orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            total_amount REAL NOT NULL,
            status TEXT DEFAULT \'pending\',
            remark TEXT,
            member_remark TEXT,
            receiver_name TEXT,
            receiver_phone TEXT,
            receiver_address TEXT,
            linepay_transaction_id TEXT,
            payment_method TEXT DEFAULT \'\',
            table_number INTEGER,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');
        $this->pdo->exec('CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL,
            email TEXT,
            phone TEXT,
            address TEXT
        )');
        $this->pdo->exec("INSERT INTO users (id, username, email) VALUES (1, 'test', 'test@example.com')");
        $this->pdo->exec('CREATE TABLE order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            price REAL NOT NULL,
            quantity INTEGER NOT NULL
        )');

        $this->orderSvc = new OrderService(
            $this->pdo,
            new OrderRepository($this->pdo),
            new ProductRepository($this->pdo),
            new LinePayGateway(new LinePayClient(new LinePayConfig('', '', true)))
        );
    }

    private function product(string $status = 'active', float $price = 100.0): int {
        $stmt = $this->pdo->prepare('INSERT INTO products (name, price, status) VALUES (?, ?, ?)');
        $stmt->execute(['測試商品', $price, $status]);
        return (int)$this->pdo->lastInsertId();
    }

    private function orderCount(): int {
        return (int)$this->pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    }

    private function receiver(): array {
        return ['name' => '王小明', 'phone' => '0912345678', 'address' => '台北市大安區'];
    }

    public function testCreateOrderSuccess(): void {
        $a = $this->product('active', 100.0);
        $b = $this->product('active', 50.5);

        $orderId = $this->orderSvc->createOrder(1, [
            ['product_id' => $a, 'quantity' => 2],
            ['product_id' => $b, 'quantity' => 3],
        ], $this->receiver(), '請快點送');

        $order = $this->orderSvc->getWithItems($orderId);
        $this->assertNotNull($order);
        $this->assertSame('pending', $order['status']);
        $this->assertEqualsWithDelta(351.5, $order['total_amount'], 1e-6);
        $this->assertCount(2, $order['items']);
    }

    public function testCreateOrderRejectsInactiveProduct(): void {
        $a = $this->product('inactive');
        try {
            $this->orderSvc->createOrder(1, [
                ['product_id' => $a, 'quantity' => 1],
            ], $this->receiver(), '');
            $this->fail('應拋出 ServiceException');
        } catch (ServiceException $e) {
            $this->assertStringContainsString('商品不存在或已下架', $e->getMessage());
        }
        $this->assertSame(0, $this->orderCount());
    }

    public function testCreateOrderRejectsUnknownProduct(): void {
        try {
            $this->orderSvc->createOrder(1, [
                ['product_id' => 999, 'quantity' => 1],
            ], $this->receiver(), '');
            $this->fail('應拋出 ServiceException');
        } catch (ServiceException $e) {
            $this->assertStringContainsString('商品不存在或已下架', $e->getMessage());
        }
        $this->assertSame(0, $this->orderCount());
    }

    public function testCreateOrderRejectsEmptyItems(): void {
        try {
            $this->orderSvc->createOrder(1, [], $this->receiver(), '');
            $this->fail('應拋出 ServiceException');
        } catch (ServiceException $e) {
            $this->assertStringContainsString('訂單不得為空', $e->getMessage());
        }
    }

    public function testGetWithItemsReturnsNullForMissingOrder(): void {
        $this->assertNull($this->orderSvc->getWithItems(999));
    }

    public function testUpdateStatus(): void {
        $a = $this->product();
        $orderId = $this->orderSvc->createOrder(1, [
            ['product_id' => $a, 'quantity' => 1],
        ], $this->receiver(), '');
        $this->orderSvc->updateStatus($orderId, 'paid');
        $this->assertSame('paid', $this->orderSvc->getWithItems($orderId)['status']);
    }

    public function testUpdateStatusRejectsInvalidStatus(): void {
        $a = $this->product();
        $orderId = $this->orderSvc->createOrder(1, [
            ['product_id' => $a, 'quantity' => 1],
        ], $this->receiver(), '');
        $this->expectException(ServiceException::class);
        try {
            $this->orderSvc->updateStatus($orderId, 'hacked');
        } catch (ServiceException $e) {
            $this->assertStringContainsString('無效的狀態', $e->getMessage());
            throw $e;
        }
    }

    public function testCompletedStatusIsTerminal(): void {
        $a = $this->product();
        $orderId = $this->orderSvc->createOrder(1, [
            ['product_id' => $a, 'quantity' => 1],
        ], $this->receiver(), '');
        $this->orderSvc->updateStatus($orderId, 'completed');
        $this->expectException(ServiceException::class);
        try {
            $this->orderSvc->updateStatus($orderId, 'cancelled');
        } catch (ServiceException $e) {
            $this->assertStringContainsString('訂單已完成', $e->getMessage());
            throw $e;
        }
    }

    public function testStartLinePayRequiresConfiguration(): void {
        $a = $this->product();
        $orderId = $this->orderSvc->createOrder(1, [
            ['product_id' => $a, 'quantity' => 1],
        ], $this->receiver(), '');
        $this->expectException(ServiceException::class);
        try {
            $this->orderSvc->startLinePay($orderId, 1);
        } catch (ServiceException $e) {
            $this->assertStringContainsString('LINE Pay 尚未設定', $e->getMessage());
            throw $e;
        }
    }

    public function testCreateOrderWithTableNumber(): void {
        $a = $this->product();
        $orderId = $this->orderSvc->createOrder(1, [
            ['product_id' => $a, 'quantity' => 2],
        ], $this->receiver(), '', 5);
        $order = $this->orderSvc->getWithItems($orderId);
        $this->assertSame(5, $order['table_number']);
    }

    public function testCreateOrderAnonymous(): void {
        $a = $this->product();
        $orderId = $this->orderSvc->createOrder(0, [
            ['product_id' => $a, 'quantity' => 1],
        ], ['name' => '', 'phone' => '', 'address' => ''], '', 3);
        $order = $this->orderSvc->getWithItems($orderId);
        $this->assertNotNull($order);
        $this->assertSame(0, $order['user_id']);
        $this->assertSame('', $order['username']);
        $this->assertSame(3, $order['table_number']);
    }

    public function testCashCheckout(): void {
        $a = $this->product();
        $orderId = $this->orderSvc->createOrder(0, [
            ['product_id' => $a, 'quantity' => 1],
        ], ['name' => '', 'phone' => '', 'address' => ''], '', 2);
        $this->orderSvc->cashCheckout($orderId);
        $order = $this->orderSvc->getWithItems($orderId);
        $this->assertSame('paid', $order['status']);
        $this->assertSame('cash', $order['payment_method']);
    }
}
