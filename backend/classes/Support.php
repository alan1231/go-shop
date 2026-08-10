<?php

class Support {
    public static function float(string $s): float {
        $s = trim($s);
        if ($s === '') {
            return 0.0;
        }
        return (float)$s;
    }

    public static function int(string $s): int {
        $s = trim($s);
        if ($s === '') {
            return 0;
        }
        return (int)$s;
    }

    public static function nullIfEmpty(?string $s): ?string {
        if ($s === null || $s === '') {
            return null;
        }
        return $s;
    }

    public static function uploadUrl(string $filename): ?string {
        if ($filename === '') {
            return null;
        }
        return '/uploads/' . $filename;
    }

    public static function avatarUrl(string $avatar): string {
        if ($avatar === '') {
            return '';
        }
        if (str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://')) {
            return $avatar;
        }
        return '/uploads/' . $avatar;
    }

    public static function validStatus(string $status): bool {
        return in_array($status, ['pending', 'paid', 'shipped', 'completed', 'cancelled'], true);
    }

    public static function normalizeProduct(array $p): array {
        $p['id'] = (int)$p['id'];
        $p['price'] = (float)$p['price'];
        $p['list_price'] = $p['list_price'] === null ? null : (float)$p['list_price'];
        $p['stock'] = (int)($p['stock'] ?? 0);
        $p['listed_stock'] = (int)($p['listed_stock'] ?? 0);
        $p['image'] = $p['image'] ?? '';
        $p['description'] = $p['description'] ?? '';
        $p['category'] = $p['category'] ?? '';
        return $p;
    }

    public static function jsonBody(): array {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '', true);
        return is_array($data) ? $data : [];
    }

    public static function clientIP(): string {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    public static function randomToken(): string {
        return bin2hex(random_bytes(32));
    }

    public static function page(array $items, int $total, int $page, int $perPage): array {
        $totalPages = (int)max(1, ceil($total / max(1, $perPage)));
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    public static function bearerToken(): string {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
            return $m[1];
        }
        return '';
    }
}
