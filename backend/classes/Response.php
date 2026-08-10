<?php

class Response {
    public static function json(array $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success($data = null, string $message = ''): void {
        $payload = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $payload['data'] = $data;
        }
        self::json($payload);
    }

    public static function fail(string $message, int $code = 400): void {
        self::json(['success' => false, 'message' => $message], $code);
    }

    public static function file(string $path, string $contentType): void {
        header('Content-Type: ' . $contentType);
        readfile($path);
        exit;
    }

    public static function notFoundFile(): void {
        http_response_code(404);
        exit;
    }
}
