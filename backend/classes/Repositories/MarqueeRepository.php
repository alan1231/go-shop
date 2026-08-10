<?php

class MarqueeRepository {
    private PDO $pdo;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? Database::connect();
    }

    public function get(): string {
        $stmt = $this->pdo->prepare('SELECT content FROM marquee WHERE id = 1');
        $stmt->execute();
        $content = $stmt->fetchColumn();
        return $content === false || $content === null ? '' : $content;
    }

    public function update(string $content): void {
        $stmt = $this->pdo->prepare('UPDATE marquee SET content = ?, updated_at = NOW() WHERE id = 1');
        $stmt->execute([$content]);
    }
}
