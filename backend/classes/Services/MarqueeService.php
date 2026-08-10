<?php

class MarqueeService {
    private MarqueeRepository $repo;

    public function __construct(MarqueeRepository $repo) {
        $this->repo = $repo;
    }

    public function getContent(): string {
        return $this->repo->get();
    }

    public function updateContent(string $content): void {
        if (trim($content) === '') {
            throw new ServiceException('內容不能為空');
        }
        $this->repo->update(trim($content));
    }
}
