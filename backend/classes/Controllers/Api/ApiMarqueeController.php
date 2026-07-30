<?php
// 前台跑馬燈 API
class ApiMarqueeController extends ApiController {
    private MarqueeService $marqueeService;

    public function __construct() {
        $this->marqueeService = new MarqueeService();
    }

    // GET /api/marquee
    public function index(): void {
        $this->success(['content' => $this->marqueeService->getContent()]);
    }
}