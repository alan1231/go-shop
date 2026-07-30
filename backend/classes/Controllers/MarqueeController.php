<?php
// 後台跑馬燈編輯
class MarqueeController extends Controller {
    private MarqueeService $marqueeService;

    public function __construct() {
        Auth::start();
        Auth::check();
        $this->marqueeService = new MarqueeService();
    }

    public function edit(): void {
        $message = '';
        $message_type = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->marqueeService->updateContent($_POST['content'] ?? '');
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }

        $content = $this->marqueeService->getContent();
        $this->render('admin-marquee', compact('content', 'message', 'message_type') + ['page_title' => '跑馬燈管理']);
    }
}