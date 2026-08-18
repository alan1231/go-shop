<?php

namespace App\Services;

use App\Repositories\LoginAttemptRepository;

class RateLimitService {
    private LoginAttemptRepository $repo;
    private int $maxAttempts;
    private int $lockMinutes;

    public function __construct(LoginAttemptRepository $repo, int $maxAttempts = 5, int $lockMinutes = 15) {
        $this->repo = $repo;
        $this->maxAttempts = $maxAttempts;
        $this->lockMinutes = $lockMinutes;
    }

    public function check(string $ip, string $type): array {
        $attempt = $this->repo->find($ip, $type);
        if ($attempt !== null && $attempt['locked_until'] !== null) {
            $until = strtotime($attempt['locked_until']);
            if ($until > time()) {
                return [false, (int)ceil(($until - time()) / 60)];
            }
            $this->repo->clear($ip, $type);
        }
        return [true, 0];
    }

    public function recordFail(string $ip, string $type): void {
        $this->repo->recordFail($ip, $type, $this->maxAttempts, $this->lockMinutes);
    }

    public function clear(string $ip, string $type): void {
        $this->repo->clear($ip, $type);
    }
}
