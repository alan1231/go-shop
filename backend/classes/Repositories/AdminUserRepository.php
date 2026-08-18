<?php

namespace App\Repositories;

use App\Database;
use PDO;

class AdminUserRepository {
    private PDO $pdo;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? Database::connect();
    }

    public function findForAuth(string $username): ?array {
        $stmt = $this->pdo->prepare('SELECT id, username, password, token FROM admin_users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByToken(string $token): ?array {
        $stmt = $this->pdo->prepare('SELECT id, username, password, token FROM admin_users WHERE token = ? LIMIT 1');
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function setToken(int $id, string $token): void {
        if ($token === '') {
            $stmt = $this->pdo->prepare('UPDATE admin_users SET token = NULL WHERE id = ?');
            $stmt->execute([$id]);
            return;
        }
        $stmt = $this->pdo->prepare('UPDATE admin_users SET token = ? WHERE id = ?');
        $stmt->execute([$token, $id]);
    }
}
