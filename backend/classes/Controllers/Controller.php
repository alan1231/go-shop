<?php
// 基底控制器，提供統一的 render 與錯誤回應方法
class Controller {
    // 載入 header + 指定視圖 + footer，data 以 extract 展開為變數
    protected function render(string $view, array $data = []): void {
        extract($data);
        require __DIR__ . '/../../views/admin-header.php';
        require __DIR__ . '/../../views/' . $view . '.php';
        require __DIR__ . '/../../views/admin-footer.php';
    }

    // 回傳 404 並結束
    protected function notFound(string $message = 'Not Found'): void {
        http_response_code(404);
        echo $message;
    }

    // 回傳 403 並結束
    protected function forbidden(string $message = 'Forbidden'): void {
        http_response_code(403);
        echo $message;
    }

    // 回傳 400 並結束
    protected function badRequest(string $message = 'Bad Request'): void {
        http_response_code(400);
        echo $message;
    }
}