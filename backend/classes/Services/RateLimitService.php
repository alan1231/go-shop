<?php
// 登入/註冊速率限制：以 IP 為單位記錄失敗次數，超過上限鎖定一段時間
class RateLimitService {
    private PDO $pdo;
    private int $maxAttempts = 5;
    private int $lockMinutes = 15;

    public function __construct() {
        $this->pdo = Database::connect();
        $this->ensureTable();
    }

    private function ensureTable(): void {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip VARCHAR(45) NOT NULL,
                type VARCHAR(20) NOT NULL,
                attempts INT NOT NULL DEFAULT 0,
                locked_until DATETIME NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY uniq_ip_type (ip, type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    // 檢查目前是否可繼續嘗試，回傳 ['allowed' => bool, 'retry_after' => 剩餘秒數]
    public function check(string $type): array {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt = $this->pdo->prepare('SELECT attempts, locked_until FROM login_attempts WHERE ip = :ip AND type = :type');
        $stmt->execute([':ip' => $ip, ':type' => $type]);
        $row = $stmt->fetch();

        if ($row && $row['locked_until'] !== null) {
            $until = strtotime($row['locked_until']);
            if ($until > time()) {
                return ['allowed' => false, 'retry_after' => $until - time()];
            }
            // 鎖定期已過，清除次數
            $this->clear($type);
        }
        return ['allowed' => true, 'retry_after' => 0];
    }

    // 記錄一次失敗，累積超過上限即鎖定
    public function recordFail(string $type): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO login_attempts (ip, type, attempts, locked_until, updated_at)
             VALUES (:ip, :type, 1, NULL, :now1)
             ON DUPLICATE KEY UPDATE
                 attempts = attempts + 1,
                 locked_until = IF(attempts + 1 >= :max, DATE_ADD(NOW(), INTERVAL :mins MINUTE), NULL),
                 updated_at = :now2'
        );
        $stmt->execute([':ip' => $ip, ':type' => $type, ':now1' => $now, ':now2' => $now, ':max' => $this->maxAttempts, ':mins' => $this->lockMinutes]);
    }

    // 成功時清除紀錄
    public function clear(string $type): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt = $this->pdo->prepare('DELETE FROM login_attempts WHERE ip = :ip AND type = :type');
        $stmt->execute([':ip' => $ip, ':type' => $type]);
    }
}