<?php
// 會員資料存取層，封裝所有 users 資料表查詢
class UserRepository {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::connect();
        $this->ensureTable();
    }

    private function ensureTable(): void {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) NOT NULL,
                email VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(50) DEFAULT \'user\',
                token VARCHAR(64) DEFAULT NULL,
                provider VARCHAR(20) DEFAULT NULL,
                provider_id VARCHAR(100) DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY idx_provider (provider, provider_id)
            )'
        );
        // 舊表補欄位
        $cols = $this->pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('token', $cols)) {
            $this->pdo->exec('ALTER TABLE users ADD COLUMN token VARCHAR(64) DEFAULT NULL');
        }
        if (!in_array('provider', $cols)) {
            $this->pdo->exec('ALTER TABLE users ADD COLUMN provider VARCHAR(20) DEFAULT NULL');
        }
        if (!in_array('provider_id', $cols)) {
            $this->pdo->exec('ALTER TABLE users ADD COLUMN provider_id VARCHAR(100) DEFAULT NULL');
        }
        $indexes = $this->pdo->query('SHOW INDEX FROM users')->fetchAll(PDO::FETCH_ASSOC);
        $hasIdx = false;
        foreach ($indexes as $idx) {
            if (($idx['Key_name'] ?? '') === 'idx_provider') { $hasIdx = true; break; }
        }
        if (!$hasIdx) {
            $this->pdo->exec('CREATE UNIQUE INDEX idx_provider ON users (provider, provider_id)');
        }
    }

    // 依 provider + provider_id 查詢（三方登入用）
    public function findByProvider(string $provider, string $providerId): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE provider = :provider AND provider_id = :pid LIMIT 1');
        $stmt->execute([':provider' => $provider, ':pid' => $providerId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    // 依 email 查詢（三方登入自動建立用）
    public function findByEmail(string $email): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    // 綁定三方 provider 到既有帳號
    public function setProvider(int $id, string $provider, string $providerId): void {
        $stmt = $this->pdo->prepare('UPDATE users SET provider = :provider, provider_id = :pid WHERE id = :id');
        $stmt->execute([':provider' => $provider, ':pid' => $providerId, ':id' => $id]);
    }

    // 建立三方登入會員（無密碼）
    public function createOAuthUser(string $username, string $email, string $provider, string $providerId): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, email, password, role, provider, provider_id)
             VALUES (:username, :email, :password, :role, :provider, :pid)'
        );
        $stmt->execute([
            ':username' => $username,
            ':email'    => $email,
            ':password' => bin2hex(random_bytes(16)), // 無效密碼，僅能三方登入
            ':role'     => 'user',
            ':provider' => $provider,
            ':pid'      => $providerId,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    // 依 token 查詢使用者（前端 API 驗證用）
    public function findByToken(string $token): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE token = :token LIMIT 1');
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    // 設定使用者 token（登入/登出用）
    public function setToken(int $id, ?string $token): void {
        $stmt = $this->pdo->prepare('UPDATE users SET token = :token WHERE id = :id');
        $stmt->execute([':token' => $token, ':id' => $id]);
    }

    // 依角色列出會員（僅 id, username, email, created_at）
    public function findAllByRole(string $role): array {
        $stmt = $this->pdo->prepare('SELECT id, username, email, created_at FROM users WHERE role = :role ORDER BY created_at DESC');
        $stmt->execute([':role' => $role]);
        return $stmt->fetchAll();
    }

    // 依 id 查詢完整會員資料
    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    // 依 username 或 email 查詢（登入用）
    public function findForAuth(string $username): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = :username OR email = :email LIMIT 1');
        $stmt->execute([':username' => $username, ':email' => $username]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    // 檢查帳號或 Email 是否已存在，可排除指定 id（編輯時用）
    public function existsByUsernameOrEmail(string $username, string $email, ?int $excludeId = null): bool {
        $sql = 'SELECT COUNT(*) FROM users WHERE (username = :username OR email = :email)';
        $params = [':username' => $username, ':email' => $email];
        if ($excludeId !== null) {
            $sql .= ' AND id != :id';
            $params[':id'] = $excludeId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    // 依角色計算人數
    public function countByRole(string $role): int {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE role = :role');
        $stmt->execute([':role' => $role]);
        return (int)$stmt->fetchColumn();
    }

    // 新增會員
    public function create(string $username, string $email, string $passwordHash, string $role): void {
        $stmt = $this->pdo->prepare('INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)');
        $stmt->execute([
            ':username' => $username,
            ':email'    => $email,
            ':password' => $passwordHash,
            ':role'     => $role,
        ]);
    }

    // 更新會員資料，密碼可選
    public function update(int $id, string $username, string $email, ?string $passwordHash = null): void {
        if ($passwordHash) {
            $stmt = $this->pdo->prepare('UPDATE users SET username = :username, email = :email, password = :password WHERE id = :id');
            $stmt->execute([
                ':username' => $username,
                ':email'    => $email,
                ':password' => $passwordHash,
                ':id'       => $id,
            ]);
        } else {
            $stmt = $this->pdo->prepare('UPDATE users SET username = :username, email = :email WHERE id = :id');
            $stmt->execute([
                ':username' => $username,
                ':email'    => $email,
                ':id'       => $id,
            ]);
        }
    }

    // 僅更新密碼
    public function updatePassword(int $id, string $passwordHash): void {
        $stmt = $this->pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
        $stmt->execute([':password' => $passwordHash, ':id' => $id]);
    }

    // 刪除會員
    public function delete(int $id): void {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    // 取得指定會員的角色
    public function getRole(int $id): ?string {
        $stmt = $this->pdo->prepare('SELECT role FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : null;
    }
}