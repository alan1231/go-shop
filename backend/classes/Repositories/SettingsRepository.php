<?php

namespace App\Repositories;

use App\Database;
use PDO;

class SettingsRepository {
    private PDO $pdo;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? Database::connect();
    }

    public function get(string $key, string $default = ''): string {
        $stmt = $this->pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value === false || $value === null ? $default : $value;
    }

    public function set(string $key, string $value): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()'
        );
        $stmt->execute([$key, $value]);
    }
}