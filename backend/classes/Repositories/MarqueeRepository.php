<?php
// 跑馬燈資料存取，自動建立資料表
class MarqueeRepository {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::connect();
        $this->ensureTable();
    }

    private function ensureTable(): void {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS marquee (
                id INT PRIMARY KEY DEFAULT 1,
                content TEXT NOT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )'
        );
    }

    // 取得跑馬燈內容，無資料回傳空字串
    public function get(): string {
        $stmt = $this->pdo->query('SELECT content FROM marquee WHERE id = 1');
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : '';
    }

    // 更新跑馬燈內容
    public function update(string $content): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO marquee (id, content) VALUES (1, :content)
             ON DUPLICATE KEY UPDATE content = :content2'
        );
        $stmt->execute([':content' => $content, ':content2' => $content]);
    }
}