<?php

class LoginAttemptRepository {
    private PDO $pdo;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? Database::connect();
    }

    public function find(string $ip, string $type): ?array {
        $stmt = $this->pdo->prepare('SELECT attempts, locked_until FROM login_attempts WHERE ip = ? AND type = ?');
        $stmt->execute([$ip, $type]);
        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }
        return [
            'ip' => $ip,
            'type' => $type,
            'attempts' => (int)$row['attempts'],
            'locked_until' => $row['locked_until'] === null || $row['locked_until'] === '' ? null : $row['locked_until'],
        ];
    }

    public function recordFail(string $ip, string $type, int $maxAttempts, int $lockMinutes): void {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            "INSERT INTO login_attempts (ip, type, attempts, locked_until, updated_at) VALUES (?, ?, 1, NULL, ?)
             ON DUPLICATE KEY UPDATE attempts = attempts + 1,
             locked_until = IF(attempts + 1 >= ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), NULL), updated_at = ?"
        );
        $stmt->execute([$ip, $type, $now, $maxAttempts, $lockMinutes, $now]);
    }

    public function clear(string $ip, string $type): void {
        $stmt = $this->pdo->prepare('DELETE FROM login_attempts WHERE ip = ? AND type = ?');
        $stmt->execute([$ip, $type]);
    }
}
