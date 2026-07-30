<?php
// API 基底控制器，統一 JSON 回應格式與認證檢查
class ApiController {
    // 回傳 JSON 並設定 Content-Type
    protected function json(array $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    // 成功回應
    protected function success($data = null, string $message = 'ok'): void {
        $this->json(['success' => true, 'message' => $message, 'data' => $data]);
    }

    // 錯誤回應
    protected function error(string $message, int $code = 400): void {
        $this->json(['success' => false, 'message' => $message], $code);
    }

    // 檢查是否已登入，未登入回傳 401
    protected function requireAuth(): array {
        Auth::start();
        $user = Auth::user();
        if (!$user) {
            $this->error('請先登入', 401);
            exit;
        }
        return $user;
    }
}