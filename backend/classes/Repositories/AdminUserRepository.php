<?php

namespace App\Repositories;

use App\Database;
use PDO;

class AdminUserRepository {
    private PDO $pdo;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? Database::connect();
    }

    private const COLS = 'id, username, password, token, provider, provider_id';

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

    public function findByProvider(string $provider, string $providerId): ?array {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLS . ' FROM admin_users WHERE provider = ? AND provider_id = ? LIMIT 1');
        $stmt->execute([$provider, $providerId]);
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

    public function setProvider(int $id, string $provider, string $providerId): void {
        $stmt = $this->pdo->prepare('UPDATE admin_users SET provider = ?, provider_id = ? WHERE id = ?');
        $stmt->execute([
            $providerId !== '' ? $provider : null,
            $providerId !== '' ? $providerId : null,
            $id,
        ]);
    }
}