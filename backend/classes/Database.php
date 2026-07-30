<?php
// PDO 連線管理（Singleton），統一資料庫存取
class Database {
    private static ?PDO $instance = null;

    // 回傳 PDO 實例，僅在首次呼叫時建立連線
    public static function connect(): PDO {
        if (self::$instance === null) {
            $host    = '127.0.0.1';
            $db      = 'shop';
            $user    = 'root';
            $pass    = '';
            $charset = 'utf8mb4';
            $dsn     = "mysql:host=$host;dbname=$db;charset=$charset";

            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$instance;
    }
}