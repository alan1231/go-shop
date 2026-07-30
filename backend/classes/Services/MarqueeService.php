<?php
// 跑馬燈商業邏輯
class MarqueeService {
    private MarqueeRepository $repo;

    public function __construct() {
        $this->repo = new MarqueeRepository();
    }

    public function getContent(): string {
        return $this->repo->get();
    }

    public function updateContent(string $content): array {
        if (trim($content) === '') {
            return ['success' => false, 'message' => '內容不能為空'];
        }
        $this->repo->update(trim($content));
        return ['success' => true, 'message' => '跑馬燈已更新'];
    }
}