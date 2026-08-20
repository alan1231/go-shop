<?php

namespace App\Repositories;

use App\Database;
use PDO;

class AdminUserRepository {
    private PDO $pdo;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? Database::connect();
    }

    private const COLS = 'id, username, password, token, role';

    public function findForAuth(string $username): ?array {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLS . ' FROM admin_users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByToken(string $token): ?array {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLS . ' FROM admin_users WHERE token = ? LIMIT 1');
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findAll(): array {
        $stmt = $this->pdo->query('SELECT id, username, role, created_at FROM admin_users ORDER BY id');
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLS . ' FROM admin_users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function existsByUsername(string $username): bool {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM admin_users WHERE username = ?');
        $stmt->execute([$username]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function count(): int {
        return (int)$this->pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
    }

    public function create(string $username, string $password, ?string $role): void {
        $stmt = $this->pdo->prepare('INSERT INTO admin_users (username, password, role) VALUES (?, ?, ?)');
        $stmt->execute([$username, $password, $role]);
    }

    public function deleteById(int $id): void {
        $stmt = $this->pdo->prepare('DELETE FROM admin_users WHERE id = ?');
        $stmt->execute([$id]);
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